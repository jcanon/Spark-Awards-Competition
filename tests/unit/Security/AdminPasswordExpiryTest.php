<?php

use App\Controllers\Auth\PasswordExpiryController;
use App\Filters\AuthFilter;
use App\Services\AuthService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\AuthPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

final class AdminPasswordExpiryTest extends CIUnitTestCase
{
    public static function loginCases(): array
    {
        return [
            'admin with old password' => ['Yes', 'No', '2000-01-01 00:00:00', false, false],
            'admin without password date' => ['Yes', 'No', null, false, false],
            'admin with invalid password date' => ['Yes', 'No', 'invalid', false, false],
            'editor with old password' => ['No', 'Yes', '2000-01-01 00:00:00', true, false],
            'user with old password' => ['No', 'No', '2000-01-01 00:00:00', true, true],
        ];
    }

    #[RequiresPhpExtension('sqlite3')]
    #[DataProvider('loginCases')]
    public function testLoginExpiryAndTwoFactor(string $admin, string $editor, ?string $changedAt, bool $reset, bool $twoFactorPassed): void
    {
        $db = db_connect();
        $db->query('CREATE TEMP TABLE comp_users (
            user_id TEXT, email_address TEXT, password TEXT, account_active TEXT,
            is_admin TEXT, is_editor TEXT, password_changed_at TEXT
        )');
        try {
            $db->query('INSERT INTO comp_users VALUES (?, ?, ?, ?, ?, ?, ?)', [
                'expiry-test', 'expiry@example.com', password_hash('StrongPass1!', PASSWORD_DEFAULT),
                'Yes', $admin, $editor, $changedAt,
            ]);
            $policy = new AuthPolicy();
            $policy->enforcePasswordExpiry = true;
            $policy->passwordExpiryMonths = 6;

            $this->assertTrue((new AuthService($policy))->attempt('expiry@example.com', 'StrongPass1!'));
            $this->assertSame($reset, session('password_reset_required'));
            $this->assertSame($twoFactorPassed, session('2fa_pass'));
        } finally {
            $db->query('DROP TABLE temp.comp_users');
        }
    }

    public function testExistingAdminSessionStillRequiresTwoFactor(): void
    {
        session()->set(['uid' => 'admin', 'role' => 'admin', 'password_reset_required' => true, '2fa_pass' => false]);
        $request = service('request');
        $request->setPath('admin/users');

        $response = (new AuthFilter())->before($request, ['admin']);

        $this->assertFalse(session('password_reset_required'));
        $this->assertStringEndsWith('/auth/2fa', $response->getHeaderLine('Location'));
    }

    public function testVerifiedAdminCanContinueAndOldResetFormIsIgnored(): void
    {
        session()->set(['uid' => 'admin', 'role' => 'admin', 'password_reset_required' => true, '2fa_pass' => true]);
        $request = service('request');
        $request->setPath('auth/password-expired');

        $this->assertNull((new AuthFilter())->before($request));
        $this->assertFalse(session('password_reset_required'));
        $controller = new PasswordExpiryController();
        $this->assertStringEndsWith('/home', $controller->show()->getHeaderLine('Location'));
        $this->assertStringEndsWith('/home', $controller->update()->getHeaderLine('Location'));
    }

    public function testNonAdminSessionStillRequiresPasswordReset(): void
    {
        session()->set(['uid' => 'editor', 'role' => 'editor', 'password_reset_required' => true, '2fa_pass' => true]);
        $request = service('request');
        $request->setPath('home');

        $response = (new AuthFilter())->before($request);

        $this->assertTrue(session('password_reset_required'));
        $this->assertStringEndsWith('/auth/password-expired', $response->getHeaderLine('Location'));
    }
}
