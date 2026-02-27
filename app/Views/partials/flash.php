<?php

declare(strict_types=1);

$success = session('success');
$error = session('error');
$showSuccess = !isset($showSuccess) || (bool)$showSuccess;
$showError = !isset($showError) || (bool)$showError;

if ($showSuccess && $success): ?>
    <div class="alert alert-success"><?= esc((string)$success) ?></div>
<?php endif; ?>
<?php if ($showError && $error): ?>
    <div class="alert alert-danger"><?= esc((string)$error) ?></div>
<?php endif; ?>
