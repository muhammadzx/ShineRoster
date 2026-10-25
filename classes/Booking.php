<?php
class Booking
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

    public function getStats($providerId)
    {
        $data = new SiteData($this->db);
        return $data->stats($data->providerBookings($providerId));
    }
    public function getByProvider($providerId, $search = '', $status = '', $date = '')
    {
        $data = new SiteData($this->db);
        $result = [];
        foreach ($data->providerBookings($providerId) as $booking) {
            if ($status !== '' && $booking['status'] !== $status) {
                continue;
            }
            if ($date !== '' && substr($booking['starts_at'], 0, 10) !== $date) {
                continue;
            }
            if (
                $search !== '' &&
                mb_stripos($booking['customer_name'] . ' ' . $booking['service_name'], $search) ===
                    false &&
                ltrim($search, '#') != $booking['id']
            ) {
                continue;
            }
            $result[] = $booking;
        }
        return $result;
    }
    public function cancel($bookingId, $providerId)
    {
        return $this->changeStatus($bookingId, $providerId, 'cancelled');
    }
    public function complete($bookingId, $providerId)
    {
        return $this->changeStatus($bookingId, $providerId, 'completed');
    }
    private function changeStatus($bookingId, $providerId, $status)
    {
        $data = new SiteData($this->db);
        $booking = $data->getBooking($bookingId);
        if (!$booking || $booking['provider_id'] != $providerId) {
            return $this->fail('Booking not found.');
        }
        if ($booking['status'] !== 'confirmed') {
            return $this->fail('Only confirmed bookings can be updated.');
        }
        if ($status === 'cancelled') {
            if (strtotime($booking['starts_at']) <= time()) {
                return $this->fail('Started bookings cannot be cancelled.');
            }
            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'cancelled', cancelled_at = ? WHERE id = ? AND status = 'confirmed'"
            );
        } else {
            if (strtotime($booking['ends_at']) > time()) {
                return $this->fail('The appointment must end before completion.');
            }
            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'completed', completed_at = ? WHERE id = ? AND status = 'confirmed'"
            );
        }
        if (!$stmt->execute([date('Y-m-d H:i:s'), $bookingId])) {
            return $this->fail('Could not update this booking.');
        }
        return true;
    }
}
