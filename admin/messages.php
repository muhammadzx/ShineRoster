<?php
require_once __DIR__ . '/includes/init.php';

$subject = inputText($_GET, 'subject');
$filters = ['search' => $search, 'subject' => $subject];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        inputText($_POST, 'action') === 'delete' &&
        $admin->deleteMessage(positiveId($_POST, 'id'))
    ) {
        adminSaved('messages.php', $filters);
    }
    $error = $admin->getError();
    if ($error === '') {
        $error = 'Invalid action.';
    }
}
$result = $admin->getMessages($search, $subject, $page);

$pageTitle = 'Messages';
$pageDescription = 'Read contact requests and open your email app when you want to reply.';
require __DIR__ . '/includes/header.php';
?>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-subject">Subject</label><select id="filter-subject" name="subject"><option value="">All subject</option><option value="booking" <?php if (
    $subject === 'booking'
) {
    echo 'selected';
} ?>>Booking</option><option value="account" <?php if ($subject === 'account') {
    echo 'selected';
} ?>>Account</option><option value="provider" <?php if ($subject === 'provider') {
    echo 'selected';
} ?>>Provider</option><option value="feedback" <?php if ($subject === 'feedback') {
    echo 'selected';
} ?>>Feedback</option><option value="other" <?php if ($subject === 'other') {
    echo 'selected';
} ?>>Other</option></select></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $message
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Message #<?= (int) $message[
    'id'
] ?></span><h2><?= escapeHtml($message['full_name']) ?></h2><p><?= escapeHtml(
    $message['email']
) ?> · <?= escapeHtml(
     $message['phone']
 ) ?></p><div class="admin-tags"><span class="admin-badge"><?= escapeHtml(
    ucfirst($message['subject'])
) ?></span><span class="admin-meta"><?= escapeHtml(
    date('d M Y, g:i A', strtotime($message['created_at']))
) ?></span></div><p class="admin-preserve admin-message-text"><?= escapeHtml(
    $message['message']
) ?></p></div><div class="admin-actions"><a class="admin-button admin-button-light" href="mailto:<?= escapeHtml(
    $message['email']
) ?>">Reply by email</a><details class="admin-confirm"><summary>Delete</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $message[
    'id'
] ?>"><p>Permanently remove this contact message.</p><button class="admin-button admin-button-danger" name="action" value="delete">Confirm delete</button></form></details></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
