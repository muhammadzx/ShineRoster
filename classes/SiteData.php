<?php
class SiteData
{
    public $users;
    public $providers;
    public $services;
    public $slots;
    public $bookings;
    public $reviews;
    public $skills;
    public $providerSkills;

    public function __construct($db)
    {
        $stmt = $db->prepare('SELECT * FROM users ORDER BY id');
        $stmt->execute();
        $this->users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM provider_profiles ORDER BY id DESC');
        $stmt->execute();
        $this->providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM services ORDER BY id DESC');
        $stmt->execute();
        $this->services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM appointment_slots ORDER BY starts_at, id');
        $stmt->execute();
        $this->slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM bookings ORDER BY created_at DESC, id DESC');
        $stmt->execute();
        $this->bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM reviews ORDER BY created_at DESC, id DESC');
        $stmt->execute();
        $this->reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM skills ORDER BY name');
        $stmt->execute();
        $this->skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->prepare('SELECT * FROM provider_skills ORDER BY provider_id, skill_id');
        $stmt->execute();
        $this->providerSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUser($id)
    {
        foreach ($this->users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }

    public function getProvider($id)
    {
        foreach ($this->providers as $provider) {
            if ($provider['id'] == $id) {
                $user = $this->getUser($provider['user_id']);
                if (!$user) {
                    return null;
                }
                $provider['full_name'] = $user['full_name'];
                $provider['provider_name'] = $user['full_name'];
                $provider['provider_active'] = $user['is_active'];
                $provider['provider_role'] = $user['role'];
                return $provider;
            }
        }
        return null;
    }

    public function getService($id)
    {
        foreach ($this->services as $service) {
            if ($service['id'] == $id) {
                $provider = $this->getProvider($service['provider_id']);
                if (!$provider) {
                    return null;
                }
                $service['city'] = $provider['city'];
                $service['bio'] = $provider['bio'];
                $service['provider_name'] = $provider['full_name'];
                $service['provider_active'] = $provider['provider_active'];
                $service['provider_role'] = $provider['provider_role'];
                return $service;
            }
        }
        return null;
    }

    public function getSlot($id)
    {
        foreach ($this->slots as $slot) {
            if ($slot['id'] == $id) {
                $service = $this->getService($slot['service_id']);
                if (!$service) {
                    return null;
                }
                $slot['slot_id'] = $slot['id'];
                $slot['service_name'] = $service['name'];
                $slot['price'] = $service['price'];
                $slot['duration_minutes'] = $service['duration_minutes'];
                $slot['service_active'] = $service['is_active'];
                $slot['provider_id'] = $service['provider_id'];
                $slot['provider_name'] = $service['provider_name'];
                $slot['provider_active'] = $service['provider_active'];
                $slot['provider_role'] = $service['provider_role'];
                $slot['city'] = $service['city'];
                return $slot;
            }
        }
        return null;
    }

    public function getBooking($id)
    {
        foreach ($this->bookings as $booking) {
            if ($booking['id'] == $id) {
                $slot = $this->getSlot($booking['slot_id']);
                $user = $this->getUser($booking['user_id']);
                if (!$slot || !$user) {
                    return null;
                }
                $booking['starts_at'] = $slot['starts_at'];
                $booking['ends_at'] = $slot['ends_at'];
                $booking['service_id'] = $slot['service_id'];
                $booking['service_name'] = $slot['service_name'];
                $booking['provider_id'] = $slot['provider_id'];
                $booking['provider_name'] = $slot['provider_name'];
                $booking['customer_name'] = $user['full_name'];
                $booking['customer_email'] = $user['email'];
                $booking['customer_phone'] = $user['phone'];
                $booking['can_cancel'] = false;
                if ($booking['status'] === 'confirmed' && strtotime($slot['starts_at']) > time()) {
                    $booking['can_cancel'] = true;
                }
                $booking['review_id'] = null;
                $booking['review_rating'] = null;
                $booking['review_comment'] = null;
                $booking['review_created_at'] = null;
                foreach ($this->reviews as $review) {
                    if ($review['booking_id'] == $id) {
                        $booking['review_id'] = $review['id'];
                        $booking['review_rating'] = $review['rating'];
                        $booking['review_comment'] = $review['comment'];
                        $booking['review_created_at'] = $review['created_at'];
                        break;
                    }
                }
                return $booking;
            }
        }
        return null;
    }

    public function getSkills($providerId)
    {
        $result = [];
        foreach ($this->skills as $skill) {
            foreach ($this->providerSkills as $link) {
                if ($link['provider_id'] == $providerId && $link['skill_id'] == $skill['id']) {
                    $result[] = $skill;
                    break;
                }
            }
        }
        return $result;
    }

    public function availableSlots($serviceId)
    {
        $result = [];
        foreach ($this->slots as $row) {
            if ($row['service_id'] != $serviceId) {
                continue;
            }
            $slot = $this->getSlot($row['id']);
            if (
                !$slot ||
                !$slot['is_open'] ||
                !$slot['service_active'] ||
                !$slot['provider_active'] ||
                $slot['provider_role'] !== 'provider' ||
                trim($slot['city']) === '' ||
                strtotime($slot['starts_at']) <= time()
            ) {
                continue;
            }
            $reserved = false;
            foreach ($this->bookings as $booking) {
                if (
                    $booking['slot_id'] == $slot['id'] &&
                    ($booking['status'] === 'confirmed' || $booking['status'] === 'completed')
                ) {
                    $reserved = true;
                    break;
                }
            }
            if (!$reserved) {
                $result[] = $slot;
            }
        }
        return $result;
    }

    public function getReviews($providerId, $serviceId = 0)
    {
        $result = [];
        foreach ($this->reviews as $review) {
            if (!$review['is_visible']) {
                continue;
            }
            $booking = $this->getBooking($review['booking_id']);
            if (
                !$booking ||
                $booking['provider_id'] != $providerId ||
                $booking['status'] !== 'completed'
            ) {
                continue;
            }
            if ($serviceId > 0 && $booking['service_id'] != $serviceId) {
                continue;
            }
            $review['customer_name'] = $booking['customer_name'];
            $review['service_name'] = $booking['service_name'];
            $result[] = $review;
        }
        return $result;
    }

    public function rating($providerId)
    {
        $reviews = $this->getReviews($providerId);
        $sum = 0;
        foreach ($reviews as $review) {
            $sum += $review['rating'];
        }
        $average = null;
        if (count($reviews) > 0) {
            $average = round($sum / count($reviews), 2);
        }
        return ['review_count' => count($reviews), 'average_rating' => $average];
    }

    public function serviceCard($serviceId)
    {
        $service = $this->getService($serviceId);
        if (
            !$service ||
            !$service['is_active'] ||
            !$service['provider_active'] ||
            $service['provider_role'] !== 'provider' ||
            trim($service['city']) === ''
        ) {
            return null;
        }
        $rating = $this->rating($service['provider_id']);
        $service['average_rating'] = $rating['average_rating'];
        $service['review_count'] = $rating['review_count'];
        $service['available_count'] = count($this->availableSlots($serviceId));
        $service['skills'] = [];
        foreach ($this->getSkills($service['provider_id']) as $skill) {
            $service['skills'][] = $skill['name'];
        }
        return $service;
    }

    public function providerCard($providerId)
    {
        $provider = $this->getProvider($providerId);
        if (
            !$provider ||
            !$provider['provider_active'] ||
            $provider['provider_role'] !== 'provider' ||
            trim($provider['city']) === ''
        ) {
            return null;
        }
        $provider['service_count'] = 0;
        $provider['minimum_price'] = null;
        $provider['available_count'] = 0;
        $provider['next_appointment'] = null;
        $provider['completed_count'] = 0;
        foreach ($this->services as $service) {
            if ($service['provider_id'] != $providerId || !$service['is_active']) {
                continue;
            }
            $provider['service_count']++;
            if (
                $provider['minimum_price'] === null ||
                $service['price'] < $provider['minimum_price']
            ) {
                $provider['minimum_price'] = $service['price'];
            }
            foreach ($this->availableSlots($service['id']) as $slot) {
                $provider['available_count']++;
                if (
                    $provider['next_appointment'] === null ||
                    $slot['starts_at'] < $provider['next_appointment']
                ) {
                    $provider['next_appointment'] = $slot['starts_at'];
                }
            }
        }
        foreach ($this->bookings as $row) {
            if ($row['status'] !== 'completed') {
                continue;
            }
            $booking = $this->getBooking($row['id']);
            if ($booking && $booking['provider_id'] == $providerId) {
                $provider['completed_count']++;
            }
        }
        $rating = $this->rating($providerId);
        $provider['average_rating'] = $rating['average_rating'];
        $provider['review_count'] = $rating['review_count'];
        $provider['skills'] = [];
        foreach ($this->getSkills($providerId) as $skill) {
            $provider['skills'][] = $skill['name'];
        }
        return $provider;
    }

    public function providerBookings($providerId)
    {
        $result = [];
        foreach ($this->bookings as $row) {
            $booking = $this->getBooking($row['id']);
            if ($booking && $booking['provider_id'] == $providerId) {
                $result[] = $booking;
            }
        }
        return $result;
    }

    public function stats($bookings)
    {
        $stats = ['total' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($bookings as $booking) {
            $stats['total']++;
            if ($booking['status'] === 'confirmed' && strtotime($booking['starts_at']) > time()) {
                $stats['upcoming']++;
            }
            if ($booking['status'] === 'completed') {
                $stats['completed']++;
            }
            if ($booking['status'] === 'cancelled') {
                $stats['cancelled']++;
            }
        }
        return $stats;
    }

    public function sortCards($rows, $sort, $priceKey)
    {
        // Compare adjacent cards using ordinary loops.
        for ($i = 0; $i < count($rows); $i++) {
            for ($j = 0; $j < count($rows) - 1; $j++) {
                $a = $rows[$j];
                $b = $rows[$j + 1];
                $swap = false;
                if ($sort === 'price_low') {
                    $swap = $a[$priceKey] > $b[$priceKey];
                } elseif ($sort === 'price_high') {
                    $swap = $a[$priceKey] < $b[$priceKey];
                } elseif ($sort === 'newest') {
                    $swap = $a['id'] < $b['id'];
                } else {
                    $aAvailable = 0;
                    $bAvailable = 0;
                    if ($sort === 'recommended' && $a['available_count'] > 0) {
                        $aAvailable = 1;
                    }
                    if ($sort === 'recommended' && $b['available_count'] > 0) {
                        $bAvailable = 1;
                    }
                    if ($aAvailable != $bAvailable) {
                        $swap = $aAvailable < $bAvailable;
                    } elseif ($a['average_rating'] != $b['average_rating']) {
                        $swap = $a['average_rating'] < $b['average_rating'];
                    } elseif ($a['review_count'] != $b['review_count']) {
                        $swap = $a['review_count'] < $b['review_count'];
                    } else {
                        $swap = $a['id'] < $b['id'];
                    }
                }
                if (
                    ($sort === 'price_low' || $sort === 'price_high') &&
                    $a[$priceKey] == $b[$priceKey]
                ) {
                    $swap = $a['id'] < $b['id'];
                }
                if ($swap) {
                    $rows[$j] = $b;
                    $rows[$j + 1] = $a;
                }
            }
        }
        return $rows;
    }

    public function page($rows, $page, $perPage, $key)
    {
        $total = count($rows);
        $pages = (int) ceil($total / $perPage);
        if ($pages < 1) {
            $pages = 1;
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $pages) {
            $page = $pages;
        }
        $start = ($page - 1) * $perPage;
        $visible = [];
        for ($i = $start; $i < $start + $perPage && $i < $total; $i++) {
            $visible[] = $rows[$i];
        }
        return [
            $key => $visible,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage
        ];
    }
}
