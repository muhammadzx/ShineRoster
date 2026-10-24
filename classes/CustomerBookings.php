<?php

class CustomerBookings
{
    public function __construct(private PDO $db) {}

    public function getStats(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,

                COALESCE(SUM(
                    b.status = 'confirmed'
                    AND a.starts_at > CURRENT_TIMESTAMP
                ), 0) AS upcoming,

                COALESCE(SUM(
                    b.status = 'completed'
                ), 0) AS completed,

                COALESCE(SUM(
                    b.status = 'cancelled'
                ), 0) AS cancelled

             FROM bookings b

             JOIN appointment_slots a
                ON a.id = b.slot_id

             WHERE b.user_id = ?"
        );

        $stmt->execute([$customerId]);

        return array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function getBookings(
        int $customerId,
        string $search = '',
        string $status = '',
        int $page = 1
    ): array {
        $where = 'b.user_id = ?';
        $params = [$customerId];

        if ($search !== '') {
            $where .= "
                AND (
                    s.name LIKE ?
                    OR u.full_name LIKE ?
                    OR b.city LIKE ?
                    OR CAST(b.id AS CHAR) = ?
                )
            ";

            $term = '%' . $search . '%';

            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = ltrim($search, '#');
        }

        if (in_array($status, ['confirmed', 'completed', 'cancelled'], true)) {
            $where .= ' AND b.status = ?';
            $params[] = $status;
        }

        $from = "
            FROM bookings b

            JOIN appointment_slots a
                ON a.id = b.slot_id

            JOIN services s
                ON s.id = a.service_id

            JOIN provider_profiles p
                ON p.id = s.provider_id

            JOIN users u
                ON u.id = p.user_id
        ";

        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             $from
             WHERE $where"
        );

        $stmt->execute($params);

        $total = (int) $stmt->fetchColumn();
        $perPage = 6;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT
                b.id,
                b.status,
                b.city,
                b.address,
                b.notes,
                b.total_price,
                b.created_at,
                b.cancelled_at,
                b.completed_at,

                a.starts_at,
                a.ends_at,

                s.name AS service_name,
                u.full_name AS provider_name,

                (
                    b.status = 'confirmed'
                    AND a.starts_at > CURRENT_TIMESTAMP
                ) AS can_cancel,

                r.id AS review_id,
                r.rating AS review_rating

             $from

             LEFT JOIN reviews r
                ON r.booking_id = b.id

             WHERE $where

             ORDER BY
                CASE
                    WHEN b.status = 'confirmed'
                         AND a.starts_at > CURRENT_TIMESTAMP
                    THEN 0
                    ELSE 1
                END ASC,

                CASE
                    WHEN b.status = 'confirmed'
                         AND a.starts_at > CURRENT_TIMESTAMP
                    THEN a.starts_at
                END ASC,

                a.starts_at DESC,
                b.id DESC

             LIMIT $perPage OFFSET $offset"
        );

        $stmt->execute($params);

        return [
            'bookings' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage
        ];
    }

    public function cancel(int $customerId, int $bookingId): void
    {
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
                "SELECT
                    b.id,
                    b.status,
                    (
                        a.starts_at > CURRENT_TIMESTAMP
                    ) AS is_future

                 FROM bookings b

                 JOIN appointment_slots a
                    ON a.id = b.slot_id

                 WHERE b.id = ?
                   AND b.user_id = ?

                 FOR UPDATE"
            );

            $stmt->execute([$bookingId, $customerId]);

            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new RuntimeException('Booking not found.');
            }

            if ($booking['status'] !== 'confirmed') {
                throw new RuntimeException('Only confirmed bookings can be cancelled.');
            }

            if (!(int) $booking['is_future']) {
                throw new RuntimeException(
                    'You cannot cancel a booking after its scheduled start time.'
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE bookings

                 SET status = 'cancelled',
                     cancelled_at = CURRENT_TIMESTAMP,
                     completed_at = NULL

                 WHERE id = ?
                   AND user_id = ?
                   AND status = 'confirmed'"
            );

            $stmt->execute([$bookingId, $customerId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('This booking changed. Refresh the page and try again.');
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
