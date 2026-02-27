<?php
$path = trim(service('request')->getUri()->getPath(), '/');
$is = static function (string $prefix) use ($path): bool {
    return str_starts_with($path, trim($prefix, '/'));
};
$notificationsData = service('notifications')->getTopbarData((string)session('role'), 5);
$notificationItems = is_array($notificationsData['items'] ?? null) ? $notificationsData['items'] : [];
$notificationCount = (int)($notificationsData['count'] ?? 0);
$isDemoNotifications = (bool)($notificationsData['is_demo'] ?? false);
$isImpersonating = (bool)session('impersonating');
$pendingCertificateRequests = 0;
try {
    $pendingCertificateRequests = (int)db_connect()
        ->table('comp_entry_certificate_requests')
        ->where('request_status', 'Pending')
        ->countAllResults();
} catch (\Throwable $e) {
    $pendingCertificateRequests = 0;
}
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= site_url('home') ?>">
        <img src="/img/sparklogo.jpg" alt="Spark Awards Logo" class="img-fluid" width="160" height="50" loading="eager">
    </a>

    <hr class="sidebar-divider my-0">

    <?php if ($user['is_admin'] || $user['is_editor']): ?>
        <li class="nav-item <?= $is('admin') ? 'active' : '' ?>">
            <a class="nav-link" href="<?= site_url('admin') ?>">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAdmin" aria-expanded="true" aria-controls="collapseAdmin">
                <i class="fas fa-fw fa-cog"></i>
                <span>Administration</span>
            </a>
            <div id="collapseAdmin" class="collapse <?= $is('admin') ? 'show' : '' ?>" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <?php $usersOpen = $is('admin/users'); ?>
                    <a class="collapse-item menu-parent <?= $usersOpen ? 'active is-open' : '' ?>" href="<?= site_url('admin/users') ?>">
                        <span>Users</span>
                        <i class="fas fa-chevron-right menu-parent-caret small text-gray-600"></i>
                    </a>

                    <?php $competitionsOpen = $is('admin/competitions') || $is('admin/competition-types') || $is('admin/design-types'); ?>
                    <a class="collapse-item menu-parent <?= $competitionsOpen ? 'active is-open' : '' ?>" href="<?= site_url('admin/competitions') ?>">
                        <span>Competitions</span>
                        <i class="fas fa-chevron-right menu-parent-caret small text-gray-600"></i>
                    </a>
                    <div class="menu-sub <?= $competitionsOpen ? 'show' : '' ?>">
                        <div class="py-1">
                            <a class="collapse-item pl-4 <?= $is('admin/competitions/create') ? 'active' : '' ?>" href="<?= site_url('admin/competitions/create') ?>">- Add Competition</a>
                            <a class="collapse-item pl-4 <?= $is('admin/competition-types') ? 'active' : '' ?>" href="<?= site_url('admin/competition-types') ?>">- Competition Categories</a>
                            <a class="collapse-item pl-4 <?= $is('admin/design-types') ? 'active' : '' ?>" href="<?= site_url('admin/design-types') ?>">- Design Types</a>
                        </div>
                    </div>

                    <?php $retailOpen = $is('admin/retail-items'); ?>
                    <a class="collapse-item menu-parent <?= $retailOpen ? 'active is-open' : '' ?>" href="<?= site_url('admin/retail-items') ?>">
                        <span>Competition Retail Items</span>
                        <i class="fas fa-chevron-right menu-parent-caret small text-gray-600"></i>
                    </a>
                    <div class="menu-sub <?= $retailOpen ? 'show' : '' ?>">
                        <div class="py-1">
                            <a class="collapse-item pl-4 <?= $is('admin/retail-items/create') ? 'active' : '' ?>" href="<?= site_url('admin/retail-items/create') ?>">- Add Retail Item</a>
                        </div>
                    </div>

                    <?php $submissionsOpen = $is('admin/submissions') || $is('admin/submission-questions'); ?>
                    <a class="collapse-item menu-parent <?= $submissionsOpen ? 'active is-open' : '' ?>" href="<?= site_url('admin/submissions') ?>">
                        <span>Submissions</span>
                        <i class="fas fa-chevron-right menu-parent-caret small text-gray-600"></i>
                    </a>
                    <div class="menu-sub <?= $submissionsOpen ? 'show' : '' ?>">
                        <div class="py-1">
                            <a class="collapse-item pl-4 <?= $is('admin/submissions/create') ? 'active' : '' ?>" href="<?= site_url('admin/submissions/create') ?>">- Add Submission</a>
                            <a class="collapse-item pl-4 <?= $is('admin/submission-questions') ? 'active' : '' ?>" href="<?= site_url('admin/submission-questions') ?>">- Submission Questions</a>
                        </div>
                    </div>
                    <a class="collapse-item menu-parent <?= $is('admin/certificate-requests') ? 'active' : '' ?>" href="<?= site_url('admin/certificate-requests') ?>">
                        <span>Certificate Requests</span>
                        <span class="menu-count-pill <?= $pendingCertificateRequests > 0 ? 'is-alert' : 'is-ok' ?>"><?= $pendingCertificateRequests ?></span>
                    </a>

                    <?php $couponsOpen = $is('admin/coupons'); ?>
                    <a class="collapse-item menu-parent <?= $couponsOpen ? 'active is-open' : '' ?>" href="<?= site_url('admin/coupons') ?>">
                        <span>Coupons</span>
                        <i class="fas fa-chevron-right menu-parent-caret small text-gray-600"></i>
                    </a>
                    <div class="menu-sub <?= $couponsOpen ? 'show' : '' ?>">
                        <div class="py-1">
                            <a class="collapse-item pl-4 <?= $is('admin/coupons/create') ? 'active' : '' ?>" href="<?= site_url('admin/coupons/create') ?>">- Add Coupon</a>
                        </div>
                    </div>

                    <a class="collapse-item <?= $is('admin/score-results') ? 'active' : '' ?>" href="<?= site_url('admin/score-results') ?>">Score Results</a>
                    <a class="collapse-item <?= $is('admin/email-lists') ? 'active' : '' ?>" href="<?= site_url('admin/email-lists') ?>">Email Lists</a>

                    <?php if ($user['is_admin']): ?>
                        <?php $systemToolsOpen = $is('admin/system-tools'); ?>
                        <a class="collapse-item menu-parent <?= $systemToolsOpen ? 'active is-open' : '' ?>" href="<?= site_url('admin/system-tools') ?>">
                            <span>System Tools</span>
                            <i class="fas fa-chevron-right menu-parent-caret small text-gray-600"></i>
                        </a>
                        <div class="menu-sub <?= $systemToolsOpen ? 'show' : '' ?>">
                            <div class="py-1">
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/countries') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/countries') ?>">- Countries</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/diagnostics') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/diagnostics') ?>">- Diagnostics Bundle</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/logs') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/logs') ?>">- Log Viewer</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/media-cleaner') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/media-cleaner') ?>">- Media Cleaner</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/notifications') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/notifications') ?>">- Notifications</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/readiness') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/readiness') ?>">- Readiness Checklist</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/retention') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/retention') ?>">- Retention Rules</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/settings') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/settings') ?>">- Settings</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/site-health') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/site-health') ?>">- Site Health</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/states') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/states') ?>">- States / Provinces</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/trash') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/trash') ?>">- Trash Manager</a>
                                <a class="collapse-item pl-4 <?= $is('admin/system-tools/upload-health') ? 'active' : '' ?>" href="<?= site_url('admin/system-tools/upload-health') ?>">- Upload Health</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </li>
    <?php endif; ?>

    <?php if ($user['is_judge']): ?>
        <li class="nav-item <?= $is('judging') ? 'active' : '' ?>">
            <a href="<?= site_url('judging') ?>" class="nav-link"><i class="fas fa-fw fa-medal"></i> <span>Judging</span></a>
        </li>
    <?php endif; ?>

    <?php if ($user['profile_completed'] || $user['is_admin'] || $user['is_editor']): ?>
        <li class="nav-item <?= $is('competitions') ? 'active' : '' ?>">
            <a href="<?= site_url('competitions') ?>" class="nav-link"><i class="fas fa-fw fa-trophy"></i> <span>Competitions</span></a>
        </li>
        <li class="nav-item <?= $is('submissions') ? 'active' : '' ?>">
            <a href="<?= site_url('submissions') ?>" class="nav-link"><i class="fas fa-fw fa-list"></i> <span>Submissions</span></a>
        </li>
        <li class="nav-item <?= $is('certificates') ? 'active' : '' ?>">
            <a href="<?= site_url('certificates') ?>" class="nav-link"><i class="fas fa-fw fa-certificate"></i> <span>Certificates</span></a>
        </li>
    <?php endif; ?>

    <li class="nav-item <?= $is('profile') ? 'active' : '' ?>">
        <a href="<?= site_url('profile') ?>" class="nav-link"><i class="fas fa-fw fa-user"></i> <span>Profile</span></a>
    </li>

    <li class="nav-item">
        <a href="https://www.sparkawards.com/contact/" class="nav-link" target="_blank" rel="noopener">
            <i class="fas fa-fw fa-question-circle"></i> <span>Support</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="#" class="nav-link" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-fw fa-sign-out-alt"></i> <span>Logout</span></a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php
        $displayName = trim((string)(session('name') ?? ''));
        if ($displayName === '') {
            $displayName = (string)($user['email'] ?? 'Account');
        }
        ?>

        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="fa fa-bars"></i>
            </button>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-bell fa-fw text-gray-600"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="badge badge-danger badge-counter"><?= $notificationCount > 99 ? '99+' : $notificationCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                        <h6 class="dropdown-header">Notifications<?= $isDemoNotifications ? ' ' . '(Demo)' : '' ?></h6>
                        <?php if (!empty($notificationItems)): ?>
                            <?php foreach ($notificationItems as $item): ?>
                                <?php
                                $level = (string)($item['level'] ?? 'info');
                                $icon = 'info';
                                $circleClass = 'bg-primary';
                                if ($level === 'success') {
                                    $icon = 'check';
                                    $circleClass = 'bg-success';
                                } elseif ($level === 'warning') {
                                    $icon = 'exclamation-triangle';
                                    $circleClass = 'bg-warning';
                                } elseif ($level === 'danger') {
                                    $icon = 'exclamation-circle';
                                    $circleClass = 'bg-danger';
                                }
                                $when = (string)($item['starts_at'] ?? $item['created_at'] ?? '');
                                $title = trim((string)($item['title'] ?? ''));
                                $message = trim((string)($item['message'] ?? ''));
                                $linkUrl = trim((string)($item['link_url'] ?? ''));
                                $linkText = trim((string)($item['link_text'] ?? ''));
                                $href = (preg_match('#^(https?://|/)#i', $linkUrl) === 1) ? $linkUrl : '#';
                                ?>
                                <a class="dropdown-item d-flex align-items-start" href="<?= esc($href) ?>" <?= $href !== '#' ? 'target="_blank" rel="noopener"' : '' ?>>
                                    <div class="mr-3">
                                        <div class="icon-circle <?= esc($circleClass) ?>"><i class="fas fa-<?= esc($icon) ?> text-white"></i></div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-600"><?= esc(format_datetime_ui($when, 'Now')) ?></div>
                                        <?php if ($title !== ''): ?><div><?= esc($title) ?></div><?php endif; ?>
                                        <div><?= esc($message) ?></div>
                                        <?php if ($linkText !== '' && $linkUrl !== ''): ?>
                                            <div class="small mt-1 text-primary"><?= esc($linkText) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="dropdown-item text-center small text-gray-600">No active notifications.</div>
                        <?php endif; ?>
                    </div>
                </li>

                <li class="topbar-divider d-none d-sm-block"></li>

                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= esc($displayName) ?></span>
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border spark-square-2rem">
                            <i class="fas fa-user-circle text-gray-600" aria-hidden="true"></i>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                        <?php if ($isImpersonating): ?>
                            <a class="dropdown-item text-warning" href="<?= site_url('auth/impersonation/stop') ?>">
                                <i class="fas fa-user-shield fa-sm fa-fw mr-2 text-warning"></i>
                                Stop Impersonating
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a class="dropdown-item" href="<?= site_url('profile') ?>">
                            <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-600"></i>
                            Edit Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-600"></i>
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
