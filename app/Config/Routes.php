<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Public
$routes->get('/', 'Auth\LoginController::show', ['filter' => 'guest']);
$routes->get('auth/login', 'Auth\LoginController::show', ['filter' => 'guest']);
$routes->post('auth/login', 'Auth\LoginController::authenticate', ['filter' => 'guest']);
$routes->post('auth/register', 'Auth\LoginController::register', ['filter' => 'guest']);
$routes->get('auth/activate/(:segment)', 'Auth\LoginController::activate/$1', ['filter' => 'guest']);
$routes->get('auth/forgot', 'Auth\ForgotPasswordController::showForgot', ['filter' => 'guest']);
$routes->post('auth/forgot', 'Auth\ForgotPasswordController::sendResetLink', ['filter' => 'guest']);
$routes->get('auth/reset', 'Auth\ForgotPasswordController::showResetFromQuery', ['filter' => 'guest']);
$routes->get('auth/reset/(:segment)', 'Auth\ForgotPasswordController::showReset/$1', ['filter' => 'guest']);
$routes->post('auth/reset/(:segment)', 'Auth\ForgotPasswordController::resetPassword/$1', ['filter' => 'guest']);

// Authenticated
$routes->get('auth/logout', 'Auth\LoginController::logout', ['filter' => 'auth']);
$routes->get('auth/impersonation/stop', 'Auth\LoginController::stopImpersonation', ['filter' => 'auth']);
$routes->get('auth/password-expired', 'Auth\PasswordExpiryController::show', ['filter' => 'auth']);
$routes->post('auth/password-expired', 'Auth\PasswordExpiryController::update', ['filter' => 'auth']);
$routes->get('auth/2fa', 'Auth\TwoFactorController::start', ['filter' => 'auth:admin,editor']);
$routes->get('auth/2fa/callback', 'Auth\TwoFactorController::callback', ['filter' => 'auth:admin,editor']);
$routes->post('auth/2fa/recovery', 'Auth\TwoFactorController::verifyRecoveryCode', ['filter' => 'auth:admin,editor']);
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('home', 'HomeController::index');

    $routes->group('profile', static function ($routes) {
        $routes->get('/', 'ProfileController::index');
        $routes->post('update', 'ProfileController::update');
        $routes->post('recovery-codes/regenerate', 'ProfileController::regenerateRecoveryCodes');
    });

    $routes->group('competitions', static function ($routes) {
        $routes->get('/', 'CompetitionsController::index');
        $routes->get('create/(:num)', 'CompetitionsController::create/$1');
    });

    $routes->group('submissions', static function ($routes) {
        $routes->get('/', 'SubmissionsController::index');
        $routes->get('create/(:num)', 'SubmissionsController::create/$1');
        $routes->post('store', 'SubmissionsController::store');
        $routes->get('update/(:segment)', 'SubmissionsController::update/$1');
        $routes->post('update/(:segment)', 'SubmissionsController::updatePost/$1');
        $routes->post('delete/(:segment)', 'SubmissionsController::delete/$1');
        $routes->post('photo/delete/(:num)', 'SubmissionsController::deletePhoto/$1');
    });

    $routes->group('certificates', static function ($routes) {
        $routes->get('/', 'CertificatesController::index');
        $routes->get('request/(:segment)', 'CertificatesController::request/$1');
        $routes->post('request/(:segment)', 'CertificatesController::save/$1');
    });

    $routes->group('payments', static function ($routes) {
        $routes->get('entry/(:segment)/phase/(:num)', 'PaymentsController::show/$1/$2');
        $routes->post('entry/(:segment)/phase/(:num)/coupon', 'PaymentsController::setCoupon/$1/$2');
        $routes->post('entry/(:segment)/phase/(:num)/addons', 'PaymentsController::setAddons/$1/$2');
        $routes->post('entry/(:segment)/phase/(:num)/total', 'PaymentsController::setTotal/$1/$2');
        $routes->get('entry/(:segment)/phase/(:num)/checkout', 'PaymentsController::checkout/$1/$2');
        $routes->get('entry/(:segment)/phase/(:num)/receipt', 'PaymentsController::receipt/$1/$2');
        $routes->get('entry/(:segment)/phase/(:num)/receipt-content', 'PaymentsController::receiptContent/$1/$2');
        $routes->get('entry/(:segment)/phase/(:num)/receipt-pdf', 'PaymentsController::receiptPdf/$1/$2');
        $routes->post('entry/(:segment)/phase/(:num)/success', 'PaymentsController::success/$1/$2');
        $routes->get('hosted-return', 'PaymentController::hostedReturn');
    });

    $routes->get('media/photo/(:num)', 'MediaController::photo/$1');
});

// Judging access
$routes->group('judging', ['filter' => 'auth:juror'], static function ($routes) {
    $routes->get('/', 'JudgingController::index');
    $routes->get('entries/competition/(:num)/status/(:segment)', 'JudgingController::listByCompetition/$1/$2');
    $routes->get('entry/(:segment)/status/(:segment)', 'JudgingController::entry/$1/$2');
    $routes->post('score', 'JudgingController::saveScore');
    $routes->get('results', 'JudgingController::results');
});

// Admin + Editor (2FA required)
$routes->group('admin', ['filter' => 'auth:admin,editor,2fa'], static function ($routes) {
    $routes->get('/', 'Admin\DashboardController::index');

    $routes->get('users', 'Admin\UsersController::index');
    $routes->get('users/data', 'Admin\UsersController::data');
    $routes->get('users/edit/(:segment)', 'Admin\UsersController::edit/$1');
    $routes->post('users/update/(:segment)', 'Admin\UsersController::update/$1');
    $routes->post('users/impersonate/(:segment)', 'Admin\UsersController::impersonate/$1');
    $routes->post('users/delete/(:segment)', 'Admin\UsersController::delete/$1');
    $routes->get('users/export', 'Admin\UsersExportController::export');

    $routes->get('competitions', 'Admin\CompetitionsController::index');
    $routes->get('competitions/create', 'Admin\CompetitionsController::create');
    $routes->post('competitions/store', 'Admin\CompetitionsController::store');
    $routes->get('competitions/edit/(:num)', 'Admin\CompetitionsController::edit/$1');
    $routes->post('competitions/update/(:num)', 'Admin\CompetitionsController::update/$1');
    $routes->post('competitions/delete/(:num)', 'Admin\CompetitionsController::delete/$1');
    $routes->get('competition-types', 'Admin\CompetitionsController::types');
    $routes->post('competition-types/store', 'Admin\CompetitionsController::storeType');
    $routes->get('competition-types/edit/(:num)', 'Admin\CompetitionsController::editType/$1');
    $routes->post('competition-types/update/(:num)', 'Admin\CompetitionsController::updateType/$1');
    $routes->post('competition-types/archive/(:num)', 'Admin\CompetitionsController::archiveType/$1');
    $routes->get('design-types', 'Admin\DesignTypesController::index');
    $routes->post('design-types/store', 'Admin\DesignTypesController::store');
    $routes->get('design-types/edit/(:num)', 'Admin\DesignTypesController::edit/$1');
    $routes->post('design-types/update/(:num)', 'Admin\DesignTypesController::update/$1');
    $routes->post('design-types/delete/(:num)', 'Admin\DesignTypesController::delete/$1');

    $routes->get('coupons', 'Admin\CouponsController::index');
    $routes->get('coupons/create', 'Admin\CouponsController::create');
    $routes->post('coupons/store', 'Admin\CouponsController::store');
    $routes->get('coupons/edit/(:num)', 'Admin\CouponsController::edit/$1');
    $routes->post('coupons/update/(:num)', 'Admin\CouponsController::update/$1');
    $routes->post('coupons/delete/(:num)', 'Admin\CouponsController::delete/$1');

    $routes->get('retail-items', 'Admin\RetailItemsController::index');
    $routes->get('retail-items/create', 'Admin\RetailItemsController::create');
    $routes->post('retail-items/store', 'Admin\RetailItemsController::store');
    $routes->get('retail-items/edit/(:num)', 'Admin\RetailItemsController::edit/$1');
    $routes->post('retail-items/update/(:num)', 'Admin\RetailItemsController::update/$1');
    $routes->post('retail-items/delete/(:num)', 'Admin\RetailItemsController::delete/$1');

    $routes->get('submissions', 'Admin\SubmissionsController::index');
    $routes->get('submissions/create', 'Admin\SubmissionsController::create');
    $routes->get('submissions/user-search', 'Admin\SubmissionsController::userSearch');
    $routes->get('submission-questions', 'Admin\SubmissionQuestionsController::index');
    $routes->post('submission-questions/store', 'Admin\SubmissionQuestionsController::store');
    $routes->post('submission-questions/update/(:num)', 'Admin\SubmissionQuestionsController::update/$1');
    $routes->post('submission-questions/delete/(:num)', 'Admin\SubmissionQuestionsController::delete/$1');
    $routes->post('submissions/store', 'Admin\SubmissionsController::store');
    $routes->get('submissions/edit/(:segment)', 'Admin\SubmissionsController::edit/$1');
    $routes->post('submissions/update/(:segment)', 'Admin\SubmissionsController::update/$1');
    $routes->get('submissions/copy/(:segment)', 'Admin\SubmissionsController::copy/$1');
    $routes->post('submissions/copy/(:segment)', 'Admin\SubmissionsController::copyPost/$1');
    $routes->post('submissions/delete/(:segment)', 'Admin\SubmissionsController::delete/$1');
    $routes->post('submissions/photo/delete/(:num)', 'Admin\SubmissionsController::deletePhoto/$1');
    $routes->post('submissions/certificate/delete/(:num)', 'Admin\SubmissionsController::deleteCertificate/$1');
    $routes->post('submissions/bulk', 'Admin\SubmissionsController::bulk');
    $routes->get('submissions/export', 'Admin\SubmissionsController::export');
    $routes->get('submissions/judging-cards', 'Admin\SubmissionsController::judgingCards');
    $routes->get('submissions/receipt/(:num)/(:segment)', 'Admin\SubmissionsController::receipt/$1/$2');
    $routes->get('submissions/receipt-content/(:num)/(:segment)', 'Admin\SubmissionsController::receiptContent/$1/$2');
    $routes->get('submissions/receipt-pdf/(:num)/(:segment)', 'Admin\SubmissionsController::receiptPdf/$1/$2');
    $routes->get('certificate-requests', 'Admin\CertificateRequestsController::index');
    $routes->get('certificate-requests/edit/(:num)', 'Admin\CertificateRequestsController::edit/$1');
    $routes->post('certificate-requests/status/(:num)', 'Admin\CertificateRequestsController::updateStatus/$1');
    $routes->post('certificate-requests/update/(:num)', 'Admin\CertificateRequestsController::update/$1');

    $routes->get('score-results', 'Admin\ScoreResultsController::index');
    $routes->post('score-results/bulk', 'Admin\ScoreResultsController::bulk');
    $routes->get('score-results/export', 'Admin\ScoreResultsController::export');

    $routes->get('email-lists', 'Admin\EmailListsController::index');
    $routes->get('notifications', 'Admin\NotificationsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('notifications/create', 'Admin\NotificationsController::create', ['filter' => 'auth:admin,2fa']);
    $routes->post('notifications/store', 'Admin\NotificationsController::store', ['filter' => 'auth:admin,2fa']);
    $routes->get('notifications/edit/(:num)', 'Admin\NotificationsController::edit/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('notifications/update/(:num)', 'Admin\NotificationsController::update/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('notifications/delete/(:num)', 'Admin\NotificationsController::delete/$1', ['filter' => 'auth:admin,2fa']);

    $routes->get('countries', 'Admin\CountriesController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('countries/create', 'Admin\CountriesController::create', ['filter' => 'auth:admin,2fa']);
    $routes->post('countries/store', 'Admin\CountriesController::store', ['filter' => 'auth:admin,2fa']);
    $routes->get('countries/edit/(:segment)', 'Admin\CountriesController::edit/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('countries/update/(:segment)', 'Admin\CountriesController::update/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('countries/delete/(:segment)', 'Admin\CountriesController::delete/$1', ['filter' => 'auth:admin,2fa']);

    $routes->get('states', 'Admin\StatesController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('states/create', 'Admin\StatesController::create', ['filter' => 'auth:admin,2fa']);
    $routes->post('states/store', 'Admin\StatesController::store', ['filter' => 'auth:admin,2fa']);
    $routes->get('states/edit/(:segment)', 'Admin\StatesController::edit/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('states/update/(:segment)', 'Admin\StatesController::update/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('states/delete/(:segment)', 'Admin\StatesController::delete/$1', ['filter' => 'auth:admin,2fa']);

    $routes->get('settings', 'Admin\SettingsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->post('settings/update', 'Admin\SettingsController::update', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools', 'Admin\SystemToolsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/upload-health', 'Admin\SystemToolsController::uploadHealth', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/upload-health/repair', 'Admin\SystemToolsController::uploadHealthRepair', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/media-cleaner', 'Admin\SystemToolsController::mediaCleaner', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/media-cleaner/purge', 'Admin\SystemToolsController::mediaCleanerPurge', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/retention', 'Admin\SystemToolsController::retention', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/retention/save', 'Admin\SystemToolsController::retentionSave', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/retention/run', 'Admin\SystemToolsController::retentionRun', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/readiness', 'Admin\SystemToolsController::readiness', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/site-health', 'Admin\SystemToolsController::siteHealth', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/diagnostics', 'Admin\SystemToolsController::diagnostics', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/diagnostics/generate', 'Admin\SystemToolsController::diagnosticsGenerate', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/diagnostics/download/(:segment)', 'Admin\SystemToolsController::diagnosticsDownload/$1', ['filter' => 'auth:admin,2fa']);
    $routes->get('logs', 'Admin\LogsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->post('logs/purge', 'Admin\LogsController::purge', ['filter' => 'auth:admin,2fa']);
    $routes->get('trash', 'Admin\TrashController::index', ['filter' => 'auth:admin,2fa']);
    $routes->post('trash/purge', 'Admin\TrashController::purge', ['filter' => 'auth:admin,2fa']);

    // System Tools aliases (preferred)
    $routes->get('system-tools/notifications', 'Admin\NotificationsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/notifications/create', 'Admin\NotificationsController::create', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/notifications/store', 'Admin\NotificationsController::store', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/notifications/edit/(:num)', 'Admin\NotificationsController::edit/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/notifications/update/(:num)', 'Admin\NotificationsController::update/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/notifications/delete/(:num)', 'Admin\NotificationsController::delete/$1', ['filter' => 'auth:admin,2fa']);

    $routes->get('system-tools/countries', 'Admin\CountriesController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/countries/create', 'Admin\CountriesController::create', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/countries/store', 'Admin\CountriesController::store', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/countries/edit/(:segment)', 'Admin\CountriesController::edit/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/countries/update/(:segment)', 'Admin\CountriesController::update/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/countries/delete/(:segment)', 'Admin\CountriesController::delete/$1', ['filter' => 'auth:admin,2fa']);

    $routes->get('system-tools/states', 'Admin\StatesController::index', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/states/create', 'Admin\StatesController::create', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/states/store', 'Admin\StatesController::store', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/states/edit/(:segment)', 'Admin\StatesController::edit/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/states/update/(:segment)', 'Admin\StatesController::update/$1', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/states/delete/(:segment)', 'Admin\StatesController::delete/$1', ['filter' => 'auth:admin,2fa']);

    $routes->get('system-tools/settings', 'Admin\SettingsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/settings/update', 'Admin\SettingsController::update', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/logs', 'Admin\LogsController::index', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/logs/purge', 'Admin\LogsController::purge', ['filter' => 'auth:admin,2fa']);
    $routes->get('system-tools/trash', 'Admin\TrashController::index', ['filter' => 'auth:admin,2fa']);
    $routes->post('system-tools/trash/purge', 'Admin\TrashController::purge', ['filter' => 'auth:admin,2fa']);
    $routes->get('purge-entries', 'Admin\PurgeEntriesController::index');
    $routes->post('purge-entries/run', 'Admin\PurgeEntriesController::run');
});

// Webhook must stay public
$routes->post('payments/webhook', 'PaymentController::webhook');
