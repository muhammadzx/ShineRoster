<?php
class CustomerBookings
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

    public function getStats($customerId)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->bookings as $row) {
            if ($row['user_id'] == $customerId) {
                $booking = $data->getBooking($row['id']);
                if ($booking) {
                    $rows[] = $booking;
                }
            }
        }
        return $data->stats($rows);
    }
    public function getBookings($customerId, $search = '', $status = '', $page = 1)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->bookings as $row) {
            if ($row['user_id'] != $customerId) {
                continue;
            }
            $booking = $data->getBooking($row['id']);
            if (!$booking) {
                continue;
            }
            if ($status !== '' && $booking['status'] !== $status) {
                continue;
            }
            $text =
                $booking['service_name'] . ' ' . $booking['provider_name'] . ' ' . $booking['city'];
            if (
                $search !== '' &&
                mb_stripos($text, $search) === false &&
                ltrim($search, '#') != $booking['id']
            ) {
                continue;
            }
            $rows[] = $booking;
        }
        for ($i = 0; $i < count($rows); $i++) {
            for ($j = 0; $j < count($rows) - 1; $j++) {
                $a = $rows[$j];
                $b = $rows[$j + 1];
                $swap = false;
                if ($a['can_cancel'] != $b['can_cancel']) {
                    $swap = $b['can_cancel'];
                } elseif ($a['can_cancel']) {
                    $swap = $a['starts_at'] > $b['starts_at'];
                } elseif ($a['starts_at'] != $b['starts_at']) {
                    $swap = $a['starts_at'] < $b['starts_at'];
                } else {
                    $swap = $a['id'] < $b['id'];
                }
                if ($swap) {
                    $rows[$j] = $b;
                    $rows[$j + 1] = $a;
                }
            }
        }
        return $data->page($rows, $page, 6, 'bookings');
    }
    public function cancel($customerId, $bookingId)
    {
        $data = new SiteData($this->db);
        $booking = $data->getBooking($bookingId);
        if (!$booking || $booking['user_id'] != $customerId) {
            return $this->fail('Booking not found.');
        }
        if (!$booking['can_cancel']) {
            return $this->fail('Only future confirmed bookings can be cancelled.');
        }
        $stmt = $this->db->prepare(
            "UPDATE bookings SET status = 'cancelled', cancelled_at = ? WHERE id = ? AND user_id = ? AND status = 'confirmed'"
        );
        if (!$stmt->execute([date('Y-m-d H:i:s'), $bookingId, $customerId])) {
            return $this->fail('Could not cancel this booking.');
        }
        return true;
    }
}
