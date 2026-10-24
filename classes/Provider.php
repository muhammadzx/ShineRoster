<?php

require_once __DIR__ . '/User.php';

class Provider
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getProfileByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, user_id, bio, city
             FROM provider_profiles
             WHERE user_id = ?"
        );

        $stmt->execute([$userId]);

        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    public function getSkills(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name
             FROM skills
             ORDER BY name"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSkillIds(int $providerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT skill_id
             FROM provider_skills
             WHERE provider_id = ?"
        );

        $stmt->execute([$providerId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function saveAccountProfile(int $userId, array $data): void
    {
        $name = $this->getText($data, 'full_name');
        $email = strtolower($this->getText($data, 'email'));
        $phone = $this->getText($data, 'phone');
        $city = $this->getText($data, 'city');
        $bio = $this->getText($data, 'bio');

        foreach ([$name, $email, $phone, $city, $bio] as $text) {
            if (!mb_check_encoding($text, 'UTF-8')) {
                throw new RuntimeException('Invalid text encoding.');
            }
        }
        if ($this->textLength($name) < 2 || $this->textLength($name) > 100) {
            throw new RuntimeException('Full name must contain between 2 and 100 characters.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            throw new RuntimeException('Enter a valid email address.');
        }

        if (
            !preg_match('/^\+?[0-9 ()-]{7,20}$/D', $phone) ||
            strlen(preg_replace('/\D/', '', $phone)) < 7
        ) {
            throw new RuntimeException('Enter a valid phone number.');
        }

        if ($city === '' || $this->textLength($city) > 100) {
            throw new RuntimeException('Enter a city of up to 100 characters.');
        }

        if ($this->textLength($bio) > 3000) {
            throw new RuntimeException('Your description must not exceed 3,000 characters.');
        }

        $rawSkills = $data['skills'] ?? [];

        if (!is_array($rawSkills)) {
            throw new RuntimeException('Invalid skill selection.');
        }

        $skillIds = [];

        foreach ($rawSkills as $value) {
            $skillId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($skillId === false) {
                throw new RuntimeException('Invalid skill selection.');
            }

            $skillIds[] = (int) $skillId;
        }

        $skillIds = array_values(array_unique($skillIds));

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM provider_profiles
                 WHERE user_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$userId]);

            $providerId = $stmt->fetchColumn();

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM users
                 WHERE id = ?
                   AND role = 'provider'
                   AND is_active = 1
                 FOR UPDATE"
            );

            $stmt->execute([$userId]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('This provider account is unavailable.');
            }

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM users
                 WHERE email = ? AND id <> ?
                 LIMIT 1"
            );

            $stmt->execute([$email, $userId]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException('This email address is already registered.');
            }

            if ($skillIds) {
                $placeholders = implode(',', array_fill(0, count($skillIds), '?'));

                $stmt = $this->db->prepare(
                    "SELECT COUNT(*)
                     FROM skills
                     WHERE id IN ($placeholders)"
                );

                $stmt->execute($skillIds);

                if ((int) $stmt->fetchColumn() !== count($skillIds)) {
                    throw new RuntimeException(
                        'One of the selected skills is no longer available.'
                    );
                }
            }

            $user = new User($this->db);

            $user->updateProfileAccount($userId, $name, $email, $phone);

            if ($providerId) {
                $providerId = (int) $providerId;

                $stmt = $this->db->prepare(
                    "UPDATE provider_profiles
                     SET city = ?, bio = ?
                     WHERE id = ?"
                );

                $stmt->execute([$city, $bio, $providerId]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO provider_profiles
                        (user_id, city, bio)
                     VALUES (?, ?, ?)"
                );

                $stmt->execute([$userId, $city, $bio]);

                $providerId = (int) $this->db->lastInsertId();
            }

            $stmt = $this->db->prepare(
                "DELETE FROM provider_skills
                 WHERE provider_id = ?"
            );

            $stmt->execute([$providerId]);

            $stmt = $this->db->prepare(
                "INSERT INTO provider_skills
                    (provider_id, skill_id)
                 VALUES (?, ?)"
            );

            foreach ($skillIds as $skillId) {
                $stmt->execute([$providerId, $skillId]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function getText(array $data, string $key): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
    }

    private function textLength(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    public function getDashboardData(int $providerId): array
    {
        require_once __DIR__ . '/Booking.php';

        $booking = new Booking($this->db);

        $stats = $booking->getStats($providerId);

        $stmt = $this->db->prepare(
            "SELECT
            b.id,
            b.city,
            a.starts_at,
            a.ends_at,
            s.name AS service_name,
            u.full_name AS customer_name

         FROM bookings b
         JOIN appointment_slots a ON a.id = b.slot_id
         JOIN services s ON s.id = a.service_id
         JOIN users u ON u.id = b.user_id

         WHERE s.provider_id = ?
           AND b.status = 'confirmed'
           AND a.starts_at > CURRENT_TIMESTAMP

         ORDER BY a.starts_at ASC, b.id ASC
         LIMIT 1"
        );

        $stmt->execute([$providerId]);

        $nextBooking = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $this->db->prepare(
            "SELECT
            b.id,
            b.status,
            b.total_price,
            a.starts_at,
            a.ends_at,
            s.name AS service_name,
            u.full_name AS customer_name

         FROM bookings b
         JOIN appointment_slots a ON a.id = b.slot_id
         JOIN services s ON s.id = a.service_id
         JOIN users u ON u.id = b.user_id

         WHERE s.provider_id = ?

         ORDER BY b.created_at DESC, b.id DESC
         LIMIT 4"
        );

        $stmt->execute([$providerId]);

        $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "SELECT id, name, price, duration_minutes, is_active
         FROM services
         WHERE provider_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 3"
        );

        $stmt->execute([$providerId]);

        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "SELECT
            AVG(r.rating) AS average_rating,
            COUNT(r.id) AS review_count

         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         JOIN appointment_slots a ON a.id = b.slot_id
         JOIN services s ON s.id = a.service_id

         WHERE s.provider_id = ?
           AND b.status = 'completed'
           AND r.is_visible = 1"
        );

        $stmt->execute([$providerId]);

        $rating = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "SELECT
            r.rating,
            r.comment,
            r.created_at,
            u.full_name AS customer_name

         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         JOIN appointment_slots a ON a.id = b.slot_id
         JOIN services s ON s.id = a.service_id
         JOIN users u ON u.id = b.user_id

         WHERE s.provider_id = ?
           AND b.status = 'completed'
           AND r.is_visible = 1

         ORDER BY r.created_at DESC, r.id DESC
         LIMIT 1"
        );

        $stmt->execute([$providerId]);

        $latestReview = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return [
            'stats' => $stats,
            'next_booking' => $nextBooking,
            'recent_bookings' => $recentBookings,
            'services' => $services,
            'rating' => $rating,
            'latest_review' => $latestReview
        ];
    }
}
