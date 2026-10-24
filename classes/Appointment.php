<?php

class Appointment
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getServices(int $providerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, duration_minutes, is_active
             FROM services
             WHERE provider_id = ?
             ORDER BY name"
        );

        $stmt->execute([$providerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $slotId, int $providerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, s.name AS service_name
             FROM appointment_slots a
             JOIN services s ON s.id = a.service_id
             WHERE a.id = ? AND s.provider_id = ?"
        );

        $stmt->execute([$slotId, $providerId]);

        $slot = $stmt->fetch(PDO::FETCH_ASSOC);

        return $slot ?: null;
    }

    public function getAll(int $providerId, int $serviceId = 0, string $date = ''): array
    {
        $sql = "
            SELECT
                a.*,
                s.name AS service_name,

                EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.slot_id = a.id
                ) AS has_history,

                EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.slot_id = a.id
                    AND b.status IN ('confirmed', 'completed')
                ) AS is_reserved

            FROM appointment_slots a
            JOIN services s ON s.id = a.service_id
            WHERE s.provider_id = ?
        ";

        $params = [$providerId];

        if ($serviceId > 0) {
            $sql .= ' AND a.service_id = ?';
            $params[] = $serviceId;
        }

        if ($date !== '') {
            $sql .= ' AND a.starts_at >= ? AND a.starts_at < ?';
            $params[] = $date . ' 00:00:00';

            $nextDate = new DateTimeImmutable($date);
            $params[] = $nextDate->modify('+1 day')->format('Y-m-d 00:00:00');
        }

        $sql .= ' ORDER BY a.starts_at DESC, a.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // All appointment and booking mutations acquire the provider lock first.
    private function transaction(int $providerId, callable $operation)
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

            $result = $operation();

            $this->db->commit();

            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function hasHistory(int $slotId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT EXISTS(
                SELECT 1 FROM bookings WHERE slot_id = ?
             )"
        );

        $stmt->execute([$slotId]);

        return (bool) $stmt->fetchColumn();
    }

    public function save(
        int $providerId,
        int $serviceId,
        string $date,
        string $time,
        int $slotId = 0
    ): void {
        $this->transaction($providerId, function () use (
            $providerId,
            $serviceId,
            $date,
            $time,
            $slotId
        ) {
            if ($slotId > 0) {
                $existing = $this->getById($slotId, $providerId);

                if (!$existing) {
                    throw new RuntimeException('Appointment not found.');
                }

                if ($this->hasHistory($slotId)) {
                    throw new RuntimeException(
                        'Appointments with booking history cannot be edited.'
                    );
                }
            }

            $stmt = $this->db->prepare(
                "SELECT id, duration_minutes
                     FROM services
                     WHERE id = ?
                       AND provider_id = ?
                       AND is_active = 1"
            );

            $stmt->execute([$serviceId, $providerId]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                throw new RuntimeException('Please select one of your active services.');
            }

            $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);

            if (!$start || $start->format('Y-m-d H:i') !== $date . ' ' . $time) {
                throw new RuntimeException('Enter a valid date and time.');
            }

            if ($start <= new DateTimeImmutable()) {
                throw new RuntimeException('The appointment must start in the future.');
            }

            $end = $start->modify('+' . (int) $service['duration_minutes'] . ' minutes');

            $startsAt = $start->format('Y-m-d H:i:s');
            $endsAt = $end->format('Y-m-d H:i:s');

            $stmt = $this->db->prepare(
                "SELECT a.id
                     FROM appointment_slots a
                     JOIN services s ON s.id = a.service_id
                     WHERE s.provider_id = ?
                       AND a.id <> ?
                       AND a.starts_at < ?
                       AND a.ends_at > ?
                     LIMIT 1"
            );

            $stmt->execute([$providerId, $slotId, $endsAt, $startsAt]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException(
                    'This time overlaps another appointment, including closed appointments.'
                );
            }

            if ($slotId > 0) {
                $stmt = $this->db->prepare(
                    "UPDATE appointment_slots
                         SET service_id = ?, starts_at = ?, ends_at = ?
                         WHERE id = ?"
                );

                $stmt->execute([$serviceId, $startsAt, $endsAt, $slotId]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO appointment_slots
                            (service_id, starts_at, ends_at, is_open)
                         VALUES (?, ?, ?, 1)"
                );

                $stmt->execute([$serviceId, $startsAt, $endsAt]);
            }
        });
    }

    public function setOpen(int $slotId, int $providerId, bool $isOpen): void
    {
        $this->transaction($providerId, function () use ($slotId, $providerId, $isOpen) {
            $slot = $this->getById($slotId, $providerId);

            if (!$slot) {
                throw new RuntimeException('Appointment not found.');
            }

            if (new DateTimeImmutable($slot['starts_at']) <= new DateTimeImmutable()) {
                throw new RuntimeException(
                    'Past or started appointments cannot be opened or closed.'
                );
            }

            $stmt = $this->db->prepare('UPDATE appointment_slots SET is_open = ? WHERE id = ?');

            $stmt->execute([(int) $isOpen, $slotId]);
        });
    }

    public function delete(int $slotId, int $providerId): void
    {
        $this->transaction($providerId, function () use ($slotId, $providerId) {
            if (!$this->getById($slotId, $providerId)) {
                throw new RuntimeException('Appointment not found.');
            }

            if ($this->hasHistory($slotId)) {
                throw new RuntimeException('Appointments with booking history cannot be deleted.');
            }

            $stmt = $this->db->prepare('DELETE FROM appointment_slots WHERE id = ?');

            $stmt->execute([$slotId]);
        });
    }
}
