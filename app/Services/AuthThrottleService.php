<?php

namespace App\Services;

class AuthThrottleService
{
    public function allowLogin(string $ip, string $email): bool
    {
        return $this->check('auth.login', $ip, $email, 8, 600);
    }

    public function allowRegister(string $ip, string $email): bool
    {
        return $this->check('auth.register', $ip, $email, 5, 1800);
    }

    public function allowForgot(string $ip, string $email): bool
    {
        return $this->check('auth.forgot', $ip, $email, 5, 1800);
    }

    public function allowReset(string $ip, string $token): bool
    {
        return $this->check('auth.reset', $ip, $token, 8, 1800);
    }

    public function allowForcedPasswordUpdate(string $ip, string $userId): bool
    {
        return $this->check('auth.password-expired', $ip, $userId, 6, 1800);
    }

    public function allowTwoFactorRecovery(string $ip, string $userId): bool
    {
        return $this->check('auth.2fa.recovery', $ip, $userId, 8, 1800);
    }

    private function check(string $prefix, string $ip, string $subject, int $max, int $seconds): bool
    {
        $normalizedPrefix = preg_replace('/[^a-z0-9_.-]+/i', '_', trim($prefix));
        $normalizedIp = trim($ip) !== '' ? trim($ip) : 'unknown-ip';
        $normalizedSubject = strtolower(trim($subject));
        if ($normalizedSubject === '') {
            $normalizedSubject = 'unknown-subject';
        }

        // CI cache keys cannot contain reserved characters {}()/\@: keep key strictly safe.
        $key = $normalizedPrefix . '_' . hash('sha256', $normalizedIp . '|' . $normalizedSubject);
        return service('throttler')->check($key, $max, $seconds);
    }
}
