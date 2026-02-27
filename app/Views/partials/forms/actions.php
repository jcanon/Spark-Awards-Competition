<?php

declare(strict_types=1);

$submitLabel = (string)($submitLabel ?? 'Save');
$submitClass = (string)($submitClass ?? 'btn btn-primary');
$backLabel = (string)($backLabel ?? 'Back');
$backUrl = (string)($backUrl ?? '#');
$backClass = (string)($backClass ?? 'btn btn-secondary');
?>
<button class="<?= esc($submitClass) ?>" type="submit"><?= esc($submitLabel) ?></button>
<a class="<?= esc($backClass) ?>" href="<?= esc($backUrl) ?>"><?= esc($backLabel) ?></a>
