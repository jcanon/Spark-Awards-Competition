<?php

use App\Services\RecoveryCodeService;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * @internal
 */
#[RequiresPhpExtension('sqlite3')]
final class RecoveryCodeServiceTest extends CIUnitTestCase
{
    private $testDb;
    private string $table = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDb = db_connect();
        $this->table = $this->testDb->prefixTable('comp_user_recovery_codes');
        $this->testDb->query('DROP TABLE IF EXISTS ' . $this->table);
        $this->testDb->query(
            'CREATE TABLE ' . $this->table . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id VARCHAR(36) NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                used_at DATETIME NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL
            )'
        );
    }

    protected function tearDown(): void
    {
        if ($this->testDb) {
            $this->testDb->query('DROP TABLE IF EXISTS ' . $this->table);
        }
        parent::tearDown();
    }

    public function testGenerateAndConsumeRecoveryCode(): void
    {
        $svc = new RecoveryCodeService();
        $userId = 'recovery-user-1';

        $codes = $svc->regenerateCodes($userId, 6, 12);
        $this->assertCount(6, $codes);
        $this->assertTrue($svc->hasActiveCodes($userId));
        $this->assertSame(6, $svc->countRemainingCodes($userId));

        $first = (string)$codes[0];
        $this->assertTrue($svc->verifyAndConsume($userId, $first));
        $this->assertFalse($svc->verifyAndConsume($userId, $first));
        $this->assertSame(5, $svc->countRemainingCodes($userId));
    }
}
