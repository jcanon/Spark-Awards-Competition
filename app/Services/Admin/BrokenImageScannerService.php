<?php

declare(strict_types=1);

namespace App\Services\Admin;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/** Durable, bounded two-pass scanner. Only unchanged, verified images are cached. */
class BrokenImageScannerService
{
    private BaseConnection $db;
    private string $root;
    private string $lockPath;

    public function __construct(?BaseConnection $db = null, ?string $root = null, ?string $lockPath = null)
    {
        $this->db = $db ?? db_connect();
        $this->root = rtrim(str_replace('\\', '/', $root ?? FCPATH), '/');
        $this->lockPath = $lockPath ?? WRITEPATH . 'image-scanner.lock';
    }

    public function ready(): bool
    {
        return $this->db->tableExists('image_scan_job');
    }

    private function job(): array
    {
        $row = $this->db->table('image_scan_job')->where('id', 1)->get()->getRowArray();
        return $row ? json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR) : ['phase' => 'idle'];
    }

    public function status(): array
    {
        $job = $this->job();
        unset($job['directories']);
        $job['results'] = empty($job['run_id']) ? [] : $this->db->table('image_scan_items')
            ->select('photo_id, status, message')->where('run_id', $job['run_id'])
            ->whereIn('status', ['repaired', 'copied', 'optional', 'missing', 'review'])->orderBy('photo_id', 'DESC')->limit(100)->get()->getResultArray();
        return $job;
    }

    public function advance(bool $start = false): array
    {
        $lock = fopen($this->lockPath, 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) { fclose($lock); }
            throw new RuntimeException('Another scanner batch is running. Try again shortly.');
        }
        try {
            $this->db->transException(true)->transBegin();
            $job = $this->job();
            $job += ['copied' => 0, 'pending' => 0, 'optional' => 0, 'fill_cursor' => '', 'filled_entries' => 0];
            if ($start && in_array($job['phase'], ['idle', 'complete'], true)) {
                $this->db->table('image_scan_files')->emptyTable();
                $job = [
                    'run_id' => bin2hex(random_bytes(16)), 'phase' => 'scan', 'cursor' => 0,
                    'max_id' => (int) ($this->photos()->selectMax('p.entry_photo_id', 'max_id')->get()->getRow('max_id') ?? 0),
                    'total' => $this->photos()->countAllResults(), 'checked' => 0, 'cached' => 0,
                    'clean' => 0, 'queued' => 0, 'indexed' => 0, 'resolved' => 0,
                    'repaired' => 0, 'missing' => 0, 'review' => 0, 'directories' => [],
                    'copied' => 0, 'pending' => 0, 'optional' => 0, 'fill_cursor' => '', 'filled_entries' => 0,
                    'activity' => 'Checking uploaded images across every submission and year.',
                ];
            } elseif ($job['phase'] === 'scan') {
                $this->scan($job);
            } elseif ($job['phase'] === 'index') {
                $this->index($job);
            } elseif ($job['phase'] === 'repair') {
                $this->repair($job);
            } elseif ($job['phase'] === 'fill') {
                $this->fillRequiredPhotos($job);
            }
            $payload = json_encode($job, JSON_THROW_ON_ERROR);
            if ($this->db->table('image_scan_job')->where('id', 1)->countAllResults()) {
                $this->db->table('image_scan_job')->where('id', 1)->update(['payload' => $payload]);
            } else {
                $this->db->table('image_scan_job')->insert(['id' => 1, 'payload' => $payload]);
            }
            if (!$this->db->transStatus()) { throw new RuntimeException('Could not save scanner progress. Resume to retry this batch.'); }
            $this->db->transCommit();
            return $this->status();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function photos()
    {
        return $this->db->table('comp_entry_photos p')
            ->join('comp_entries e', 'e.entry_id = p.entry_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id', 'left')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id', 'left');
    }

    private function rowQuery()
    {
        return $this->photos()->select('p.*, e.photo_id, e.comp_id, c.comp_year, t.comp_type_name');
    }

    private function fingerprint(array $row): string
    {
        return hash('sha256', json_encode(array_intersect_key($row, array_flip([
            'entry_photo', 'entry_photo_res', 'entry_photo_order', 'entry_id', 'photo_id', 'comp_id', 'comp_year', 'comp_type_name',
        ])), JSON_THROW_ON_ERROR));
    }

    private function saveItem(array $row, array $job, string $status, string $message = ''): void
    {
        $data = ['fingerprint' => $this->fingerprint($row), 'status' => $status,
            'run_id' => $job['run_id'], 'payload' => json_encode($row, JSON_THROW_ON_ERROR), 'message' => $message];
        $id = (int) $row['entry_photo_id'];
        if ($this->db->table('image_scan_items')->where('photo_id', $id)->countAllResults()) {
            $this->db->table('image_scan_items')->where('photo_id', $id)->update($data);
        } else {
            $this->db->table('image_scan_items')->insert(['photo_id' => $id] + $data);
        }
    }

    private function scan(array &$job): void
    {
        $deadline = microtime(true) + 3;
        $rows = $this->rowQuery()->where('p.entry_photo_id >', $job['cursor'])
            ->where('p.entry_photo_id <=', $job['max_id'])->orderBy('p.entry_photo_id')->limit(100)->get()->getResultArray();
        foreach ($rows as $row) {
            $id = (int) $row['entry_photo_id'];
            $job['cursor'] = $id;
            $job['checked']++;
            // Certificate PDFs are documents; their uploaded preview images are checked normally.
            $raw = trim((string) $row['entry_photo']);
            if (($raw === '' && strtoupper((string) $row['entry_photo_res']) === 'PDF') || preg_match('/\.pdf(?:\?|$)/i', $raw)) {
                continue;
            }
            $cached = $this->db->table('image_scan_items')->where('photo_id', $id)->get()->getRowArray();
            if ($cached && in_array($cached['status'], ['clean', 'repaired', 'copied'], true) && $cached['fingerprint'] === $this->fingerprint($row)) {
                $job['cached']++;
                continue;
            }
            $path = $this->localPath($raw);
            $remote = $path === null && preg_match('#^https?://#i', $raw) ? $this->remoteImage($raw) : false;
            if ($remote === null) {
                $this->saveItem($row, $job, 'review', 'Submission ' . $row['entry_id'] . ': Remote image could not be verified. Gallery visibility unchanged.');
                $job['review']++;
            } elseif (($path !== null && $this->isImage($path)) || $remote) {
                $this->saveItem($row, $job, 'clean');
                $job['clean']++;
            } else {
                $this->saveItem($row, $job, 'queued');
                $job['queued']++;
            }
            if (microtime(true) >= $deadline) { break; }
        }
        $job['activity'] = 'Checking image records: ' . $job['checked'] . ' of ' . $job['total'] . '.';
        if (!$rows || $job['cursor'] >= $job['max_id']) {
            $queue = $job['queued'] > 0;
            $years = $this->photos()->select('c.comp_year')->distinct()
                ->join('image_scan_items i', 'i.photo_id = p.entry_photo_id')
                ->where('i.run_id', $job['run_id'])->where('i.status', 'queued')->get()->getResultArray();
            foreach ($years as $yearRow) {
                $year = (int) $yearRow['comp_year'];
                if ($year <= 0) { continue; }
                $job['directories'][] = ['path' => '/uploads/' . $year, 'year' => $year, 'offset' => 0];
            }
            if ($queue) { $job['directories'][] = ['path' => '/trash', 'year' => 0, 'offset' => 0]; }
            $job['phase'] = $queue ? 'index' : 'fill';
            $job['activity'] = $queue ? 'Initial scan complete. Searching year folders and trash.' : 'Checking required Photos 1–3 for empty slots.';
        }
    }

    private function index(array &$job): void
    {
        $budget = 1000;
        $deadline = microtime(true) + 3;
        while ($budget > 0 && microtime(true) < $deadline && $job['directories']) {
            $dir = array_pop($job['directories']);
            $abs = $this->localPath($dir['path']);
            if ($abs === null) { throw new RuntimeException('Unsafe search directory: ' . $dir['path']); }
            if (!is_dir($abs)) { continue; }
            $handle = @opendir($abs);
            if (!$handle) { throw new RuntimeException('Cannot read ' . $dir['path'] . '. Restore access and resume; no missing files have been excluded.'); }
            $position = 0;
            while ($position < $dir['offset'] && readdir($handle) !== false) { $position++; }
            while ($budget > 0 && microtime(true) < $deadline && ($name = readdir($handle)) !== false) {
                $dir['offset']++;
                $budget--;
                if ($name === '.' || $name === '..' || is_link($abs . '/' . $name)) { continue; }
                $path = $dir['path'] . '/' . $name;
                if (is_dir($abs . '/' . $name)) {
                    $job['directories'][] = ['path' => $path, 'year' => $dir['year'], 'offset' => 0];
                } elseif (is_file($abs . '/' . $name)) {
                    $this->db->table('image_scan_files')->insert(['year' => $dir['year'], 'name' => $name, 'path' => $path]);
                    $job['indexed']++;
                }
            }
            closedir($handle);
            if ($budget === 0 || microtime(true) >= $deadline) { $job['directories'][] = $dir; }
            $job['activity'] = 'Searching ' . $dir['path'] . ' — ' . $job['indexed'] . ' files indexed.';
        }
        if (!$job['directories']) {
            $job['phase'] = 'repair';
            $job['activity'] = 'Search complete. Restoring missing images to their current competition folders.';
        }
    }

    private function repair(array &$job): void
    {
        $deadline = microtime(true) + 3;
        $items = $this->db->table('image_scan_items')->where('run_id', $job['run_id'])->where('status', 'queued')->limit(20)->get()->getResultArray();
        foreach ($items as $item) {
            $row = $this->rowQuery()->where('p.entry_photo_id', $item['photo_id'])->get()->getRowArray();
            $job['resolved']++;
            if (!$row || $this->fingerprint($row) !== $item['fingerprint']) {
                $this->db->table('image_scan_items')->where('photo_id', $item['photo_id'])->update(['status' => 'review', 'message' => 'Submission changed during scanning. Run another scan.']);
                $job['review']++;
                continue;
            }
            try {
                [$status, $message] = $this->restore($row);
            } catch (\Throwable $e) {
                $status = 'review';
                $message = $e->getMessage();
            }
            $job[$status]++;
            $this->saveItem($row, $job, $status, 'Submission ' . $row['entry_id'] . ': ' . $message);
            if (microtime(true) >= $deadline) { break; }
        }
        $job['activity'] = 'Repairing queued images: ' . $job['resolved'] . ' of ' . $job['queued'] . '.';
        if (!$this->db->table('image_scan_items')->where('run_id', $job['run_id'])->where('status', 'queued')->countAllResults()) {
            $job['phase'] = 'fill';
            $job['activity'] = 'Original recovery complete. Filling missing Photos 1–3 from the same submission.';
        }
    }

    private function restore(array &$row): array
    {
        $raw = trim((string) $row['entry_photo']);
        $current = $this->localPath($raw);
        if ($current !== null && $this->isImage($current)) { return ['repaired', 'Image now resolves.']; }
        if ($raw !== '' && $current === null) {
            if (!preg_match('#^https?://#i', $raw) || $this->remoteImage($raw) !== false) {
                return ['review', 'External or unsupported image location requires review; gallery visibility unchanged.'];
            }
        }
        $year = (int) $row['comp_year'];
        $type = str_replace(['/', '\\'], '-', strtolower(trim((string) $row['comp_type_name'])));
        $name = basename(rawurldecode((string) (parse_url($raw, PHP_URL_PATH) ?: $raw)));
        if ($name === '' && strtoupper((string) $row['entry_photo_res']) === 'LOW' && !empty($row['photo_id'])) {
            $name = 'CompPhotoLow_' . (int) $row['entry_photo_order'] . '_' . $row['photo_id'] . '.jpg';
        }
        if ($year <= 0 || $type === '' || $name === '') { return ['review', 'Missing competition or filename metadata.']; }
        $target = '/uploads/' . $year . '/' . $type . '/' . $name;
        $dest = $this->localPath($target);
        if ($dest === null) { return ['review', 'Unsafe destination path.']; }
        if (!$this->isImage($dest)) {
            if (file_exists($dest)) { return ['review', 'Destination exists but is not a readable image; it was not overwritten.']; }
            $matches = $this->db->table('image_scan_files')->whereIn('year', [$year, 0])->where('name', $name)->get()->getResultArray();
            $valid = [];
            foreach ($matches as $match) {
                $source = $this->localPath($match['path']);
                if ($source !== null && $this->isImage($source)) { $valid[$source] = hash_file('sha256', $source); }
            }
            if (count(array_unique($valid)) > 1) { return ['review', 'Different images share this filename. Choose the correct file manually.']; }
            if (!$valid) {
                return $this->requiredPhoto($row)
                    ? ['pending', 'Original not found. Waiting for a working image from this submission.']
                    : ['optional', 'Optional image not found. Gallery visibility unchanged.'];
            }
            $source = array_key_first($valid);
            if (!is_dir(dirname($dest)) && !@mkdir(dirname($dest), 0775, true) && !is_dir(dirname($dest))) {
                throw new RuntimeException('Cannot create the destination folder.');
            }
            // Exclusive creation prevents replacing a concurrent upload. Keep shared sources intact.
            $output = @fopen($dest, 'x+b');
            if (!$output) { throw new RuntimeException('Cannot create image; destination may have changed.'); }
            $input = @fopen($source, 'rb');
            $copied = $input ? stream_copy_to_stream($input, $output) : false;
            if ($input) { fclose($input); }
            fclose($output);
            if ($copied === false || hash_file('sha256', $dest) !== $valid[$source]) {
                @unlink($dest);
                throw new RuntimeException('Image copy failed verification. Source retained.');
            }
            // Conservative filename matching also covers absolute and URL-encoded references.
            $shared = $this->db->table('comp_entry_photos')->where('entry_photo_id !=', $row['entry_photo_id'])
                ->groupStart()->like('entry_photo', $name)->orLike('entry_photo', rawurlencode($name))
                ->groupEnd()->countAllResults() > 0;
        }
        if (!$this->db->table('comp_entry_photos')->where('entry_photo_id', $row['entry_photo_id'])
            ->where('entry_photo', $row['entry_photo'])->update(['entry_photo' => $target])) {
            throw new RuntimeException('Could not save image path. Source retained.');
        }
        $saved = $this->db->table('comp_entry_photos')->where('entry_photo_id', $row['entry_photo_id'])->get()->getRowArray();
        if (!$saved || $saved['entry_photo'] !== $target) {
            throw new RuntimeException('Image changed during repair. Source retained; run another scan.');
        }
        if (isset($source) && !$shared) { @unlink($source); }
        $row['entry_photo'] = $target;
        return ['repaired', 'Restored ' . $target];
    }

    private function requiredPhoto(array $row): bool
    {
        return strtoupper((string) $row['entry_photo_res']) === 'LOW'
            && (int) $row['entry_photo_order'] >= 1 && (int) $row['entry_photo_order'] <= 3;
    }

    /** Run only after all original images have been recovered, regardless of row order. */
    private function fillRequiredPhotos(array &$job): void
    {
        $entries = $this->photos()->select('e.entry_id')->distinct()
            ->where('e.entry_id >', $job['fill_cursor'])->orderBy('e.entry_id')->limit(20)->get()->getResultArray();
        $deadline = microtime(true) + 3;
        foreach ($entries as $entry) {
            $rows = $this->rowQuery()->where('p.entry_id', $entry['entry_id'])
                ->where('UPPER(p.entry_photo_res)', 'LOW')->orderBy('p.entry_photo_order')->orderBy('p.entry_photo_id')->get()->getResultArray();
            $slots = [];
            $candidates = [];
            $missing = [];
            $uncertain = false;
            foreach ($rows as $row) {
                $slot = (int) $row['entry_photo_order'];
                $slots[$slot] = $row;
                $item = $this->db->table('image_scan_items')->where('photo_id', $row['entry_photo_id'])->get()->getRowArray();
                // Concurrent edits and review conflicts must not be replaced automatically.
                if (!$item || $item['fingerprint'] !== $this->fingerprint($row)) { $uncertain = true; continue; }
                if (in_array($item['status'], ['clean', 'repaired', 'copied'], true)) {
                    $candidates[] = $row;
                } elseif ($this->requiredPhoto($row) && in_array($item['status'], ['pending', 'missing'], true)) {
                    $missing[$slot] = $row;
                } elseif ($item['status'] === 'review') {
                    $uncertain = true;
                }
            }
            $emptySlots = array_diff([1, 2, 3], array_keys($slots));
            if ($missing || $emptySlots) {
                $donor = null;
                foreach ($candidates as $candidate) {
                    $path = $this->localPath((string) $candidate['entry_photo']);
                    if ($path !== null && $this->isImage($path)) { $donor = $candidate; break; }
                }
                if ($donor) {
                    foreach ($emptySlots as $slot) {
                        $missing[$slot] = array_merge($donor, ['entry_photo_id' => 0, 'entry_photo_order' => $slot, 'entry_photo' => '']);
                    }
                }
                foreach ($missing as $slot => $row) {
                    try {
                        if ($donor) {
                            $this->copyRequiredPhoto($row, $donor);
                            $status = 'copied';
                            $message = 'Photo ' . $slot . ' filled with a copy of Photo ' . $donor['entry_photo_order'] . ' from this submission.';
                        } elseif ($candidates) {
                            $status = 'review';
                            $message = 'Working image cannot be copied locally. Gallery visibility unchanged.';
                        } else {
                            // Uncertain images may become usable after manual review; do not hide them.
                            $status = $uncertain ? 'review' : 'missing';
                            $message = $uncertain ? 'Other images need review. Gallery visibility unchanged.' : 'No working submission image remains. Hidden from gallery.';
                            if (!$uncertain && !$this->db->table('comp_entries')->where('entry_id', $row['entry_id'])->update(['gallery_hide' => 'Yes'])) {
                                throw new RuntimeException('Could not save gallery exclusion.');
                            }
                        }
                    } catch (\Throwable $e) {
                        $status = 'review';
                        $message = $e->getMessage();
                    }
                    $job[$status]++;
                    if ((int) $row['entry_photo_id'] > 0) {
                        $this->saveItem($row, $job, $status, 'Submission ' . $row['entry_id'] . ': ' . $message);
                    }
                }
            }
            $job['fill_cursor'] = $entry['entry_id'];
            $job['filled_entries']++;
            $job['activity'] = 'Filling required Photos 1–3: ' . $job['filled_entries'] . ' submissions checked; ' . $job['copied'] . ' copies saved.';
            if (microtime(true) >= $deadline) { return; }
        }
        if (count($entries) < 20) {
            $job['phase'] = 'complete';
            $job['activity'] = 'Scan complete. Original recovery, required-photo copies, and gallery decisions saved.';
        }
    }

    private function copyRequiredPhoto(array &$row, array $donor): void
    {
        $source = $this->localPath((string) $donor['entry_photo']);
        $year = (int) $row['comp_year'];
        $type = str_replace(['/', '\\'], '-', strtolower(trim((string) $row['comp_type_name'])));
        if ($source === null || !$this->isImage($source) || $year <= 0 || $type === '') {
            throw new RuntimeException('Cannot copy this submission image.');
        }
        // Separate deterministic filename preserves the original, even if the old target is corrupt.
        $extension = image_type_to_extension((int) getimagesize($source)[2], false);
        $target = '/uploads/' . $year . '/' . $type . '/ScannerCopy_' . (int) $row['entry_photo_order']
            . '_' . hash('sha256', (string) $row['entry_id']) . '.' . $extension;
        $dest = $this->localPath($target);
        if ($dest === null) { throw new RuntimeException('Unsafe copy destination.'); }
        if (!is_dir(dirname($dest)) && !@mkdir(dirname($dest), 0775, true) && !is_dir(dirname($dest))) {
            throw new RuntimeException('Cannot create copy destination folder.');
        }
        if (!file_exists($dest)) {
            $out = @fopen($dest, 'x+b');
            $in = @fopen($source, 'rb');
            if (!$out || !$in) {
                if ($out) { fclose($out); @unlink($dest); }
                if ($in) { fclose($in); }
                throw new RuntimeException('Cannot create required-photo copy.');
            }
            $copied = stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
            if ($copied === false || hash_file('sha256', $dest) !== hash_file('sha256', $source)) {
                @unlink($dest);
                throw new RuntimeException('Required-photo copy failed verification.');
            }
        } elseif (!$this->isImage($dest) || hash_file('sha256', $dest) !== hash_file('sha256', $source)) {
            throw new RuntimeException('A different file exists at the copy destination.');
        }
        if ((int) $row['entry_photo_id'] === 0) {
            $this->db->table('comp_entry_photos')->insert([
                'entry_id' => $row['entry_id'], 'entry_photo' => $target, 'entry_photo_res' => 'Low',
                'entry_photo_order' => $row['entry_photo_order'], 'entry_photo_caption' => $donor['entry_photo_caption'] ?? '',
            ]);
            $row['entry_photo_id'] = (int) $this->db->insertID();
        } else {
            $this->db->table('comp_entry_photos')->where('entry_photo_id', $row['entry_photo_id'])
                ->where('entry_photo', $row['entry_photo'])->update(['entry_photo' => $target]);
            if ($this->db->affectedRows() !== 1) { throw new RuntimeException('Photo changed during copying. Run another scan.'); }
        }
        $row['entry_photo'] = $target;
    }

    /** Resolve only this site's uploads/trash, reject traversal and symlink escapes. */
    private function localPath(string $raw): ?string
    {
        $host = parse_url($raw, PHP_URL_HOST);
        if ($host && strcasecmp((string) $host, (string) parse_url(config('App')->baseURL, PHP_URL_HOST)) !== 0) { return null; }
        $path = '/' . ltrim(rawurldecode((string) (parse_url($raw, PHP_URL_PATH) ?: $raw)), '/');
        if (!preg_match('#^/(uploads|trash)(/|$)#', $path) || str_contains($path, '\\') || str_contains($path, "\0")) { return null; }
        foreach (explode('/', $path) as $part) {
            if ($part === '..' || $part === '.' || str_contains($part, ':')) { return null; }
        }
        $abs = $this->root . $path;
        $check = $abs;
        while ($check !== $this->root) {
            if (is_link($check)) { return null; }
            $check = str_replace('\\', '/', dirname($check));
        }
        return $abs;
    }

    private function isImage(string $path): bool
    {
        return is_file($path) && is_readable($path) && @getimagesize($path) !== false;
    }

    /** Bounded remote GET. Pin a public DNS address; never follow redirects into private networks. */
    private function remoteImage(string $url): ?bool
    {
        if (!function_exists('curl_init')) { return null; }
        $parts = parse_url($url);
        if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])) { return null; }
        $host = $parts['host'] ?? '';
        $port = (int) ($parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80));
        if (!in_array($port, [80, 443], true)) { return null; }
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (@gethostbynamel($host) ?: []);
        if (!$addresses) { return null; }
        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { return null; }
        }
        $buffer = '';
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 4,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $addresses[0]],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$buffer): int {
                if (strlen($buffer) + strlen($chunk) > 16 * 1024 * 1024) { return 0; }
                $buffer .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (in_array($status, [404, 410], true)) { return false; }
        if ($ok === false || $status < 200 || $status >= 300) { return null; }
        return @getimagesizefromstring($buffer) !== false;
    }
}
