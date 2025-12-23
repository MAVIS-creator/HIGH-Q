<?php
$pageTitle = 'Slug Management';
require_once __DIR__ . '/../includes/auth.php';
requirePermission('manage_settings');
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .page-frame {
        width: 100%;
        height: calc(100vh - 70px);
        border: none;
    }
</style>

<iframe src="../modules/slugs.php" class="page-frame" title="Slug Management"></iframe>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
