<?php

class Service
{
    private $db;
    public int $lastResultCount = 0;
    private function ownsService($id): bool
    {
        if (!filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]])) return false;
        $stmt = $this->db->prepare('SELECT id FROM services WHERE id=? AND provider_id=?');
        $stmt->execute([$id, $this->provider_id]);
        return (bool) $stmt->fetchColumn();
    }
    private function validateService($name, $description, $price, $duration): void
    {
        if (!is_string($name) || trim($name) === '' || !mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 120) throw new RuntimeException('Enter a service name of 1 to 120 characters.');
        if (!is_string($description) || trim($description) === '' || !mb_check_encoding($description, 'UTF-8') || strlen($description) > 65535) throw new RuntimeException('Enter a description up to 65535 bytes.');
        if (!is_scalar($price) || !preg_match('/^\\d{1,7}(?:\\.\\d{1,3})?$/D', (string)$price)) throw new RuntimeException('Enter a valid price with up to 3 decimal places.');
        if (!filter_var($duration, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>65535]])) throw new RuntimeException('Enter a valid duration.');
    }
    private $id;
    private $provider_id;
    public function __construct(PDO $db, $id)
    {
        $this->db = $db;
        $this->id = $id;
        $sql = "SELECT `id` FROM `provider_profiles` WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$profile) throw new RuntimeException('Complete your provider profile first.');
        $this->provider_id = (int) $profile['id'];
    }


    private function mutate(callable $operation): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id FROM provider_profiles WHERE id=? FOR UPDATE');
            $stmt->execute([$this->provider_id]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Provider profile not found.');
            $result = $operation();
            $this->db->commit(); return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
    public function displayInfo()
    {
        $sql = "SELECT `id` FROM `services` WHERE is_active = 1 AND provider_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->provider_id]);
        $active = $stmt->fetchAll();
        $totel_active = count($active);

        $sql = "SELECT `id` FROM `services` WHERE is_active = 0 AND provider_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->provider_id]);
        $inactive = $stmt->fetchAll();
        $totel_inactive = count($inactive);

        $totel = $totel_inactive + $totel_active;

        echo '<article class="stat-card">
                        <div class="stat-card-top">
                            <span>Total services</span>
                            <span class="stat-icon" aria-hidden="true">✦</span>
                        </div>
                        <strong class="stat-value">' . $totel . '</strong>
                        <p>All your cleaning offers</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Active services</span>
                            <span class="stat-icon" aria-hidden="true">✓</span>
                        </div>
                        <strong class="stat-value">' . $totel_active . '</strong>
                        <p>Available for new bookings</p>
                    </article>

                    <article class="stat-card">
                        <div class="stat-card-top">
                            <span>Inactive services</span>
                            <span class="stat-icon" aria-hidden="true">Ⅱ</span>
                        </div>
                        <strong class="stat-value">' . $totel_inactive . '</strong>
                        <p>Hidden from new bookings</p>
                    </article>';
    }


    public function addService($name, $description, $price, $duration, $status): bool
    {
        $this->validateService($name, $description, $price, $duration);
        if (!in_array($status, [0,1,'0','1'], true)) throw new RuntimeException('Invalid status.');
        return $this->mutate(function () use ($name, $description, $price, $duration, $status): bool {
            $stmt = $this->db->prepare('INSERT INTO services (provider_id,name,description,price,duration_minutes,is_active) VALUES (?,?,?,?,?,?)');
            return $stmt->execute([$this->provider_id,trim($name),trim($description),$price,$duration,$status]);
        });
    }

    public function myServices($search = '', $status = '')
{
    $sql = "SELECT id, name, description, price,
                   duration_minutes, is_active
            FROM services
            WHERE provider_id = ?";

    $params = [$this->provider_id];

    if ($search !== '') {
        $sql .= " AND name LIKE ?";
        $params[] = '%' . $search . '%';
    }

    if ($status === 'active' || $status === 'inactive') {
        $sql .= " AND is_active = ?";
        $params[] = $status === 'active' ? 1 : 0;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $this->lastResultCount = count($services);
    foreach ($services as $service) {
        $id = (int) $service['id'];

        $name = htmlspecialchars(
            $service['name'],
            ENT_QUOTES,
            'UTF-8'
        );

        $description = htmlspecialchars(
            $service['description'],
            ENT_QUOTES,
            'UTF-8'
        );

        $price = htmlspecialchars(
            (string) $service['price'],
            ENT_QUOTES,
            'UTF-8'
        );

        $duration = (int) $service['duration_minutes'];
        $isActive = (int) $service['is_active'] === 1;

        $statusClass = $isActive
            ? 'status-completed'
            : 'status-inactive';

        $statusText = $isActive ? 'Active' : 'Inactive';
        $action = $isActive ? 'deactivate' : 'activate';
        $buttonText = $isActive ? 'Deactivate' : 'Activate';

        echo '
        <article class="provider-service-card">

            <div class="provider-service-top">
                <span class="provider-service-icon" aria-hidden="true">
                    ✦
                </span>

                <span class="status-badge ' . $statusClass . '">
                    ' . $statusText . '
                </span>
            </div>

            <h3>' . $name . '</h3>

            <p class="provider-service-description">'
                . $description .
            '</p>

            <dl class="provider-service-details">
                <div>
                    <dt>Price per visit</dt>
                    <dd>JOD ' . $price . '</dd>
                </div>

                <div>
                    <dt>Duration</dt>
                    <dd>' . $duration . ' minutes</dd>
                </div>
            </dl>

            <a class="service-appointments-link"
               href="slots.php?service_id=' . $id . '">
                Manage appointments
                <span aria-hidden="true">↗</span>
            </a>

            <div class="provider-service-actions">
                <details class="service-edit">
                    <summary>Edit service</summary>

                    <form class="service-edit-form"
                          action="services.php" method="post">
                        <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">

                        <input type="hidden"
                               name="action" value="update">

                        <input type="hidden"
                               name="service_id" value="' . $id . '">

                        <div class="service-field">
                            <label for="edit-name-' . $id . '">
                                Service name
                            </label>

                            <input type="text"
                                   id="edit-name-' . $id . '"
                                   name="name"
                                   value="' . $name . '"
                                   maxlength="120" required>
                        </div>

                        <div class="service-field">
                            <label for="edit-description-' . $id . '">
                                Description
                            </label>

                            <textarea
                                id="edit-description-' . $id . '"
                                name="description"
                                rows="4" required>'
                                . $description .
                            '</textarea>
                        </div>

                        <div class="service-form-row">
                            <div class="service-field">
                                <label for="edit-price-' . $id . '">
                                    Price (JOD)
                                </label>

                                <input type="number"
                                       id="edit-price-' . $id . '"
                                       name="price"
                                       value="' . $price . '"
                                       min="0"
                                       max="9999999.999"
                                       step="0.001" required>
                            </div>

                            <div class="service-field">
                                <label for="edit-duration-' . $id . '">
                                    Minutes
                                </label>

                                <input type="number"
                                       id="edit-duration-' . $id . '"
                                       name="duration_minutes"
                                       value="' . $duration . '"
                                       min="1"
                                       max="65535"
                                       step="1" required>
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">
                            Save changes
                        </button>
                    </form>
                </details>

                <form action="services.php" method="post">
                        <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden"
                           name="action" value="' . $action . '">

                    <input type="hidden"
                           name="service_id" value="' . $id . '">

                    <button class="service-action-button" type="submit">
                        ' . $buttonText . '
                    </button>
                </form>
            </div>

        </article>';
    }
}

public function updateService($service_id, $name, $description, $price, $duration): bool
    {
        $this->validateService($name, $description, $price, $duration);
        return $this->mutate(function () use ($service_id, $name, $description, $price, $duration): bool {
            if (!$this->ownsService($service_id)) return false;
            $stmt = $this->db->prepare('UPDATE services SET name=?,description=?,price=?,duration_minutes=? WHERE id=? AND provider_id=?');
            return $stmt->execute([trim($name),trim($description),$price,$duration,$service_id,$this->provider_id]);
        });
    }
    public function changeStatus($service_id, $status): bool
    {
        if (!in_array($status, [0,1,'0','1'], true)) throw new RuntimeException('Invalid status.');
        return $this->mutate(function () use ($service_id, $status): bool {
            if (!$this->ownsService($service_id)) return false;
            $stmt = $this->db->prepare('UPDATE services SET is_active=? WHERE id=? AND provider_id=?');
            return $stmt->execute([$status,$service_id,$this->provider_id]);
        });
    }
}
