<?php
class Provider
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

    public function getProfileByUserId($userId)
    {
        $stmt = $this->db->prepare('SELECT * FROM provider_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
        return null;
    }
    public function getSkills()
    {
        $stmt = $this->db->prepare('SELECT * FROM skills ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getSkillIds($providerId)
    {
        $stmt = $this->db->prepare('SELECT skill_id FROM provider_skills WHERE provider_id = ?');
        $stmt->execute([$providerId]);
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = (int) $row['skill_id'];
        }
        return $ids;
    }
    public function saveAccountProfile($userId, $form)
    {
        $name = trim($form['full_name']);
        $email = strtolower(trim($form['email']));
        $phone = trim($form['phone']);
        $city = trim($form['city']);
        $bio = trim($form['bio']);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return $this->fail('Enter a name of 2 to 100 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            return $this->fail('Enter a valid email address.');
        }
        if (!validPhone($phone)) {
            return $this->fail('Enter a valid phone number.');
        }
        if ($city === '' || mb_strlen($city) > 100) {
            return $this->fail('Enter your city (up to 100 characters).');
        }
        if (mb_strlen($bio) > 3000) {
            return $this->fail('Your description must not exceed 3,000 characters.');
        }
        if (!is_array($form['skills'])) {
            return $this->fail('Invalid skill selection.');
        }
        $skillIds = [];
        foreach ($form['skills'] as $id) {
            if (!filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                return $this->fail('Invalid skill selection.');
            }
            $stmt = $this->db->prepare('SELECT id FROM skills WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return $this->fail('Invalid skill selection.');
            }
            if (!in_array((int) $id, $skillIds)) {
                $skillIds[] = (int) $id;
            }
        }
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('This email address is already registered.');
        }
        $user = new User($this->db);
        $account = $user->getProfileAccount($userId);
        if (!$account || !$account['is_active'] || $account['role'] !== 'provider') {
            return $this->fail('Provider account unavailable.');
        }
        if (!$user->updateProfileAccount($userId, $name, $email, $phone)) {
            return $this->fail('Could not update your account.');
        }
        $profile = $this->getProfileByUserId($userId);
        if ($profile) {
            $stmt = $this->db->prepare(
                'UPDATE provider_profiles SET city = ?, bio = ? WHERE user_id = ?'
            );
            $saved = $stmt->execute([$city, $bio, $userId]);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO provider_profiles (user_id, city, bio) VALUES (?, ?, ?)'
            );
            $saved = $stmt->execute([$userId, $city, $bio]);
        }
        if (!$saved) {
            return $this->fail('Could not update the provider profile.');
        }
        $profile = $this->getProfileByUserId($userId);
        $stmt = $this->db->prepare('DELETE FROM provider_skills WHERE provider_id = ?');
        if (!$stmt->execute([$profile['id']])) {
            return $this->fail('Could not update your skills.');
        }
        foreach ($skillIds as $id) {
            $stmt = $this->db->prepare(
                'INSERT INTO provider_skills (provider_id, skill_id) VALUES (?, ?)'
            );
            if (!$stmt->execute([$profile['id'], $id])) {
                return $this->fail('Could not save all selected skills.');
            }
        }
        return true;
    }
    public function getDashboardData($providerId)
    {
        $data = new SiteData($this->db);
        $bookings = $data->providerBookings($providerId);
        $stats = $data->stats($bookings);
        $next = null;
        $recent = [];
        foreach ($bookings as $booking) {
            if (count($recent) < 4) {
                $recent[] = $booking;
            }
            if (
                $booking['can_cancel'] &&
                ($next === null || $booking['starts_at'] < $next['starts_at'])
            ) {
                $next = $booking;
            }
        }
        $services = [];
        foreach ($data->services as $service) {
            if ($service['provider_id'] == $providerId && count($services) < 3) {
                $services[] = $service;
            }
        }
        $reviews = $data->getReviews($providerId);
        $latest = null;
        if (count($reviews) > 0) {
            $latest = $reviews[0];
        }
        return [
            'stats' => $stats,
            'next_booking' => $next,
            'recent_bookings' => $recent,
            'services' => $services,
            'rating' => $data->rating($providerId),
            'latest_review' => $latest
        ];
    }
}
