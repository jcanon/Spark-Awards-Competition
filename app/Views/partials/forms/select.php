<?php

declare(strict_types=1);

$colClass = (string)($colClass ?? 'col-md-12 form-group');
$label = (string)($label ?? '');
$name = (string)($name ?? '');
$id = (string)($id ?? $name);
$options = is_array($options ?? null) ? $options : [];
$value = $value ?? null;
$required = !empty($required);
$attrs = is_array($attrs ?? null) ? $attrs : [];
$invalid = (string)($invalid ?? '');

$attrParts = [];
foreach ($attrs as $attrName => $attrValue) {
    if ($attrValue === null || $attrValue === false) {
        continue;
    }
    if ($attrValue === true) {
        $attrParts[] = esc((string)$attrName, 'attr');
        continue;
    }
    $attrParts[] = esc((string)$attrName, 'attr') . '="' . esc((string)$attrValue, 'attr') . '"';
}
$attrHtml = implode(' ', $attrParts);
?>
<div class="<?= esc($colClass) ?>">
    <label for="<?= esc($id, 'attr') ?>">
        <?= esc($label) ?>
        <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
    </label>
    <select
        id="<?= esc($id, 'attr') ?>"
        class="form-control"
        name="<?= esc($name, 'attr') ?>"
        <?= $required ? 'required' : '' ?>
        <?= $attrHtml ?>
    >
        <?php foreach ($options as $option): ?>
            <?php
            $optValue = (string)($option['value'] ?? '');
            $optLabel = (string)($option['label'] ?? '');
            $selected = !empty($option['selected']) || ((string)$value !== '' && (string)$value === $optValue);
            ?>
            <option value="<?= esc($optValue, 'attr') ?>" <?= $selected ? 'selected' : '' ?>><?= esc($optLabel) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($invalid !== ''): ?><div class="invalid-feedback"><?= esc($invalid) ?></div><?php endif; ?>
</div>
