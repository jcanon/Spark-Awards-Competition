<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Captcha extends BaseConfig
{
    // Provider: 'recaptcha' (Google v2 checkbox)
    public string $provider = 'recaptcha';

    // Keys (set real values in .env; these are fallbacks)
    public string $siteKey = '';
    public string $secretKey = '';

    // Verify endpoint
    public string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

    // For reCAPTCHA v3 you could add a score threshold (unused for v2)
    public float $minScore = 0.5;
}
