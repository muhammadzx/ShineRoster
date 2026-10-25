<?php
class Review
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

    public function getBooking($customerId, $bookingId)
    {
        $data = new SiteData($this->db);
        $booking = $data->getBooking($bookingId);
        if ($booking && $booking['user_id'] == $customerId) {
            return $booking;
        }
        return null;
    }
    public function create($customerId, $bookingId, $rating, $comment)
    {
        if (!is_numeric($rating) || (int) $rating != $rating || $rating < 1 || $rating > 5) {
            return $this->fail('Choose a rating between 1 and 5 stars.');
        }
        if (mb_strlen($comment) > 2000) {
            return $this->fail('Your comment must not exceed 2,000 characters.');
        }
        $booking = $this->getBooking($customerId, $bookingId);
        if (!$booking) {
            return $this->fail('Booking not found.');
        }
        if ($booking['status'] !== 'completed') {
            return $this->fail('You can review completed bookings only.');
        }
        if ($booking['review_id'] !== null) {
            return $this->fail('You have already reviewed this booking.');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO reviews (booking_id, rating, comment) VALUES (?, ?, ?)'
        );
        if (!$stmt->execute([$bookingId, (int) $rating, trim($comment)])) {
            return $this->fail('The review could not be saved.');
        }
        $stmt = $this->db->prepare('SELECT id FROM reviews WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $review['id'];
    }
}
