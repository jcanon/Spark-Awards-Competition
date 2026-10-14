<?php

use App\Services\Admin\SubmissionsAdminService;
use CodeIgniter\Test\CIUnitTestCase;

final class SubmissionsExportTest extends CIUnitTestCase
{
    private array $schemas = [
        'comp_entries' => 'entry_id TEXT PRIMARY KEY, comp_id INTEGER, user_id INTEGER, design_name TEXT, entry_status TEXT, entry_non_finalist TEXT, winner_level INTEGER',
        'comp_competitions' => 'comp_id INTEGER PRIMARY KEY, comp_year INTEGER, comp_type_id INTEGER',
        'comp_type' => 'comp_type_id INTEGER PRIMARY KEY, comp_type_name TEXT',
        'comp_users' => 'user_id INTEGER PRIMARY KEY, user_type_id INTEGER, first_name TEXT, last_name TEXT, email_address TEXT, company_name TEXT',
        'comp_user_type' => 'user_type_id INTEGER PRIMARY KEY, user_type_pricing TEXT',
        'comp_winner_levels' => 'winner_level_id INTEGER PRIMARY KEY, winner_level_name TEXT',
        'comp_entry_payments' => 'entry_id TEXT, payment_phase INTEGER, payment_status TEXT, payment_date TEXT',
        'comp_judging' => 'entry_id TEXT, user_id INTEGER, entry_score INTEGER, entry_comments TEXT',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect();
        foreach ($this->schemas as $table => $schema) {
            $this->db->query('CREATE TABLE ' . $this->db->prefixTable($table) . ' (' . $schema . ')');
        }
        $this->db->table('comp_user_type')->insertBatch([
            ['user_type_id' => 1, 'user_type_pricing' => 'Pro'],
            ['user_type_id' => 2, 'user_type_pricing' => 'Student'],
        ]);
        $this->db->table('comp_users')->insert(['user_id' => 1, 'user_type_id' => 1, 'first_name' => 'Test', 'last_name' => 'Judge']);
        $this->db->table('comp_users')->insert(['user_id' => 2, 'user_type_id' => 2]);
        $this->db->table('comp_users')->insert(['user_id' => 3, 'user_type_id' => 99]);
        foreach ([1 => 2025, 2 => 2026] as $id => $year) {
            $this->db->table('comp_type')->insert(['comp_type_id' => $id, 'comp_type_name' => 'Competition ' . $id]);
            $this->db->table('comp_competitions')->insert(['comp_id' => $id, 'comp_year' => $year, 'comp_type_id' => $id]);
        }
        foreach (['student' => 2, 'unknown' => 3] as $entryId => $userId) {
            $this->db->table('comp_entries')->insert([
                'entry_id' => $entryId, 'comp_id' => 2, 'user_id' => $userId,
                'design_name' => $entryId, 'entry_status' => 'Finalist', 'entry_non_finalist' => 'No',
            ]);
        }
        foreach (['old' => 1, 'current' => 2, 'draft' => 2, 'orphan' => 99] as $id => $competition) {
            $this->db->table('comp_entries')->insert([
                'entry_id' => $id, 'comp_id' => $competition, 'user_id' => $id === 'orphan' ? 99 : 1,
                'design_name' => $id, 'entry_status' => $id === 'draft' ? 'Draft' : 'Finalist',
                'entry_non_finalist' => $id === 'draft' ? 'Yes' : 'No',
            ]);
            if ($id !== 'orphan') {
                $this->db->table('comp_judging')->insert([
                    'entry_id' => $id, 'user_id' => 1, 'entry_score' => 5, 'entry_comments' => 'Review ' . $id,
                ]);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (array_reverse(array_keys($this->schemas)) as $table) {
            $this->db->query('DROP TABLE ' . $this->db->prefixTable($table));
        }
        parent::tearDown();
    }

    public function testAllProExportIgnoresEveryFilterAndExcludesStudentsAndUnknownUserTypes(): void
    {
        $service = new SubmissionsAdminService();
        $filters = ['compYear' => 2026, 'compType' => '2', 'filterBy' => 'current', 'excludeNonFinalists' => 'Yes'];

        $this->assertSame(['current'], array_column($service->export($filters), 'entry_id'));
        $this->assertSame(['current', 'draft', 'old'], array_column($service->export($filters, true), 'entry_id'));
        $this->assertSame(['current', 'draft', 'student', 'unknown'], array_column($service->export(['compYear' => 2026]), 'entry_id'));
        $this->assertSame(['current'], array_column($service->exportJudging($filters), 'submission_name'));
        $this->assertSame(['current', 'draft', 'old'], array_column($service->exportJudging($filters, true), 'submission_name'));
    }

    public function testProWorkbookOmitsDescriptionsAndJudgingSheet(): void
    {
        $service = new SubmissionsAdminService();
        $controller = new \App\Controllers\Admin\SubmissionsController();
        $method = new ReflectionMethod($controller, 'toXlsx');
        $path = tempnam(sys_get_temp_dir(), 'pro-export-');
        try {
            file_put_contents($path, $method->invoke($controller, $service->export([], true), null));
            $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $this->assertSame(['Submissions'], $workbook->getSheetNames());
            $headers = $workbook->getActiveSheet()->toArray()[0];
            $this->assertNotContains('short_description', $headers);
            $this->assertNotContains('full_description', $headers);
            $this->assertContains('competition_year', $headers);
            $workbook->disconnectWorksheets();

            file_put_contents($path, $method->invoke($controller, $service->export(['compYear' => 2026]), []));
            $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $this->assertSame(['Submissions', 'Judging'], $workbook->getSheetNames());
            $headers = $workbook->getActiveSheet()->toArray()[0];
            $this->assertContains('short_description', $headers);
            $this->assertContains('full_description', $headers);
            $workbook->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function testEmptyDatabaseExportsEmptySheets(): void
    {
        $this->db->table('comp_entries')->emptyTable();
        $service = new SubmissionsAdminService();
        $this->assertSame([], $service->export([], true));
        $this->assertSame([], $service->exportJudging([], true));
    }
}
