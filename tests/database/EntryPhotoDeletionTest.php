<?php

use App\Services\EntryPhotoService;
use CodeIgniter\Test\CIUnitTestCase;

final class EntryPhotoDeletionTest extends CIUnitTestCase
{
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = db_connect();
        $this->db->query('CREATE TABLE ' . $this->db->prefixTable('comp_entry_photos') . ' (
            entry_photo_id INTEGER PRIMARY KEY, entry_photo VARCHAR(200)
        )');
        $this->filename = 'photo-deletion-test-' . bin2hex(random_bytes(8)) . '.jpg';
        $this->db->table('comp_entry_photos')->insert([
            'entry_photo_id' => 1,
            'entry_photo' => '/' . $this->filename,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([FCPATH . $this->filename, WRITEPATH . $this->filename, FCPATH . 'trash/' . $this->filename] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->db->query('DROP TABLE ' . $this->db->prefixTable('comp_entry_photos'));
        parent::tearDown();
    }

    public function testMissingImageStillDeletesRecordWithoutFilesystemWarnings(): void
    {
        // Surface filesystem warnings even when the operation uses @ suppression.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $this->assertTrue((new EntryPhotoService())->deletePhotoWithFile(1));
        } finally {
            restore_error_handler();
        }
        $this->assertSame(0, $this->db->table('comp_entry_photos')->countAllResults());
    }

    public function testExistingPublicImageMovesToTrashAndDeletesRecord(): void
    {
        $this->assertExistingImageDeletion(FCPATH);
    }

    public function testExistingWritableImageMovesToTrashAndDeletesRecord(): void
    {
        $this->assertExistingImageDeletion(WRITEPATH);
    }

    private function assertExistingImageDeletion(string $directory): void
    {
        file_put_contents($directory . $this->filename, 'test image');
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $this->assertTrue((new EntryPhotoService())->deletePhotoWithFile(1));
        } finally {
            restore_error_handler();
        }
        clearstatcache();
        $this->assertFileDoesNotExist($directory . $this->filename);
        $this->assertSame('test image', file_get_contents(FCPATH . 'trash/' . $this->filename));
        $this->assertSame(0, $this->db->table('comp_entry_photos')->countAllResults());
    }

    public function testMissingRecordReturnsFalse(): void
    {
        $this->assertFalse((new EntryPhotoService())->deletePhotoWithFile(999));
        $this->assertSame(1, $this->db->table('comp_entry_photos')->countAllResults());
    }
}
