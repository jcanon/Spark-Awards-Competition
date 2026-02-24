<?php

use App\Services\ProfileService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ProfileServicePolicyTest extends CIUnitTestCase
{
    public function testNonAdminCannotAssignElevatedRoles(): void
    {
        $svc = new ProfileService();
        $method = new ReflectionMethod($svc, 'enforceRoleUpdatePolicy');
        $method->setAccessible(true);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Only Admins can assign Admin or Editor roles.');

        $method->invoke($svc, ['is_admin' => 'Yes', 'is_editor' => 'No'], 'test-user', 'editor');
    }

    public function testAdminCanAssignElevatedRoles(): void
    {
        $svc = new ProfileService();
        $method = new ReflectionMethod($svc, 'enforceRoleUpdatePolicy');
        $method->setAccessible(true);

        $method->invoke($svc, ['is_admin' => 'Yes', 'is_editor' => 'No'], 'test-user', 'admin');
        $this->assertTrue(true);
    }
}
