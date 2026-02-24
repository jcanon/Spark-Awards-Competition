<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$targetDir = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'authorizenet'
    . DIRECTORY_SEPARATOR . 'authorizenet' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
    . 'net' . DIRECTORY_SEPARATOR . 'authorize' . DIRECTORY_SEPARATOR . 'api'
    . DIRECTORY_SEPARATOR . 'contract' . DIRECTORY_SEPARATOR . 'v1';

if (!is_dir($targetDir)) {
    fwrite(STDOUT, "[authorizenet-patch] Skipped: target directory not found.\n");
    exit(0);
}

$pattern = '/if\s*\(get_parent_class\(\)\s*==\s*""\)\s*\{\s*return\s+\$values;\s*\}\s*else\s*\{\s*return\s+array_merge\(parent::jsonSerialize\(\),\s*\$values\);\s*\}/s';
$replacement = <<<'PHP'
$parentClass = get_parent_class(__CLASS__);
        if (!$parentClass || !method_exists($parentClass, 'jsonSerialize')){
            return $values;
        }
        $parentMethod = new \ReflectionMethod($parentClass, 'jsonSerialize');
        $parentMethod->setAccessible(true);
        $parentValues = $parentMethod->invoke($this);
        return is_array($parentValues) ? array_merge($parentValues, $values) : $values;
PHP;

$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetDir, FilesystemIterator::SKIP_DOTS)
);

$processed = 0;
$patched = 0;
$failed = 0;

foreach ($iter as $file) {
    /** @var SplFileInfo $file */
    if (strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $processed++;
    $path = $file->getPathname();
    $raw = @file_get_contents($path);
    if (!is_string($raw)) {
        $failed++;
        fwrite(STDERR, "[authorizenet-patch] Failed reading: {$path}\n");
        continue;
    }

    $updated = preg_replace($pattern, $replacement, $raw, -1, $count);
    if (!is_string($updated)) {
        $failed++;
        fwrite(STDERR, "[authorizenet-patch] Failed patching (regex): {$path}\n");
        continue;
    }

    // PHP 8.3 compatibility: normalize every jsonSerialize signature to include : mixed
    $updated2 = preg_replace(
        '/public function jsonSerialize\(\)\s*(?::\s*mixed\s*)?\{/',
        'public function jsonSerialize(): mixed {',
        $updated,
        -1,
        $count2
    );
    if (!is_string($updated2)) {
        $failed++;
        fwrite(STDERR, "[authorizenet-patch] Failed patching signatures (regex): {$path}\n");
        continue;
    }

    if ($count < 1 && $count2 < 1) {
        continue;
    }

    $ok = @file_put_contents($path, $updated2);
    if ($ok === false) {
        $failed++;
        fwrite(STDERR, "[authorizenet-patch] Failed writing: {$path}\n");
        continue;
    }

    $patched++;
}

fwrite(STDOUT, "[authorizenet-patch] Processed: {$processed}, Patched: {$patched}, Failed: {$failed}\n");
exit($failed > 0 ? 1 : 0);
