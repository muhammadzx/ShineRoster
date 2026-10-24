<?php

class ProviderDirectory
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
             WHERE u.role = 'provider'
               AND u.is_active = 1
               AND TRIM(p.city) <> ''
               AND EXISTS (
                   SELECT 1
                   FROM services s
                   WHERE s.provider_id = p.id
                     AND s.is_active = 1
               )
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
        float $minimumRating = 0,
        string $sort = 'recommended',
        int $page = 1
    ): array {
        $conditions = [
            "u.role = 'provider'",
            'u.is_active = 1',
            "TRIM(p.city) <> ''",
            "EXISTS (
                SELECT 1
                FROM services active_service
                WHERE active_service.provider_id = p.id
                  AND active_service.is_active = 1
            )"
        ];

        $params = [];

        if ($search !== '') {
            $conditions[] = "(
                u.full_name LIKE ?
                OR p.bio LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM services search_service
                    WHERE search_service.provider_id = p.id
                      AND search_service.is_active = 1
                      AND search_service.name LIKE ?
                )
                OR EXISTS (
                    SELECT 1
                    FROM provider_skills search_ps
                    JOIN skills search_skill
                        ON search_skill.id = search_ps.skill_id
                    WHERE search_ps.provider_id = p.id
                      AND search_skill.name LIKE ?
                )
            )";

            $term = '%' . $search . '%';

            $params[] = $term;
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
                    FROM provider_skills selected_skill
                    WHERE selected_skill.provider_id = p.id
                      AND selected_skill.skill_id = ?
                )
            ";

            $params[] = $skillId;
        }

        if ($minimumRating > 0) {
            $conditions[] = 'r.average_rating >= ?';
            $params[] = $minimumRating;
        }

        $where = implode(' AND ', $conditions);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM provider_profiles p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN provider_rating_summary r
                ON r.provider_id = p.id
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
                 p.id DESC',

            'rating' => 'COALESCE(r.average_rating, 0) DESC,
                 COALESCE(r.review_count, 0) DESC,
                 p.id DESC',

            'price_low' => 'service_summary.minimum_price ASC, p.id DESC',

            'price_high' => 'service_summary.minimum_price DESC, p.id DESC',

            'newest' => 'p.id DESC'
        ];

        $orderBy = $sortOptions[$sort] ?? $sortOptions['recommended'];

        $sql = "
            SELECT
                p.id,
                p.city,
                p.bio,

                u.full_name,

                r.average_rating,
                COALESCE(r.review_count, 0) AS review_count,

                service_summary.service_count,
                service_summary.minimum_price,

                COALESCE(a.available_count, 0) AS available_count,
                a.next_appointment

            FROM provider_profiles p

            JOIN users u
                ON u.id = p.user_id

            JOIN (
                SELECT
                    provider_id,
                    COUNT(*) AS service_count,
                    MIN(price) AS minimum_price
                FROM services
                WHERE is_active = 1
                GROUP BY provider_id
            ) service_summary
                ON service_summary.provider_id = p.id

            LEFT JOIN provider_rating_summary r
                ON r.provider_id = p.id

            LEFT JOIN (
                SELECT
                    provider_id,
                    COUNT(*) AS available_count,
                    MIN(starts_at) AS next_appointment
                FROM available_appointment_slots
                GROUP BY provider_id
            ) a ON a.provider_id = p.id

            WHERE $where

            ORDER BY $orderBy

            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $providerIds = array_map(
            static fn(array $provider): int => (int) $provider['id'],
            $providers
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

        foreach ($providers as &$provider) {
            $provider['skills'] = $providerSkills[(int) $provider['id']] ?? [];
        }

        unset($provider);

        return [
            'providers' => $providers,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage
        ];
    }
}
