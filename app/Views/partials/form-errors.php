<?php

declare(strict_types=1);

$errors = $errors ?? session('errors');
$errors = is_array($errors) ? $errors : [];
$heading = isset($heading) && is_string($heading) ? trim($heading) : '';

if ($errors === []) {
    return;
}
?>
<div class="alert alert-danger">
    <?php if ($heading !== ''): ?>
        <strong><?= esc($heading) ?></strong>
    <?php endif; ?>
    <ul class="mb-0<?= $heading !== '' ? ' mt-2' : '' ?>">
        <?php foreach ($errors as $field => $message): ?>
            <?php
            $label = is_string($field) ? trim($field) : '';
            $value = is_array($message) ? implode(', ', array_map('strval', $message)) : (string)$message;
            ?>
            <li>
                <?php if ($label !== '' && !is_int($field)): ?>
                    <strong><?= esc($label) ?>:</strong>
                <?php endif; ?>
                <?= esc($value) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
