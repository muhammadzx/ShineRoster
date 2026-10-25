<?php
http_response_code(503);
if (isset($prefix)) {
    $prefix = $prefix;
} else {
    $prefix = '';
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Coming soon | ShineRoster</title>
<link rel="icon" href="<?= $prefix ?>images/favicon.ico"><link rel="stylesheet" href="<?= $prefix ?>css/style.css">
<link rel="stylesheet" href="<?= $prefix ?>css/auth.css"></head>
<body class="logout-page"><main class="logout-card">
<a href="<?= $prefix ?>index.php"><img src="<?= $prefix ?>images/ShineRoster_Logo.png" alt="ShineRoster" width="220" height="74"></a>
<h1>This area is coming soon</h1><p>The administrator area is planned for a later stage.</p>
<a class="btn btn-primary" href="<?= $prefix ?>index.php">Back to home</a>
<?php if (
    isset($_SESSION['user_id'])
): ?><a href="<?= $prefix ?>logout.php">Log out</a><?php endif; ?>
</main></body></html>
