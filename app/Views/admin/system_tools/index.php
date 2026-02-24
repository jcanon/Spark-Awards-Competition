<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$tools = [
    ['title' => 'Countries', 'icon' => 'globe-americas', 'desc' => 'Manage country reference records used across profiles and admin forms.', 'url' => site_url('admin/system-tools/countries')],
    ['title' => 'Diagnostics Bundle', 'icon' => 'file-archive', 'desc' => 'Generate a sanitized diagnostics zip for troubleshooting and support.', 'url' => site_url('admin/system-tools/diagnostics')],
    ['title' => 'Log Viewer', 'icon' => 'file-alt', 'desc' => 'Inspect recent application logs and purge old log files safely.', 'url' => site_url('admin/system-tools/logs')],
    ['title' => 'Media Cleaner', 'icon' => 'images', 'desc' => 'Find duplicate media and safe orphan file cleanup candidates.', 'url' => site_url('admin/system-tools/media-cleaner')],
    ['title' => 'Notifications', 'icon' => 'bell', 'desc' => 'Create and manage topbar notifications for different audiences.', 'url' => site_url('admin/system-tools/notifications')],
    ['title' => 'Readiness Checklist', 'icon' => 'tasks', 'desc' => 'Review competition setup blockers and warnings before launch.', 'url' => site_url('admin/system-tools/readiness')],
    ['title' => 'Retention Rules', 'icon' => 'broom', 'desc' => 'Configure and run scheduled retention cleanup for logs/trash/temp.', 'url' => site_url('admin/system-tools/retention')],
    ['title' => 'Settings', 'icon' => 'cogs', 'desc' => 'Update site-level email and branding settings.', 'url' => site_url('admin/system-tools/settings')],
    ['title' => 'Site Health', 'icon' => 'stethoscope', 'desc' => 'View runtime, PHP, database, storage, and gateway diagnostics in one place.', 'url' => site_url('admin/system-tools/site-health')],
    ['title' => 'States / Provinces', 'icon' => 'map-marked-alt', 'desc' => 'Manage state/province reference records for address forms.', 'url' => site_url('admin/system-tools/states')],
    ['title' => 'Trash Manager', 'icon' => 'trash-alt', 'desc' => 'See trash usage and purge files from the public trash directory.', 'url' => site_url('admin/system-tools/trash')],
    ['title' => 'Upload Health', 'icon' => 'heartbeat', 'desc' => 'Detect upload inconsistencies and run one-click repair actions.', 'url' => site_url('admin/system-tools/upload-health')],
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">System Tools</h1>
        <a class="btn btn-sm btn-secondary" href="<?= site_url('admin') ?>">Back to Dashboard</a>
    </div>

    <div class="row">
        <?php foreach ($tools as $tool): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-left-primary">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-<?= esc((string) $tool['icon']) ?> text-primary mr-2"></i>
                            <h6 class="mb-0 font-weight-bold text-gray-800"><?= esc((string) $tool['title']) ?></h6>
                        </div>
                        <p class="text-muted small mb-3"><?= esc((string) $tool['desc']) ?></p>
                        <div class="mt-auto">
                            <a class="btn btn-sm btn-primary" href="<?= esc((string) $tool['url']) ?>">Open Tool</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= $this->endSection() ?>
