<?php

declare(strict_types=1);

$colClass = (string)($colClass ?? 'col-md-12 form-group');
$label = (string)($label ?? '');
$name = (string)($name ?? '');
$id = (string)($id ?? $name);
$type = (string)($type ?? 'text');
$value = (string)($value ?? '');
$required = !empty($required);
$attrs = is_array($attrs ?? null) ? $attrs : [];
$help = (string)($help ?? '');
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
    <input
        id="<?= esc($id, 'attr') ?>"
        class="form-control"
        type="<?= esc($type, 'attr') ?>"
        name="<?= esc($name, 'attr') ?>"
        value="<?= esc($value) ?>"
        <?= $required ? 'required' : '' ?>
        <?= $attrHtml ?>
    >
    <?php if ($help !== ''): ?><small class="text-muted"><?= esc($help) ?></small><?php endif; ?>
    <?php if ($invalid !== ''): ?><div class="invalid-feedback"><?= esc($invalid) ?></div><?php endif; ?>
</div>
