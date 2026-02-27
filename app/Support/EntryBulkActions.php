<?php

declare(strict_types=1);

namespace App\Support;

final class EntryBulkActions
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function submissionPayloadMap(): array
    {
        return [
            'gallery_show' => ['gallery_hide' => 'No'],
            'gallery_hide' => ['gallery_hide' => 'Yes'],
            'non_finalist' => ['entry_non_finalist' => 'Yes', 'entry_status' => 'Entrant'],
            'finalist' => ['entry_status' => 'Finalist', 'entry_non_finalist' => 'No'],
            'winner_platinum' => ['entry_status' => 'Winner', 'winner_level' => 1],
            'winner_gold' => ['entry_status' => 'Winner', 'winner_level' => 2],
            'winner_silver' => ['entry_status' => 'Winner', 'winner_level' => 3],
            'winner_bronze' => ['entry_status' => 'Winner', 'winner_level' => 4],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function scoreResultPatchMap(): array
    {
        return [
            'non_finalist' => ['entry_non_finalist' => 'Yes'],
            'finalist' => ['entry_non_finalist' => 'No'],
            'winner_platinum' => ['winner_level' => 1],
            'winner_gold' => ['winner_level' => 2],
            'winner_silver' => ['winner_level' => 3],
            'winner_bronze' => ['winner_level' => 4],
        ];
    }

    /**
     * @return array{0:string,1:array<string,mixed>}|null
     */
    public static function scoreResultStatusAndPatch(string $action): ?array
    {
        $action = trim($action);
        $submissionPayload = self::submissionPayloadMap()[$action] ?? null;
        $scorePatch = self::scoreResultPatchMap()[$action] ?? null;

        if ($submissionPayload === null || $scorePatch === null) {
            return null;
        }

        $status = (string)($submissionPayload['entry_status'] ?? '');
        if ($status === '') {
            return null;
        }

        return [$status, $scorePatch];
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function submissionPayload(string $action): ?array
    {
        return self::submissionPayloadMap()[trim($action)] ?? null;
    }
}
