<?php
class ProviderDirectory
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getCities()
    {
        $data = new SiteData($this->db);
        $cities = [];
        foreach ($data->providers as $row) {
            $provider = $data->providerCard($row['id']);
            if (
                $provider &&
                $provider['service_count'] > 0 &&
                !in_array($provider['city'], $cities)
            ) {
                $cities[] = $provider['city'];
            }
        }
        for ($i = 0; $i < count($cities); $i++) {
            for ($j = 0; $j < count($cities) - 1; $j++) {
                if ($cities[$j] > $cities[$j + 1]) {
                    $temp = $cities[$j];
                    $cities[$j] = $cities[$j + 1];
                    $cities[$j + 1] = $temp;
                }
            }
        }
        return $cities;
    }
    public function getSkills()
    {
        $stmt = $this->db->prepare('SELECT * FROM skills ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(
        $search = '',
        $city = '',
        $skillId = 0,
        $minimumRating = 0,
        $sort = 'recommended',
        $page = 1
    ) {
        $data = new SiteData($this->db);
        $rows = [];
        foreach ($data->providers as $row) {
            $card = $data->providerCard($row['id']);
            if (!$card || $card['service_count'] == 0) {
                continue;
            }
            if ($city !== '' && mb_strtolower($card['city']) !== mb_strtolower($city)) {
                continue;
            }
            if ($card['average_rating'] < $minimumRating) {
                continue;
            }
            if ($skillId > 0) {
                $found = false;
                foreach ($data->getSkills($card['id']) as $skill) {
                    if ($skill['id'] == $skillId) {
                        $found = true;
                    }
                }
                if (!$found) {
                    continue;
                }
            }
            $text = $card['full_name'] . ' ' . $card['bio'];
            foreach ($card['skills'] as $name) {
                $text .= ' ' . $name;
            }
            foreach ($data->services as $service) {
                if ($service['provider_id'] == $card['id'] && $service['is_active']) {
                    $text .= ' ' . $service['name'];
                }
            }
            if ($search !== '' && mb_stripos($text, $search) === false) {
                continue;
            }
            $rows[] = $card;
        }
        $rows = $data->sortCards($rows, $sort, 'minimum_price');
        return $data->page($rows, $page, 9, 'providers');
    }
}
