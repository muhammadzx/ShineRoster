<?php
class Database
{
    private PDO $db;
    public function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'shineroster';
        $port = getenv('DB_PORT') ?: '3306';
        if (
            !preg_match('/^[a-zA-Z0-9_.-]+$/D', $host) ||
            !preg_match('/^[a-zA-Z0-9_]+$/D', $name) ||
            !ctype_digit($port)
        ) {
            throw new RuntimeException('Invalid database configuration.');
        }
        $this->db = new PDO(
            "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            getenv('DB_USER') !== false ? getenv('DB_USER') : 'root',
            getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        $this->db->exec("SET time_zone = '+03:00'");
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }
}
