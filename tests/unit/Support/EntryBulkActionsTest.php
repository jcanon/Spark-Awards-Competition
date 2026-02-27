<?php

use App\Support\EntryBulkActions;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EntryBulkActionsTest extends CIUnitTestCase
{
    public function testSubmissionPayloadIncludesGalleryAndWinnerMappings(): void
    {
        $this->assertSame(['gallery_hide' => 'Yes'], EntryBulkActions::submissionPayload('gallery_hide'));
        $this->assertSame(['entry_status' => 'Winner', 'winner_level' => 2], EntryBulkActions::submissionPayload('winner_gold'));
    }

    public function testScoreResultStatusAndPatchForFinalist(): void
    {
        $result = EntryBulkActions::scoreResultStatusAndPatch('finalist');

        $this->assertNotNull($result);
        $this->assertSame('Finalist', $result[0]);
        $this->assertSame(['entry_non_finalist' => 'No'], $result[1]);
    }

    public function testUnknownActionReturnsNull(): void
    {
        $this->assertNull(EntryBulkActions::submissionPayload('nope'));
        $this->assertNull(EntryBulkActions::scoreResultStatusAndPatch('nope'));
    }
}
