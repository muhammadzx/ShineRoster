<?php

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getProfileAccount(int $userId): ?array
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

        return $user ?: null;
    }

    public function updateProfileAccount(
        int $userId,
        string $name,
        string $email,
        string $phone
    ): void {
        $stmt = $this->db->prepare(
            "UPDATE users
             SET full_name = ?,
                 email = ?,
                 phone = ?
             WHERE id = ?"
        );

        $stmt->execute([$name, $email, $phone, $userId]);
    }
}
