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
                ->select('payment_receipt, payment_date')
                ->where('entry_id', $entryId)
                ->orderBy('payment_phase', 'ASC')
                ->asArray()
                ->first();

            if ($row && ($row['payment_receipt'] ?? '') !== '') {
                return $this->response->setJSON([
                    'status' => 'paid',
                    'receipt' => $row['payment_receipt'],
                    'date' => $row['payment_date'],
                ]);
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
            return $this->response->setStatusCode(400);
        }

        if (!$this->verifyAnetSignature($payload, $sig, $keyHex)) {
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
            return $this->response->setStatusCode(202);
        }

        // Example maps for common event
        $txn = $data['payload']['id'] ?? null; // transaction id
        $inv = $data['payload']['order']['invoiceNumber'] ?? null; // we store payment_id in invoice
        $sta = $data['payload']['transactionStatus'] ?? null;

        if ($inv && $txn && is_string($sta)) {
            $status = strtolower($sta);
            $okStatuses = [
                'settledsuccessfully',
                'capturedpendingsettlement',
                'authorizedpendingcapture',
            ];
            if (!in_array($status, $okStatuses, true)) {
                return $this->response->setStatusCode(202);
            }

            $payment = (new EntryPaymentModel())->asArray()
                ->where('payment_id', (int)$inv)
                ->first();

            if ($payment) {
                service('payments')->userSuccessPaid(
                    (string)$payment['entry_id'],
                    (string)$payment['payment_phase'],
                    (string)$txn
                );
            }
        }

        cache()->save($replayKey, 1, 86400);

        return $this->response->setStatusCode(204);
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
}
