<?php

class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getProfileAccount($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                id,
                full_name,
                email,
                phone,
                role,
                is_active

             FROM users
             WHERE id = ?"
        );

        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        } else {
            return null;
        }
    }

    public function updateProfileAccount($userId, $name, $email, $phone)
    {
        $stmt = $this->db->prepare(
            "UPDATE users
             SET full_name = ?,
                 email = ?,
                 phone = ?
             WHERE id = ?"
        );

        return $stmt->execute([$name, $email, $phone, $userId]);
    }
}
