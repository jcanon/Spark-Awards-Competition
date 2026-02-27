<?php

declare(strict_types=1);

if (!function_exists('certificate_request_status_pill')) {
    /**
     * Build certificate-request status pill metadata.
     *
     * @return array{0:string,1:string,2:string} [label, cssClass, statusKey]
     */
    function certificate_request_status_pill(?string $status, bool $defaultPending = false): array
    {
        $label = trim((string)$status);
        if ($label === '') {
            $label = $defaultPending ? 'Pending' : 'Not Requested';
        }

        $key = strtolower(str_replace(' ', '-', $label));
        $class = match ($key) {
            'pending' => 'request-pending',
            'in-review' => 'request-in-review',
            'quoted' => 'request-quoted',
            'paid' => 'request-paid',
            'in-production' => 'request-in-production',
            'shipped' => 'request-shipped',
            'completed' => 'request-completed',
            'cancelled' => 'request-cancelled',
            default => 'request-not-requested',
        };

        return [$label, $class, $key];
    }
}
