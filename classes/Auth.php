<?php
class Auth
{
    private PDO $db;
    private string $error = '';
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function register($full_name, $email, $phone, $password, $role, $confirm_password): bool
    {
        $this->error = '';
        foreach ([$full_name, $email, $phone, $password, $role, $confirm_password] as $value) {
            if (!is_string($value)) {
                return $this->fail('Invalid registration details.');
            }
        }
        $full_name = trim($full_name);
        $email = strtolower(trim($email));
        $phone = trim($phone);
        if (
            mb_strlen($full_name) < 2 ||
            !mb_check_encoding($full_name, 'UTF-8') ||
            mb_strlen($full_name) > 100
        ) {
            return $this->fail('Enter a name of 2 to 100 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            return $this->fail('Enter a valid email address.');
        }
        if (
            !preg_match('/^\+?[0-9 ()-]{7,20}$/D', $phone) ||
            strlen(preg_replace('/\D/', '', $phone)) < 7
        ) {
            return $this->fail('Enter a valid phone number.');
        }
        if (!in_array($role, ['customer', 'provider'], true)) {
            return $this->fail('Choose customer or provider.');
        }
        if (
            !mb_check_encoding($password, 'UTF-8') ||
            mb_strlen($password) < 8 ||
            strlen($password) > 72 ||
            str_contains($password, "\0")
        ) {
            return $this->fail(
                'Use at least 8 characters and no more than 72 bytes for your password.'
            );
        }
        if ($password !== $confirm_password) {
            return $this->fail('The passwords do not match.');
        }
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'INSERT INTO users (full_name,email,phone,password_hash,role) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([
                $full_name,
                $email,
                $phone,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            ]);
            $id = (int) $this->db->lastInsertId();
            if ($role === 'provider') {
                $stmt = $this->db->prepare(
                    "INSERT INTO provider_profiles (user_id,city) VALUES (?, '')"
                );
                $stmt->execute([$id]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e instanceof PDOException && $e->getCode() === '23000') {
                return $this->fail('Unable to create an account with these details.');
            }
            throw $e;
        }
    }

    private function fail(string $message): bool
    {
        $this->error = $message;
        return false;
    }

    public function login($email, $password): bool
    {
        $this->error = '';
        if (
            !is_string($email) ||
            !is_string($password) ||
            strlen($email) > 190 ||
            strlen($password) > 72 ||
            str_contains($password, "\0")
        ) {
            return $this->fail('The email or password is incorrect.');
        }
        $email = strtolower(trim($email));

        // Store failed attempts by account so changing cookies cannot reset the limit.
        $key = hash('sha256', $email);
        $this->db->beginTransaction();
        try {
            $this->db
                ->prepare(
                    'INSERT IGNORE INTO auth_limits (account_key,attempts,window_started) VALUES (?,0,NOW())'
                )
                ->execute([$key]);
            $stmt = $this->db->prepare(
                'SELECT attempts,window_started FROM auth_limits WHERE account_key=? FOR UPDATE'
            );
            $stmt->execute([$key]);
            $limit = $stmt->fetch();
            if (strtotime($limit['window_started']) < time() - 900) {
                $this->db
                    ->prepare(
                        'UPDATE auth_limits SET attempts=0,window_started=NOW() WHERE account_key=?'
                    )
                    ->execute([$key]);
                $limit['attempts'] = 0;
            }
            if ((int) $limit['attempts'] >= 10) {
                $this->db->commit();
                return $this->fail('Too many attempts. Try again in 15 minutes.');
            }
            $stmt = $this->db->prepare(
                'SELECT id,full_name,email,password_hash,role,is_active FROM users WHERE email=?'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $valid = password_verify(
                $password,
                $user['password_hash'] ??
                    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'
            );
            if (!$user || !$valid || !(int) $user['is_active']) {
                $this->db
                    ->prepare('UPDATE auth_limits SET attempts=attempts+1 WHERE account_key=?')
                    ->execute([$key]);
                $this->db->commit();
                return $this->fail('The email or password is incorrect.');
            }
            $this->db->prepare('DELETE FROM auth_limits WHERE account_key=?')->execute([$key]);
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $this->db
                    ->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        session_regenerate_id(true);
        $_SESSION = [
            'user_id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'last_activity' => time()
        ];
        return true;
    }

    public function allowAttempt(string $scope, int $maximum, int $seconds = 900): bool
    {
        // Use the server-observed address instead of untrusted proxy headers.
        $key = hash('sha256', $scope . ':' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $this->db->beginTransaction();
        try {
            $this->db
                ->prepare(
                    'INSERT IGNORE INTO auth_limits (account_key,attempts,window_started) VALUES (?,0,NOW())'
                )
                ->execute([$key]);
            $stmt = $this->db->prepare(
                'SELECT attempts,window_started FROM auth_limits WHERE account_key=? FOR UPDATE'
            );
            $stmt->execute([$key]);
            $limit = $stmt->fetch();
            $attempts =
                strtotime($limit['window_started']) < time() - $seconds
                    ? 0
                    : (int) $limit['attempts'];
            if ($attempts >= $maximum) {
                $this->db->commit();
                return false;
            }
            $stmt = $this->db->prepare(
                'UPDATE auth_limits SET attempts=?, window_started=? WHERE account_key=?'
            );
            $stmt->execute([
                $attempts + 1,
                $attempts === 0 ? date('Y-m-d H:i:s') : $limit['window_started'],
                $key
            ]);
            $this->db->commit();
            if (random_int(1, 100) === 1) {
                $this->db->exec(
                    'DELETE FROM auth_limits WHERE window_started < NOW() - INTERVAL 1 DAY'
                );
            }
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function currentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $expired =
            isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800;

        $user = null;
        if ($id && !$expired) {
            $user = (new User($this->db))->getProfileAccount((int) $id);
        }

        if (
            !$user ||
            !(int) $user['is_active'] ||
            !in_array($user['role'], ['customer', 'provider', 'admin'], true)
        ) {
            $_SESSION = [];
            session_regenerate_id(true);
            return null;
        }

        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time();
        return $user;
    }

    public function requireRole(string $role, string $login = '../login.php'): array
    {
        $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $expired =
            isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800;

        if (!$id || $expired) {
            $this->logout();
            header('Location: ' . $login);
            exit();
        }

        $user = $this->currentUser();
        if (!$user) {
            $this->logout();
            http_response_code(403);
            exit('Access denied.');
        }
        if ($user['role'] !== $role) {
            http_response_code(403);
            exit('Access denied.');
        }
        return $user;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
