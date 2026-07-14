<?php

declare(strict_types=1);

if (!function_exists('entry_status_pill')) {
    /**
     * Build status pill metadata from an entry row/object or explicit values.
     *
     * @param array|object|string|null $entryOrStatus Entry row/object or status text.
     * @param string|null $winnerLevel Winner level label when passing explicit status.
     *
     * @return array{0:string,1:string,2:string} [label, cssClass, iconClass]
     */
    function entry_status_pill($entryOrStatus, ?string $winnerLevel = null): array
    {
        $status = '';
        $level = $winnerLevel ?? '';

        if (is_array($entryOrStatus)) {
            $status = trim((string)($entryOrStatus['entry_status'] ?? ''));
            if ($winnerLevel === null) {
                $level = trim((string)($entryOrStatus['winner_level_name'] ?? ''));
            }
        } elseif (is_object($entryOrStatus)) {
            $status = trim((string)($entryOrStatus->entry_status ?? ''));
            if ($winnerLevel === null) {
                $level = trim((string)($entryOrStatus->winner_level_name ?? ''));
            }
        } else {
            $status = trim((string)$entryOrStatus);
            $level = trim((string)$level);
        }

        $label = $status;
        $class = 'status-draft';
        $icon = '';

        if (strcasecmp($status, 'Entrant') === 0) {
            $class = 'status-entrant';
        } elseif (strcasecmp($status, 'Finalist') === 0) {
            $class = 'status-finalist';
        } elseif (strcasecmp($status, 'Winner') === 0) {
            $class = 'status-winner';
            if ($level !== '') {
                $label .= ': ' . $level;
                $icon = 'fas fa-medal';
            }

            $class = match (strtolower($level)) {
                'platinum' => 'winner-platinum',
                'gold' => 'winner-gold',
                'silver' => 'winner-silver',
                'bronze' => 'winner-bronze',
                default => $class,
            };
        }

        return [$label, $class, $icon];
    }
}

if (!function_exists('entry_status_bulk_actions')) {
    /**
     * Shared entry status bulk actions used by admin grids.
     *
     * @return array<string,string> Action value => label
     */
    function entry_status_bulk_actions(): array
    {
        return [
            'non_finalist' => 'Change Status - Non-Finalist (Entrant)',
            'finalist' => 'Change Status - Finalist',
            'winner_platinum' => 'Change Status - Winner: Platinum',
            'winner_gold' => 'Change Status - Winner: Gold',
            'winner_silver' => 'Change Status - Winner: Silver',
            'winner_bronze' => 'Change Status - Winner: Bronze',
        ];
    }
}
