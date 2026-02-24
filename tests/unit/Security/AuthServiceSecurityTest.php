<?php

use App\Services\AuthService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthPolicy;

/**
 * @internal
 */
final class AuthServiceSecurityTest extends CIUnitTestCase
{
    public function testPasswordStrengthRules(): void
    {
        $svc = new AuthService($this->policy());

        $this->assertSame('short', $svc->checkPassword('Ab1!'));
        $this->assertSame('missing_upper', $svc->checkPassword('lowercase1!'));
        $this->assertSame('missing_lower', $svc->checkPassword('UPPERCASE1!'));
        $this->assertSame('missing_number', $svc->checkPassword('NoNumber!'));
        $this->assertSame('missing_symbol', $svc->checkPassword('NoSymbol1'));
        $this->assertSame('ok', $svc->checkPassword('StrongPass1!'));
    }

    public function testPasswordExpiryLogic(): void
    {
        $policy = $this->policy();
        $policy->passwordExpiryMonths = 6;
        $svc = new AuthService($policy);

        $this->assertTrue($svc->isPasswordExpired(''));
        $this->assertTrue($svc->isPasswordExpired('not-a-date'));

        $expiredDate = Time::now()->subMonths(7)->toDateTimeString();
        $freshDate = Time::now()->subMonths(5)->toDateTimeString();

        $this->assertTrue($svc->isPasswordExpired($expiredDate));
        $this->assertFalse($svc->isPasswordExpired($freshDate));
    }

    private function policy(): AuthPolicy
    {
        $policy = new AuthPolicy();
        $policy->enforcePasswordExpiry = true;
        $policy->passwordExpiryMonths = 6;
        $policy->passwordHistoryLimit = 24;
        return $policy;
    }
}
