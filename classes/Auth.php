<?php
class Auth
{
    private $db;
    private $error = '';

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getError()
    {
        return $this->error;
    }

    private function fail($message)
    {
        $this->error = $message;
        return false;
    }

    public function register($full_name, $email, $phone, $password, $role, $confirm_password)
    {
        $this->error = '';
        if (mb_strlen(trim($full_name)) < 2 || mb_strlen($full_name) > 100) {
            return $this->fail('Enter a name of 2 to 100 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            return $this->fail('Enter a valid email address.');
        }
        if (!validPhone($phone)) {
            return $this->fail('Enter a valid phone number.');
        }
        if ($role !== 'customer' && $role !== 'provider') {
            return $this->fail('Choose customer or provider.');
        }
        if (mb_strlen($password) < 8 || strlen($password) > 72) {
            return $this->fail(
                'Use at least 8 characters and no more than 72 bytes for your password.'
            );
        }
        if ($password !== $confirm_password) {
            return $this->fail('The passwords do not match.');
        }
        $email = strtolower(trim($email));
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('This email address is already registered.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)'
        );
        if (!$stmt->execute([trim($full_name), $email, trim($phone), $hash, $role])) {
            return $this->fail('Unable to create this account.');
        }
        if ($role === 'provider') {
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $this->db->prepare(
                "INSERT INTO provider_profiles (user_id, city) VALUES (?, '')"
            );
            if (!$stmt->execute([$user['id']])) {
                $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$user['id']]);
                return $this->fail('Unable to create the provider profile.');
            }
        }
        return true;
    }
    public function login($email, $password)
    {
        $this->error = '';
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
            return $this->fail('The email or password is incorrect.');
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    public function currentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        $userObject = new User($this->db);
        $user = $userObject->getProfileAccount((int) $_SESSION['user_id']);
        if (!$user || !$user['is_active']) {
            $_SESSION = [];
            return null;
        }
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        return $user;
    }
    public function requireRole($role, $login = '../login.php')
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $login);
            exit();
        }
        $user = $this->currentUser();
        if (!$user || $user['role'] !== $role) {
            http_response_code(403);
            exit('Access denied.');
        }
        return $user;
    }
    public function logout()
    {
        $_SESSION = [];
        session_destroy();
    }
}
