<?php
// Local development only: php -S 127.0.0.1:8000 router.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~(?:^|/)(?:classes|includes|database|tests|verification|\.[^/]*)(?:/|$)|\.(?:sql|zip|log|ini|md|bak|env|sh|py)$|(?:^|/)router\.php$~i', $path) || str_contains($path, "\0")) {
    http_response_code(403); exit('Access denied.');
}
$file = realpath(__DIR__ . $path);
if ($file !== false && $file !== __DIR__ && !str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
    http_response_code(403); exit('Access denied.');
}
return false;
