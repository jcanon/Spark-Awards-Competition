<?php

namespace App\Services;

use App\Config\Captcha as CaptchaConfig;
use Config\Services as CI;
use RuntimeException;

class CaptchaService
{
    public function __construct(private CaptchaConfig $config)
    {
        // Prefer env with config fallback
        $this->config->siteKey = env('RECAPTCHA_SITE_KEY', $this->config->siteKey);
        $this->config->secretKey = env('RECAPTCHA_SECRET_KEY', $this->config->secretKey);

        if ($this->config->secretKey === '' || $this->config->secretKey === null) {
            throw new RuntimeException('reCAPTCHA secret key is not configured.');
        }
    }

    public function siteKey(): string
    {
        return (string)$this->config->siteKey;
    }

    /**
     * Verify a Google reCAPTCHA v2 token.
     * Returns true only on a successful verification response.
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        $client = CI::curlrequest();
        $response = $client->post($this->config->verifyUrl, [
            'form_params' => array_filter([
                'secret' => $this->config->secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]),
            'http_errors' => false,
            'timeout' => 5,
        ]);

        $json = json_decode($response->getBody(), true) ?: [];
        return ($json['success'] ?? false) === true;
    }
}