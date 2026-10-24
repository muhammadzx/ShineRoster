<?php

class ServiceDetails
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getService(int $serviceId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                s.id,
                s.name,
                s.description,
                s.price,
                s.duration_minutes,

                p.id AS provider_id,
                p.city,
                p.bio,

                u.full_name AS provider_name,

                r.average_rating,
                COALESCE(r.review_count, 0) AS review_count

             FROM services s

             JOIN provider_profiles p
                ON p.id = s.provider_id

             JOIN users u
                ON u.id = p.user_id

             LEFT JOIN provider_rating_summary r
                ON r.provider_id = p.id

             WHERE s.id = ?
               AND s.is_active = 1
               AND u.is_active = 1
               AND u.role = 'provider'
               AND TRIM(p.city) <> ''

             LIMIT 1"
        );

        $stmt->execute([$serviceId]);

        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        return $service ?: null;
    }

    public function getProviderSkills(int $providerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT sk.id, sk.name
             FROM provider_skills ps
             JOIN skills sk ON sk.id = ps.skill_id
             WHERE ps.provider_id = ?
             ORDER BY sk.name"
        );

        $stmt->execute([$providerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableAppointments(int $serviceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT slot_id, starts_at, ends_at
             FROM available_appointment_slots
             WHERE service_id = ?
             ORDER BY starts_at, slot_id
             LIMIT 30"
        );

        $stmt->execute([$serviceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviews(int $serviceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                r.id,
                r.rating,
                r.comment,
                r.created_at,
                u.full_name AS customer_name

             FROM reviews r

             JOIN bookings b
                ON b.id = r.booking_id

             JOIN appointment_slots a
                ON a.id = b.slot_id

             JOIN users u
                ON u.id = b.user_id

             WHERE a.service_id = ?
               AND b.status = 'completed'
               AND r.is_visible = 1

             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 5"
        );

        $stmt->execute([$serviceId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
