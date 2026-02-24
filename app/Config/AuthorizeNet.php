<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AuthorizeNet extends BaseConfig
{
    /**
     * Mode: auto | sandbox | production
     * - auto: production in CI production env, sandbox otherwise.
     */
    public string $mode = 'auto';

    public string $sandboxApiLoginId = '';
    public string $sandboxTransactionKey = '';
    public string $sandboxSignatureKey = '';

    public string $productionApiLoginId = '';
    public string $productionTransactionKey = '';
    public string $productionSignatureKey = '';

    public function __construct()
    {
        parent::__construct();

        $this->mode = strtolower(trim((string)env('ANET_MODE', $this->mode)));
        if (!in_array($this->mode, ['auto', 'sandbox', 'production'], true)) {
            $this->mode = 'auto';
        }

        // Explicit environment-specific credentials.
        $this->sandboxApiLoginId = trim((string)env('ANET_SANDBOX_API_LOGIN_ID', ''));
        $this->sandboxTransactionKey = trim((string)env('ANET_SANDBOX_TRANSACTION_KEY', ''));
        $this->sandboxSignatureKey = trim((string)env('ANET_SANDBOX_SIGNATURE_KEY', ''));

        $this->productionApiLoginId = trim((string)env('ANET_PRODUCTION_API_LOGIN_ID', ''));
        $this->productionTransactionKey = trim((string)env('ANET_PRODUCTION_TRANSACTION_KEY', ''));
        $this->productionSignatureKey = trim((string)env('ANET_PRODUCTION_SIGNATURE_KEY', ''));
    }

    public function useProduction(): bool
    {
        if ($this->mode === 'production') {
            return true;
        }
        if ($this->mode === 'sandbox') {
            return false;
        }
        return ENVIRONMENT === 'production';
    }

    public function apiLoginId(): string
    {
        // Backward-compatible fallback to legacy env vars if not split yet.
        if ($this->useProduction()) {
            return $this->productionApiLoginId !== ''
                ? $this->productionApiLoginId
                : trim((string)env('ANET_API_LOGIN_ID', ''));
        }
        return $this->sandboxApiLoginId !== ''
            ? $this->sandboxApiLoginId
            : trim((string)env('ANET_API_LOGIN_ID', ''));
    }

    public function transactionKey(): string
    {
        if ($this->useProduction()) {
            return $this->productionTransactionKey !== ''
                ? $this->productionTransactionKey
                : trim((string)env('ANET_TRANSACTION_KEY', ''));
        }
        return $this->sandboxTransactionKey !== ''
            ? $this->sandboxTransactionKey
            : trim((string)env('ANET_TRANSACTION_KEY', ''));
    }

    public function signatureKey(): string
    {
        if ($this->useProduction()) {
            return $this->productionSignatureKey !== ''
                ? $this->productionSignatureKey
                : trim((string)env('ANET_SIGNATURE_KEY', ''));
        }
        return $this->sandboxSignatureKey !== ''
            ? $this->sandboxSignatureKey
            : trim((string)env('ANET_SIGNATURE_KEY', ''));
    }

    public function hostedPaymentUrl(): string
    {
        return $this->useProduction()
            ? 'https://accept.authorize.net/payment/payment'
            : 'https://test.authorize.net/payment/payment';
    }
}

