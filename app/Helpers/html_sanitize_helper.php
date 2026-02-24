<?php

declare(strict_types=1);

if (!function_exists('sanitize_receipt_html')) {
    function sanitize_receipt_html(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        $value = trim($html);
        if ($value === '') {
            return '';
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value, '<br><p><strong><em><b><i><u><ul><ol><li><a>');

        // Remove inline event handlers and style attributes.
        $value = preg_replace('/\s+on[a-z]+\s*=\s*("|\').*?\1/iu', '', $value) ?? $value;
        $value = preg_replace('/\s+style\s*=\s*("|\').*?\1/iu', '', $value) ?? $value;

        // Neutralize javascript: and data: URLs in href attributes.
        $value = preg_replace_callback(
            '/<a\s+([^>]*?)href\s*=\s*("|\')(.*?)\2([^>]*)>/iu',
            static function (array $m): string {
                $href = trim((string)$m[3]);
                if (preg_match('/^(javascript|data):/iu', $href) === 1) {
                    $href = '#';
                }

                return '<a ' . trim((string)$m[1]) . 'href="' . esc($href, 'attr') . '" ' . trim((string)$m[4]) . '>';
            },
            $value
        ) ?? $value;

        return $value;
    }
}
