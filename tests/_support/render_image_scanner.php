<?php
// CLI-only view fixture for isolated browser checks; no application database required.
if (PHP_SAPI !== 'cli') { exit(1); }
function esc($value, $context = null) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function site_url($path) { return 'http://scanner.test/' . $path; }
function base_url($path) { return site_url($path); }
function csrf_token() { return 'csrf_test_name'; }
function csrf_hash() { return 'test-token'; }
(new class {
    public function extend($name) { return ''; }
    public function section($name) { return ''; }
    public function endSection() { return ''; }
    public function render() {
        $ready = true;
        $state = ['phase' => 'idle'];
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/css/sb-admin-2.css"></head><body class="bg-light"><main class="py-4">';
        require __DIR__ . '/../../app/Views/admin/system_tools/broken_image_scanner.php';
        echo '</main></body></html>';
    }
})->render();

