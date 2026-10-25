<?php
require_once __DIR__ . '/includes/init.php';

$filters = ['search' => $search];
$form = ['id' => 0, 'name' => ''];
$editId = positiveId($_GET, 'edit');
if ($editId) {
    $found = false;
    foreach ($admin->getSkills() as $skill) {
        if ($skill['id'] == $editId) {
            $form = $skill;
            $found = true;
        }
    }
    if (!$found) {
        $error = 'Skill not found.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = positiveId($_POST, 'id');
    $action = inputText($_POST, 'action');
    $saved = false;
    if ($action === 'save') {
        $form = ['id' => $id, 'name' => inputText($_POST, 'name')];
        $saved = $admin->saveSkill($id, $form['name']);
    } elseif ($action === 'delete') {
        $saved = $admin->deleteSkill($id);
    } else {
        $error = 'Invalid action.';
    }
    if ($saved) {
        adminSaved('skills.php', $filters);
    }
    if ($error === '') {
        $error = $admin->getError();
    }
}
$rows = [];
foreach ($admin->getSkills() as $skill) {
    if ($search === '' || mb_stripos($skill['name'], $search) !== false) {
        $rows[] = $skill;
    }
}
$data = new SiteData($db);
$result = $data->page($rows, $page, 12, 'rows');

$pageTitle = 'Skills';
$pageDescription = 'Maintain the skills that providers can select for their public profiles.';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-panel"><div class="admin-section-heading"><h2><?php if ($form['id']) {
    echo 'Edit skill';
} else {
    echo 'Add a skill';
} ?></h2><?php if (
    $form['id']
): ?><a href="skills.php">Cancel edit</a><?php endif; ?></div><form method="post" class="admin-inline-form"><input type="hidden" name="id" value="<?= (int) $form[
    'id'
] ?>"><div class="admin-field"><label for="skill-name">Skill name</label><input id="skill-name" name="name" required maxlength="100" value="<?= escapeHtml(
    $form['name']
) ?>"></div><button class="admin-button" name="action" value="save">Save skill</button></form></section>
<form method="get" class="admin-filters"><div class="admin-field"><label for="search">Search</label><input type="search" id="search" name="search" value="<?= escapeHtml(
    $search
) ?>" placeholder="Search records" maxlength="120"></div><button class="admin-button" type="submit">Search</button><a class="admin-reset" href="<?= escapeHtml(
    $adminPage
) ?>">Reset</a></form><div class="admin-list"><?php
if (!$result['rows']): ?><p class="admin-empty">No records match your filters.</p><?php endif;
foreach (
    $result['rows']
    as $skill
): ?><article class="admin-record"><div class="admin-record-main"><span class="admin-record-id">Skill #<?= (int) $skill[
    'id'
] ?></span><h2><?= escapeHtml(
    $skill['name']
) ?></h2></div><div class="admin-actions"><a class="admin-button admin-button-light" href="skills.php?edit=<?= (int) $skill[
    'id'
] ?>">Edit</a><details class="admin-confirm"><summary>Delete</summary><form method="post"><input type="hidden" name="id" value="<?= (int) $skill[
    'id'
] ?>"><p>Only skills that are not assigned to a provider can be deleted.</p><button class="admin-button admin-button-danger" name="action" value="delete">Confirm delete</button></form></details></div></article><?php endforeach;
?></div><?php require __DIR__ . '/includes/pagination.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
