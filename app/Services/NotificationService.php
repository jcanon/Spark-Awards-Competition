<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin\NotificationModel;

class NotificationService
{
    private ?bool $schemaReady = null;

    public function getTopbarData(string $role, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        if (!$this->isSchemaReady()) {
            $demo = $this->demoNotifications();
            return [
                'count' => count($demo),
                'items' => array_slice($demo, 0, $limit),
                'is_demo' => true,
            ];
        }

        $count = $this->countActiveForRole($role);
        $items = $this->listActiveForRole($role, $limit);
        return [
            'count' => $count,
            'items' => $items,
            'is_demo' => false,
        ];
    }

    public function listAdmin(): array
    {
        if (!$this->isSchemaReady()) {
            return [];
        }

        return (new NotificationModel())
            ->orderBy('is_active', 'DESC')
            ->orderBy('sort_order', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function findAdmin(int $id): ?array
    {
        if (!$this->isSchemaReady() || $id <= 0) {
            return null;
        }

        $row = (new NotificationModel())->find($id);
        return is_array($row) ? $row : null;
    }

    public function createAdmin(array $form, string $createdBy): int
    {
        if (!$this->isSchemaReady()) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => trim((string)($form['title'] ?? '')),
            'message' => trim((string)($form['message'] ?? '')),
            'level' => $this->normalizeLevel((string)($form['level'] ?? 'info')),
            'audience' => $this->normalizeAudience((string)($form['audience'] ?? 'all')),
            'link_url' => $this->normalizeNullableString($form['link_url'] ?? null),
            'link_text' => $this->normalizeNullableString($form['link_text'] ?? null),
            'starts_at' => $this->normalizeDateTime($form['starts_at'] ?? null),
            'ends_at' => $this->normalizeDateTime($form['ends_at'] ?? null),
            'is_active' => (int)($form['is_active'] ?? 0) === 1 ? 1 : 0,
            'sort_order' => (int)($form['sort_order'] ?? 0),
            'created_by' => $createdBy !== '' ? $createdBy : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $model = new NotificationModel();
        $ok = $model->insert($payload, false);
        if ($ok === false) {
            return 0;
        }

        return (int)$model->getInsertID();
    }

    public function updateAdmin(int $id, array $form): bool
    {
        if (!$this->isSchemaReady() || $id <= 0) {
            return false;
        }

        $payload = [
            'title' => trim((string)($form['title'] ?? '')),
            'message' => trim((string)($form['message'] ?? '')),
            'level' => $this->normalizeLevel((string)($form['level'] ?? 'info')),
            'audience' => $this->normalizeAudience((string)($form['audience'] ?? 'all')),
            'link_url' => $this->normalizeNullableString($form['link_url'] ?? null),
            'link_text' => $this->normalizeNullableString($form['link_text'] ?? null),
            'starts_at' => $this->normalizeDateTime($form['starts_at'] ?? null),
            'ends_at' => $this->normalizeDateTime($form['ends_at'] ?? null),
            'is_active' => (int)($form['is_active'] ?? 0) === 1 ? 1 : 0,
            'sort_order' => (int)($form['sort_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return (bool)(new NotificationModel())->update($id, $payload);
    }

    public function deleteAdmin(int $id): bool
    {
        if (!$this->isSchemaReady() || $id <= 0) {
            return false;
        }
        return (bool)(new NotificationModel())->delete($id);
    }

    public function isSchemaReady(): bool
    {
        if ($this->schemaReady !== null) {
            return $this->schemaReady;
        }

        $db = db_connect();
        $this->schemaReady = $db->tableExists('comp_notifications');
        return $this->schemaReady;
    }

    private function listActiveForRole(string $role, int $limit): array
    {
        $allowed = $this->allowedAudiencesForRole($role);
        $now = date('Y-m-d H:i:s');

        return (new NotificationModel())
            ->where('is_active', 1)
            ->whereIn('audience', $allowed)
            ->groupStart()
                ->where('starts_at IS NULL', null, false)
                ->orWhere('starts_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('ends_at IS NULL', null, false)
                ->orWhere('ends_at >=', $now)
            ->groupEnd()
            ->orderBy('sort_order', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    private function countActiveForRole(string $role): int
    {
        $allowed = $this->allowedAudiencesForRole($role);
        $now = date('Y-m-d H:i:s');

        return (int)(new NotificationModel())
            ->where('is_active', 1)
            ->whereIn('audience', $allowed)
            ->groupStart()
                ->where('starts_at IS NULL', null, false)
                ->orWhere('starts_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('ends_at IS NULL', null, false)
                ->orWhere('ends_at >=', $now)
            ->groupEnd()
            ->countAllResults();
    }

    private function allowedAudiencesForRole(string $role): array
    {
        $role = strtolower(trim($role));
        if (!in_array($role, ['admin', 'editor', 'judge', 'user'], true)) {
            $role = 'user';
        }

        if ($role === 'judge') {
            return ['all', 'judge', 'user'];
        }

        return ['all', $role];
    }

    private function normalizeDateTime($value): ?string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function normalizeNullableString($value): ?string
    {
        $raw = trim((string)$value);
        return $raw === '' ? null : $raw;
    }

    private function normalizeLevel(string $level): string
    {
        $level = strtolower(trim($level));
        return in_array($level, ['info', 'success', 'warning', 'danger'], true) ? $level : 'info';
    }

    private function normalizeAudience(string $audience): string
    {
        $audience = strtolower(trim($audience));
        return in_array($audience, ['all', 'admin', 'editor', 'judge', 'user'], true) ? $audience : 'all';
    }

    private function demoNotifications(): array
    {
        return [
            [
                'title' => 'Scheduled Maintenance',
                'message' => 'System maintenance is scheduled for Sunday at 11:00 PM ET.',
                'level' => 'info',
                'link_url' => null,
                'link_text' => null,
                'starts_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'Payment Sync Healthy',
                'message' => 'Phase 1 payment confirmations are syncing normally.',
                'level' => 'success',
                'link_url' => null,
                'link_text' => null,
                'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'title' => 'Deadline Reminder',
                'message' => 'Review submission deadlines for currently active competitions.',
                'level' => 'warning',
                'link_url' => null,
                'link_text' => null,
                'starts_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
        ];
    }
}
