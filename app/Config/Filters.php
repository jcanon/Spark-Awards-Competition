<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;
use CodeIgniter\Filters\Cors;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf' => CSRF::class,
        'toolbar' => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'invalidchars' => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors' => Cors::class,

        // Custom
        'auth' => \App\Filters\AuthFilter::class,
        'guest' => \App\Filters\GuestFilter::class,
        'role' => \App\Filters\RoleFilter::class,
    ];

    public array $globals = [
        'before' => [
            'csrf' => ['except' => ['payments/webhook']],
            // 'invalidchars',
        ],
        'after' => [
            'secureheaders',
            // 'toolbar' added in constructor for dev
        ],
    ];

    public array $methods = [];

    public array $filters = [];

    public function __construct()
    {
        if (ENVIRONMENT === 'development') {
            $this->globals['after'][] = 'toolbar';
        }
    }
}
