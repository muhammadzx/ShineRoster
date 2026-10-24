<?php
class Service
{
    private int $providerId;

    public function __construct(private PDO $db, int $userId)
    {
        $stmt = $this->db->prepare('SELECT id FROM provider_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $providerId = $stmt->fetchColumn();
        if (!$providerId) {
            throw new RuntimeException('Complete your provider profile first.');
        }
        $this->providerId = (int) $providerId;
    }

    public function getStats(): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total,
                COALESCE(SUM(is_active = 1), 0) AS active,
                COALESCE(SUM(is_active = 0), 0) AS inactive
             FROM services WHERE provider_id = ?'
        );
        $stmt->execute([$this->providerId]);
        return array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function getServices(string $search = '', string $status = ''): array
    {
        $sql = 'SELECT id, name, description, price, duration_minutes, is_active
                FROM services WHERE provider_id = ?';
        $params = [$this->providerId];
        if ($search !== '') {
            $sql .= ' AND name LIKE ?';
            $params[] = '%' . $search . '%';
        }
        if (in_array($status, ['active', 'inactive'], true)) {
            $sql .= ' AND is_active = ?';
            $params[] = $status === 'active' ? 1 : 0;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addService($name, $description, $price, $duration, $status): bool
    {
        $this->validateService($name, $description, $price, $duration);
        $this->validateStatus($status);
        return $this->mutate(function () use (
            $name,
            $description,
            $price,
            $duration,
            $status
        ): bool {
            $stmt = $this->db->prepare(
                'INSERT INTO services (provider_id, name, description, price, duration_minutes, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            return $stmt->execute([
                $this->providerId,
                trim($name),
                trim($description),
                $price,
                $duration,
                $status
            ]);
        });
    }

    public function updateService($serviceId, $name, $description, $price, $duration): bool
    {
        $this->validateService($name, $description, $price, $duration);
        return $this->mutate(function () use (
            $serviceId,
            $name,
            $description,
            $price,
            $duration
        ): bool {
            if (!$this->ownsService($serviceId)) {
                return false;
            }
            $stmt = $this->db->prepare(
                'UPDATE services SET name = ?, description = ?, price = ?, duration_minutes = ?
                 WHERE id = ? AND provider_id = ?'
            );
            return $stmt->execute([
                trim($name),
                trim($description),
                $price,
                $duration,
                $serviceId,
                $this->providerId
            ]);
        });
    }

    public function changeStatus($serviceId, $status): bool
    {
        $this->validateStatus($status);
        return $this->mutate(function () use ($serviceId, $status): bool {
            if (!$this->ownsService($serviceId)) {
                return false;
            }
            $stmt = $this->db->prepare(
                'UPDATE services SET is_active = ? WHERE id = ? AND provider_id = ?'
            );
            return $stmt->execute([$status, $serviceId, $this->providerId]);
        });
    }

    private function ownsService($id): bool
    {
        if (!filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT id FROM services WHERE id = ? AND provider_id = ?');
        $stmt->execute([$id, $this->providerId]);
        return (bool) $stmt->fetchColumn();
    }

    private function validateStatus($status): void
    {
        if (!in_array($status, [0, 1, '0', '1'], true)) {
            throw new RuntimeException('Invalid status.');
        }
    }

    private function validateService($name, $description, $price, $duration): void
    {
        if (
            !is_string($name) ||
            trim($name) === '' ||
            !mb_check_encoding($name, 'UTF-8') ||
            mb_strlen($name) > 120
        ) {
            throw new RuntimeException('Enter a service name of 1 to 120 characters.');
        }
        if (
            !is_string($description) ||
            trim($description) === '' ||
            !mb_check_encoding($description, 'UTF-8') ||
            strlen($description) > 65535
        ) {
            throw new RuntimeException('Enter a description up to 65535 bytes.');
        }
        if (!is_scalar($price) || !preg_match('/^\d{1,7}(?:\.\d{1,3})?$/D', (string) $price)) {
            throw new RuntimeException('Enter a valid price with up to 3 decimal places.');
        }
        if (
            !filter_var($duration, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 65535]
            ])
        ) {
            throw new RuntimeException('Enter a valid duration.');
        }
    }

    // Share the provider lock used by appointment and booking mutations.
    private function mutate(callable $operation): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id FROM provider_profiles WHERE id = ? FOR UPDATE');
            $stmt->execute([$this->providerId]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Provider profile not found.');
            }
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
