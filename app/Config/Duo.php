<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Duo extends BaseConfig
{
    public string $clientId = '';
    public string $clientSecret = '';
    public string $apiHost = '';
    public string $redirectUri = '';

    public function __construct()
    {
        parent::__construct();

        // Trim to avoid stray spaces in .env
        $this->clientId = trim((string)env('DUO_CLIENT_ID', ''));
        $this->clientSecret = trim((string)env('DUO_CLIENT_SECRET', ''));
        $this->apiHost = trim((string)env('DUO_API_HOSTNAME', ''));
        $this->redirectUri = trim((string)env('DUO_REDIRECT_URI', site_url('auth/2fa/callback')));
    }
}
