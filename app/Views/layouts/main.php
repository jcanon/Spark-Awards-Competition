<?php

declare(strict_types=1);

helper(['url', 'view_normalize', 'datetime']);

$normalizedSettings = view_settings($settings ?? [], $siteTitle ?? null);
$normalizedUser = view_user(isset($user) && $user instanceof \App\Entities\Accounts\User ? $user : null);

$isLoggedIn = !empty($normalizedUser['id']);
?>

<?php if ($isLoggedIn): ?>
    <?= view('partials/header-private', ['settings' => $normalizedSettings]) ?>
    <?= view('partials/nav-private', ['user' => $normalizedUser]) ?>
    <div class="mt-4 mb-4">
        <?= $this->renderSection('content') ?>
    </div>
<?php else: ?>
    <?= view('partials/header-public', ['settings' => $normalizedSettings]) ?>
    <?= view('partials/nav-public') ?>
    <?= $this->renderSection('content') ?>
<?php endif; ?>

<?php if ($isLoggedIn): ?>
    <?= view('partials/footer-private') ?>
<?php else: ?>
    <?= view('partials/footer-public') ?>
<?php endif; ?>
