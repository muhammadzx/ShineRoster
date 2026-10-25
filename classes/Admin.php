<?php
class Admin
{
    private $db;
    private $ownerId;
    private $error = '';

    public function __construct($db, $ownerId)
    {
        $this->db = $db;
        $this->ownerId = $ownerId;
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

    public function getOverview()
    {
        $data = new SiteData($this->db);
        $stats = [
            'users' => count($data->users),
            'customers' => 0,
            'providers' => 0,
            'active_services' => 0,
            'services' => count($data->services),
            'bookings' => count($data->bookings),
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'completed_value' => 0,
            'reviews' => count($data->reviews),
            'messages' => 0
        ];
        foreach ($data->users as $user) {
            if ($user['role'] === 'customer') {
                $stats['customers']++;
            }
            if ($user['role'] === 'provider') {
                $stats['providers']++;
            }
        }
        foreach ($data->services as $service) {
            if ($service['is_active']) {
                $stats['active_services']++;
            }
        }
        $recent = [];
        foreach ($data->bookings as $row) {
            if ($row['status'] === 'confirmed') {
                $stats['confirmed']++;
            }
            if ($row['status'] === 'cancelled') {
                $stats['cancelled']++;
            }
            if ($row['status'] === 'completed') {
                $stats['completed']++;
                $stats['completed_value'] += $row['total_price'];
            }
            if (count($recent) < 5) {
                $booking = $data->getBooking($row['id']);
                if ($booking) {
                    $recent[] = $booking;
                }
            }
        }
        $stmt = $this->db->prepare(
            'SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['messages'] = count($messages);
        $latest = [];
        foreach ($messages as $message) {
            if (count($latest) < 4) {
                $latest[] = $message;
            }
        }
        return ['stats' => $stats, 'recent_bookings' => $recent, 'latest_messages' => $latest];
    }

    public function getUser($id)
    {
        $user = new User($this->db);
        return $user->getProfileAccount($id);
    }

    public function getUsers($search, $role, $status, $page)
    {
        $stmt = $this->db->prepare(
            'SELECT id, full_name, email, phone, role, is_active, created_at FROM users ORDER BY id DESC'
        );
        $stmt->execute();
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user) {
            if ($role !== '' && $user['role'] !== $role) {
                continue;
            }
            if ($status === 'active' && !$user['is_active']) {
                continue;
            }
            if ($status === 'inactive' && $user['is_active']) {
                continue;
            }
            if (
                $search !== '' &&
                mb_stripos(
                    $user['full_name'] . ' ' . $user['email'] . ' ' . $user['phone'],
                    $search
                ) === false
            ) {
                continue;
            }
            $rows[] = $user;
        }
        $data = new SiteData($this->db);
        return $data->page($rows, $page, 10, 'rows');
    }

    public function createUser($form)
    {
        $auth = new Auth($this->db);
        if (
            !$auth->register(
                $form['full_name'],
                $form['email'],
                $form['phone'],
                $form['password'],
                $form['role'],
                $form['confirm_password']
            )
        ) {
            return $this->fail($auth->getError());
        }
        return true;
    }

    public function saveUser($id, $form)
    {
        $user = $this->getUser($id);
        if (!$user || $user['role'] === 'admin') {
            return $this->fail('This account cannot be edited here. Use My account for the owner.');
        }
        if (!$this->validAccount($id, $form)) {
            return false;
        }
        $stmt = $this->db->prepare(
            'UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?'
        );
        if (
            !$stmt->execute([
                trim($form['full_name']),
                strtolower(trim($form['email'])),
                trim($form['phone']),
                $id
            ])
        ) {
            return $this->fail('The account could not be saved.');
        }
        return true;
    }

    private function validAccount($id, $form)
    {
        if (mb_strlen(trim($form['full_name'])) < 2 || mb_strlen($form['full_name']) > 100) {
            return $this->fail('Enter a name of 2 to 100 characters.');
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 190) {
            return $this->fail('Enter a valid email address.');
        }
        if (!validPhone($form['phone'])) {
            return $this->fail('Enter a valid phone number.');
        }
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([strtolower(trim($form['email'])), $id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('This email address is already registered.');
        }
        return true;
    }

    public function setUserStatus($id, $value)
    {
        $user = $this->getUser($id);
        if (!$user || $user['role'] === 'admin' || $id == $this->ownerId) {
            return $this->fail('The site owner cannot be disabled.');
        }
        if ($value != 0 && $value != 1) {
            return $this->fail('Invalid account status.');
        }
        if ($value == 0) {
            $data = new SiteData($this->db);
            foreach ($data->bookings as $row) {
                if ($row['status'] !== 'confirmed') {
                    continue;
                }
                $booking = $data->getBooking($row['id']);
                if (!$booking) {
                    continue;
                }
                $provider = $data->getProvider($booking['provider_id']);
                if ($booking['user_id'] == $id || ($provider && $provider['user_id'] == $id)) {
                    return $this->fail(
                        "Resolve this account's confirmed bookings before disabling it."
                    );
                }
            }
        }
        $stmt = $this->db->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        if (!$stmt->execute([$value, $id])) {
            return $this->fail('The account status could not be changed.');
        }
        return true;
    }

    public function deleteUser($id)
    {
        $user = $this->getUser($id);
        if (!$user || $user['role'] === 'admin' || $id == $this->ownerId) {
            return $this->fail('The site owner cannot be deleted.');
        }
        $stmt = $this->db->prepare('SELECT id FROM bookings WHERE user_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('This account has booking history. Disable it instead.');
        }
        $stmt = $this->db->prepare('SELECT id FROM provider_profiles WHERE user_id = ?');
        $stmt->execute([$id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            $stmt = $this->db->prepare('SELECT id FROM services WHERE provider_id = ? LIMIT 1');
            $stmt->execute([$profile['id']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return $this->fail(
                    'This provider still has services. Disable the account instead.'
                );
            }
            $stmt = $this->db->prepare('DELETE FROM provider_skills WHERE provider_id = ?');
            if (!$stmt->execute([$profile['id']])) {
                return $this->fail('Could not remove the provider skills.');
            }
            $stmt = $this->db->prepare('DELETE FROM provider_profiles WHERE id = ?');
            if (!$stmt->execute([$profile['id']])) {
                return $this->fail('Could not remove the provider profile.');
            }
        }
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        if (!$stmt->execute([$id])) {
            return $this->fail('This account could not be deleted.');
        }
        return true;
    }

    public function getProviders($search, $status, $page)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->users as $user) {
            if ($user['role'] !== 'provider') {
                continue;
            }
            if ($status === 'active' && !$user['is_active']) {
                continue;
            }
            if ($status === 'inactive' && $user['is_active']) {
                continue;
            }
            $provider = [
                'user_id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'is_active' => $user['is_active'],
                'id' => 0,
                'city' => '',
                'bio' => '',
                'service_count' => 0,
                'skills' => []
            ];
            foreach ($data->providers as $profile) {
                if ($profile['user_id'] == $user['id']) {
                    $provider['id'] = $profile['id'];
                    $provider['city'] = $profile['city'];
                    $provider['bio'] = $profile['bio'];
                    $provider['skills'] = $data->getSkills($profile['id']);
                    foreach ($data->services as $service) {
                        if ($service['provider_id'] == $profile['id']) {
                            $provider['service_count']++;
                        }
                    }
                    break;
                }
            }
            if (
                $search !== '' &&
                mb_stripos(
                    $provider['full_name'] . ' ' . $provider['email'] . ' ' . $provider['city'],
                    $search
                ) === false
            ) {
                continue;
            }
            $rows[] = $provider;
        }
        return $data->page($rows, $page, 10, 'rows');
    }

    public function getProviderForm($userId)
    {
        $user = $this->getUser($userId);
        if (!$user || $user['role'] !== 'provider') {
            return null;
        }
        $form = [
            'user_id' => $userId,
            'full_name' => $user['full_name'],
            'city' => '',
            'bio' => '',
            'skills' => []
        ];
        $provider = new Provider($this->db);
        $profile = $provider->getProfileByUserId($userId);
        if ($profile) {
            $form['city'] = $profile['city'];
            $form['bio'] = $profile['bio'];
            $form['skills'] = $provider->getSkillIds($profile['id']);
        }
        return $form;
    }

    public function saveProvider($userId, $form)
    {
        if (!$this->getProviderForm($userId)) {
            return $this->fail('Provider not found.');
        }
        if (trim($form['city']) === '' || mb_strlen($form['city']) > 100) {
            return $this->fail('Enter a city of 1 to 100 characters.');
        }
        if (mb_strlen($form['bio']) > 3000 || !is_array($form['skills'])) {
            return $this->fail('Check the description and selected skills.');
        }
        $ids = [];
        foreach ($form['skills'] as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) {
                return $this->fail('Invalid skill selection.');
            }
            $stmt = $this->db->prepare('SELECT id FROM skills WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return $this->fail('Invalid skill selection.');
            }
            if (!in_array((int) $id, $ids)) {
                $ids[] = (int) $id;
            }
        }
        $provider = new Provider($this->db);
        $profile = $provider->getProfileByUserId($userId);
        if ($profile) {
            $stmt = $this->db->prepare(
                'UPDATE provider_profiles SET city = ?, bio = ? WHERE user_id = ?'
            );
            $saved = $stmt->execute([trim($form['city']), trim($form['bio']), $userId]);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO provider_profiles (user_id, city, bio) VALUES (?, ?, ?)'
            );
            $saved = $stmt->execute([$userId, trim($form['city']), trim($form['bio'])]);
        }
        if (!$saved) {
            return $this->fail('The provider profile could not be saved.');
        }
        $profile = $provider->getProfileByUserId($userId);
        $stmt = $this->db->prepare('DELETE FROM provider_skills WHERE provider_id = ?');
        if (!$stmt->execute([$profile['id']])) {
            return $this->fail('Could not update the skills.');
        }
        foreach ($ids as $id) {
            $stmt = $this->db->prepare(
                'INSERT INTO provider_skills (provider_id, skill_id) VALUES (?, ?)'
            );
            if (!$stmt->execute([$profile['id'], $id])) {
                return $this->fail('Could not save all selected skills.');
            }
        }
        return true;
    }

    public function getSkills()
    {
        $stmt = $this->db->prepare('SELECT * FROM skills ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServices($search, $status, $page)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->services as $row) {
            $service = $data->getService($row['id']);
            if (!$service) {
                continue;
            }
            if ($status === 'active' && !$service['is_active']) {
                continue;
            }
            if ($status === 'inactive' && $service['is_active']) {
                continue;
            }
            if (
                $search !== '' &&
                mb_stripos(
                    $service['name'] . ' ' . $service['provider_name'] . ' ' . $service['city'],
                    $search
                ) === false
            ) {
                continue;
            }
            $rows[] = $service;
        }
        return $data->page($rows, $page, 10, 'rows');
    }

    public function setServiceStatus($id, $value)
    {
        $data = new SiteData($this->db);
        $row = $data->getService($id);
        if (!$row) {
            return $this->fail('Service not found.');
        }
        $profile = $data->getProvider($row['provider_id']);
        $service = new Service($this->db, $profile['user_id']);
        if (!$service->changeStatus($id, $value)) {
            return $this->fail($service->getError());
        }
        return true;
    }

    public function deleteService($id)
    {
        $stmt = $this->db->prepare('SELECT id FROM services WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('Service not found.');
        }
        $stmt = $this->db->prepare('SELECT id FROM appointment_slots WHERE service_id = ? LIMIT 1');
        $stmt->execute([$id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail(
                'This service has appointments. Remove unused appointments first, or deactivate the service.'
            );
        }
        $stmt = $this->db->prepare('DELETE FROM services WHERE id = ?');
        if (!$stmt->execute([$id])) {
            return $this->fail('The service could not be deleted.');
        }
        return true;
    }

    public function getAppointments($search, $status, $serviceId, $page)
    {
        $data = new SiteData($this->db);
        $rows = [];
        for ($i = count($data->slots) - 1; $i >= 0; $i--) {
            $slot = $data->getSlot($data->slots[$i]['id']);
            if (!$slot) {
                continue;
            }
            if ($serviceId > 0 && $slot['service_id'] != $serviceId) {
                continue;
            }
            $reserved = false;
            $history = false;
            foreach ($data->bookings as $booking) {
                if ($booking['slot_id'] == $slot['id']) {
                    $history = true;
                    if ($booking['status'] === 'confirmed' || $booking['status'] === 'completed') {
                        $reserved = true;
                    }
                }
            }
            $slot['reserved'] = $reserved;
            $slot['history'] = $history;
            $slot['past'] = strtotime($slot['starts_at']) <= time();
            $slot['available'] =
                !$slot['past'] &&
                !$reserved &&
                $slot['is_open'] &&
                $slot['service_active'] &&
                $slot['provider_active'] &&
                $slot['provider_role'] === 'provider' &&
                trim($slot['city']) !== '';
            if ($status === 'available' && !$slot['available']) {
                continue;
            }
            if ($status === 'closed' && $slot['is_open']) {
                continue;
            }
            if ($status === 'reserved' && !$reserved) {
                continue;
            }
            if ($status === 'past' && !$slot['past']) {
                continue;
            }
            if (
                $search !== '' &&
                mb_stripos(
                    $slot['service_name'] . ' ' . $slot['provider_name'] . ' ' . $slot['city'],
                    $search
                ) === false
            ) {
                continue;
            }
            $rows[] = $slot;
        }
        return $data->page($rows, $page, 12, 'rows');
    }

    public function appointmentAction($id, $action)
    {
        $data = new SiteData($this->db);
        $slot = $data->getSlot($id);
        if (!$slot) {
            return $this->fail('Appointment not found.');
        }
        $appointment = new Appointment($this->db);
        $saved = false;
        if ($action === 'delete') {
            $saved = $appointment->delete($id, $slot['provider_id']);
        } elseif ($action === 'open') {
            $saved = $appointment->setOpen($id, $slot['provider_id'], true);
        } elseif ($action === 'close') {
            $saved = $appointment->setOpen($id, $slot['provider_id'], false);
        } else {
            return $this->fail('Invalid appointment action.');
        }
        if (!$saved) {
            return $this->fail($appointment->getError());
        }
        return true;
    }

    public function getBookings($search, $status, $page)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->bookings as $row) {
            $booking = $data->getBooking($row['id']);
            if (!$booking) {
                continue;
            }
            if ($status !== '' && $booking['status'] !== $status) {
                continue;
            }
            $text =
                $booking['customer_name'] .
                ' ' .
                $booking['provider_name'] .
                ' ' .
                $booking['service_name'] .
                ' ' .
                $booking['city'];
            if (
                $search !== '' &&
                mb_stripos($text, $search) === false &&
                ltrim($search, '#') != $booking['id']
            ) {
                continue;
            }
            $rows[] = $booking;
        }
        return $data->page($rows, $page, 10, 'rows');
    }

    public function bookingAction($id, $action)
    {
        $data = new SiteData($this->db);
        $booking = $data->getBooking($id);
        if (!$booking) {
            return $this->fail('Booking not found.');
        }
        if ($booking['status'] !== 'confirmed') {
            return $this->fail('Only confirmed bookings can be updated.');
        }
        if ($action === 'cancel') {
            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'cancelled', cancelled_at = ? WHERE id = ? AND status = 'confirmed'"
            );
        } elseif ($action === 'complete') {
            if (strtotime($booking['ends_at']) > time()) {
                return $this->fail('The appointment must end before it can be completed.');
            }
            $stmt = $this->db->prepare(
                "UPDATE bookings SET status = 'completed', completed_at = ? WHERE id = ? AND status = 'confirmed'"
            );
        } else {
            return $this->fail('Invalid booking action.');
        }
        if (!$stmt->execute([date('Y-m-d H:i:s'), $id])) {
            return $this->fail('The booking could not be updated.');
        }
        return true;
    }

    public function getReviews($search, $status, $page)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->reviews as $review) {
            $booking = $data->getBooking($review['booking_id']);
            if (!$booking) {
                continue;
            }
            if ($status === 'visible' && !$review['is_visible']) {
                continue;
            }
            if ($status === 'hidden' && $review['is_visible']) {
                continue;
            }
            $review['customer_name'] = $booking['customer_name'];
            $review['provider_name'] = $booking['provider_name'];
            $review['service_name'] = $booking['service_name'];
            if (
                $search !== '' &&
                mb_stripos(
                    $review['comment'] .
                        ' ' .
                        $review['customer_name'] .
                        ' ' .
                        $review['provider_name'] .
                        ' ' .
                        $review['service_name'],
                    $search
                ) === false
            ) {
                continue;
            }
            $rows[] = $review;
        }
        return $data->page($rows, $page, 10, 'rows');
    }

    public function reviewAction($id, $action)
    {
        $stmt = $this->db->prepare('SELECT id FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('Review not found.');
        }
        if ($action === 'delete') {
            $stmt = $this->db->prepare('DELETE FROM reviews WHERE id = ?');
            $saved = $stmt->execute([$id]);
        } elseif ($action === 'show' || $action === 'hide') {
            $value = 0;
            if ($action === 'show') {
                $value = 1;
            }
            $stmt = $this->db->prepare('UPDATE reviews SET is_visible = ? WHERE id = ?');
            $saved = $stmt->execute([$value, $id]);
        } else {
            return $this->fail('Invalid review action.');
        }
        if (!$saved) {
            return $this->fail('The review could not be updated.');
        }
        return true;
    }

    public function getMessages($search, $subject, $page)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute();
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $message) {
            if ($subject !== '' && $message['subject'] !== $subject) {
                continue;
            }
            if (
                $search !== '' &&
                mb_stripos(
                    $message['full_name'] . ' ' . $message['email'] . ' ' . $message['message'],
                    $search
                ) === false
            ) {
                continue;
            }
            $rows[] = $message;
        }
        $data = new SiteData($this->db);
        return $data->page($rows, $page, 10, 'rows');
    }

    public function deleteMessage($id)
    {
        $stmt = $this->db->prepare('SELECT id FROM contact_messages WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('Message not found.');
        }
        $stmt = $this->db->prepare('DELETE FROM contact_messages WHERE id = ?');
        if (!$stmt->execute([$id])) {
            return $this->fail('The message could not be deleted.');
        }
        return true;
    }

    public function saveSkill($id, $name)
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->fail('Enter a skill name of 1 to 100 characters.');
        }
        $stmt = $this->db->prepare('SELECT id FROM skills WHERE name = ? AND id <> ?');
        $stmt->execute([$name, $id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('This skill already exists.');
        }
        if ($id > 0) {
            $stmt = $this->db->prepare('SELECT id FROM skills WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return $this->fail('Skill not found.');
            }
            $stmt = $this->db->prepare('UPDATE skills SET name = ? WHERE id = ?');
            $saved = $stmt->execute([$name, $id]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO skills (name) VALUES (?)');
            $saved = $stmt->execute([$name]);
        }
        if (!$saved) {
            return $this->fail('The skill could not be saved.');
        }
        return true;
    }

    public function deleteSkill($id)
    {
        $stmt = $this->db->prepare('SELECT id FROM skills WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('Skill not found.');
        }
        $stmt = $this->db->prepare(
            'SELECT skill_id FROM provider_skills WHERE skill_id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail(
                'This skill is assigned to providers. Remove the assignments before deleting it.'
            );
        }
        $stmt = $this->db->prepare('DELETE FROM skills WHERE id = ?');
        if (!$stmt->execute([$id])) {
            return $this->fail('The skill could not be deleted.');
        }
        return true;
    }

    public function saveOwner($form)
    {
        if (!$this->validAccount($this->ownerId, $form)) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$this->ownerId]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$owner || $owner['role'] !== 'admin') {
            return $this->fail('Owner account not found.');
        }
        if (!password_verify($form['current_password'], $owner['password_hash'])) {
            return $this->fail('Enter your current password to save account changes.');
        }
        $hash = $owner['password_hash'];
        if ($form['password'] !== '' || $form['confirm_password'] !== '') {
            if (mb_strlen($form['password']) < 8 || strlen($form['password']) > 72) {
                return $this->fail('Use at least 8 characters and no more than 72 bytes.');
            }
            if ($form['password'] !== $form['confirm_password']) {
                return $this->fail('The new passwords do not match.');
            }
            $hash = password_hash($form['password'], PASSWORD_DEFAULT);
        }
        $stmt = $this->db->prepare(
            'UPDATE users SET full_name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?'
        );
        if (
            !$stmt->execute([
                trim($form['full_name']),
                strtolower(trim($form['email'])),
                trim($form['phone']),
                $hash,
                $this->ownerId
            ])
        ) {
            return $this->fail('The owner account could not be saved.');
        }
        $_SESSION['full_name'] = trim($form['full_name']);
        return true;
    }
}
