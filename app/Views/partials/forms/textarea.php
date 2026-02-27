<?php

declare(strict_types=1);

$colClass = (string)($colClass ?? 'col-md-12 form-group');
$label = (string)($label ?? '');
$name = (string)($name ?? '');
$id = (string)($id ?? $name);
$value = (string)($value ?? '');
$required = !empty($required);
$rows = (int)($rows ?? 4);
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
    <textarea
        id="<?= esc($id, 'attr') ?>"
        class="form-control"
        name="<?= esc($name, 'attr') ?>"
        rows="<?= $rows ?>"
        <?= $required ? 'required' : '' ?>
        <?= $attrHtml ?>
    ><?= esc($value) ?></textarea>
    <?php if ($invalid !== ''): ?><div class="invalid-feedback"><?= esc($invalid) ?></div><?php endif; ?>
</div>
