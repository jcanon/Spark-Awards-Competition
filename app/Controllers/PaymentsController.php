<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Payments\EntryPaymentModel;
use App\Services\Payments\AuthorizeNetGateway;

class PaymentsController extends BaseController
{
    public function show(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
        }

        $phase = $this->normalizePhase($phase);
        service('payments')->ensurePaymentRow($entryId, $phase);
        $cart = service('payments')->calculateTotalsForEntry($entryId, $phase);

        return view('payment/index', [
            'entryId' => $entryId,
            'phase' => $phase,
            'cart' => $cart,
        ]);
    }

    public function setCoupon(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
        }

        $phase = $this->normalizePhase($phase);
        $coupon = trim((string)$this->request->getPost('coupon_code'));
        if ($coupon === '') {
            service('payments')->updateCoupon($entryId, (string)$phase, '');
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}");
        }

        $entry = db_connect()->table('comp_entries')->select('comp_id')->where('entry_id', $entryId)->get()->getRowArray();
        $compId = (int)($entry['comp_id'] ?? 0);
        $check = service('payments')->getCoupon($coupon, $compId);
        if ($check === []) {
            return redirect()->back()->with('error', lang('Entrant.invalid_or_expired_coupon'));
        }

        service('payments')->updateCoupon($entryId, (string)$phase, $coupon);
        return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")->with('success', lang('Entrant.coupon_applied'));
    }

    public function setAddons(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
        }

        $phase = $this->normalizePhase($phase);
        $selected = $this->request->getPost('addon_ids') ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }

        service('payments')->setAddonsForEntryPhase($entryId, $phase, $selected);
        return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")->with('success', lang('Entrant.addons_updated'));
    }

    public function setTotal(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return $this->response->setStatusCode(404);
        }

        $phase = $this->normalizePhase($phase);
        $total = (string)$this->request->getPost('total');
        $ok = service('payments')->updateTotal($entryId, (string)$phase, $total);
        return $this->response->setJSON(['ok' => $ok]);
    }

    public function checkout(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
        }

        $phase = $this->normalizePhase($phase);
        $cart = service('payments')->calculateTotalsForEntry($entryId, $phase);
        $total = (float)($cart['total'] ?? 0);

        if ($total <= 0) {
            service('payments')->userSuccessPaid($entryId, (string)$phase, 'NO-CHARGE-' . date('YmdHis'));
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/receipt")
                ->with('success', lang('Entrant.no_payment_due_marked_paid'));
        }

        $gateway = new AuthorizeNetGateway();
        $returnUrl = site_url('payments/entry/' . $entryId . '/phase/' . $phase . '/receipt');
        $cancelUrl = site_url('payments/entry/' . $entryId . '/phase/' . $phase);
        $payment = service('payments')->getPaymentDetails($entryId, (string)$phase);
        $invoice = $payment ? (string)$payment->payment_id : '';

        try {
            $token = $gateway->createAcceptHostedToken([
                'amountCents' => (int)round($total * 100),
                'returnUrl' => $returnUrl,
                'cancelUrl' => $cancelUrl,
                'invoice' => $invoice,
                'description' => 'Spark Awards Entry Payment',
            ]);
        } catch (\Throwable $e) {
            log_message(
                'critical',
                'Checkout gateway init failed: {class}: {message} at {file}:{line}',
                [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            $token = null;
        }

        if (!$token) {
            $errorMessage = lang('Entrant.unable_initialize_gateway');
            if (ENVIRONMENT !== 'production') {
                $gatewayDetail = $gateway->lastError();
                if (is_string($gatewayDetail) && $gatewayDetail !== '') {
                    $errorMessage .= ' ' . $gatewayDetail;
                }
            }

            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")
                ->with('error', $errorMessage);
        }

        return view('payment/checkout', [
            'entryId' => $entryId,
            'phase' => $phase,
            'cart' => $cart,
            'token' => $token,
            'hostedAction' => $gateway->hostedPaymentUrl(),
        ]);
    }

    public function receipt(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', lang('Entrant.entry_not_found'));
        }

        $phase = $this->normalizePhase($phase);
        $payment = (new EntryPaymentModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->where('payment_phase', $phase)
            ->first();

        if ($payment) {
            $payment['payment_receipt'] = $this->resolveLegacyReceiptTemplate((string)($payment['payment_receipt'] ?? ''), $payment, $entryId);
        }

        return view('payment/receipt', [
            'entryId' => $entryId,
            'phase' => $phase,
            'payment' => $payment,
            'paid' => $payment && ((string)($payment['payment_receipt'] ?? '') !== ''),
        ]);
    }

    public function receiptContent(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('<div class="alert alert-danger mb-0">' . esc(lang('Entrant.entry_not_found')) . '</div>');
        }

        $phase = $this->normalizePhase($phase);
        $payment = (new EntryPaymentModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->where('payment_phase', $phase)
            ->first();

        if (!$payment) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('<div class="alert alert-danger mb-0">Receipt not found.</div>');
        }

        $payment['payment_receipt'] = $this->resolveLegacyReceiptTemplate((string)($payment['payment_receipt'] ?? ''), $payment, $entryId);

        return $this->response->setBody(
            view('payment/_receipt_content', ['payment' => $payment])
        );
    }

    public function success(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return $this->response->setStatusCode(404);
        }
        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Forbidden']);
        }

        $phase = $this->normalizePhase($phase);
        $receipt = trim((string)$this->request->getPost('receipt'));
        if ($receipt === '' || preg_match('/^[A-Za-z0-9\-_]{6,128}$/', $receipt) !== 1) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Missing receipt']);
        }

        service('payments')->userSuccessPaid($entryId, (string)$phase, $receipt);
        return $this->response->setJSON(['ok' => true]);
    }

    private function canAccessEntry(string $entryId): bool
    {
        $entry = db_connect()->table('comp_entries')->select('user_id')->where('entry_id', $entryId)->get()->getRowArray();
        if (!$entry) {
            return false;
        }

        $role = (string)session('role');
        if (in_array($role, ['admin', 'editor'], true)) {
            return true;
        }

        return hash_equals((string)$entry['user_id'], (string)session('uid'));
    }

    private function normalizePhase(int $phase): int
    {
        return in_array($phase, [1, 2, 3], true) ? $phase : 1;
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
