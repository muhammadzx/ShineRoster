<?php
class Service
{
    private $db;
    private $providerId = 0;
    private $error = '';
    public function __construct($db, $userId)
    {
        $this->db = $db;
        $stmt = $db->prepare('SELECT id FROM provider_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            $this->providerId = (int) $profile['id'];
        }
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
    public function getStats()
    {
        $rows = $this->getServices();
        $stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
        foreach ($rows as $row) {
            $stats['total']++;
            if ($row['is_active']) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }
        }
        return $stats;
    }
    public function getServices($search = '', $status = '')
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE provider_id = ? ORDER BY id DESC');
        $stmt->execute([$this->providerId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            if ($search !== '' && mb_stripos($row['name'], $search) === false) {
                continue;
            }
            if ($status === 'active' && !$row['is_active']) {
                continue;
            }
            if ($status === 'inactive' && $row['is_active']) {
                continue;
            }
            $result[] = $row;
        }
        return $result;
    }
    private function validService($name, $description, $price, $duration)
    {
        if (!$this->providerId) {
            return $this->fail('Complete your provider profile first.');
        }
        if (trim($name) === '' || mb_strlen($name) > 120) {
            return $this->fail('Enter a service name of 1 to 120 characters.');
        }
        if (trim($description) === '' || strlen($description) > 65535) {
            return $this->fail('Enter a description up to 65535 bytes.');
        }
        if (!validPrice($price)) {
            return $this->fail('Enter a valid price with up to 3 decimal places.');
        }
        if (
            !filter_var($duration, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 65535]
            ])
        ) {
            return $this->fail('Enter a valid duration.');
        }
        return true;
    }
    public function addService($name, $description, $price, $duration, $status)
    {
        if (!$this->validService($name, $description, $price, $duration)) {
            return false;
        }
        if ($status != '0' && $status != '1') {
            return $this->fail('Invalid status.');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO services (provider_id, name, description, price, duration_minutes, is_active) VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (
            !$stmt->execute([
                $this->providerId,
                trim($name),
                trim($description),
                $price,
                $duration,
                $status
            ])
        ) {
            return $this->fail('Could not save this service.');
        }
        return true;
    }
    private function ownsService($id)
    {
        $stmt = $this->db->prepare('SELECT id FROM services WHERE id = ? AND provider_id = ?');
        $stmt->execute([$id, $this->providerId]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return true;
        }
        return false;
    }
    public function updateService($id, $name, $description, $price, $duration)
    {
        if (!$this->ownsService($id)) {
            return $this->fail('Service not found.');
        }
        if (!$this->validService($name, $description, $price, $duration)) {
            return false;
        }
        $stmt = $this->db->prepare(
            'UPDATE services SET name = ?, description = ?, price = ?, duration_minutes = ? WHERE id = ? AND provider_id = ?'
        );
        if (
            !$stmt->execute([
                trim($name),
                trim($description),
                $price,
                $duration,
                $id,
                $this->providerId
            ])
        ) {
            return $this->fail('Could not save this service.');
        }
        return true;
    }
    public function changeStatus($id, $status)
    {
        if (!$this->ownsService($id)) {
            return $this->fail('Service not found.');
        }
        if ($status != '0' && $status != '1') {
            return $this->fail('Invalid status.');
        }
        $stmt = $this->db->prepare(
            'UPDATE services SET is_active = ? WHERE id = ? AND provider_id = ?'
        );
        if (!$stmt->execute([$status, $id, $this->providerId])) {
            return $this->fail('Could not update this service.');
        }
        return true;
    }
}
