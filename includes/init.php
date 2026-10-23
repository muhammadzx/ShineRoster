<?php
// Every entry point loads this file before any HTML output.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Amman');
set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    http_response_code(500);
    echo 'The request could not be completed. Please try again later.';
});
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
    header('Cache-Control: no-store');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
}
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ShineRosterSession');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax', 'path' => '/',
    ]);
    session_start();
}
// Reject malformed/oversized input consistently before parsers or database code run.
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET','POST','HEAD'], true)) {
    header('Allow: GET, POST, HEAD'); http_response_code(405); exit('Method not allowed.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 1048576 || strlen($_SERVER['QUERY_STRING'] ?? '') > 8192) {
    http_response_code(413); exit('Request too large.');
}
$inputItems = 0;
$validateInput = static function ($value, int $depth = 0) use (&$validateInput, &$inputItems): bool {
    if (++$inputItems > 1500 || $depth > 8) return false;
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            if (!$validateInput((string)$key, $depth+1) || !$validateInput($child, $depth+1)) return false;
        }
        return true;
    }
    return is_string($value) && !str_contains($value, "\0") && mb_check_encoding($value, 'UTF-8');
};
if (!$validateInput($_GET) || !$validateInput($_POST)) {
    http_response_code(400); exit('Invalid request data.');
}
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Service.php';
function inputText(array $source, string $key): string
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
}
function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function validCsrf($token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
$Database = new Database();
$db = $Database->getConnection();
$auth = new Auth($db);
