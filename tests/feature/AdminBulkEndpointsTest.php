<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AdminBulkEndpointsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $db = db_connect();
        $table = $db->getPrefix() . 'comp_settings';
        $db->query('CREATE TABLE IF NOT EXISTS ' . $table . ' (
            url TEXT PRIMARY KEY,
            email_server TEXT NULL,
            email_username TEXT NULL,
            email_password TEXT NULL,
            email TEXT NULL,
            title TEXT NULL
        )');
        $exists = $db->table('comp_settings')->where('url', 'default')->countAllResults();
        if ($exists === 0) {
            $db->table('comp_settings')->insert([
                'url' => 'default',
                'title' => 'Spark Awards',
            ]);
        }

        $usersTable = $db->getPrefix() . 'comp_users';
        $db->query('CREATE TABLE IF NOT EXISTS ' . $usersTable . ' (
            user_id TEXT PRIMARY KEY,
            user_type_id INTEGER NULL,
            is_admin TEXT NULL,
            is_editor TEXT NULL,
            is_judge TEXT NULL,
            account_active TEXT NULL,
            profile_completed TEXT NULL
        )');
        $userExists = $db->table('comp_users')->where('user_id', 'test-admin-id')->countAllResults();
        if ($userExists === 0) {
            $db->table('comp_users')->insert([
                'user_id' => 'test-admin-id',
                'user_type_id' => 0,
                'is_admin' => 'Yes',
                'is_editor' => 'No',
                'is_judge' => 'No',
                'account_active' => 'Yes',
                'profile_completed' => 'Yes',
            ]);
        }

        $entriesTable = $db->getPrefix() . 'comp_entries';
        $db->query('CREATE TABLE IF NOT EXISTS ' . $entriesTable . ' (
            entry_id TEXT PRIMARY KEY,
            user_id TEXT NULL,
            date_created TEXT NULL
        )');
    }

    private function adminSession(): array
    {
        return [
            'uid' => 'test-admin-id',
            'role' => 'admin',
            '2fa_pass' => true,
            'password_reset_required' => false,
        ];
    }

    private function postAsAdminWithCsrf(string $uri, array $payload, string $referer)
    {
        $security = service('security');
        $csrfHash = (string)($security->getHash() ?? '');
        $payload[$security->getTokenName()] = $csrfHash;
        $cookie = $security->getCookieName() . '=' . $csrfHash;

        return $this->withSession($this->adminSession())
            ->withHeaders([
                'HTTP_REFERER' => $referer,
                'Cookie' => $cookie,
            ])
            ->post($uri, $payload);
    }

    public function testSubmissionsBulkRequiresAction(): void
    {
        $result = $this->postAsAdminWithCsrf('/admin/submissions/bulk', [
            'entry_ids' => ['abc-entry-id'],
            'action' => '',
        ], 'http://localhost/admin/submissions');

        $result->assertRedirect();
        $result->assertSessionHas('error', 'Please choose a bulk update option.');
    }

    public function testSubmissionsBulkUnknownActionReturnsUpdatedZero(): void
    {
        $result = $this->postAsAdminWithCsrf('/admin/submissions/bulk', [
            'entry_ids' => ['abc-entry-id'],
            'action' => 'not-a-real-action',
        ], 'http://localhost/admin/submissions');

        $result->assertRedirect();
        $result->assertSessionHas('success', 'Updated 0 submission(s).');
    }

    public function testScoreResultsBulkRequiresSelection(): void
    {
        $result = $this->postAsAdminWithCsrf('/admin/score-results/bulk', [
            'action' => 'finalist',
        ], 'http://localhost/admin/score-results');

        $result->assertRedirect();
        $result->assertSessionHas('error', 'No entries selected.');
    }

    public function testScoreResultsBulkUnknownActionReturnsUpdatedZero(): void
    {
        $result = $this->postAsAdminWithCsrf('/admin/score-results/bulk', [
            'action' => 'not-a-real-action',
            'entry_ids' => ['abc-entry-id'],
        ], 'http://localhost/admin/score-results');

        $result->assertRedirect();
        $result->assertSessionHas('success', 'Updated 0 record(s).');
    }
}
