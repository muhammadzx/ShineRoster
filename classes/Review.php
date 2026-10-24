<?php

class Review
{
    public function __construct(private PDO $db) {}

    public function getBooking(int $customerId, int $bookingId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                b.id,
                b.status,
                b.total_price,
                b.city,

                a.starts_at,
                a.ends_at,

                s.name AS service_name,
                u.full_name AS provider_name,

                r.id AS review_id,
                r.rating AS review_rating,
                r.comment AS review_comment,
                r.created_at AS review_created_at

             FROM bookings b

             JOIN appointment_slots a
                ON a.id = b.slot_id

             JOIN services s
                ON s.id = a.service_id

             JOIN provider_profiles p
                ON p.id = s.provider_id

             JOIN users u
                ON u.id = p.user_id

             LEFT JOIN reviews r
                ON r.booking_id = b.id

             WHERE b.id = ?
               AND b.user_id = ?

             LIMIT 1"
        );

        $stmt->execute([$bookingId, $customerId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(int $customerId, int $bookingId, int $rating, string $comment): int
    {
        $comment = trim($comment);

        if ($rating < 1 || $rating > 5) {
            throw new RuntimeException('Choose a rating between 1 and 5 stars.');
        }

        if (!mb_check_encoding($comment, 'UTF-8') || mb_strlen($comment) > 2000) {
            throw new RuntimeException('Your comment must not exceed 2,000 characters.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT s.provider_id

                 FROM bookings b

                 JOIN appointment_slots a
                    ON a.id = b.slot_id

                 JOIN services s
                    ON s.id = a.service_id

                 WHERE b.id = ?
                   AND b.user_id = ?"
            );

            $stmt->execute([$bookingId, $customerId]);

            $providerId = $stmt->fetchColumn();

            if (!$providerId) {
                throw new RuntimeException('Booking not found.');
            }

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM provider_profiles
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$providerId]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Booking not found.');
            }

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM users
                 WHERE id = ?
                   AND role = 'customer'
                   AND is_active = 1
                 FOR UPDATE"
            );

            $stmt->execute([$customerId]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('An active customer account is required.');
            }

            $stmt = $this->db->prepare(
                "SELECT id, status
                 FROM bookings
                 WHERE id = ?
                   AND user_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$bookingId, $customerId]);

            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new RuntimeException('Booking not found.');
            }

            if ($booking['status'] !== 'completed') {
                throw new RuntimeException('You can review completed bookings only.');
            }

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM reviews
                 WHERE booking_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$bookingId]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException('You have already reviewed this booking.');
            }

            $stmt = $this->db->prepare(
                "INSERT INTO reviews (
                    booking_id,
                    rating,
                    comment
                 )
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([$bookingId, $rating, $comment !== '' ? $comment : null]);

            $reviewId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return $reviewId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('You have already reviewed this booking.');
            }

            throw $e;
        }
    }
}
