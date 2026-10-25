<?php

date_default_timezone_set('Asia/Amman');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_name('ShineRosterSession');
    session_start();
}

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/SiteData.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Provider.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Appointment.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/ServiceCatalog.php';
require_once __DIR__ . '/../classes/ServiceDetails.php';
require_once __DIR__ . '/../classes/ProviderDirectory.php';
require_once __DIR__ . '/../classes/ProviderDetails.php';
require_once __DIR__ . '/../classes/BookingCheckout.php';
require_once __DIR__ . '/../classes/CustomerBookings.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/Contact.php';

function inputValue($source, $key)
{
    if (isset($source[$key]) && is_string($source[$key]) && strpos($source[$key], "\0") === false) {
        return $source[$key];
    }
    return '';
}

function inputText($source, $key, $maximum = null)
{
    $text = trim(inputValue($source, $key));
    if ($maximum !== null) {
        $text = mb_substr($text, 0, $maximum, 'UTF-8');
    }
    return $text;
}

function positiveId($source, $key)
{
    $value = inputValue($source, $key);
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        return 0;
    }
    return (int) $id;
}

function escapeHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function validPhone($phone)
{
    if (strlen($phone) < 7 || strlen($phone) > 20) {
        return false;
    }
    $digits = 0;
    for ($i = 0; $i < strlen($phone); $i++) {
        $character = $phone[$i];
        if ($character >= '0' && $character <= '9') {
            $digits++;
        } elseif ($character === '+' && $i === 0) {
            continue;
        } elseif (
            $character !== ' ' &&
            $character !== '(' &&
            $character !== ')' &&
            $character !== '-'
        ) {
            return false;
        }
    }
    return $digits >= 7;
}

function validPrice($price)
{
    $parts = explode('.', $price);
    if (count($parts) > 2 || !ctype_digit($parts[0]) || strlen($parts[0]) > 7) {
        return false;
    }
    if (isset($parts[1]) && (!ctype_digit($parts[1]) || strlen($parts[1]) > 3)) {
        return false;
    }
    return true;
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
