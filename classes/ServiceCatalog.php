<?php

class ServiceCatalog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getCities(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT p.city
             FROM provider_profiles p
             JOIN users u ON u.id = p.user_id
             JOIN services s ON s.provider_id = p.id
             WHERE u.role = 'provider'
               AND u.is_active = 1
               AND s.is_active = 1
               AND TRIM(p.city) <> ''
             ORDER BY p.city"
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSkills(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name
             FROM skills
             ORDER BY name"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(
        string $search = '',
        string $city = '',
        int $skillId = 0,
        string $sort = 'recommended',
        int $page = 1
    ): array {
        $conditions = [
            "u.role = 'provider'",
            'u.is_active = 1',
            's.is_active = 1',
            "TRIM(p.city) <> ''"
        ];

        $params = [];

        if ($search !== '') {
            $conditions[] = "(
                s.name LIKE ?
                OR s.description LIKE ?
                OR u.full_name LIKE ?
            )";

            $term = '%' . $search . '%';

            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ($city !== '') {
            $conditions[] = 'p.city = ?';
            $params[] = $city;
        }

        if ($skillId > 0) {
            $conditions[] = "
                EXISTS (
                    SELECT 1
                    FROM provider_skills ps
                    WHERE ps.provider_id = p.id
                      AND ps.skill_id = ?
                )
            ";

            $params[] = $skillId;
        }

        $where = implode(' AND ', $conditions);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM services s
             JOIN provider_profiles p ON p.id = s.provider_id
             JOIN users u ON u.id = p.user_id
             WHERE $where"
        );

        $countStmt->execute($params);

        $total = (int) $countStmt->fetchColumn();
        $perPage = 9;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sortOptions = [
            'recommended' => '(COALESCE(a.available_count, 0) > 0) DESC,
                 COALESCE(r.average_rating, 0) DESC,
                 COALESCE(r.review_count, 0) DESC,
                 s.id DESC',

            'rating' => 'COALESCE(r.average_rating, 0) DESC,
                 COALESCE(r.review_count, 0) DESC,
                 s.id DESC',

            'price_low' => 's.price ASC, s.id DESC',
            'price_high' => 's.price DESC, s.id DESC',
            'newest' => 's.id DESC'
        ];

        $orderBy = $sortOptions[$sort] ?? $sortOptions['recommended'];

        $sql = "
            SELECT
                s.id,
                s.name,
                s.description,
                s.price,
                s.duration_minutes,

                p.id AS provider_id,
                p.city,

                u.full_name AS provider_name,

                r.average_rating,
                COALESCE(r.review_count, 0) AS review_count,

                COALESCE(a.available_count, 0) AS available_count

            FROM services s

            JOIN provider_profiles p
                ON p.id = s.provider_id

            JOIN users u
                ON u.id = p.user_id

            LEFT JOIN provider_rating_summary r
                ON r.provider_id = p.id

            LEFT JOIN (
                SELECT
                    service_id,
                    COUNT(*) AS available_count
                FROM available_appointment_slots
                GROUP BY service_id
            ) a ON a.service_id = s.id

            WHERE $where

            ORDER BY $orderBy

            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $providerIds = array_values(
            array_unique(
                array_map(
                    static fn(array $service): int => (int) $service['provider_id'],
                    $services
                )
            )
        );

        $providerSkills = [];

        if ($providerIds) {
            $placeholders = implode(',', array_fill(0, count($providerIds), '?'));

            $skillStmt = $this->db->prepare(
                "SELECT ps.provider_id, sk.name
                 FROM provider_skills ps
                 JOIN skills sk ON sk.id = ps.skill_id
                 WHERE ps.provider_id IN ($placeholders)
                 ORDER BY sk.name"
            );

            $skillStmt->execute($providerIds);

            foreach ($skillStmt->fetchAll(PDO::FETCH_ASSOC) as $skill) {
                $providerSkills[(int) $skill['provider_id']][] = $skill['name'];
            }
        }

        foreach ($services as &$service) {
            $service['skills'] = $providerSkills[(int) $service['provider_id']] ?? [];
        }

        unset($service);

        return [
            'services' => $services,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage
        ];
    }
}
