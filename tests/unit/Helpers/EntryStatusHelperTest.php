<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EntryStatusHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('entry_status');
    }

    public function testWinnerLevelPillIncludesSpecificClassAndLabel(): void
    {
        [$label, $class, $icon] = entry_status_pill('Winner', 'Gold');

        $this->assertSame('Winner: Gold', $label);
        $this->assertSame('winner-gold', $class);
        $this->assertSame('fas fa-medal', $icon);
    }

    public function testEntrantPillFromArray(): void
    {
        [$label, $class, $icon] = entry_status_pill([
            'entry_status' => 'Entrant',
            'winner_level_name' => '',
        ]);

        $this->assertSame('Entrant', $label);
        $this->assertSame('status-entrant', $class);
        $this->assertSame('', $icon);
    }

    public function testUnknownStatusFallsBackToDraftClass(): void
    {
        [$label, $class, $icon] = entry_status_pill('In Review');

        $this->assertSame('In Review', $label);
        $this->assertSame('status-draft', $class);
        $this->assertSame('', $icon);
    }
}
