<?php

namespace App\Controllers;

use App\Models\Payments\EntryPaymentModel;
use Config\AuthorizeNet as AuthorizeNetConfig;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentController extends BaseController
{
    /**
     * Return URL for Accept Hosted (or similar) flows.
     * Expects entry_id in the query so we can show the latest payment state.
     */
    public function hostedReturn(): ResponseInterface
    {
        $entryId = (string)$this->request->getGet('entry_id');

        if ($entryId !== '') {
            $row = (new EntryPaymentModel())
                ->select('payment_receipt, payment_date, payment_status, payment_transaction_id, payment_status_message, payment_status_updated_at')
                ->where('entry_id', $entryId)
                ->orderBy('payment_phase', 'DESC')
                ->asArray()
                ->first();

            if ($row && ($row['payment_receipt'] ?? '') !== '') {
                return $this->response->setJSON([
                    'status' => 'paid',
                    'receipt' => $row['payment_receipt'],
                    'date' => $row['payment_date'],
                ]);
            }

            if ($row && strtolower((string)($row['payment_status'] ?? 'pending')) === 'held_for_review') {
                return $this->response->setJSON([
                    'status' => 'held_for_review',
                    'transaction_id' => (string)($row['payment_transaction_id'] ?? ''),
                    'message' => (string)($row['payment_status_message'] ?? ''),
                    'updated_at' => (string)($row['payment_status_updated_at'] ?? ''),
                ]);
            }

            if ($row) {
                $status = strtolower((string)($row['payment_status'] ?? 'pending'));
                if ($status !== 'pending') {
                    return $this->response->setJSON([
                        'status' => $status,
                        'transaction_id' => (string)($row['payment_transaction_id'] ?? ''),
                        'message' => (string)($row['payment_status_message'] ?? ''),
                        'updated_at' => (string)($row['payment_status_updated_at'] ?? ''),
                    ]);
                }
            }
        }

        return $this->response->setJSON(['status' => 'pending']);
    }

    /**
     * Webhook endpoint for Authorize.Net event notifications.
     * Verifies payload signature and updates payment records.
     */
    public function webhook(): ResponseInterface
    {
        $payload = (string)$this->request->getBody();
        $sig = (string)$this->request->getHeaderLine('X-ANET-Signature');
        $keyHex = config(AuthorizeNetConfig::class)->signatureKey();

        if ($payload === '' || $sig === '' || $keyHex === '') {
            log_message('warning', 'Authorize.Net webhook rejected: missing payload/signature/signature-key. payload_present={payloadPresent} signature_present={signaturePresent} signature_key_present={signatureKeyPresent}', [
                'payloadPresent' => $payload !== '' ? 'yes' : 'no',
                'signaturePresent' => $sig !== '' ? 'yes' : 'no',
                'signatureKeyPresent' => $keyHex !== '' ? 'yes' : 'no',
            ]);
            return $this->response->setStatusCode(400);
        }

        if (!$this->verifyAnetSignature($payload, $sig, $keyHex)) {
            log_message('warning', 'Authorize.Net webhook rejected: signature verification failed.');
            return $this->response->setStatusCode(401);
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return $this->response->setStatusCode(400);
        }

        // Replay/idempotency guard: ignore duplicate webhook payloads for 24h.
        $eventId = (string)($data['eventId'] ?? $data['notificationId'] ?? '');
        if ($eventId === '') {
            $eventId = hash('sha256', $payload);
        }
        $replayKey = 'anet_webhook_' . hash('sha256', $eventId);
        if (cache()->get($replayKey) !== null) {
            log_message('info', 'Authorize.Net webhook ignored duplicate event {eventId}.', [
                'eventId' => $eventId,
            ]);
            return $this->response->setStatusCode(200);
        }

        // Example maps for common event
        $txn = $data['payload']['id'] ?? null; // transaction id
        $inv = $data['payload']['order']['invoiceNumber'] ?? null; // we store payment_id in invoice
        $sta = $data['payload']['transactionStatus'] ?? null;

        if ($inv && $txn && is_string($sta)) {
            $status = strtolower($sta);
            $payment = (new EntryPaymentModel())->asArray()
                ->where('payment_id', (int)$inv)
                ->first();

            if ($payment) {
                $normalizedStatus = $this->normalizeWebhookPaymentStatus($status);
                $message = 'Authorize.Net status update: ' . $status;

                if ($normalizedStatus === 'paid') {
                    service('payments')->userSuccessPaidWithTransaction(
                        (string)$payment['entry_id'],
                        (string)$payment['payment_phase'],
                        (string)$txn,
                        (string)$txn
                    );
                } else {
                    service('payments')->markPaymentStatusByPaymentId(
                        (int)$payment['payment_id'],
                        $normalizedStatus,
                        (string)$txn,
                        $message
                    );
                }

                log_message('info', 'Authorize.Net webhook applied status update. event_id={eventId} payment_id={paymentId} entry_id={entryId} phase={phase} transaction_id={transactionId} raw_status={rawStatus} normalized_status={normalizedStatus}', [
                    'eventId' => $eventId,
                    'paymentId' => (string)($payment['payment_id'] ?? ''),
                    'entryId' => (string)($payment['entry_id'] ?? ''),
                    'phase' => (string)($payment['payment_phase'] ?? ''),
                    'transactionId' => (string)$txn,
                    'rawStatus' => $status,
                    'normalizedStatus' => $normalizedStatus,
                ]);
            } else {
                log_message('warning', 'Authorize.Net webhook received for unknown payment_id. event_id={eventId} invoice={invoice} transaction_id={transactionId} raw_status={rawStatus}', [
                    'eventId' => $eventId,
                    'invoice' => (string)$inv,
                    'transactionId' => (string)$txn,
                    'rawStatus' => $status,
                ]);
            }
        } else {
            log_message('warning', 'Authorize.Net webhook payload missing required transaction identifiers/status. event_id={eventId}', [
                'eventId' => $eventId,
            ]);
        }

        cache()->save($replayKey, 1, 86400);

        return $this->response->setStatusCode(200);
    }

    // ----- helpers -----

    /**
     * Verify Authorize.Net webhook signature.
     * $sig format: "SHA512=<hex>"
     */
    private function verifyAnetSignature(string $payload, string $sigHeader, string $keyHex): bool
    {
        // Normalize signature header
        $provided = $sigHeader;
        $prefix = 'SHA512=';
        if (stripos($provided, $prefix) === 0) {
            $provided = substr($provided, strlen($prefix));
        }
        $provided = strtolower(trim($provided));

        // Convert hex key to binary for HMAC
        $binKey = @pack('H*', $keyHex);
        if ($binKey === false) {
            return false;
        }

        $calc = strtolower(hash_hmac('sha512', $payload, $binKey));
        return hash_equals($calc, $provided);
    }

    private function normalizeWebhookPaymentStatus(string $status): string
    {
        return match (strtolower(trim($status))) {
            'settledsuccessfully',
            'capturedpendingsettlement',
            'authorizedpendingcapture' => 'paid',
            'heldforreview' => 'held_for_review',
            'declined',
            'communicationerror',
            'generalerror' => 'declined',
            'voided',
            'expired' => 'voided',
            'refundsettledsuccessfully',
            'returneditem',
            'chargeback' => 'refunded',
            default => 'pending',
        };
    }
}
