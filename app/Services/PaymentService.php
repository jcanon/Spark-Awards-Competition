<?php

declare(strict_types=1);

namespace App\Services;

use App\Entities\Payments\EntryPayment;
use App\Models\Entries\UserEntriesModel;
use App\Models\Payments\EntryPaymentModel;
use App\Models\Payments\RetailItemModel;
use App\Models\Payments\RetailItemUserModel;
use RuntimeException;

class PaymentService
{
    private PricingService $pricing;

    public function __construct()
    {
        $this->pricing = new PricingService();
    }

    public function ensurePaymentRow(string $entryId, int $phase): void
    {
        $phase = max(1, min(3, $phase));
        $existing = (new EntryPaymentModel())
            ->where('entry_id', $entryId)
            ->where('payment_phase', $phase)
            ->first();

        if (!$existing) {
            (new EntryPaymentModel())->insert([
                'entry_id' => $entryId,
                'payment_phase' => $phase,
                'payment_total' => 0,
                'coupon_code' => '',
            ], false);
        }
    }

    public function getPaymentDetails(string $entryId, string $phase): ?EntryPayment
    {
        return (new EntryPaymentModel())
            ->where('entry_id', $entryId)
            ->where('payment_phase', (int)$phase)
            ->first();
    }

    public function getAddonItems(int $phase = 0): array
    {
        return (new RetailItemModel())->getActive($phase > 0 ? $phase : null);
    }

    public function addonItemUser(string $entryId, int $addonId): array
    {
        return (new RetailItemUserModel())->findForEntryItem($entryId, $addonId);
    }

    public function setAddonsForEntryPhase(string $entryId, int $phase, array $selectedIds): void
    {
        $phaseItems = $this->getAddonItems($phase);
        $phaseItemIds = array_map(static fn (array $i): int => (int)$i['retail_item_id'], $phaseItems);

        $selected = [];
        foreach ($selectedIds as $id) {
            $id = (int)$id;
            if ($id > 0 && in_array($id, $phaseItemIds, true)) {
                $selected[$id] = 1;
            }
        }

        $m = new RetailItemUserModel();
        if ($phaseItemIds !== []) {
            $m->where('entry_id', $entryId)->whereIn('retail_item_id', $phaseItemIds)->delete();
        }

        foreach (array_keys($selected) as $retailItemId) {
            $m->insert([
                'entry_id' => $entryId,
                'retail_item_id' => $retailItemId,
                'quantity' => 1,
            ], false);
        }
    }

    public function getAllPaymentDetails(string $entryId): array
    {
        return (new EntryPaymentModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->groupStart()
                ->where('payment_receipt !=', '')
                ->orWhere('payment_status !=', 'pending')
            ->groupEnd()
            ->orderBy('payment_phase', 'ASC')
            ->findAll();
    }

    public function getPaymentReceipt(int $paymentId, string $entryId): ?array
    {
        $row = (new EntryPaymentModel())
            ->asArray()
            ->where('payment_id', $paymentId)
            ->where('entry_id', $entryId)
            ->first();

        return $row ?: null;
    }

    public function getCoupon(string $couponCode, ?int $compId = null): array
    {
        $code = strtoupper(trim($couponCode));
        if ($code === '') {
            return [];
        }

        $db = db_connect();
        $params = [$code];
        $sql = "SELECT coupon_id, coupon_code, coupon_type, coupon_amount
                  FROM comp_coupons
                 WHERE coupon_code = ?
                   AND coupon_start_date <= NOW()
                   AND coupon_end_date > NOW()";

        if ($compId !== null) {
            $sql .= " AND (coupon_comp = 0 OR coupon_comp = ?)";
            $params[] = $compId;
        }

        return $db->query($sql, $params)->getResultArray();
    }

    public function updateCoupon(string $entryId, string $phase, string $couponCode): bool
    {
        $this->ensurePaymentRow($entryId, (int)$phase);
        $code = strtoupper(trim($couponCode));
        $model = new EntryPaymentModel();

        /** @var EntryPayment|null $existing */
        $existing = $model->where('entry_id', $entryId)->where('payment_phase', (int)$phase)->first();
        if (!$existing) {
            return false;
        }

        return $model->update((int)$existing->payment_id, ['coupon_code' => $code]);
    }

    public function updateTotal(string $entryId, string $phase, string $total): bool
    {
        $this->ensurePaymentRow($entryId, (int)$phase);
        $amount = $this->normalizeMoney($total);

        $model = new EntryPaymentModel();
        /** @var EntryPayment|null $existing */
        $existing = $model->where('entry_id', $entryId)->where('payment_phase', (int)$phase)->first();
        if (!$existing) {
            return false;
        }

        return $model->update((int)$existing->payment_id, ['payment_total' => $amount]);
    }

    public function calculateTotalsForEntry(string $entryId, int $phase): array
    {
        $entry = (new UserEntriesModel())
            ->select('comp_entries.*, comp_users.user_type_id')
            ->join('comp_users', 'comp_users.user_id = comp_entries.user_id')
            ->where('entry_id', $entryId)
            ->asArray()
            ->first();

        if (!$entry) {
            throw new RuntimeException('Entry not found.');
        }

        $compId = (int)$entry['comp_id'];
        $userTypeId = (int)$entry['user_type_id'];
        $prices = $this->pricing->pricesFor($compId, $userTypeId);
        $tier = $this->pricing->currentRegTier($compId) ?? 'regular';

        $base = 0.0;
        if ($phase === 1) {
            $base = (float)($prices[$tier] ?? 0);
        } elseif ($phase === 2) {
            $base = (float)($prices['finalist'] ?? 0);
        } else {
            $base = (float)($prices['winner'] ?? 0);
        }

        // Legacy behavior: value-add package addon (id=4) waives phase-2 base fee.
        $valueAddPackage = ((int)($this->addonItemUser($entryId, 4)['quantity'] ?? 0)) > 0;
        if ($phase === 2 && $valueAddPackage) {
            $base = 0.0;
        }

        $seriesAmount = 0.0;
        if ($phase === 1 && (($entry['series'] ?? 'No') === 'Yes') && !$valueAddPackage) {
            $seriesAmount = (float)($prices['series'] ?? 0);
        }

        $addons = $this->getAddonItems($phase);
        $addonTotal = 0.0;
        $addonRows = [];
        foreach ($addons as $addon) {
            $itemId = (int)$addon['retail_item_id'];
            $userAddon = $this->addonItemUser($entryId, $itemId);
            $qty = (int)($userAddon['quantity'] ?? 0);
            $line = round($qty * $this->normalizeMoney((string)$addon['item_price']), 2);
            $addonTotal += $line;
            $addonRows[] = [
                'id' => $itemId,
                'name' => (string)$addon['item_name'],
                'price' => $this->normalizeMoney((string)$addon['item_price']),
                'selected' => $qty > 0,
                'line_total' => $line,
            ];
        }

        $subtotal = round($base + $seriesAmount + $addonTotal, 2);

        $payment = $this->getPaymentDetails($entryId, (string)$phase);
        $couponCode = strtoupper(trim((string)($payment?->coupon_code ?? '')));
        $coupon = [];
        if ($couponCode !== '') {
            $coupon = $this->getCoupon($couponCode, $compId);
            if ($coupon === []) {
                $couponCode = '';
                if ($payment) {
                    (new EntryPaymentModel())->update((int)$payment->payment_id, ['coupon_code' => '']);
                }
            }
        }

        $discount = 0.0;
        if ($coupon !== []) {
            $totals = $this->pricing->applyCoupon(
                $subtotal,
                (string)$coupon[0]['coupon_type'],
                $coupon[0]['coupon_amount']
            );
            $discount = (float)$totals['discount'];
        }

        $total = round(max(0, $subtotal - $discount), 2);

        $this->updateTotal($entryId, (string)$phase, (string)$total);

        return [
            'entry' => $entry,
            'phase' => $phase,
            'tier' => $tier,
            'base' => $base,
            'series' => $seriesAmount,
            'addons' => $addonRows,
            'subtotal' => $subtotal,
            'coupon_code' => $couponCode,
            'coupon' => $coupon[0] ?? null,
            'discount' => $discount,
            'total' => $total,
        ];
    }

    public function userSuccessPaid(string $entryId, string $phase, string $receipt): void
    {
        $this->userSuccessPaidWithTransaction($entryId, $phase, $receipt, '');
    }

    public function userSuccessPaidWithTransaction(string $entryId, string $phase, string $receipt, string $transactionId = ''): void
    {
        $phaseInt = (int)$phase;
        if ($phaseInt < 1 || $phaseInt > 3) {
            throw new RuntimeException('Invalid payment phase.');
        }

        $this->ensurePaymentRow($entryId, $phaseInt);

        $db = db_connect();
        $db->transStart();

        $model = new EntryPaymentModel();
        /** @var EntryPayment|null $payment */
        $payment = $model->where('entry_id', $entryId)->where('payment_phase', $phaseInt)->first();
        if ($payment) {
            $normalizedReceipt = $this->resolveLegacyReceiptTemplate(
                $receipt,
                [
                    'payment_id' => (string)$payment->payment_id,
                    'payment_total' => (string)$payment->payment_total,
                ],
                $entryId
            );
            $resolvedTransactionId = $this->normalizeTransactionId($transactionId);
            if ($resolvedTransactionId === '') {
                $resolvedTransactionId = $this->normalizeTransactionId((string)$payment->payment_transaction_id);
            }
            if ($resolvedTransactionId === '') {
                $resolvedTransactionId = $this->normalizeTransactionId($receipt);
            }
            $model->update((int)$payment->payment_id, [
                'payment_receipt' => $normalizedReceipt,
                'payment_status' => 'paid',
                'payment_transaction_id' => $resolvedTransactionId,
                'payment_status_message' => '',
                'payment_status_updated_at' => date('Y-m-d H:i:s'),
                'payment_date' => date('Y-m-d H:i:s'),
            ]);
        }

        $entryUpdate = [];
        if ($phaseInt === 1) {
            $entryUpdate = ['phase_1_payment' => 'Paid', 'entry_status' => 'Entrant'];
        } elseif ($phaseInt === 2) {
            $entryUpdate = ['phase_2_payment' => 'Paid'];
        } elseif ($phaseInt === 3) {
            $entryUpdate = ['phase_3_payment' => 'Paid'];
        }

        if ($entryUpdate !== []) {
            (new UserEntriesModel())->update($entryId, $entryUpdate);
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException('Payment finalization failed.');
        }
    }

    public function markPaymentStatus(string $entryId, string $phase, string $status, string $transactionId = '', string $message = ''): void
    {
        $phaseInt = (int)$phase;
        if ($phaseInt < 1 || $phaseInt > 3) {
            throw new RuntimeException('Invalid payment phase.');
        }

        $this->ensurePaymentRow($entryId, $phaseInt);

        /** @var EntryPayment|null $payment */
        $payment = (new EntryPaymentModel())
            ->where('entry_id', $entryId)
            ->where('payment_phase', $phaseInt)
            ->first();

        if (!$payment) {
            throw new RuntimeException('Unable to load payment row.');
        }

        $this->updatePaymentStatusRecord((int)$payment->payment_id, $status, $transactionId, $message);
    }

    public function markPaymentStatusByPaymentId(int $paymentId, string $status, string $transactionId = '', string $message = ''): void
    {
        if ($paymentId <= 0) {
            throw new RuntimeException('Invalid payment ID.');
        }

        $this->updatePaymentStatusRecord($paymentId, $status, $transactionId, $message);
    }

    private function updatePaymentStatusRecord(int $paymentId, string $status, string $transactionId, string $message): void
    {
        $normalizedStatus = $this->normalizePaymentStatus($status);
        $normalizedTransactionId = $this->normalizeTransactionId($transactionId);

        $update = [
            'payment_status' => $normalizedStatus,
            'payment_status_message' => trim($message),
            'payment_status_updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($normalizedTransactionId !== '') {
            $update['payment_transaction_id'] = $normalizedTransactionId;
        }

        if ($normalizedStatus === 'paid') {
            $update['payment_status_message'] = '';
        }

        (new EntryPaymentModel())->update($paymentId, $update);
    }

    private function normalizePaymentStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'held_for_review', 'heldforreview' => 'held_for_review',
            'paid', 'settledsuccessfully', 'capturedpendingsettlement', 'authorizedpendingcapture' => 'paid',
            'declined', 'decline', 'failed' => 'declined',
            'voided', 'void' => 'voided',
            'refunded', 'refund' => 'refunded',
            'error' => 'error',
            default => 'pending',
        };
    }

    private function normalizeTransactionId(string $transactionId): string
    {
        $transactionId = trim($transactionId);
        if ($transactionId === '') {
            return '';
        }

        return preg_match('/^[A-Za-z0-9_-]{4,40}$/', $transactionId) === 1 ? $transactionId : '';
    }

    private function normalizeMoney(string $val): float
    {
        $num = (float)preg_replace('/[^0-9.\-]/', '', $val);
        return round($num, 2, PHP_ROUND_HALF_UP);
    }

    private function resolveLegacyReceiptTemplate(string $rawReceipt, array $payment, string $entryId): string
    {
        if ($rawReceipt === '' || strpos($rawReceipt, '#') === false) {
            return $rawReceipt;
        }

        $ctx = db_connect()->table('comp_entries e')
            ->select('e.design_name, u.first_name, u.last_name, u.address1, u.city, u.state, u.zipcode, u.country, u.email_address')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray() ?? [];

        $tokenMap = [
            '#x_description#' => (string)($ctx['design_name'] ?? 'Spark Awards Entry Payment'),
            '#paymentDetails.payment_id#' => (string)($payment['payment_id'] ?? ''),
            '#x_first_name#' => (string)($ctx['first_name'] ?? ''),
            '#x_last_name#' => (string)($ctx['last_name'] ?? ''),
            '#x_address#' => (string)($ctx['address1'] ?? ''),
            '#x_city#' => (string)($ctx['city'] ?? ''),
            '#x_state#' => (string)($ctx['state'] ?? ''),
            '#x_zip#' => (string)($ctx['zipcode'] ?? ''),
            '#x_country#' => (string)($ctx['country'] ?? ''),
            '#userDetails.email_address#' => (string)($ctx['email_address'] ?? ''),
            '#x_amount#' => (string)($payment['payment_total'] ?? ''),
        ];

        $resolved = strtr($rawReceipt, $tokenMap);
        $resolved = preg_replace('/#[A-Za-z0-9_.]+#/', '', $resolved) ?? $resolved;

        return trim($resolved);
    }
}
