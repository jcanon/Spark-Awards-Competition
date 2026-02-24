<?php

namespace App\Services;

use Duo\DuoUniversal\Client as DuoClient;
use Duo\DuoUniversal\DuoException;

class DuoService
{
    private string $clientId;
    private string $clientSecret;
    private string $apiHost;
    private string $redirectUri;

    public function __construct(?\Config\Duo $config = null)
    {
        $cfg = $config ?? config('Duo');

        $this->clientId = (string)$cfg->clientId;
        $this->clientSecret = (string)$cfg->clientSecret;
        $this->apiHost = (string)$cfg->apiHost;
        $this->redirectUri = (string)$cfg->redirectUri;
    }

    private function client(): DuoClient
    {
        return new DuoClient(
            $this->clientId,
            $this->clientSecret,
            $this->apiHost,
            $this->redirectUri
        );
    }

    /**
     * Starts Universal Prompt: stores state in session and returns the Duo auth URL.
     */
    public function start(string $username): string
    {
        $state = bin2hex(random_bytes(16));
        session()->set('duo_state', $state);

        return $this->client()->createAuthUrl($username, $state);
    }

    /**
     * Verifies the Duo callback. Returns true on success.
     */
    public function verify(?string $duoCode, ?string $duoState, string $username): bool
    {
        if (!$duoCode || !$duoState) {
            return false;
        }

        $saved = (string)(session('duo_state') ?? '');
        if ($saved === '' || !hash_equals($saved, $duoState)) {
            return false;
        }

        try {
            $result = $this->client()->exchangeAuthorizationCodeFor2faResult($duoCode, $username);

            // Handle SDK variations: array or object result
            $ok = false;
            if (is_array($result)) {
                $ok = ($result['success'] ?? false)
                    || (isset($result['auth_result']) && strtoupper((string)$result['auth_result']) === 'ALLOW')
                    || (isset($result['result']) && strtolower((string)$result['result']) === 'allow');
            } elseif (is_object($result)) {
                if (method_exists($result, 'isSuccess')) {
                    $ok = (bool)$result->isSuccess();
                } elseif (method_exists($result, 'getAuthResult')) {
                    $ok = strtoupper((string)$result->getAuthResult()) === 'ALLOW';
                }
            }

            if ($ok) {
                session()->remove('duo_state');
            }

            return $ok;
        } catch (DuoException $e) {
            log_message('error', 'Duo verify failed: ' . $e->getMessage());
            return false;
        }
    }

    public function redirectUri(): string
    {
        return $this->redirectUri;
    }
}