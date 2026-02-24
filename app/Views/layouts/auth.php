<?php

declare(strict_types=1);

helper(['url', 'view_normalize']);

$normalizedSettings = view_settings($settings ?? [], $siteTitle ?? null);
?>

<?= view('partials/header-public', ['settings' => $normalizedSettings]) ?>
<?= view('partials/nav-public') ?>
<?= $this->renderSection('content') ?>
<?= view('partials/footer-public') ?>
