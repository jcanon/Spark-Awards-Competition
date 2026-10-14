<?php

use App\Database\Migrations\AddBrokenImageScanner;
use App\Services\Admin\BrokenImageScannerService;
use CodeIgniter\Test\CIUnitTestCase;

final class BrokenImageScannerTest extends CIUnitTestCase
{
    private string $root;
    private BrokenImageScannerService $scanner;
    private array $schemas = [
        'comp_entries' => 'entry_id TEXT PRIMARY KEY, comp_id INTEGER, photo_id TEXT, gallery_hide TEXT',
        'comp_competitions' => 'comp_id INTEGER PRIMARY KEY, comp_year INTEGER, comp_type_id INTEGER',
        'comp_type' => 'comp_type_id INTEGER PRIMARY KEY, comp_type_name TEXT',
        'comp_entry_photos' => 'entry_photo_id INTEGER PRIMARY KEY, entry_id TEXT, entry_photo TEXT, entry_photo_res TEXT, entry_photo_order INTEGER, entry_photo_caption TEXT',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect();
        foreach ($this->schemas as $table => $schema) {
            $this->db->query('CREATE TABLE ' . $this->db->prefixTable($table) . ' (' . $schema . ')');
        }
        require_once APPPATH . 'Database/Migrations/2026-09-18-000003_AddBrokenImageScanner.php';
        (new AddBrokenImageScanner())->up();
        $this->root = sys_get_temp_dir() . '/image-scanner-' . bin2hex(random_bytes(6));
        mkdir($this->root);
        $this->scanner = new BrokenImageScannerService($this->db, $this->root, $this->root . '/lock');
        $this->db->table('comp_type')->insert(['comp_type_id' => 1, 'comp_type_name' => 'Mobile']);
        $this->db->table('comp_competitions')->insertBatch([
            ['comp_id' => 1, 'comp_year' => 2020, 'comp_type_id' => 1],
            ['comp_id' => 2, 'comp_year' => 2026, 'comp_type_id' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        (new AddBrokenImageScanner())->down();
        foreach (array_reverse(array_keys($this->schemas)) as $table) { $this->db->query('DROP TABLE ' . $this->db->prefixTable($table)); }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($this->root);
        parent::tearDown();
    }

    private function photo(int $id, string $path, int $competition = 1): void
    {
        $this->db->table('comp_entries')->insert(['entry_id' => 'e' . $id, 'comp_id' => $competition, 'photo_id' => 'p' . $id, 'gallery_hide' => 'No']);
        $this->db->table('comp_entry_photos')->insert(['entry_photo_id' => $id, 'entry_id' => 'e' . $id, 'entry_photo' => $path, 'entry_photo_res' => 'Low', 'entry_photo_order' => 1]);
    }

    private function image(string $path, bool $different = false): void
    {
        $absolute = $this->root . $path;
        if (!is_dir(dirname($absolute))) { mkdir(dirname($absolute), 0775, true); }
        file_put_contents($absolute, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZkAAAAASUVORK5CYII=') . ($different ? 'different' : ''));
    }

    private function runScan(): array
    {
        $state = $this->scanner->advance(true);
        for ($i = 0; $i < 100 && $state['phase'] !== 'complete'; $i++) { $state = $this->scanner->advance(); }
        $this->assertSame('complete', $state['phase']);
        return $state;
    }

    public function testAllYearsRecoveryTrashMissingAndPersistentCleanCache(): void
    {
        $this->photo(1, '/uploads/2020/mobile/a.png');
        $this->image('/uploads/2020/architecture/a.png');
        $this->photo(2, '/uploads/2026/mobile/b.png', 2);
        $this->image('/trash/b.png');
        $this->photo(3, '/uploads/2020/mobile/missing.png');
        $this->photo(4, '/uploads/2020/mobile/clean.png');
        $this->image('/uploads/2020/mobile/clean.png');
        $this->image('/uploads/2026/mobile/missing.png'); // Wrong year must not match.
        $state = $this->runScan();
        $this->assertSame(2, $state['repaired']);
        $this->assertSame(1, $state['missing']);
        $this->assertFileExists($this->root . '/uploads/2020/mobile/a.png');
        $this->assertFileDoesNotExist($this->root . '/uploads/2020/architecture/a.png');
        $this->assertFileDoesNotExist($this->root . '/trash/b.png');
        $this->assertSame('Yes', $this->db->table('comp_entries')->where('entry_id', 'e3')->get()->getRow('gallery_hide'));
        unlink($this->root . '/uploads/2020/mobile/clean.png');
        $this->scanner = new BrokenImageScannerService($this->db, $this->root, $this->root . '/lock');
        $second = $this->runScan();
        $this->assertSame(9, $second['cached']); // Includes six required-slot copies; verified paths are not rechecked.
        $this->assertSame(1, $second['queued']);
    }

    public function testChangedPathsAreRescannedAndConflictsDoNotHideEntry(): void
    {
        $this->photo(1, '/uploads/2020/mobile/a.png');
        $this->image('/uploads/2020/mobile/a.png');
        $this->runScan();
        $this->db->table('comp_entry_photos')->where('entry_photo_id', 1)->update(['entry_photo' => '/uploads/2020/mobile/b.png']);
        $this->image('/uploads/2020/architecture/b.png');
        $this->image('/trash/b.png', true);
        $state = $this->runScan();
        $this->assertSame(2, $state['cached']); // Only the unchanged required-slot copies are cached.
        $this->assertSame(1, $state['review']);
        $this->assertSame('No', $this->db->table('comp_entries')->where('entry_id', 'e1')->get()->getRow('gallery_hide'));
        $this->assertFileDoesNotExist($this->root . '/uploads/2020/mobile/b.png');
    }

    public function testChangedQueuedRecordIsNotMutatedAndTraversalIsRejected(): void
    {
        $this->photo(1, '/uploads/2020/mobile/a.png');
        $this->photo(2, '/uploads/../../outside.png');
        $this->scanner->advance(true);
        $this->scanner->advance();
        $this->db->table('comp_entry_photos')->where('entry_photo_id', 1)->update(['entry_photo' => '/uploads/2020/mobile/new.png']);
        $state = $this->runScan();
        $this->assertSame(2, $state['review']);
        $this->assertSame(0, $state['missing']);
    }

    public function testIncompleteScanResumesAndDoesNotExcludeBeforeSearch(): void
    {
        $this->photo(1, '/uploads/2020/mobile/a.png');
        $this->scanner->advance(true);
        $state = $this->scanner->advance();
        $this->assertSame('index', $state['phase']);
        $this->assertSame('No', $this->db->table('comp_entries')->get()->getRow('gallery_hide'));
        $this->image('/trash/a.png');
        $this->scanner = new BrokenImageScannerService($this->db, $this->root, $this->root . '/lock');
        $state = $this->runScan();
        $this->assertSame(1, $state['repaired']);
    }

    public function testSharedSourceIsRetainedAndExistingExclusionIsPreserved(): void
    {
        $this->photo(1, '/uploads/2020/mobile/a.png');
        $this->photo(2, '/uploads/2020/architecture/a.png');
        $this->image('/uploads/2020/architecture/a.png');
        $this->db->table('comp_entries')->where('entry_id', 'e1')->update(['gallery_hide' => 'Yes']);
        $state = $this->runScan();
        $this->assertSame(1, $state['repaired']);
        $this->assertFileExists($this->root . '/uploads/2020/architecture/a.png');
        $this->assertFileExists($this->root . '/uploads/2020/mobile/a.png');
        $this->assertSame('Yes', $this->db->table('comp_entries')->where('entry_id', 'e1')->get()->getRow('gallery_hide'));
    }

    public function testEmptyLegacyPathRecoversAndCorruptDestinationIsNotOverwritten(): void
    {
        $this->photo(1, '');
        $this->image('/trash/CompPhotoLow_1_p1.jpg');
        $this->photo(2, '/uploads/2020/mobile/corrupt.png');
        $this->image('/uploads/2020/mobile/corrupt.png');
        file_put_contents($this->root . '/uploads/2020/mobile/corrupt.png', 'not an image');
        $this->image('/trash/corrupt.png');
        $state = $this->runScan();
        $this->assertSame(1, $state['repaired']);
        $this->assertSame(1, $state['review']);
        $this->assertSame('not an image', file_get_contents($this->root . '/uploads/2020/mobile/corrupt.png'));
        $this->assertSame('/uploads/2020/mobile/CompPhotoLow_1_p1.jpg', $this->db->table('comp_entry_photos')->where('entry_photo_id', 1)->get()->getRow('entry_photo'));
    }

    public function testConcurrentBatchIsRejectedWithoutAdvancingProgress(): void
    {
        $this->photo(1, '/uploads/2020/mobile/a.png');
        $lock = fopen($this->root . '/lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $this->scanner->advance(true);
            $this->fail('Expected lock contention.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Another scanner batch', $e->getMessage());
            $this->assertSame('idle', $this->scanner->status()['phase']);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function addSlot(int $id, int $slot, string $path): void
    {
        $this->db->table('comp_entry_photos')->insert([
            'entry_photo_id' => $id, 'entry_id' => 'e1', 'entry_photo' => $path,
            'entry_photo_res' => 'Low', 'entry_photo_order' => $slot,
        ]);
    }

    public function testCopiesPhotoTwoToMissingOneAndThreeWithoutHidingOrChangingDonor(): void
    {
        $this->photo(1, '/uploads/2020/mobile/one.png');
        $this->addSlot(2, 2, '/uploads/2020/mobile/two.png');
        $this->addSlot(3, 3, '/uploads/2020/mobile/three.png');
        $this->addSlot(4, 4, '/uploads/2020/mobile/four.png');
        $this->image('/uploads/2020/mobile/two.png');
        $state = $this->runScan();
        $this->assertSame(2, $state['copied']);
        $this->assertSame(0, $state['missing']);
        $this->assertSame(1, $state['optional']);
        $this->assertSame('No', $this->db->table('comp_entries')->get()->getRow('gallery_hide'));
        $photos = $this->db->table('comp_entry_photos')->orderBy('entry_photo_order')->get()->getResultArray();
        $this->assertSame('/uploads/2020/mobile/two.png', $photos[1]['entry_photo']);
        foreach ([0, 2] as $index) {
            $this->assertNotSame($photos[1]['entry_photo'], $photos[$index]['entry_photo']);
            $this->assertSame(hash_file('sha256', $this->root . $photos[1]['entry_photo']), hash_file('sha256', $this->root . $photos[$index]['entry_photo']));
        }
        $this->assertSame('/uploads/2020/mobile/four.png', $photos[3]['entry_photo']);
        $second = $this->runScan();
        $this->assertSame(3, $second['cached']);
        $this->assertSame(0, $second['copied']);
    }

    public function testRecoversLaterPhotoBeforeCopyingAndCreatesAbsentRequiredRows(): void
    {
        $this->photo(1, '/uploads/2020/mobile/one.png');
        $this->addSlot(2, 4, '/uploads/2020/mobile/four.png');
        $this->image('/trash/four.png');
        $state = $this->runScan();
        $this->assertSame(1, $state['repaired']);
        $this->assertSame(3, $state['copied']);
        $this->assertSame(0, $state['missing']);
        $this->assertSame(4, $this->db->table('comp_entry_photos')->countAllResults());
        $this->assertSame('No', $this->db->table('comp_entries')->get()->getRow('gallery_hide'));
        $second = $this->runScan();
        $this->assertSame(4, $second['cached']);
        $this->assertSame(0, $second['copied']);
    }

    public function testNeverUsesAnotherSubmissionsImageAsDonor(): void
    {
        $this->photo(1, '/uploads/2020/mobile/one.png');
        $this->photo(2, '/uploads/2020/mobile/other.png');
        $this->image('/uploads/2020/mobile/other.png');
        $state = $this->runScan();
        $this->assertSame(1, $state['missing']);
        $this->assertSame('Yes', $this->db->table('comp_entries')->where('entry_id', 'e1')->get()->getRow('gallery_hide'));
        $this->assertSame('/uploads/2020/mobile/one.png', $this->db->table('comp_entry_photos')->where('entry_photo_id', 1)->get()->getRow('entry_photo'));
    }
}

