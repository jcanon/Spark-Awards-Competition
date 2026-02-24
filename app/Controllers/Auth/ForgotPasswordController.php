<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\Auth\PasswordResetModel;
use CodeIgniter\I18n\Time;

class ForgotPasswordController extends BaseController
{
    // GET /auth/forgot
    public function showForgot()
    {
        if (session('uid')) {
            return redirect()->to('/home');
        }

        return view('auth/forgot', [
            'title' => 'Forgot Password',
            'sent' => (bool)$this->request->getGet('sent'),
        ]);
    }

    // POST /auth/forgot
    public function sendResetLink()
    {
        $email = trim((string)$this->request->getPost('forgot_email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', lang('Entrant.invalid_email_try_again'));
        }
        $ip = (string)$this->request->getIPAddress();
        if (!service('authThrottle')->allowForgot($ip, $email)) {
            log_message('warning', 'Forgot-password throttled for {email_hash} from {ip}', [
                'email_hash' => hash('sha256', strtolower(trim($email))),
                'ip' => $ip,
            ]);
            return redirect()->back()->withInput()->with(
                'error',
                lang('Entrant.too_many_reset_requests')
            );
        }

        // Unified flow: use AuthService to create a DB-backed token and send mail
        service('auth')->beginPasswordReset($email);

        return redirect()->to(site_url('auth/forgot?sent=1'));
    }

    // GET /auth/reset/{token}
    public function showReset(string $token)
    {
        $uid = $this->checkToken($token);
        if (!$uid) {
            return redirect()->to(site_url('auth/forgot'))->with('error', lang('Entrant.invalid_or_expired_link'));
        }

        return view('auth/reset', [
            'title' => 'Reset Password',
            'token' => $token,
        ]);
    }

    // GET /auth/reset?token=...
    public function showResetFromQuery()
    {
        $token = (string)($this->request->getGet('token') ?? '');
        if ($token === '') {
            return redirect()->to(site_url('auth/forgot'))->with('error', lang('Entrant.invalid_or_expired_link'));
        }

        return $this->showReset($token);
    }

    // POST /auth/reset/{token}
    public function resetPassword(string $token)
    {
        $ip = (string)$this->request->getIPAddress();
        if (!service('authThrottle')->allowReset($ip, $token)) {
            log_message('warning', 'Reset-password throttled from {ip} token_hash={token_hash}', [
                'ip' => $ip,
                'token_hash' => hash('sha256', trim($token)),
            ]);
            return redirect()->back()->withInput()->with(
                'error',
                lang('Entrant.too_many_reset_attempts')
            );
        }

        $uid = $this->checkToken($token);
        $token = trim($token);
        if (!$uid) {
            return redirect()->to(site_url('auth/forgot'))->with('error', lang('Entrant.invalid_or_expired_link'));
        }

        $plain = (string)$this->request->getPost('password');
        $confirm = (string)$this->request->getPost('password_confirm');

        $auth = service('auth');
        $pwStatus = $auth->checkPassword($plain);
        $confirmStatus = $auth->checkConfirmPassword($confirm, $plain);

        if ($pwStatus !== 'ok' || $confirmStatus !== 'ok') {
            $errorMsg = $auth->passwordErrorMessage($pwStatus);
            if ($confirmStatus === 'blank') {
                $errorMsg = lang('Entrant.password_confirmation_blank');
            } elseif ($confirmStatus === 'nomatch') {
                $errorMsg = lang('Entrant.passwords_do_not_match');
            }
            return redirect()->back()->withInput()->with('error', $errorMsg);
        }

        $setStatus = $auth->setUserPassword($uid, $plain, true, 'forgot_reset');
        if ($setStatus === 'reused') {
            return redirect()->back()->withInput()->with(
                'error',
                lang('Entrant.password_reuse_not_allowed')
            );
        }
        if ($setStatus !== 'ok') {
            return redirect()->back()->withInput()->with('error', lang('Entrant.unable_reset_password'));
        }

        $this->consumeResetToken($token);

        return redirect()->to(site_url('auth/login'))->with('success', lang('Entrant.password_reset_success'));
    }

    private function checkToken(string $token): ?string
    {
        $token = trim($token);

        $parts = explode('.', $token, 2);
        if (count($parts) === 2) {
            [$selector, $verifier] = $parts;
            $selector = trim($selector);
            $verifier = trim($verifier);
            if ($selector !== '' && $verifier !== '') {
                $resetModel = new PasswordResetModel();
                $record = $resetModel
                    ->where('selector', $selector)
                    ->where('used_at', null)
                    ->first();
                if ($record) {
                    $hash = (string)($record['token_hash'] ?? '');
                    if ($hash !== '' && password_verify($verifier, $hash)) {
                        $expiry = strtotime((string)($record['expires_at'] ?? ''));
                        if ($expiry !== false && $expiry >= time()) {
                            return (string)($record['user_id'] ?? '');
                        }
                        return null;
                    }
                    return null;
                }
            }
        }

        return null;
    }

    private function consumeResetToken(string $token): void
    {
        $token = trim($token);
        $now = Time::now()->toDateTimeString();
        $resetModel = new PasswordResetModel();

        $parts = explode('.', $token, 2);
        if (count($parts) === 2) {
            $selector = trim($parts[0]);
            if ($selector !== '') {
                $record = $resetModel
                    ->where('selector', $selector)
                    ->where('used_at', null)
                    ->first();
                if ($record) {
                    $resetModel->update($record['id'], [
                        'used_at' => $now,
                        'expires_at' => $now,
                    ]);
                    return;
                }
            }
        }

    }
}
