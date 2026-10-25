<?php
class ProviderDetails
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getProvider($providerId)
    {
        $data = new SiteData($this->db);
        return $data->providerCard($providerId);
    }
    public function getSkills($providerId)
    {
        $data = new SiteData($this->db);
        return $data->getSkills($providerId);
    }
    public function getServices($providerId)
    {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->services as $service) {
            if ($service['provider_id'] == $providerId) {
                $card = $data->serviceCard($service['id']);
                if ($card) {
                    $rows[] = $card;
                }
            }
        }
        return $data->sortCards($rows, 'recommended', 'price');
    }
    public function getReviews($providerId, $page = 1)
    {
        $data = new SiteData($this->db);
        return $data->page($data->getReviews($providerId), $page, 6, 'reviews');
    }
}
