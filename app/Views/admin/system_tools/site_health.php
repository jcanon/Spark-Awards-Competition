<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$runtime = $report['runtime'] ?? [];
$php = $report['php'] ?? [];
$ci = $report['codeigniter'] ?? [];
$db = $report['database'] ?? [];
$counts = $report['table_counts'] ?? [];
$permissions = $report['permissions'] ?? [];
$storage = $report['storage'] ?? [];
$payment = $report['payment'] ?? [];

$formatBool = static function ($v): string {
    $ok = (bool) $v;
    $cls = $ok ? 'success' : 'danger';
    $txt = $ok ? 'Yes' : 'No';
    return '<span class="badge badge-' . $cls . '">' . $txt . '</span>';
};

$formatBytes = static function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return number_format($size, $i === 0 ? 0 : 2) . ' ' . $units[$i];
};
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Site Health</h1>
        <div>
            <a class="btn btn-sm btn-secondary" href="<?= site_url('admin/system-tools') ?>">Back to System Tools</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Environment</div>
                    <div class="h5 mb-0"><?= esc((string) ($runtime['environment'] ?? '')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-info h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">PHP</div>
                    <div class="h5 mb-0"><?= esc((string) ($php['version'] ?? '')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">CodeIgniter</div>
                    <div class="h5 mb-0"><?= esc((string) ($ci['version'] ?? '')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <div class="text-xs text-uppercase text-muted mb-1">Database</div>
                    <div class="h6 mb-0"><?= esc((string) ($db['driver'] ?? '')) ?><?= !empty($db['version']) ? ' ' . esc((string)$db['version']) : '' ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Runtime</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                <tr><th class="spark-w-260">Generated At</th><td><?= esc((string) ($report['generated_at'] ?? '')) ?></td></tr>
                <tr><th>Server Time</th><td><?= esc((string) ($runtime['server_time'] ?? '')) ?></td></tr>
                <tr><th>UTC Time</th><td><?= esc((string) ($runtime['now_utc'] ?? '')) ?></td></tr>
                <tr><th>Timezone</th><td><?= esc((string) ($runtime['timezone'] ?? '')) ?></td></tr>
                <tr><th>Hostname</th><td><?= esc((string) ($runtime['hostname'] ?? '')) ?></td></tr>
                <tr><th>OS</th><td><?= esc((string) ($runtime['os'] ?? '')) ?></td></tr>
                <tr><th>Base URL</th><td><code><?= esc((string) ($runtime['base_url'] ?? '')) ?></code></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>PHP Configuration</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                <tr><th class="spark-w-260">SAPI</th><td><?= esc((string) ($runtime['php_sapi'] ?? '')) ?></td></tr>
                <tr><th>memory_limit</th><td><?= esc((string) ($php['memory_limit'] ?? '')) ?></td></tr>
                <tr><th>upload_max_filesize</th><td><?= esc((string) ($php['upload_max_filesize'] ?? '')) ?></td></tr>
                <tr><th>post_max_size</th><td><?= esc((string) ($php['post_max_size'] ?? '')) ?></td></tr>
                <tr><th>max_file_uploads</th><td><?= esc((string) ($php['max_file_uploads'] ?? '')) ?></td></tr>
                <tr><th>max_execution_time</th><td><?= esc((string) ($php['max_execution_time'] ?? '')) ?></td></tr>
                <tr><th>max_input_time</th><td><?= esc((string) ($php['max_input_time'] ?? '')) ?></td></tr>
                <tr><th>max_input_vars</th><td><?= esc((string) ($php['max_input_vars'] ?? '')) ?></td></tr>
                <tr><th>display_errors</th><td><?= esc((string) ($php['display_errors'] ?? '')) ?></td></tr>
                <tr><th>log_errors</th><td><?= esc((string) ($php['log_errors'] ?? '')) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Extensions and OPCache</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Extension</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach (($php['required_extensions'] ?? []) as $ext => $loaded): ?>
                            <tr>
                                <td><code><?= esc((string) $ext) ?></code></td>
                                <td><?= $formatBool($loaded) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <tbody>
                        <tr><th class="spark-w-260">OPcache INI Enabled</th><td><?= $formatBool(((string)($php['opcache']['enabled_ini'] ?? '0')) === '1') ?></td></tr>
                        <tr><th>OPcache Status Available</th><td><?= $formatBool((bool)($php['opcache']['status_available'] ?? false)) ?></td></tr>
                        <tr><th>OPcache Active</th><td><?= $formatBool((bool)(($php['opcache']['status']['opcache_enabled'] ?? false))) ?></td></tr>
                        <tr><th>JIT Setting</th><td><code><?= esc((string) ($php['opcache']['jit_enabled_ini'] ?? '')) ?></code></td></tr>
                        <tr><th>Loaded Extensions</th><td><?= number_format((int) ($php['loaded_extensions_count'] ?? 0)) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Database</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                <tr><th class="spark-w-260">Connected</th><td><?= $formatBool((bool) ($db['connected'] ?? false)) ?></td></tr>
                <tr><th>Driver</th><td><?= esc((string) ($db['driver'] ?? '')) ?></td></tr>
                <tr><th>Database Name</th><td><?= esc((string) ($db['database'] ?? '')) ?></td></tr>
                <tr><th>Server Version</th><td><?= esc((string) ($db['version'] ?? '')) ?></td></tr>
                <?php if (! empty($db['error'])): ?><tr><th>Error</th><td class="text-danger"><?= esc((string) $db['error']) ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Authorize.Net</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                <tr><th class="spark-w-260">Configured Mode</th><td><?= esc((string) ($payment['authorize_net_mode'] ?? '')) ?></td></tr>
                <tr><th>Active Environment</th><td><?= esc((string) ($payment['active_environment'] ?? '')) ?></td></tr>
                <tr><th>Active Hosted URL</th><td><code><?= esc((string) ($payment['active_hosted_url'] ?? '')) ?></code></td></tr>
                <tr><th>Sandbox Credentials Present</th><td><?= $formatBool((bool) ($payment['sandbox_credentials_present'] ?? false)) ?></td></tr>
                <tr><th>Production Credentials Present</th><td><?= $formatBool((bool) ($payment['production_credentials_present'] ?? false)) ?></td></tr>
                <tr><th>Sandbox Endpoint Reachable</th><td><?= $formatBool((bool) ($payment['sandbox_endpoint_probe']['ok'] ?? false)) ?><?php if (isset($payment['sandbox_endpoint_probe']['latency_ms'])): ?> <span class="text-muted">(<?= (int)$payment['sandbox_endpoint_probe']['latency_ms'] ?> ms)</span><?php endif; ?></td></tr>
                <tr><th>Production Endpoint Reachable</th><td><?= $formatBool((bool) ($payment['production_endpoint_probe']['ok'] ?? false)) ?><?php if (isset($payment['production_endpoint_probe']['latency_ms'])): ?> <span class="text-muted">(<?= (int)$payment['production_endpoint_probe']['latency_ms'] ?> ms)</span><?php endif; ?></td></tr>
                <tr><th>Authorize.Net SDK Version</th><td><?= esc((string) ($payment['sdk_version'] ?? '')) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Storage and Permissions</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                <tr><th>Key</th><th>Path</th><th>Exists</th><th>Readable</th><th>Writable</th><th>Files</th><th>Size</th></tr>
                </thead>
                <tbody>
                <?php foreach ($permissions as $key => $perm): ?>
                    <?php $stat = $storage[str_replace('_public', '', str_replace('_writable', '', (string) $key))] ?? ['files' => 0, 'bytes' => 0]; ?>
                    <tr>
                        <td><code><?= esc((string) $key) ?></code></td>
                        <td><code><?= esc((string) ($perm['path'] ?? '')) ?></code></td>
                        <td><?= $formatBool((bool) ($perm['exists'] ?? false)) ?></td>
                        <td><?= $formatBool((bool) ($perm['readable'] ?? false)) ?></td>
                        <td><?= $formatBool((bool) ($perm['writable'] ?? false)) ?></td>
                        <td><?= number_format((int) ($stat['files'] ?? 0)) ?></td>
                        <td><?= esc($formatBytes((int) ($stat['bytes'] ?? 0))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Core Table Counts</strong></div>
        <div class="card-body table-responsive">
            <?php if (isset($counts['error'])): ?>
                <div class="alert alert-danger mb-0"><?= esc((string) $counts['error']) ?></div>
            <?php else: ?>
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                    <?php foreach ($counts as $k => $v): ?>
                        <tr>
                            <th class="spark-w-260"><?= esc(ucwords(str_replace('_', ' ', (string) $k))) ?></th>
                            <td><?= $v === null ? 'N/A' : number_format((int) $v) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
