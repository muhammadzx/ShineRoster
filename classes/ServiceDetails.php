<?php
class ServiceDetails
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getService($serviceId)
    {
        $data = new SiteData($this->db);
        return $data->serviceCard($serviceId);
    }
    public function getProviderSkills($providerId)
    {
        $data = new SiteData($this->db);
        return $data->getSkills($providerId);
    }
    public function getAvailableAppointments($serviceId)
    {
        $data = new SiteData($this->db);
        $rows = $data->availableSlots($serviceId);
        $result = [];
        for ($i = 0; $i < count($rows) && $i < 30; $i++) {
            $result[] = $rows[$i];
        }
        return $result;
    }
    public function getReviews($serviceId)
    {
        $data = new SiteData($this->db);
        $service = $data->getService($serviceId);
        if (!$service) {
            return [];
        }
        $rows = $data->getReviews($service['provider_id'], $serviceId);
        $result = [];
        for ($i = 0; $i < count($rows) && $i < 5; $i++) {
            $result[] = $rows[$i];
        }
        return $result;
    }
}
