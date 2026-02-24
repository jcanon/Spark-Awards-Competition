<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthPolicy extends BaseConfig
{
    /**
     * Enforce periodic password expiry.
     * Set via env: AUTH_ENFORCE_PASSWORD_EXPIRY=true|false
     */
    public bool $enforcePasswordExpiry = true;

    /**
     * Password expiry age in months.
     * Set via env: AUTH_PASSWORD_EXPIRY_MONTHS=6
     */
    public int $passwordExpiryMonths = 6;

    /**
     * Number of recent password hashes to check for reuse.
     * Set via env: AUTH_PASSWORD_HISTORY_LIMIT=24
     */
    public int $passwordHistoryLimit = 24;

    public function __construct()
    {
        parent::__construct();

        $enforce = env('AUTH_ENFORCE_PASSWORD_EXPIRY');
        if ($enforce !== null && $enforce !== '') {
            $normalized = strtolower(trim((string)$enforce));
            $this->enforcePasswordExpiry = in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        $months = (int)env('AUTH_PASSWORD_EXPIRY_MONTHS', $this->passwordExpiryMonths);
        $this->passwordExpiryMonths = max(1, min(60, $months));

        $history = (int)env('AUTH_PASSWORD_HISTORY_LIMIT', $this->passwordHistoryLimit);
        $this->passwordHistoryLimit = max(1, min(100, $history));
    }
}

