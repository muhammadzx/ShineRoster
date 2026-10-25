<?php
require_once __DIR__ . '/includes/init.php';

$filters = ['search' => $search, 'status' => $status];
$editId = positiveId($_GET, 'edit');
$form = null;
if ($editId) {
    $form = $admin->getProviderForm($editId);
    if (!$form) {
        $error = 'Provider not found.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = positiveId($_POST, 'user_id');
    $form = $admin->getProviderForm($id);
    $skills = [];
    if (isset($_POST['skills']) && is_array($_POST['skills'])) {
        $skills = $_POST['skills'];
    }
    if (!$form) {
        $error = 'Provider not found.';
    } elseif (isset($_POST['skills']) && !is_array($_POST['skills'])) {
        $error = 'Invalid skill selection.';
    } else {
        $form['city'] = inputText($_POST, 'city');
        $form['bio'] = inputText($_POST, 'bio');
        $form['skills'] = $skills;
        if ($admin->saveProvider($id, $form)) {
            adminSaved('providers.php', $filters);
        }
        $error = $admin->getError();
    }
}
$allSkills = $admin->getSkills();
$result = $admin->getProviders($search, $status, $page);

$pageTitle = 'Providers';
$pageDescription =
    'Manage public profiles, cities and skills. Service details remain owned by each provider.';
require __DIR__ . '/includes/header.php';
?>
<?php if (
    $form
): ?><section class="admin-panel"><div class="admin-section-heading"><h2>Edit <?= escapeHtml(
    $form['full_name']
) ?></h2><a href="providers.php">Cancel edit</a></div><form method="post" class="admin-form"><input type="hidden" name="user_id" value="<?= (int) $form[
    'user_id'
] ?>"><div class="admin-field"><label for="city">City</label><input id="city" name="city" required maxlength="100" value="<?= escapeHtml(
    $form['city']
) ?>"></div><div class="admin-field admin-span-all"><label for="bio">Provider description</label><textarea id="bio" name="bio" rows="4" maxlength="3000"><?= escapeHtml(
    $form['bio']
) ?></textarea></div><fieldset class="admin-skill-options admin-span-all"><legend>Skills</legend><?php foreach (
    $allSkills
    as $skill
): ?><label><input type="checkbox" name="skills[]" value="<?= (int) $skill['id'] ?>" <?php if (
    in_array($skill['id'], $form['skills'])
) {
    echo 'checked';
} ?>><span><?= escapeHtml(
    $skill['name']
) ?></span></label><?php endforeach; ?></fieldset><div class="admin-form-bottom"><button class="admin-button">Save profile</button></div></form></section><?php endif; ?>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><div class="admin-field"><label for="filter-status">Status</label><select id="filter-status" name="status"><option value="">All status</option><option value="active" <?php if (
    $status === 'active'
) {
    echo 'selected';
} ?>>Active</option><option value="inactive" <?php if ($status === 'inactive') {
    echo 'selected';
} ?>>Inactive</option></select></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $provider
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Provider account #<?= (int) $provider[
    'user_id'
] ?></span><h2><?= escapeHtml($provider['full_name']) ?></h2><p><?= escapeHtml(
    $provider['email']
) ?></p><div class="admin-tags"><span class="admin-badge <?php if (!$provider['is_active']) {
    echo 'admin-badge-muted';
} ?>"><?php if ($provider['is_active']) {
    echo 'Active';
} else {
    echo 'Inactive';
} ?></span><span class="admin-badge admin-badge-muted"><?php if ($provider['city'] !== '') {
    echo escapeHtml($provider['city']);
} else {
    echo 'Profile incomplete';
} ?></span><span class="admin-badge admin-badge-muted"><?= (int) $provider[
    'service_count'
] ?> services</span></div><div class="admin-tags"><?php foreach (
     $provider['skills']
     as $skill
 ): ?><span class="admin-skill"><?= escapeHtml(
    $skill['name']
) ?></span><?php endforeach; ?></div></div><div class="admin-actions"><a class="admin-button admin-button-light" href="providers.php?edit=<?= (int) $provider[
    'user_id'
] ?>">Edit profile</a><a class="admin-button admin-button-light" href="users.php?search=<?= escapeHtml(
    rawurlencode($provider['email'])
) ?>">Manage account</a></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
