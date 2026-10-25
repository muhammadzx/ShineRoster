<?php
class BookingCheckout
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

    public function getAppointment($slotId)
    {
        $data = new SiteData($this->db);
        $slot = $data->getSlot($slotId);
        if (!$slot) {
            return null;
        }
        foreach ($data->availableSlots($slot['service_id']) as $available) {
            if ($available['id'] == $slotId) {
                return $available;
            }
        }
        return null;
    }
    public function confirm($customerId, $slotId, $address, $notes, $quote)
    {
        if (mb_strlen(trim($address)) < 10 || mb_strlen($address) > 255) {
            return $this->fail('Enter a detailed address between 10 and 255 characters.');
        }
        if (mb_strlen($notes) > 2000) {
            return $this->fail('Notes must not exceed 2,000 characters.');
        }
        $data = new SiteData($this->db);
        $user = $data->getUser($customerId);
        if (!$user || !$user['is_active'] || $user['role'] !== 'customer') {
            return $this->fail('An active customer account is required.');
        }
        $slot = $this->getAppointment($slotId);
        if (!$slot) {
            return $this->fail('This appointment is no longer available.');
        }
        foreach (['service_id', 'provider_id', 'starts_at', 'ends_at', 'price', 'city'] as $key) {
            if (
                !isset($quote[$key]) ||
                !is_scalar($quote[$key]) ||
                (string) $quote[$key] !== (string) $slot[$key]
            ) {
                return $this->fail(
                    'The service details, price or appointment changed. Review the updated details and confirm again.'
                );
            }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO bookings (user_id, slot_id, city, address, notes, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')"
        );
        if (
            !$stmt->execute([
                $customerId,
                $slotId,
                $slot['city'],
                trim($address),
                trim($notes),
                $slot['price']
            ])
        ) {
            return $this->fail('The booking could not be saved. Please try again.');
        }
        $stmt = $this->db->prepare(
            'SELECT id FROM bookings WHERE user_id = ? AND slot_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$customerId, $slotId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $booking['id'];
    }
}
