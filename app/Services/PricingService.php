<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Accounts\UserPricingModel;
use App\Models\Admin\CompetitionModel;

class PricingService
{
    public function pricesFor(int $compId, int $userTypeId): array
    {
        $comp = (new CompetitionModel())->asArray()->find($compId);
        if (!$comp) {
            return [];
        }

        $pricing = (new UserPricingModel())->forType($userTypeId);
        $isStudent = strtoupper((string)($pricing['user_type_pricing'] ?? 'PRO')) === 'STUDENT';
        $prefix = $isStudent ? 'student_' : 'pro_';

        return [
            'early' => $this->money($comp[$prefix . 'early_reg_price'] ?? 0),
            'regular' => $this->money($comp[$prefix . 'regular_reg_price'] ?? 0),
            'late' => $this->money($comp[$prefix . 'late_reg_price'] ?? 0),
            'finalist' => $this->money($comp[$prefix . 'finalist_price'] ?? 0),
            'winner' => $this->money($comp[$prefix . 'winner_price'] ?? 0),
            'series' => $this->money($comp[$prefix . 'series_price'] ?? 0),
            'trophy' => $this->money($comp['trophy_price'] ?? 0),
            'additional_trophy' => $this->money($comp['additional_trophy_price'] ?? 0),
        ];
    }

    public function currentRegTier(int $compId): ?string
    {
        $comp = (new CompetitionModel())->asArray()->find($compId);
        if (!$comp) {
            return null;
        }

        $now = time();
        $regularOpen = strtotime((string)($comp['comp_regular_reg_open'] ?? ''));
        $lateOpen = strtotime((string)($comp['comp_late_reg_open'] ?? ''));

        if ($regularOpen === false || $lateOpen === false) {
            return 'regular';
        }
        if ($now < $regularOpen) {
            return 'early';
        }
        if ($now < $lateOpen) {
            return 'regular';
        }

        return 'late';
    }

    public function applyCoupon(float $subtotal, ?string $couponType = null, $couponAmount = null): array
    {
        $subtotal = max(0, round($subtotal, 2));
        $discount = 0.0;

        $type = strtoupper(trim((string)$couponType));
        $amount = $this->money($couponAmount);
        if ($type === 'DOLLAR') {
            $discount = min($subtotal, $amount);
        } elseif ($type === 'PERCENTAGE') {
            $pct = min(100, max(0, $amount));
            $discount = round($subtotal * ($pct / 100), 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => round($discount, 2),
            'total' => round(max(0, $subtotal - $discount), 2),
        ];
    }

    private function money($raw): float
    {
        if (is_int($raw) || is_float($raw)) {
            return round((float)$raw, 2);
        }

        return round((float)preg_replace('/[^0-9.\-]/', '', (string)$raw), 2);
    }
}
