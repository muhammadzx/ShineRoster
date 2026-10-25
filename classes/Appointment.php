<?php
class Appointment
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

    public function getServices($providerId)
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE provider_id = ? ORDER BY name');
        $stmt->execute([$providerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getById($slotId, $providerId)
    {
        $data = new SiteData($this->db);
        $slot = $data->getSlot($slotId);
        if ($slot && $slot['provider_id'] == $providerId) {
            return $slot;
        }
        return null;
    }
    public function getAll($providerId, $serviceId = 0, $date = '')
    {
        $data = new SiteData($this->db);
        $result = [];
        for ($i = count($data->slots) - 1; $i >= 0; $i--) {
            $slot = $data->getSlot($data->slots[$i]['id']);
            if (!$slot || $slot['provider_id'] != $providerId) {
                continue;
            }
            if ($serviceId > 0 && $slot['service_id'] != $serviceId) {
                continue;
            }
            if ($date !== '' && substr($slot['starts_at'], 0, 10) !== $date) {
                continue;
            }
            $slot['has_history'] = false;
            $slot['is_reserved'] = false;
            foreach ($data->bookings as $booking) {
                if ($booking['slot_id'] == $slot['id']) {
                    $slot['has_history'] = true;
                    if ($booking['status'] === 'confirmed' || $booking['status'] === 'completed') {
                        $slot['is_reserved'] = true;
                    }
                }
            }
            $result[] = $slot;
        }
        return $result;
    }
    public function save($providerId, $serviceId, $date, $time, $slotId = 0)
    {
        $this->error = '';
        $data = new SiteData($this->db);
        $provider = $data->getProvider($providerId);
        if (
            !$provider ||
            !$provider['provider_active'] ||
            $provider['provider_role'] !== 'provider'
        ) {
            return $this->fail('Provider account unavailable.');
        }
        $service = $data->getService($serviceId);
        if (!$service || $service['provider_id'] != $providerId || !$service['is_active']) {
            return $this->fail('Please select one of your active services.');
        }
        if ($slotId > 0) {
            $slot = $data->getSlot($slotId);
            if (!$slot || $slot['provider_id'] != $providerId) {
                return $this->fail('Appointment not found.');
            }
            foreach ($data->bookings as $booking) {
                if ($booking['slot_id'] == $slotId) {
                    return $this->fail('Appointments with booking history cannot be edited.');
                }
            }
        }
        $start = strtotime($date . ' ' . $time);
        if (!$start || date('Y-m-d H:i', $start) !== $date . ' ' . $time) {
            return $this->fail('Enter a valid date and time.');
        }
        if ($start <= time()) {
            return $this->fail('The appointment must start in the future.');
        }
        $end = $start + (int) $service['duration_minutes'] * 60;
        foreach ($data->slots as $row) {
            $other = $data->getSlot($row['id']);
            if (
                $other &&
                $other['provider_id'] == $providerId &&
                $other['id'] != $slotId &&
                strtotime($other['starts_at']) < $end &&
                strtotime($other['ends_at']) > $start
            ) {
                return $this->fail(
                    'This time overlaps another appointment, including closed appointments.'
                );
            }
        }
        $startsAt = date('Y-m-d H:i:s', $start);
        $endsAt = date('Y-m-d H:i:s', $end);
        if ($slotId > 0) {
            $stmt = $this->db->prepare(
                'UPDATE appointment_slots SET service_id = ?, starts_at = ?, ends_at = ? WHERE id = ?'
            );
            $saved = $stmt->execute([$serviceId, $startsAt, $endsAt, $slotId]);
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO appointment_slots (service_id, starts_at, ends_at, is_open) VALUES (?, ?, ?, 1)'
            );
            $saved = $stmt->execute([$serviceId, $startsAt, $endsAt]);
        }
        if (!$saved) {
            return $this->fail('The appointment could not be saved.');
        }
        return true;
    }
    public function setOpen($slotId, $providerId, $isOpen)
    {
        $slot = $this->getById($slotId, $providerId);
        if (!$slot) {
            return $this->fail('Appointment not found.');
        }
        if (strtotime($slot['starts_at']) <= time()) {
            return $this->fail('Past or started appointments cannot be opened or closed.');
        }
        $value = 0;
        if ($isOpen) {
            $value = 1;
        }
        $stmt = $this->db->prepare('UPDATE appointment_slots SET is_open = ? WHERE id = ?');
        if (!$stmt->execute([$value, $slotId])) {
            return $this->fail('Could not update this appointment.');
        }
        return true;
    }
    public function delete($slotId, $providerId)
    {
        if (!$this->getById($slotId, $providerId)) {
            return $this->fail('Appointment not found.');
        }
        $stmt = $this->db->prepare('SELECT id FROM bookings WHERE slot_id = ?');
        $stmt->execute([$slotId]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return $this->fail('Appointments with booking history cannot be deleted.');
        }
        $stmt = $this->db->prepare('DELETE FROM appointment_slots WHERE id = ?');
        if (!$stmt->execute([$slotId])) {
            return $this->fail('Could not delete this appointment.');
        }
        return true;
    }
}
