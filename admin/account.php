<?php
require_once __DIR__ . '/includes/init.php';

$form = [
    'full_name' => $currentUser['full_name'],
    'email' => $currentUser['email'],
    'phone' => $currentUser['phone']
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'full_name' => inputText($_POST, 'full_name'),
        'email' => inputText($_POST, 'email'),
        'phone' => inputText($_POST, 'phone'),
        'current_password' => inputValue($_POST, 'current_password'),
        'password' => inputValue($_POST, 'password'),
        'confirm_password' => inputValue($_POST, 'confirm_password')
    ];
    if ($admin->saveOwner($form)) {
        adminSaved('account.php', []);
    }
    $error = $admin->getError();
}

$pageTitle = 'My account';
$pageDescription =
    'Update the site owner details and password. Your administrator role stays protected.';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-panel admin-account-panel"><div class="admin-section-heading"><h2>Owner details</h2><span class="admin-badge">Administrator</span></div><form method="post" class="admin-form">
<div class="admin-field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required maxlength="100" value="<?= escapeHtml(
    $form['full_name']
) ?>" autocomplete="name"></div>
<div class="admin-field"><label for="email">Email</label><input type="email" id="email" name="email" required maxlength="190" value="<?= escapeHtml(
    $form['email']
) ?>" autocomplete="email"></div>
<div class="admin-field"><label for="phone">Phone</label><input type="tel" id="phone" name="phone" required maxlength="20" value="<?= escapeHtml(
    $form['phone']
) ?>" autocomplete="tel"></div>
<div class="admin-field"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" required autocomplete="current-password"><small>Required to save any account changes.</small></div>
<div class="admin-field"><label for="password">New password <span>(optional)</span></label><input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password"><small>Leave blank to keep your current password.</small></div>
<div class="admin-field"><label for="confirm_password">Confirm new password</label><input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password"></div>
<div class="admin-form-bottom"><button class="admin-button">Save my account</button></div></form></section>

<?php require __DIR__ . '/includes/footer.php'; ?>
