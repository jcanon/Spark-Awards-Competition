<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthPolicy;

/**
 * @internal
 */
final class AuthPolicyTest extends CIUnitTestCase
{
    private array $originalEnv = [];

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public function testPolicyClampsExpiryAndHistoryBounds(): void
    {
        $this->setEnv('AUTH_PASSWORD_EXPIRY_MONTHS', '0');
        $this->setEnv('AUTH_PASSWORD_HISTORY_LIMIT', '1000');
        $policy = new AuthPolicy();

        $this->assertSame(1, $policy->passwordExpiryMonths);
        $this->assertSame(100, $policy->passwordHistoryLimit);
    }

    public function testPolicyReadsEnforcementToggle(): void
    {
        $this->setEnv('AUTH_ENFORCE_PASSWORD_EXPIRY', 'false');
        $policy = new AuthPolicy();
        $this->assertFalse($policy->enforcePasswordExpiry);
    }

    private function setEnv(string $key, string $value): void
    {
        if (!array_key_exists($key, $this->originalEnv)) {
            $current = getenv($key);
            $this->originalEnv[$key] = ($current === false) ? null : $current;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
