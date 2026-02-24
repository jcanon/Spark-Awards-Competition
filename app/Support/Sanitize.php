<?php

namespace App\Support;

final class Sanitize
{
    public static function mask(?string $v, int $show = 4): string
    {
        if ($v === null) {
            return '';
        }
        $len = strlen($v);
        if ($len <= $show) {
            return str_repeat('*', $len);
        }
        return str_repeat('*', max(0, $len - $show)) . substr($v, -$show);
    }
}
