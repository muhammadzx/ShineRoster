<?php

class Booking
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getStats(int $providerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,

                COALESCE(SUM(
                    b.status = 'confirmed'
                    AND a.starts_at > CURRENT_TIMESTAMP
                ), 0) AS upcoming,

                COALESCE(SUM(b.status = 'completed'), 0) AS completed,
                COALESCE(SUM(b.status = 'cancelled'), 0) AS cancelled

             FROM bookings b
             JOIN appointment_slots a ON a.id = b.slot_id
             JOIN services s ON s.id = a.service_id
             WHERE s.provider_id = ?"
        );

        $stmt->execute([$providerId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByProvider(
        int $providerId,
        string $search = '',
        string $status = '',
        string $date = ''
    ): array {
        $sql = "
            SELECT
                b.*,
                a.starts_at,
                a.ends_at,
                s.name AS service_name,
                u.full_name AS customer_name,
                u.email AS customer_email,
                u.phone AS customer_phone

            FROM bookings b
            JOIN appointment_slots a ON a.id = b.slot_id
            JOIN services s ON s.id = a.service_id
            JOIN users u ON u.id = b.user_id

            WHERE s.provider_id = ?
        ";

        $params = [$providerId];

        if ($search !== '') {
            $sql .= "
                AND (
                    u.full_name LIKE ?
                    OR s.name LIKE ?
                    OR CAST(b.id AS CHAR) = ?
                )
            ";

            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = ltrim($search, '#');
        }

        if ($status !== '') {
            $sql .= ' AND b.status = ?';
            $params[] = $status;
        }

        if ($date !== '') {
            $nextDate = new DateTimeImmutable($date);

            $sql .= ' AND a.starts_at >= ? AND a.starts_at < ?';

            $params[] = $date . ' 00:00:00';
            $params[] = $nextDate->modify('+1 day')->format('Y-m-d 00:00:00');
        }

        $sql .= ' ORDER BY b.created_at DESC, b.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancel(int $bookingId, int $providerId): void
    {
        $this->changeStatus($bookingId, $providerId, 'cancelled');
    }

    public function complete(int $bookingId, int $providerId): void
    {
        $this->changeStatus($bookingId, $providerId, 'completed');
    }

    private function changeStatus(int $bookingId, int $providerId, string $newStatus): void
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM provider_profiles
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$providerId]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Provider profile not found.');
            }

            $stmt = $this->db->prepare(
                "SELECT b.id, b.status, a.starts_at, a.ends_at
                 FROM bookings b
                 JOIN appointment_slots a ON a.id = b.slot_id
                 JOIN services s ON s.id = a.service_id
                 WHERE b.id = ? AND s.provider_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$bookingId, $providerId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) {
                throw new RuntimeException('Booking not found.');
            }

            if ($booking['status'] !== 'confirmed') {
                throw new RuntimeException('Only confirmed bookings can be changed.');
            }

            $now = new DateTimeImmutable();
            $start = new DateTimeImmutable($booking['starts_at']);
            $end = new DateTimeImmutable($booking['ends_at']);

            if ($newStatus === 'cancelled') {
                if ($start <= $now) {
                    throw new RuntimeException(
                        'A booking cannot be cancelled after its start time.'
                    );
                }

                $stmt = $this->db->prepare(
                    "UPDATE bookings
                     SET status = 'cancelled',
                         cancelled_at = CURRENT_TIMESTAMP,
                         completed_at = NULL
                     WHERE id = ? AND status = 'confirmed'"
                );
            } elseif ($newStatus === 'completed') {
                if ($end > $now) {
                    throw new RuntimeException(
                        'A booking can only be completed after its scheduled end.'
                    );
                }

                $stmt = $this->db->prepare(
                    "UPDATE bookings
                     SET status = 'completed',
                         completed_at = CURRENT_TIMESTAMP,
                         cancelled_at = NULL
                     WHERE id = ? AND status = 'confirmed'"
                );
            } else {
                throw new RuntimeException('Invalid booking status.');
            }

            $stmt->execute([$bookingId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'The booking has changed. Refresh the page and try again.'
                );
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
