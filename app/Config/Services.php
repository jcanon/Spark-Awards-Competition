<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\Email\Email as Emailer;
use RuntimeException;

use App\Services\Admin\AdminSettingsService;
use App\Services\AuthService;
use App\Services\CaptchaService;
use App\Services\Admin\CouponService;
use App\Services\CompetitionBrowseService;
use App\Services\Admin\CompetitionService;
use App\Services\DuoService;
use App\Services\JudgingService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Services\ProfileService;
use App\Services\NotificationService;
use App\Services\AuthThrottleService;
use App\Services\RecoveryCodeService;

class Services extends BaseService
{
    public static function email($config = null, bool $getShared = true): Emailer
    {
        if ($getShared) {
            return static::getSharedInstance('email', $config);
        }

        $cfg = [];

        $settings = static::settings(false);
        $primaryEmail = trim((string)$settings->get('email', ''));
        $smtpHost = trim((string)$settings->get('email_server', ''));
        $smtpUser = trim((string)$settings->get('email_username', ''));
        $smtpPass = (string)$settings->get('email_password', '');
        $smtpPort = null;
        if ($smtpHost !== '' && preg_match('/^(.+):(\d{2,5})$/', $smtpHost, $m) === 1) {
            $smtpHost = trim((string)$m[1]);
            $smtpPort = (int)$m[2];
        }

        if ($primaryEmail === '' || !filter_var($primaryEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email settings are incomplete: valid "email" (from address) is required in Site Settings.');
        }
        if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
            throw new RuntimeException('Email settings are incomplete: SMTP host, username, and password are required in Site Settings.');
        }

        $cfg['fromEmail'] = $primaryEmail;
        $cfg['fromName'] = $settings->getTitle();
        $cfg['protocol'] = 'smtp';
        $cfg['SMTPHost'] = $smtpHost;
        $cfg['SMTPPort'] = 587;
        $cfg['SMTPUser'] = $smtpUser;
        $cfg['SMTPPass'] = $smtpPass;
        $cfg['SMTPCrypto'] = 'tls';
        if ($smtpPort !== null) {
            $cfg['SMTPPort'] = $smtpPort;
        }

        if (is_array($config)) {
            $cfg = array_merge($cfg, $config);
        } elseif (is_object($config)) {
            $cfg = array_merge($cfg, get_object_vars($config));
        }

        return new Emailer($cfg);
    }

    public static function adminSettings($getShared = true): AdminSettingsService
    {
        if ($getShared) {
            return static::getSharedInstance('adminSettings');
        }
        return new AdminSettingsService();
    }

    public static function auth($getShared = true): AuthService
    {
        if ($getShared) {
            return static::getSharedInstance('auth');
        }
        return new AuthService();
    }

    public static function authThrottle($getShared = true): AuthThrottleService
    {
        if ($getShared) {
            return static::getSharedInstance('authThrottle');
        }
        return new AuthThrottleService();
    }

    public static function captcha($getShared = true): CaptchaService
    {
        if ($getShared) {
            return static::getSharedInstance('captcha');
        }
        return new CaptchaService(new \App\Config\Captcha());
    }

    public static function compbrowse($getShared = true): CompetitionBrowseService
    {
        if ($getShared) {
            return static::getSharedInstance('compbrowse');
        }
        return new CompetitionBrowseService();
    }

    public static function coupons($getShared = true): CouponService
    {
        if ($getShared) {
            return static::getSharedInstance('coupons');
        }
        return new CouponService();
    }

    public static function competitions($getShared = true): CompetitionService
    {
        if ($getShared) {
            return static::getSharedInstance('competitions');
        }
        return new CompetitionService();
    }

    public static function duo($getShared = true): DuoService
    {
        if ($getShared) {
            return static::getSharedInstance('duo');
        }
        return new DuoService();
    }

    public static function judging($getShared = true): JudgingService
    {
        if ($getShared) {
            return static::getSharedInstance('judging');
        }
        return new JudgingService();
    }

    public static function payments($getShared = true): PaymentService
    {
        if ($getShared) {
            return static::getSharedInstance('payments');
        }
        return new PaymentService();
    }

    public static function profiles($getShared = true): ProfileService
    {
        if ($getShared) {
            return static::getSharedInstance('profiles');
        }
        return new ProfileService();
    }

    public static function settings($getShared = true): SettingsService
    {
        if ($getShared) {
            return static::getSharedInstance('settings');
        }
        return new SettingsService();
    }

    public static function notifications($getShared = true): NotificationService
    {
        if ($getShared) {
            return static::getSharedInstance('notifications');
        }
        return new NotificationService();
    }

    public static function recoveryCodes($getShared = true): RecoveryCodeService
    {
        if ($getShared) {
            return static::getSharedInstance('recoveryCodes');
        }
        return new RecoveryCodeService();
    }

    public static function userctx($getShared = true): \App\Services\UserContextService
    {
        if ($getShared) {
            return static::getSharedInstance('userctx');
        }
        return new \App\Services\UserContextService();
    }
}
