<?php

class Auth
{
    private $db;
    private $full_name;
    private $email;
    private $phone;
    private $password;
    private $confirm_password;
    private $role;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function register($full_name, $email, $phone, $password, $role, $confirm_password)
    {
        $sql = "SELECT id FROM users WHERE email = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            echo '<div class="auth-alert auth-alert-error" role="alert">
                    <span class="auth-alert-icon" aria-hidden="true">!</span>

                    <div>
                        <strong>Please check your details</strong>

                        <ul>
                            <li>This email address is already registered.</li>
                        </ul>
                    </div>
                </div>';
            return false;
        }

        if ($confirm_password !== $password) {
            echo '<div class="auth-alert auth-alert-error" role="alert">
                    <span class="auth-alert-icon" aria-hidden="true">!</span>

                    <div>
                        <strong>Please check your details</strong>

                        <ul>
                            <li>The passwords do not match.</li>
                        </ul>
                    </div>
                </div>';
            return false;
        }

        $this->full_name = $full_name;
        $this->email = $email;
        $this->phone = $phone;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->role = $role;

        $sql = "INSERT INTO users
                (full_name, email, phone, password_hash, role)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $this->full_name,
            $this->email,
            $this->phone,
            $this->password,
            $this->role
        ]);


        // رقم المستخدم الذي تمت إضافته
        $user_id = $this->db->lastInsertId();
         if ($role === "provider") {
            $sql = "INSERT INTO provider_profiles (user_id) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
        }
        return true;

    }

    public function login($email, $password)
{
    $sql = "SELECT id, full_name, email, password_hash, role
        FROM users WHERE email = ?";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    return true;
}

    echo '<div class="auth-alert auth-alert-error" role="alert">
            <span class="auth-alert-icon" aria-hidden="true">!</span>

            <div>
                <strong>Please check your details</strong>

                <ul>
                    <li>The email or password is incorrect.</li>
                </ul>
            </div>
          </div>';

    return false;
}

}