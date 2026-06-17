<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Payments\EntryPaymentModel;
use App\Services\Payments\AuthorizeNetGateway;
use Config\AuthorizeNet as AuthorizeNetConfig;
use Dompdf\Dompdf;
use Dompdf\Options;

class PaymentsController extends BaseController
{
    public function show(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
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
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
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
            return redirect()->back()->with('error', 'Invalid or expired coupon code.');
        }

        service('payments')->updateCoupon($entryId, (string)$phase, $coupon);
        return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")->with('success', 'Coupon applied.');
    }

    public function setAddons(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $phase = $this->normalizePhase($phase);
        $selected = $this->request->getPost('addon_ids') ?? [];
        if (!is_array($selected)) {
            $selected = [];
        }

        service('payments')->setAddonsForEntryPhase($entryId, $phase, $selected);
        return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")->with('success', 'Add-ons updated.');
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
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $phase = $this->normalizePhase($phase);
        $cart = service('payments')->calculateTotalsForEntry($entryId, $phase);
        $total = (float)($cart['total'] ?? 0);

        if ($total <= 0) {
            service('payments')->userSuccessPaid($entryId, (string)$phase, 'NO-CHARGE-' . date('YmdHis'));
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/receipt")
                ->with('success', 'No payment due. Entry marked as paid.');
        }

        $anet = config(AuthorizeNetConfig::class);
        if ($anet->apiLoginId() === '' || $anet->clientKey() === '' || $anet->transactionKey() === '') {
            $errorMessage = 'Payment checkout is not configured. Set the Authorize.Net API login ID, client key, and transaction key.';
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")
                ->with('error', $errorMessage);
        }

        return view('payment/checkout', [
            'entryId' => $entryId,
            'phase' => $phase,
            'cart' => $cart,
            'apiLoginId' => $anet->apiLoginId(),
            'clientKey' => $anet->clientKey(),
            'acceptJsUrl' => $anet->acceptJsUrl(),
            'countries' => service('profiles')->getCountries(),
        ]);
    }

    public function processCheckout(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $phase = $this->normalizePhase($phase);
        service('payments')->ensurePaymentRow($entryId, $phase);
        $cart = service('payments')->calculateTotalsForEntry($entryId, $phase);
        $total = (float)($cart['total'] ?? 0);

        if ($total <= 0) {
            service('payments')->userSuccessPaid($entryId, (string)$phase, 'NO-CHARGE-' . date('YmdHis'));
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/receipt")
                ->with('success', 'No payment due. Entry marked as paid.');
        }

        $dataDescriptor = trim((string)$this->request->getPost('data_descriptor'));
        $dataValue = trim((string)$this->request->getPost('data_value'));
        $allowedCountries = $this->allowedBillingCountries();
        $billing = [
            'firstName' => trim((string)$this->request->getPost('billing_first_name')),
            'lastName' => trim((string)$this->request->getPost('billing_last_name')),
            'address' => trim((string)$this->request->getPost('billing_address')),
            'city' => trim((string)$this->request->getPost('billing_city')),
            'state' => trim((string)$this->request->getPost('billing_state')),
            'zip' => preg_replace('/[^A-Za-z0-9\- ]/', '', trim((string)$this->request->getPost('billing_zip'))) ?? '',
            'phone' => preg_replace('/[^0-9()+\-.\s]/', '', trim((string)$this->request->getPost('billing_phone'))) ?? '',
            'email' => trim((string)$this->request->getPost('billing_email')),
            'country' => trim((string)$this->request->getPost('billing_country')),
        ];

        if ($dataDescriptor === '' || $dataValue === '') {
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/checkout")
                ->withInput()
                ->with('error', 'Payment tokenization failed. Please enter your card details again.');
        }

        foreach (['firstName', 'lastName', 'address', 'city', 'phone', 'email', 'country'] as $field) {
            if ($billing[$field] === '') {
                return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/checkout")
                    ->withInput()
                    ->with('error', 'Please complete all required billing fields.');
            }
        }

        if (!filter_var($billing['email'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/checkout")
                ->withInput()
                ->with('error', 'Please enter a valid billing email address.');
        }

        if (!isset($allowedCountries[$billing['country']])) {
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/checkout")
                ->withInput()
                ->with('error', 'Please select a valid billing country.');
        }

        $payment = service('payments')->getPaymentDetails($entryId, (string)$phase);
        if (!$payment) {
            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}")
                ->with('error', 'Unable to load the pending payment record.');
        }

        $gateway = new AuthorizeNetGateway();

        try {
            $response = $gateway->chargeCard(
                $dataValue,
                (int)round($total * 100),
                [
                    'invoice' => (string)$payment->payment_id,
                    'description' => 'Spark Awards Entry Payment',
                    'customerEmail' => $billing['email'],
                    'billTo' => $this->buildBillingPayload($billing),
                ],
                $dataDescriptor
            );
        } catch (\Throwable $e) {
            log_message(
                'critical',
                'Checkout charge failed: {class}: {message} at {file}:{line}',
                [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            $response = null;
        }

        $result = $this->extractAuthorizeNetChargeResult($response, $gateway);
        if (!$result['ok']) {
            log_message('warning', 'Authorize.Net charge declined for entry {entryId} phase {phase}: {message}', [
                'entryId' => $entryId,
                'phase' => (string)$phase,
                'message' => $result['message'],
            ]);

            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/checkout")
                ->withInput()
                ->with('error', $result['message']);
        }

        $transId = $result['transId'];
        $details = $gateway->getTransactionDetails($transId);
        $receiptReference = $transId;
        if ($details) {
            $formatted = $this->formatAuthorizeNetReceiptReference($details, $transId, $entryId, [
                'payment_id' => (string)$payment->payment_id,
                'payment_total' => (string)$payment->payment_total,
            ], $billing);
            if ($formatted !== '') {
                $receiptReference = $formatted;
            }
        }

        try {
            service('payments')->userSuccessPaid($entryId, (string)$phase, $receiptReference);
        } catch (\Throwable $e) {
            log_message('critical', 'Payment captured but local finalization failed for entry {entryId} phase {phase}. Transaction {transId}. Error: {message}', [
                'entryId' => $entryId,
                'phase' => (string)$phase,
                'transId' => $transId,
                'message' => $e->getMessage(),
            ]);

            return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/receipt")
                ->with('error', 'Your card was charged, but we could not finish updating the submission automatically. We have logged the transaction and will reconcile it using transaction ID ' . $transId . '.');
        }

        return redirect()->to("/payments/entry/{$entryId}/phase/{$phase}/receipt")
            ->with('success', 'Payment completed successfully.');
    }

    public function receipt(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $phase = $this->normalizePhase($phase);
        $model = new EntryPaymentModel();
        $payment = $model
            ->asArray()
            ->where('entry_id', $entryId)
            ->where('payment_phase', $phase)
            ->first();

        if ($payment && ((string)($payment['payment_receipt'] ?? '') === '')) {
            $this->finalizePendingPaymentFromReturn($entryId, $phase, $payment);
            $payment = $model
                ->asArray()
                ->where('entry_id', $entryId)
                ->where('payment_phase', $phase)
                ->first();
        }

        if ($payment && $this->isPlainReceiptReference((string)($payment['payment_receipt'] ?? ''))) {
            $this->enrichStoredReceiptReference($payment);
            $payment = $model
                ->asArray()
                ->where('entry_id', $entryId)
                ->where('payment_phase', $phase)
                ->first();
        }

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
                ->setBody('<div class="alert alert-danger mb-0">' . 'Entry not found.' . '</div>');
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

    public function receiptPdf(string $entryId, int $phase)
    {
        if (!$this->canAccessEntry($entryId)) {
            return redirect()->to('/submissions')->with('error', 'Entry not found.');
        }

        $phase = $this->normalizePhase($phase);
        $payment = (new EntryPaymentModel())
            ->asArray()
            ->where('entry_id', $entryId)
            ->where('payment_phase', $phase)
            ->first();

        if (!$payment) {
            return redirect()->back()->with('error', 'Receipt not found.');
        }

        $payment['payment_receipt'] = $this->resolveLegacyReceiptTemplate((string)($payment['payment_receipt'] ?? ''), $payment, $entryId);
        $entryMeta = $this->loadReceiptEntryContext($entryId);

        helper('html_sanitize');

        $html = view('receipt/pdf', [
            'payment' => $payment,
            'entryId' => $entryId,
            'entryMeta' => $entryMeta,
            'logoPath' => FCPATH . 'img/sparklogo.jpg',
            'title' => 'Spark Awards Payment Receipt',
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $fileName = 'spark-receipt-' . $entryId . '-phase-' . $phase . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($dompdf->output());
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

    private function canAccessEntry(string &$entryId): bool
    {
        $raw = trim(rawurldecode($entryId));
        if ($raw === '') {
            return false;
        }

        $candidates = [$raw];
        // Safety fallback for environments with legacy/truncated UUID storage.
        if (strlen($raw) === 36 && substr_count($raw, '-') === 4) {
            $candidates[] = substr($raw, 0, 35);
        }

        $entry = null;
        foreach (array_values(array_unique($candidates)) as $candidate) {
            $row = db_connect()
                ->table('comp_entries')
                ->select('entry_id, user_id')
                ->where('entry_id', $candidate)
                ->get()
                ->getRowArray();
            if ($row) {
                $entry = $row;
                break;
            }
        }

        if (! $entry) {
            return false;
        }

        $role = (string)session('role');
        if (in_array($role, ['admin', 'editor'], true)) {
            $entryId = (string)$entry['entry_id'];
            return true;
        }

        $owned = hash_equals((string)$entry['user_id'], (string)session('uid'));
        if ($owned) {
            $entryId = (string)$entry['entry_id'];
        }

        return $owned;
    }

    private function normalizePhase(int $phase): int
    {
        return in_array($phase, [1, 2, 3], true) ? $phase : 1;
    }

    private function allowedBillingCountries(): array
    {
        $allowed = [];
        foreach (service('profiles')->getCountries() as $country) {
            $name = trim((string)($country['country'] ?? ''));
            if ($name !== '') {
                $allowed[$name] = true;
            }
        }

        return $allowed;
    }

    private function buildBillingPayload(array $billing): array
    {
        return [
            'firstName' => (string)($billing['firstName'] ?? ''),
            'lastName' => (string)($billing['lastName'] ?? ''),
            'address' => (string)($billing['address'] ?? ''),
            'city' => (string)($billing['city'] ?? ''),
            'state' => (string)($billing['state'] ?? ''),
            'zip' => (string)($billing['zip'] ?? ''),
            'country' => (string)($billing['country'] ?? 'USA'),
            'phone' => (string)($billing['phone'] ?? ''),
        ];
    }

    private function extractAuthorizeNetChargeResult($response, AuthorizeNetGateway $gateway): array
    {
        $fallbackError = trim((string)$gateway->lastError());
        if (!$response) {
            return [
                'ok' => false,
                'transId' => '',
                'message' => $fallbackError !== '' ? $fallbackError : 'Unable to process the payment right now.',
            ];
        }

        $apiMessages = $response->getMessages();
        $apiResultCode = $apiMessages ? trim((string)$apiMessages->getResultCode()) : '';
        $transaction = $response->getTransactionResponse();
        if ($transaction) {
            $responseCode = (string)$transaction->getResponseCode();
            $transId = trim((string)$transaction->getTransId());
            $transactionMessages = $this->collectAuthorizeNetMessages($transaction->getMessages(), 'getDescription', 'getCode');
            $transactionErrors = $this->collectAuthorizeNetMessages($transaction->getErrors(), 'getErrorText', 'getErrorCode');

            if ($responseCode === '1' && $transId !== '') {
                return [
                    'ok' => true,
                    'transId' => $transId,
                    'message' => '',
                ];
            }

            if ($transactionErrors !== '') {
                return [
                    'ok' => false,
                    'transId' => $transId,
                    'message' => $this->normalizeAuthorizeNetChargeFailure($transactionErrors),
                ];
            }

            if ($transactionMessages !== '') {
                return [
                    'ok' => false,
                    'transId' => $transId,
                    'message' => $this->normalizeAuthorizeNetChargeFailure($transactionMessages),
                ];
            }
        }

        if ($apiMessages && strcasecmp($apiResultCode, 'Ok') !== 0) {
            $messageText = $this->collectAuthorizeNetMessages($apiMessages->getMessage(), 'getText', 'getCode');
            if ($messageText !== '') {
                return [
                    'ok' => false,
                    'transId' => '',
                    'message' => $this->normalizeAuthorizeNetChargeFailure($messageText),
                ];
            }
        }

        return [
            'ok' => false,
            'transId' => '',
            'message' => $fallbackError !== '' ? $fallbackError : 'The payment was declined or could not be processed.',
        ];
    }

    private function collectAuthorizeNetMessages($messages, string $textMethod, string $codeMethod): string
    {
        if (!is_array($messages)) {
            return '';
        }

        $parts = [];
        foreach ($messages as $message) {
            if (!is_object($message) || !method_exists($message, $textMethod)) {
                continue;
            }

            $text = trim((string)$message->{$textMethod}());
            if ($text === '') {
                continue;
            }

            $code = method_exists($message, $codeMethod) ? trim((string)$message->{$codeMethod}()) : '';
            $parts[] = $code !== '' ? $code . ': ' . $text : $text;
        }

        return implode(' ', array_values(array_unique($parts)));
    }

    private function normalizeAuthorizeNetChargeFailure(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'The payment was declined or could not be processed.';
        }

        if (preg_match('/^(252|253):/i', $message) === 1) {
            return 'Your payment was received by the processor but is being held for manual review. We have not marked this entry as paid yet. Please contact support if you need immediate confirmation.';
        }

        return $message;
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

    private function loadReceiptEntryContext(string $entryId): array
    {
        return db_connect()->table('comp_entries e')
            ->select('e.design_name, e.company_name, c.comp_year, t.comp_type_name, u.first_name, u.last_name, u.email_address')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id', 'left')
            ->join('comp_type t', 't.comp_type_id = c.comp_type_id', 'left')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray() ?? [];
    }

    private function finalizePendingPaymentFromReturn(string $entryId, int $phase, array $payment): void
    {
        $gateway = new AuthorizeNetGateway();
        $transId = trim((string)($this->request->getGet('transId') ?? $this->request->getGet('x_trans_id') ?? ''));
        $details = null;

        if ($transId !== '' && preg_match('/^\d{4,32}$/', $transId) === 1) {
            $details = $gateway->getTransactionDetails($transId);
            if (!$details) {
                log_message('warning', 'Unable to finalize pending payment from return URL: failed loading transaction details for transId {transId}. Error: {error}', [
                    'transId' => $transId,
                    'error' => (string)$gateway->lastError(),
                ]);
                $invoiceLookup = $gateway->findTransactionByInvoice((string)($payment['payment_id'] ?? ''));
                if ($invoiceLookup) {
                    $details = $invoiceLookup;
                    $transId = (string)$invoiceLookup['transaction_id'];
                }
            }
        } else {
            $invoiceLookup = $gateway->findTransactionByInvoice((string)($payment['payment_id'] ?? ''));
            if ($invoiceLookup) {
                $details = $invoiceLookup;
                $transId = (string)$invoiceLookup['transaction_id'];
            }
        }
        if (!$details || $transId === '') {
            return;
        }

        $invoice = trim((string)($details['invoice'] ?? ''));
        if ($invoice === '' || (int)$invoice !== (int)($payment['payment_id'] ?? 0)) {
            log_message('warning', 'Authorize.Net return invoice mismatch for entry {entryId} phase {phase}. Expected payment_id {expected}, got invoice {invoice}', [
                'entryId' => $entryId,
                'phase' => (string)$phase,
                'expected' => (string)($payment['payment_id'] ?? ''),
                'invoice' => $invoice,
            ]);
            return;
        }

        if (!$this->isAuthorizeNetPaidStatus((string)($details['status'] ?? ''), (string)($details['response_code'] ?? ''))) {
            return;
        }

        $receiptReference = $this->formatAuthorizeNetReceiptReference($details, $transId, $entryId, $payment);
        try {
            service('payments')->userSuccessPaid($entryId, (string)$phase, $receiptReference);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to finalize payment from return URL for entry {entryId} phase {phase}: {message}', [
                'entryId' => $entryId,
                'phase' => (string)$phase,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function isAuthorizeNetPaidStatus(string $status, string $responseCode): bool
    {
        $okStatuses = [
            'settledsuccessfully',
            'capturedpendingsettlement',
            'authorizedpendingcapture',
        ];

        return in_array(strtolower($status), $okStatuses, true) || $responseCode === '1';
    }

    private function enrichStoredReceiptReference(array $payment): void
    {
        $paymentId = (int)($payment['payment_id'] ?? 0);
        if ($paymentId <= 0) {
            return;
        }

        $rawReceipt = trim((string)($payment['payment_receipt'] ?? ''));
        $gateway = new AuthorizeNetGateway();
        $details = null;
        $transId = '';

        if (preg_match('/^\d{4,32}$/', $rawReceipt) === 1) {
            $transId = $rawReceipt;
            $details = $gateway->getTransactionDetails($transId);
        }

        if (!$details) {
            $details = $gateway->findTransactionByInvoice((string)$paymentId);
            if ($details) {
                $transId = (string)($details['transaction_id'] ?? $transId);
            }
        }

        if (!$details || $transId === '') {
            return;
        }

        $formatted = $this->formatAuthorizeNetReceiptReference($details, $transId, (string)($payment['entry_id'] ?? ''), $payment);
        if (trim($formatted) === '') {
            return;
        }

        (new EntryPaymentModel())->update($paymentId, ['payment_receipt' => $formatted]);
    }

    private function isPlainReceiptReference(string $receipt): bool
    {
        $receipt = trim($receipt);
        if ($receipt === '') {
            return false;
        }

        return strpos($receipt, '<') === false && strpos($receipt, "\n") === false;
    }

    private function formatAuthorizeNetReceiptReference(array $details, string $fallbackTransId, string $entryId, array $payment, ?array $billingOverride = null): string
    {
        $txId = trim((string)($details['transaction_id'] ?? ''));
        if ($txId === '') {
            $txId = trim($fallbackTransId);
        }
        if ($txId === '') {
            return '';
        }

        $ctx = db_connect()->table('comp_entries e')
            ->select('e.design_name, u.first_name, u.last_name, u.city, u.state, u.zipcode, u.country, u.email_address')
            ->join('comp_users u', 'u.user_id = e.user_id')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray() ?? [];

        $designName = trim((string)($ctx['design_name'] ?? 'Spark Awards Entry Payment'));
        $invoice = trim((string)($details['invoice'] ?? (string)($payment['payment_id'] ?? '')));
        $fullName = trim((string)($ctx['first_name'] ?? '') . ' ' . (string)($ctx['last_name'] ?? ''));
        $street = '';
        $cityState = trim((string)($ctx['city'] ?? '') . ((string)($ctx['state'] ?? '') !== '' ? ', ' . (string)$ctx['state'] : ''));
        $country = trim((string)($ctx['country'] ?? ''));
        $zip = trim((string)($ctx['zipcode'] ?? ''));
        $email = trim((string)($ctx['email_address'] ?? ''));
        $phone = '';

        if (is_array($billingOverride) && $billingOverride !== []) {
            $fullName = trim((string)($billingOverride['firstName'] ?? '') . ' ' . (string)($billingOverride['lastName'] ?? ''));
            $street = trim((string)($billingOverride['address'] ?? ''));
            $cityState = trim((string)($billingOverride['city'] ?? '') . ((string)($billingOverride['state'] ?? '') !== '' ? ', ' . (string)$billingOverride['state'] : ''));
            $country = trim((string)($billingOverride['country'] ?? ''));
            $zip = trim((string)($billingOverride['zip'] ?? ''));
            $email = trim((string)($billingOverride['email'] ?? ''));
            $phone = trim((string)($billingOverride['phone'] ?? ''));
        }
        $total = number_format((float)($payment['payment_total'] ?? 0), 2);
        $settingsEmail = trim((string)service('settings')->get('email', 'sparknewsnow@sparkawards.com'));
        if ($settingsEmail === '') {
            $settingsEmail = 'sparknewsnow@sparkawards.com';
        }

        $status = trim((string)($details['status'] ?? ''));
        $txType = trim((string)($details['transaction_type'] ?? ''));
        $authCode = trim((string)($details['auth_code'] ?? ''));
        $accountType = trim((string)($details['account_type'] ?? ''));
        $accountNumber = trim((string)($details['account_number'] ?? ''));

        $line = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        $parts = [];
        $parts[] = $line('Thank you for your transaction with the Spark Design Awards. Your entry is greatly appreciated.');
        $parts[] = '';
        $parts[] = $line('Order Information');
        $parts[] = $line('Description: ' . $designName);
        if ($invoice !== '') {
            $parts[] = $line('Invoice Number: ' . $invoice);
        }
        $parts[] = $line('Transaction ID: ' . $txId);
        if ($status !== '') {
            $parts[] = $line('Status: ' . ucwords(str_replace(['_', '-'], ' ', $status)));
        }
        if ($txType !== '') {
            $parts[] = $line('Transaction Type: ' . $txType);
        }
        if ($authCode !== '') {
            $parts[] = $line('Auth Code: ' . $authCode);
        }
        if ($accountType !== '' || $accountNumber !== '') {
            $parts[] = $line('Payment Method: ' . trim($accountType . ' ' . $accountNumber));
        }
        $parts[] = '';
        $parts[] = $line('Billing Information');
        if ($fullName !== '') {
            $parts[] = $line($fullName);
        }
        if ($street !== '') {
            $parts[] = $line($street);
        }
        if ($cityState !== '') {
            $parts[] = $line($cityState);
        }
        if ($zip !== '') {
            $parts[] = $line($zip);
        }
        if ($country !== '') {
            $parts[] = $line($country);
        }
        if ($email !== '') {
            $parts[] = $line($email);
        }
        if ($phone !== '') {
            $parts[] = $line($phone);
        }
        $parts[] = '';
        $parts[] = $line('Total: ' . $total);
        $parts[] = '';
        $parts[] = $line('ENCENTA LLC, DBA Spark Design Awards');
        $parts[] = $line('PO Box 833');
        $parts[] = $line('Croton On Hudson, NY 10520');
        $parts[] = $line('USA');
        $parts[] = $line($settingsEmail);

        return implode('<br>', $parts);
    }
}
