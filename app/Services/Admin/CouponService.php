<?php

namespace App\Services\Admin;

use App\Models\Admin\CouponModel;
use App\Entities\Admin\Coupon;

/**
 * Entity-first CouponService.
 */
class CouponService
{
    public function createCoupon(array $form): int
    {
        [$start, $end] = $this->normalizeDateRange(
            (string)($form['coupon_start_date'] ?? ''),
            (string)($form['coupon_end_date'] ?? '')
        );

        $data = [
            'coupon_code' => (string)($form['coupon_code'] ?? ''),
            'coupon_type' => (string)($form['coupon_type'] ?? ''),
            'coupon_comp' => isset($form['coupon_comp']) && $form['coupon_comp'] !== '' ? (int)$form['coupon_comp'] : null,
            'coupon_start_date' => $start,
            'coupon_end_date' => $end,
            'coupon_amount' => (string)($form['coupon_amount'] ?? ''),
        ];

        $model = new CouponModel();
        $model->insert($data, false);
        return (int)$model->getInsertID();
    }

    public function updateCoupon(int $couponId, array $form): bool
    {
        [$start, $end] = $this->normalizeDateRange(
            (string)($form['coupon_start_date'] ?? ''),
            (string)($form['coupon_end_date'] ?? '')
        );

        $data = [
            'coupon_code' => (string)($form['coupon_code'] ?? ''),
            'coupon_type' => (string)($form['coupon_type'] ?? ''),
            'coupon_comp' => isset($form['coupon_comp']) && $form['coupon_comp'] !== '' ? (int)$form['coupon_comp'] : null,
            'coupon_start_date' => $start,
            'coupon_end_date' => $end,
            'coupon_amount' => (string)($form['coupon_amount'] ?? ''),
        ];

        return (new CouponModel())->update($couponId, $data);
    }

    public function deleteCoupon(int $couponId): bool
    {
        return (bool)(new CouponModel())->delete($couponId);
    }

    /**
     * Returns array<Coupon> ordered by code.
     */
    public function getAllCoupons(): array
    {
        return (new CouponModel())->orderBy('coupon_code', 'ASC')->findAll();
    }

    /**
     * Returns a Coupon entity or null.
     */
    public function getCouponByID(int $couponId): ?Coupon
    {
        $row = (new CouponModel())->find($couponId);
        return $row ?: null;
    }

    // ---- helpers ----

    private function normalizeDateRange(string $start, string $end): array
    {
        return [$this->toDateTime($start), $this->toDateTime($end)];
    }

    private function toDateTime(string $input): string
    {
        $ts = strtotime($input);
        if ($ts === false) {
            // fallback to now to avoid invalid datetime inserts
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', $ts);
    }
}