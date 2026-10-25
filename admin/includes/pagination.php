<nav class="admin-pagination" aria-label="Pagination">
    <span><?= (int) $result['total'] ?> results · Page <?= (int) $result[
     'page'
 ] ?> of <?= (int) $result['pages'] ?></span>
    <div>
        <?php if (
            $result['page'] > 1
        ): ?><a class="admin-button admin-button-light" href="<?= escapeHtml(
    adminPageUrl($result['page'] - 1, $adminPage, $filters)
) ?>">← Previous</a><?php endif; ?>
        <?php if (
            $result['page'] < $result['pages']
        ): ?><a class="admin-button admin-button-light" href="<?= escapeHtml(
    adminPageUrl($result['page'] + 1, $adminPage, $filters)
) ?>">Next →</a><?php endif; ?>
    </div>
</nav>
