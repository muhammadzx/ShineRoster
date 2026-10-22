<?php
class Database {
    private $db;
    private $username = "root";
    private $password = "";

    public function __construct() {
        $this->db = new PDO(
            'mysql:host=localhost;dbname=shineroster',
            $this->username,
            $this->password
        );
    }

    public function gitConnection(){
        return $this->db;
    }
}