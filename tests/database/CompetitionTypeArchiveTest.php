<?php

use App\Services\Admin\CompetitionService;
use CodeIgniter\Test\CIUnitTestCase;

final class CompetitionTypeArchiveTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect();
        $this->db->query('CREATE TABLE ' . $this->db->prefixTable('comp_type') . ' (
            comp_type_id INTEGER PRIMARY KEY, comp_type_name VARCHAR(100),
            is_student_comp INTEGER, archived INTEGER DEFAULT 0
        )');
        $this->db->table('comp_type')->insertBatch([
            ['comp_type_id' => 1, 'comp_type_name' => 'Active category', 'is_student_comp' => 0, 'archived' => 0],
            ['comp_type_id' => 2, 'comp_type_name' => 'Archived category', 'is_student_comp' => 1, 'archived' => 1],
        ]);
        helper(['url', 'form']);
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TABLE ' . $this->db->prefixTable('comp_type'));
        parent::tearDown();
    }

    public function testUnarchiveMakesCategoryAvailableAgain(): void
    {
        $service = new CompetitionService();
        $this->assertCount(1, $service->getActiveCompetitionTypes());
        $this->assertTrue($service->unarchiveCompetitionType(2));
        $this->assertCount(2, $service->getActiveCompetitionTypes());
        $restored = $service->getCompetitionTypeByID(2);
        $this->assertSame('Archived category', $restored->comp_type_name);
        $this->assertSame(1, (int)$restored->is_student_comp);
        $this->assertSame(0, (int)$restored->archived);
        $this->assertTrue($service->unarchiveCompetitionType(2));
    }

    public function testInvalidCategoryCannotBeUnarchived(): void
    {
        $service = new CompetitionService();
        $this->assertFalse($service->unarchiveCompetitionType(0));
        $this->assertFalse($service->unarchiveCompetitionType(-1));
        $this->assertFalse($service->unarchiveCompetitionType(999));
        $this->assertCount(1, $service->getActiveCompetitionTypes());
    }

    public function testArchivedVisibilityAndRestoreAction(): void
    {
        $rows = (new CompetitionService())->getAllCompetitionTypes();
        $hidden = view('admin/competitions/types', ['rows' => $rows, 'showArchived' => false]);
        $this->assertStringContainsString('Show archived', $hidden);
        $this->assertStringNotContainsString('Archived category', $hidden);
        $shown = view('admin/competitions/types', ['rows' => $rows, 'showArchived' => true]);
        $this->assertStringContainsString('Archived category', $shown);
        $this->assertStringContainsString('competition-types/unarchive/2', $shown);
        $this->assertStringContainsString('Hide archived', $shown);
        $this->assertStringNotContainsString('competition-types/unarchive/1', $shown);
    }
}
