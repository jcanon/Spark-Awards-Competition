<?php

use App\Controllers\Auth\ForgotPasswordController;
use App\Models\Auth\PasswordResetModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * @internal
 */
#[RequiresPhpExtension('sqlite3')]
final class AuthPasswordResetFlowTest extends CIUnitTestCase
{
    private $testDb;
    private string $resetTable = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('sqlite3')) {
            $this->markTestSkipped('sqlite3 extension is required for reset flow database tests.');
        }

        $this->testDb = db_connect();
        $this->resetTable = $this->testDb->prefixTable('comp_password_resets');
        $this->testDb->query('DROP TABLE IF EXISTS ' . $this->resetTable);
        $this->testDb->query(
            'CREATE TABLE ' . $this->resetTable . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id VARCHAR(36) NOT NULL,
                token VARCHAR(128) NOT NULL,
                selector VARCHAR(32) NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NULL
            )'
        );
    }

    protected function tearDown(): void
    {
        if ($this->testDb) {
            $this->testDb->query('DROP TABLE IF EXISTS ' . $this->resetTable);
        }
        parent::tearDown();
    }

    public function testResetTokenIsSingleUseAfterConsumption(): void
    {
        $userId = 'user-reset-1';
        $token = (new PasswordResetModel())->createToken($userId, 60);

        $controller = new ForgotPasswordController();

        $this->assertSame($userId, $this->invokePrivate($controller, 'checkToken', [$token]));

        $this->invokePrivate($controller, 'consumeResetToken', [$token]);

        $this->assertNull($this->invokePrivate($controller, 'checkToken', [$token]));
    }

    public function testExpiredResetTokenIsRejected(): void
    {
        $userId = 'user-reset-2';
        $token = (new PasswordResetModel())->createToken($userId, 60);

        [$selector] = explode('.', $token, 2);
        $this->testDb->table('comp_password_resets')
            ->where('selector', $selector)
            ->update([
                'expires_at' => date('Y-m-d H:i:s', time() - 7200),
            ]);

        $controller = new ForgotPasswordController();
        $this->assertNull($this->invokePrivate($controller, 'checkToken', [$token]));
    }

    private function invokePrivate(object $object, string $method, array $args = [])
    {
        $ref = new ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($object, $args);
    }
}
