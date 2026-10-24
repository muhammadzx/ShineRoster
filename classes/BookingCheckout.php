<?php

class BookingCheckout
{
    public function __construct(private PDO $db) {}

    public function getAppointment(int $slotId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                a.id AS slot_id,
                a.starts_at,
                a.ends_at,

                s.id AS service_id,
                s.name AS service_name,
                s.price,

                p.id AS provider_id,
                p.city,

                u.full_name AS provider_name

             FROM appointment_slots a

             JOIN services s
                ON s.id = a.service_id

             JOIN provider_profiles p
                ON p.id = s.provider_id

             JOIN users u
                ON u.id = p.user_id

             WHERE a.id = ?
               AND a.is_open = 1
               AND a.starts_at > CURRENT_TIMESTAMP
               AND s.is_active = 1
               AND u.is_active = 1
               AND u.role = 'provider'
               AND TRIM(p.city) <> ''

               AND NOT EXISTS (
                   SELECT 1
                   FROM bookings b
                   WHERE b.slot_id = a.id
                     AND b.status IN ('confirmed', 'completed')
               )

             LIMIT 1"
        );

        $stmt->execute([$slotId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function confirm(
        int $customerId,
        int $slotId,
        string $address,
        string $notes,
        array $quote
    ): int {
        $address = trim($address);
        $notes = trim($notes);

        if (
            !mb_check_encoding($address, 'UTF-8') ||
            mb_strlen($address) < 10 ||
            mb_strlen($address) > 255
        ) {
            throw new RuntimeException('Enter a detailed address between 10 and 255 characters.');
        }

        if (!mb_check_encoding($notes, 'UTF-8') || mb_strlen($notes) > 2000) {
            throw new RuntimeException('Notes must not exceed 2,000 characters.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT s.provider_id
                 FROM appointment_slots a
                 JOIN services s ON s.id = a.service_id
                 WHERE a.id = ?"
            );

            $stmt->execute([$slotId]);

            $providerId = $stmt->fetchColumn();

            if (!$providerId) {
                throw new RuntimeException('This appointment is no longer available.');
            }

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM provider_profiles
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$providerId]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('This provider is unavailable.');
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
                    a.id AS slot_id,
                    a.starts_at,
                    a.ends_at,

                    s.id AS service_id,
                    s.price,

                    p.id AS provider_id,
                    p.city

                 FROM appointment_slots a

                 JOIN services s
                    ON s.id = a.service_id

                 JOIN provider_profiles p
                    ON p.id = s.provider_id

                 JOIN users u
                    ON u.id = p.user_id

                 WHERE a.id = ?
                   AND p.id = ?
                   AND a.is_open = 1
                   AND a.starts_at > CURRENT_TIMESTAMP
                   AND s.is_active = 1
                   AND u.is_active = 1
                   AND u.role = 'provider'
                   AND TRIM(p.city) <> ''

                 FOR UPDATE"
            );

            $stmt->execute([$slotId, $providerId]);

            $slot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$slot) {
                throw new RuntimeException('This appointment is no longer available.');
            }

            $stmt = $this->db->prepare(
                "SELECT id
                 FROM bookings
                 WHERE slot_id = ?
                   AND status IN ('confirmed', 'completed')
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([$slotId]);

            if ($stmt->fetchColumn()) {
                throw new RuntimeException(
                    'Someone has already booked this appointment. ' . 'Please choose another time.'
                );
            }

            // Reject changes to the server-side quote before inserting the booking.
            foreach (
                ['service_id', 'provider_id', 'starts_at', 'ends_at', 'price', 'city']
                as $key
            ) {
                if (
                    !isset($quote[$key]) ||
                    !is_scalar($quote[$key]) ||
                    (string) $quote[$key] !== (string) $slot[$key]
                ) {
                    throw new RuntimeException(
                        'The service details, price or appointment changed. ' .
                            'Review the updated details and confirm again.'
                    );
                }
            }

            $stmt = $this->db->prepare(
                "INSERT INTO bookings (
                    user_id,
                    slot_id,
                    city,
                    address,
                    notes,
                    total_price,
                    status
                 )
                 VALUES (?, ?, ?, ?, ?, ?, 'confirmed')"
            );

            $stmt->execute([
                $customerId,
                $slotId,
                $slot['city'],
                $address,
                $notes !== '' ? $notes : null,
                $slot['price']
            ]);

            $bookingId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return $bookingId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException(
                    'This appointment has just been booked. ' . 'Please choose another time.'
                );
            }

            throw $e;
        }
    }
}
