<?php
require_once __DIR__ . '/../includes/init.php';
$currentUser = $auth->requireRole('admin', '../login.php');
$prefix = '../';
require __DIR__ . '/../includes/unavailable.php';
