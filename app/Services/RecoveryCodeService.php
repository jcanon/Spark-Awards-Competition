<?php

namespace App\Services;

use App\Models\Auth\RecoveryCodeModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

class RecoveryCodeService
{
    private RecoveryCodeModel $model;
    private BaseConnection $db;

    public function __construct(?RecoveryCodeModel $model = null)
    {
        $this->model = $model ?? new RecoveryCodeModel();
        $this->db = db_connect();
    }

    public function hasActiveCodes(string $userId): bool
    {
        return $this->countRemainingCodes($userId) > 0;
    }

    public function countRemainingCodes(string $userId): int
    {
        $userId = trim($userId);
        if ($userId === '') {
            return 0;
        }

        return (int)$this->model
            ->where('user_id', $userId)
            ->where('used_at', null)
            ->where('expires_at >=', Time::now()->toDateTimeString())
            ->countAllResults();
    }

    /**
     * Rotates recovery codes and returns plaintext codes for one-time display.
     */
    public function regenerateCodes(string $userId, int $count = 10, int $ttlMonths = 12): array
    {
        $userId = trim($userId);
        if ($userId === '') {
            return [];
        }

        $count = max(4, min(20, $count));
        $expiresAt = Time::now()->addMonths($ttlMonths)->toDateTimeString();
        $createdAt = Time::now()->toDateTimeString();

        $plain = [];
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
            $plain[] = $code;
            $rows[] = [
                'user_id' => $userId,
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
                'used_at' => null,
                'expires_at' => $expiresAt,
                'created_at' => $createdAt,
            ];
        }

        $this->db->transStart();
        $this->db->table('comp_user_recovery_codes')
            ->where('user_id', $userId)
            ->delete();
        $this->db->table('comp_user_recovery_codes')
            ->insertBatch($rows);
        $this->db->transComplete();

        return $this->db->transStatus() ? $plain : [];
    }

    /**
     * Verifies and atomically consumes one recovery code.
     */
    public function verifyAndConsume(string $userId, string $inputCode): bool
    {
        $userId = trim($userId);
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', trim($inputCode)));
        if ($userId === '' || $normalized === '') {
            return false;
        }

        $rows = $this->model
            ->where('user_id', $userId)
            ->where('used_at', null)
            ->where('expires_at >=', Time::now()->toDateTimeString())
            ->findAll();
        if (!$rows) {
            return false;
        }

        $matchedId = null;
        foreach ($rows as $row) {
            $hash = (string)($row['code_hash'] ?? '');
            if ($hash !== '' && password_verify($normalized, $hash)) {
                $matchedId = (int)$row['id'];
                break;
            }
        }
        if ($matchedId === null) {
            return false;
        }

        $affected = $this->db->table('comp_user_recovery_codes')
            ->where('id', $matchedId)
            ->where('user_id', $userId)
            ->where('used_at', null)
            ->update(['used_at' => Time::now()->toDateTimeString()]);

        return (bool)$affected;
    }
}
