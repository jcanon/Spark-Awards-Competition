<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\Accounts\UserModel;
use Duo\DuoUniversal\Client as DuoClient;
use Duo\DuoUniversal\DuoException;

class TwoFactorController extends BaseController
{
    // GET /auth/2fa
    public function start()
    {
        if (!session('uid')) {
            return redirect()->to('/auth/login');
        }
        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return redirect()->to('/');
        }

        // If Duo ever returns to /auth/2fa with params, forward to callback.
        $maybeDuoCode = $this->getQueryString('duo_code');
        $maybeCode = $this->getQueryString('code');
        $maybeState = $this->getQueryString('state');
        if (($maybeDuoCode !== '' || $maybeCode !== '') && $maybeState !== '') {
            $qs = http_build_query([
                'duo_code' => $maybeDuoCode !== '' ? $maybeDuoCode : $maybeCode,
                'state' => $maybeState,
            ]);
            return redirect()->to('/auth/2fa/callback?' . $qs);
        }

        $user = (new UserModel())->find(session('uid'));
        $username = strtolower((string)($user?->email_address ?? 'user'));

        $cfg = config('Duo');
        $clientId = trim((string)$cfg->clientId);
        $secret = trim((string)$cfg->clientSecret);
        $apiHost = trim((string)$cfg->apiHost);
        $redirect = trim((string)$cfg->redirectUri);

        if ($clientId === '' || $secret === '' || $apiHost === '' || $redirect === '') {
            return redirect()->to('/')->with('error', lang('Entrant.two_factor_not_configured'));
        }

        $state = (string)(session('duo_state') ?? '');
        if ($state === '') {
            $state = bin2hex(random_bytes(16));
            session()->set('duo_state', $state);
        }

        $client = new DuoClient($clientId, $secret, $apiHost, $redirect);
        $duoUrl = $client->createAuthUrl($username, $state);

        return view('auth/2fa', [
            'title' => 'Two-Factor Authentication',
            'duoUrl' => $duoUrl,
            'hasRecoveryCodes' => service('recoveryCodes')->hasActiveCodes((string)session('uid')),
            'remainingRecoveryCodes' => service('recoveryCodes')->countRemainingCodes((string)session('uid')),
        ]);
    }

    // GET /auth/2fa/callback
    public function callback()
    {
        if (!session('uid')) {
            return redirect()->to('/auth/login');
        }
        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return redirect()->to('/');
        }

        // Duo Universal returns duo_code. Accept code as fallback.
        $duoCode = $this->getQueryString('duo_code');
        $code = $this->getQueryString('code');
        $state = $this->getQueryString('state');

        $authCode = $duoCode !== '' ? $duoCode : $code;
        if ($authCode === '' || $state === '') {
            return redirect()->to('/auth/2fa')->with('error', lang('Entrant.two_factor_failed'));
        }

        $stored = (string)(session('duo_state') ?? '');
        if ($stored === '' || !hash_equals($stored, $state)) {
            return redirect()->to('/auth/2fa')->with('error', lang('Entrant.two_factor_failed'));
        }

        $user = (new UserModel())->find(session('uid'));
        $username = strtolower((string)($user?->email_address ?? 'user'));

        $cfg = config('Duo');
        $clientId = trim((string)$cfg->clientId);
        $secret = trim((string)$cfg->clientSecret);
        $apiHost = trim((string)$cfg->apiHost);
        $redirect = trim((string)$cfg->redirectUri);

        try {
            $client = new DuoClient($clientId, $secret, $apiHost, $redirect);
            $result = $client->exchangeAuthorizationCodeFor2faResult($authCode, $username);

            if (!$this->isAllow($result)) {
                return redirect()->to('/auth/2fa')->with('error', lang('Entrant.two_factor_failed'));
            }

            session()->remove('duo_state');
            session()->set('2fa_pass', true);

            $dest = session('intended_url') ?: '/admin';
            session()->remove('intended_url');

            return redirect()->to($dest);
        } catch (DuoException $e) {
            return redirect()->to('/auth/2fa')->with('error', lang('Entrant.two_factor_failed'));
        } catch (\Throwable $e) {
            return redirect()->to('/auth/2fa')->with('error', lang('Entrant.two_factor_failed'));
        }
    }

    // POST /auth/2fa/recovery
    public function verifyRecoveryCode()
    {
        $userId = (string)session('uid');
        if ($userId === '') {
            return redirect()->to('/auth/login');
        }
        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return redirect()->to('/');
        }

        $ip = (string)$this->request->getIPAddress();
        if (!service('authThrottle')->allowTwoFactorRecovery($ip, $userId)) {
            return redirect()->to('/auth/2fa')->with(
                'error',
                lang('Entrant.too_many_recovery_code_attempts')
            );
        }

        $code = (string)$this->request->getPost('recovery_code');
        $ok = service('recoveryCodes')->verifyAndConsume($userId, $code);
        if (!$ok) {
            log_message('warning', '2FA recovery code failed for uid={uid} from ip={ip}', [
                'uid' => $userId,
                'ip' => $ip,
            ]);
            return redirect()->to('/auth/2fa')->with(
                'error',
                lang('Entrant.unable_verify_recovery_code')
            );
        }

        session()->remove('duo_state');
        session()->set('2fa_pass', true);

        $remaining = service('recoveryCodes')->countRemainingCodes($userId);
        $dest = session('intended_url') ?: '/admin';
        session()->remove('intended_url');

        $msg = lang('Entrant.recovery_code_accepted');
        if ($remaining <= 3) {
            $msg .= ' ' . lang('Entrant.recovery_codes_remaining_generate', [$remaining]);
        }

        return redirect()->to($dest)->with('success', $msg);
    }

    // ---------- helpers ----------

    private function getQueryString(string $name): string
    {
        $v = $this->request->getGet($name);
        if (is_array($v)) {
            // Collapse to first scalar leaf
            $curr = $v;
            $guard = 0;
            while (is_array($curr) && $guard++ < 5) {
                $curr = reset($curr);
            }
            return is_scalar($curr) ? (string)$curr : '';
        }
        return is_scalar($v) ? (string)$v : '';
    }

    private function isAllow($result): bool
    {
        // Objects
        if (is_object($result)) {
            if (method_exists($result, 'isSuccess') && $result->isSuccess()) {
                return true;
            }
            if (method_exists($result, 'getAuthResult')) {
                $val = strtolower($this->toScalarString($result->getAuthResult()));
                if ($val === 'allow') {
                    return true;
                }
            }
            return false;
        }

        // Arrays
        if (is_array($result)) {
            if (!empty($result['success'])) {
                return true;
            }
            $raw = $result['result'] ?? ($result['auth_result'] ?? null);
            $val = strtolower($this->toScalarString($raw));
            return $val === 'allow';
        }

        return false;
    }

    private function toScalarString($v): string
    {
        if (is_null($v)) {
            return '';
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if (is_scalar($v)) {
            return (string)$v;
        }
        if (is_array($v)) {
            $curr = $v;
            $guard = 0;
            while (is_array($curr) && $guard++ < 5) {
                $curr = reset($curr);
            }
            return is_scalar($curr) ? (string)$curr : '';
        }
        if (method_exists($v, '__toString')) {
            try {
                return (string)$v;
            } catch (\Throwable $e) {
            }
        }
        return '';
    }
}
