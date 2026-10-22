<?php
require_once __DIR__ . "/../classes/Database.php";
require_once __DIR__ . "/../classes/Auth.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$Database = new Database();
$db=$Database->gitConnection();
?>