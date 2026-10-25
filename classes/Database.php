<?php
class Database
{
    private $db;

    public function __construct()
    {
        // Edit these values to match your local database.
        $host = '127.0.0.1';
        $name = 'shineroster';
        $user = 'root';
        $password = '';

        $this->db = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function getConnection()
    {
        return $this->db;
    }
}
