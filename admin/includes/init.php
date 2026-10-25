<?php
require_once __DIR__ . '/../../includes/init.php';
$currentUser = $auth->requireRole('admin', '../login.php');
require_once __DIR__ . '/../../classes/Admin.php';
$admin = new Admin($db, $currentUser['id']);
$adminPage = basename($_SERVER['SCRIPT_NAME']);
$error = '';
$success = inputText($_SESSION, 'admin_success');
unset($_SESSION['admin_success']);
$search = inputText($_GET, 'search', 120);
$status = inputText($_GET, 'status', 30);
$page = positiveId($_GET, 'page');
if ($page < 1) {
    $page = 1;
}

function adminPageUrl($targetPage, $file, $filters)
{
    $query = [];
    foreach ($filters as $key => $value) {
        if ($value !== '' && $value !== 0) {
            $query[$key] = $value;
        }
    }
    $query['page'] = $targetPage;
    return $file . '?' . http_build_query($query);
}

function adminSaved($file, $filters)
{
    $_SESSION['admin_success'] = 'Your changes have been saved.';
    header('Location: ' . adminPageUrl(1, $file, $filters), true, 303);
    exit();
}
