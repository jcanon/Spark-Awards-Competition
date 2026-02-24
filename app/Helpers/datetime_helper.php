<?php

declare(strict_types=1);

if (!function_exists('format_datetime_ui')) {
    function format_datetime_ui(?string $value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $fallback !== '' ? $fallback : $value;
        }

        return date('m/d/Y h:i A', $ts);
    }
}
