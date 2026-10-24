<?php

class ProviderDetails
{
    public function __construct(private PDO $db) {}

    public function getProvider(int $providerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                p.id,
                p.city,
                p.bio,

                u.full_name,

                r.average_rating,
                COALESCE(r.review_count, 0) AS review_count,

                COALESCE(sc.service_count, 0) AS service_count,
                sc.minimum_price,

                COALESCE(av.available_count, 0) AS available_count,
                av.next_appointment,

                (
                    SELECT COUNT(*)
                    FROM bookings b

                    JOIN appointment_slots a
                        ON a.id = b.slot_id

                    JOIN services s
                        ON s.id = a.service_id

                    WHERE s.provider_id = p.id
                      AND b.status = 'completed'
                ) AS completed_count

             FROM provider_profiles p

             JOIN users u
                ON u.id = p.user_id

             LEFT JOIN provider_rating_summary r
                ON r.provider_id = p.id

             LEFT JOIN (
                 SELECT
                    provider_id,
                    COUNT(*) AS service_count,
                    MIN(price) AS minimum_price

                 FROM services
                 WHERE is_active = 1
                 GROUP BY provider_id
             ) sc ON sc.provider_id = p.id

             LEFT JOIN (
                 SELECT
                    provider_id,
                    COUNT(*) AS available_count,
                    MIN(starts_at) AS next_appointment

                 FROM available_appointment_slots
                 GROUP BY provider_id
             ) av ON av.provider_id = p.id

             WHERE p.id = ?
               AND u.role = 'provider'
               AND u.is_active = 1
               AND TRIM(p.city) <> ''

             LIMIT 1"
        );

        $stmt->execute([$providerId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getSkills(int $providerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT sk.id, sk.name

             FROM provider_skills ps

             JOIN skills sk
                ON sk.id = ps.skill_id

             WHERE ps.provider_id = ?

             ORDER BY sk.name"
        );

        $stmt->execute([$providerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServices(int $providerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                s.id,
                s.name,
                s.description,
                s.price,
                s.duration_minutes,

                COALESCE(av.available_count, 0) AS available_count

             FROM services s

             LEFT JOIN (
                 SELECT
                    service_id,
                    COUNT(*) AS available_count

                 FROM available_appointment_slots

                 GROUP BY service_id
             ) av ON av.service_id = s.id

             WHERE s.provider_id = ?
               AND s.is_active = 1

             ORDER BY
                (COALESCE(av.available_count, 0) > 0) DESC,
                s.id DESC"
        );

        $stmt->execute([$providerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviews(int $providerId, int $page = 1): array
    {
        $from = "
            FROM reviews r

            JOIN bookings b
                ON b.id = r.booking_id

            JOIN appointment_slots a
                ON a.id = b.slot_id

            JOIN services s
                ON s.id = a.service_id

            JOIN users u
                ON u.id = b.user_id

            WHERE s.provider_id = ?
              AND b.status = 'completed'
              AND r.is_visible = 1
        ";

        $stmt = $this->db->prepare("SELECT COUNT(*) $from");

        $stmt->execute([$providerId]);

        $total = (int) $stmt->fetchColumn();
        $perPage = 6;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT
                r.id,
                r.rating,
                r.comment,
                r.created_at,

                u.full_name AS customer_name,
                s.name AS service_name

             $from

             ORDER BY r.created_at DESC, r.id DESC

             LIMIT $perPage OFFSET $offset"
        );

        $stmt->execute([$providerId]);

        return [
            'reviews' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ];
    }
}
