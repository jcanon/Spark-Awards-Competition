<?php

namespace App\Services\Payments;

use Config\AuthorizeNet as AuthorizeNetConfig;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

final class AuthorizeNetGateway
{
    private string $env;
    private string $loginId;
    private string $transKey;
    private AuthorizeNetConfig $config;
    private ?string $lastError = null;

    public function __construct(?string $env = null, ?string $loginId = null, ?string $transKey = null)
    {
        $this->config = config(AuthorizeNetConfig::class);
        $this->env = $env ?? ($this->config->useProduction() ? 'production' : 'sandbox');
        $this->loginId = $loginId ?? $this->config->apiLoginId();
        $this->transKey = $transKey ?? $this->config->transactionKey();
    }

    private function auth(): AnetAPI\MerchantAuthenticationType
    {
        $m = new AnetAPI\MerchantAuthenticationType();
        $m->setName($this->loginId);
        $m->setTransactionKey($this->transKey);
        return $m;
    }

    private function envConst()
    {
        return $this->env === 'production'
            ? \net\authorize\api\constants\ANetEnvironment::PRODUCTION
            : \net\authorize\api\constants\ANetEnvironment::SANDBOX;
    }

    public function hostedPaymentUrl(): string
    {
        return $this->config->hostedPaymentUrl();
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Server-to-server charge (only if you are not using Accept Hosted).
     * $nonce should be Authorize.Net opaqueData value from Accept.js.
     */
    public function chargeCard(
        string $nonce,
        int $amountCents,
        array $orderMeta = []
    ): AnetAPI\CreateTransactionResponse {
        $txnReq = new AnetAPI\TransactionRequestType();
        $txnReq->setTransactionType('authCaptureTransaction');
        $txnReq->setAmount(number_format($amountCents / 100, 2, '.', ''));

        $opaqueData = new AnetAPI\OpaqueDataType();
        $opaqueData->setDataDescriptor($orderMeta['descriptor'] ?? 'COMMON.ACCEPT.INAPP.PAYMENT');
        $opaqueData->setDataValue($nonce);

        $payment = new AnetAPI\PaymentType();
        $payment->setOpaqueData($opaqueData);
        $txnReq->setPayment($payment);

        if (!empty($orderMeta['invoice'])) {
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber(substr((string)$orderMeta['invoice'], 0, 20));
            $order->setDescription(substr((string)($orderMeta['description'] ?? ''), 0, 255));
            $txnReq->setOrder($order);
        }

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->auth());
        $request->setTransactionRequest($txnReq);

        $controller = new AnetController\CreateTransactionController($request);
        return $controller->executeWithApiResponse($this->envConst());
    }

    /**
     * Accept Hosted – returns a token to render the hosted form.
     * Required option: amountCents
     * Optional: returnUrl, cancelUrl
     */
    public function createAcceptHostedToken(array $options): ?string
    {
        $this->lastError = null;

        if ($this->loginId === '' || $this->transKey === '') {
            $this->lastError = 'Authorize.Net credentials are missing. Set ANET_SANDBOX_API_LOGIN_ID and ANET_SANDBOX_TRANSACTION_KEY (or production equivalents).';
            log_message('error', $this->lastError);
            return null;
        }

        try {
            $txn = new AnetAPI\TransactionRequestType();
            $txn->setTransactionType('authCaptureTransaction');
            $txn->setAmount(number_format(((int)($options['amountCents'] ?? 0)) / 100, 2, '.', ''));

            if (!empty($options['invoice'])) {
                $order = new AnetAPI\OrderType();
                $order->setInvoiceNumber(substr((string)$options['invoice'], 0, 20));
                $order->setDescription(substr((string)($options['description'] ?? ''), 0, 255));
                $txn->setOrder($order);
            }

            // Return/cancel options
            $settingReturn = new AnetAPI\SettingType();
            $settingReturn->setSettingName('hostedPaymentReturnOptions');
            $settingReturn->setSettingValue(json_encode([
                'showReceipt' => false,
                'url' => (string)($options['returnUrl'] ?? ''),
                'urlText' => 'Return',
                'cancelUrl' => (string)($options['cancelUrl'] ?? ''),
                'cancelUrlText' => 'Cancel',
            ], JSON_UNESCAPED_SLASHES));

            $settingButton = new AnetAPI\SettingType();
            $settingButton->setSettingName('hostedPaymentButtonOptions');
            $settingButton->setSettingValue(json_encode([
                'text' => 'Pay Now',
            ], JSON_UNESCAPED_SLASHES));

            $settingSecurity = new AnetAPI\SettingType();
            $settingSecurity->setSettingName('hostedPaymentSecurityOptions');
            $settingSecurity->setSettingValue(json_encode([
                'captcha' => false,
            ], JSON_UNESCAPED_SLASHES));

            $req = new AnetAPI\GetHostedPaymentPageRequest();
            $req->setMerchantAuthentication($this->auth());
            $req->setTransactionRequest($txn);
            $req->addToHostedPaymentSettings($settingReturn);
            $req->addToHostedPaymentSettings($settingButton);
            $req->addToHostedPaymentSettings($settingSecurity);

            $controller = new AnetController\GetHostedPaymentPageController($req);
            $resp = $controller->executeWithApiResponse($this->envConst());

            if (!$resp) {
                $this->lastError = 'Authorize.Net hosted token request returned an empty response.';
                log_message('error', $this->lastError);
                return null;
            }

            $messages = $resp->getMessages();
            if ($messages && $messages->getResultCode() === 'Ok') {
                $token = (string)$resp->getToken();
                return $token !== '' ? $token : null;
            }

            $code = $messages ? (string)$messages->getResultCode() : 'unknown';
            $text = '';
            if ($messages) {
                $messageList = $messages->getMessage();
                if (is_array($messageList) && isset($messageList[0])) {
                    $text = (string)$messageList[0]->getText();
                }
            }
            $this->lastError = trim('Authorize.Net request failed: ' . $code . ($text !== '' ? ' - ' . $text : ''));
            log_message('error', 'Authorize.Net hosted token request failed. Result: {code}. Message: {text}', [
                'code' => $code,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            $this->lastError = 'Authorize.Net request crashed: ' . $e->getMessage();
            log_message(
                'critical',
                'Authorize.Net hosted token request crashed: {class}: {message} at {file}:{line}',
                [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
        }

        return null;
    }

    /**
     * Look up transaction details by transaction ID.
     *
     * @return array{transaction_id:string,invoice:string,status:string,response_code:string,auth_code:string,account_type:string,account_number:string,transaction_type:string,submitted_at:string,amount:string}|null
     */
    public function getTransactionDetails(string $transactionId): ?array
    {
        $this->lastError = null;
        $transactionId = trim($transactionId);
        if ($transactionId === '') {
            $this->lastError = 'Missing transaction ID.';
            return null;
        }

        if ($this->loginId === '' || $this->transKey === '') {
            $this->lastError = 'Authorize.Net credentials are missing.';
            return null;
        }

        try {
            $req = new AnetAPI\GetTransactionDetailsRequest();
            $req->setMerchantAuthentication($this->auth());
            $req->setTransId($transactionId);

            $controller = new AnetController\GetTransactionDetailsController($req);
            $resp = $controller->executeWithApiResponse($this->envConst());

            if (!$resp) {
                $this->lastError = 'Authorize.Net transaction details returned an empty response.';
                return null;
            }

            $messages = $resp->getMessages();
            if (!$messages || $messages->getResultCode() !== 'Ok') {
                $code = $messages ? (string)$messages->getResultCode() : 'unknown';
                $text = '';
                if ($messages) {
                    $messageList = $messages->getMessage();
                    if (is_array($messageList) && isset($messageList[0])) {
                        $text = (string)$messageList[0]->getText();
                    }
                }
                $this->lastError = trim('Authorize.Net details request failed: ' . $code . ($text !== '' ? ' - ' . $text : ''));
                return null;
            }

            $tx = $resp->getTransaction();
            if (!$tx) {
                $this->lastError = 'Authorize.Net transaction details response did not include transaction data.';
                return null;
            }

            $order = $tx->getOrder();
            $invoice = $order ? (string)$order->getInvoiceNumber() : '';
            $responseCode = '';
            $respObj = $tx->getResponseCode();
            if ($respObj !== null) {
                $responseCode = (string)$respObj;
            }

            $submittedAt = '';
            $submitted = $tx->getSubmitTimeLocal();
            if ($submitted instanceof \DateTimeInterface) {
                $submittedAt = $submitted->format('Y-m-d H:i:s');
            }

            return [
                'transaction_id' => (string)$tx->getTransId(),
                'invoice' => $invoice,
                'status' => strtolower((string)$tx->getTransactionStatus()),
                'response_code' => $responseCode,
                'auth_code' => (string)$tx->getAuthCode(),
                'account_type' => (string)$tx->getAccountType(),
                'account_number' => (string)$tx->getAccountNumber(),
                'transaction_type' => (string)$tx->getTransactionType(),
                'submitted_at' => $submittedAt,
                'amount' => (string)$tx->getSettleAmount(),
            ];
        } catch (\Throwable $e) {
            $this->lastError = 'Authorize.Net details request crashed: ' . $e->getMessage();
            log_message(
                'critical',
                'Authorize.Net transaction details request crashed: {class}: {message} at {file}:{line}',
                [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
            return null;
        }
    }

    /**
     * Find a transaction by invoice number across unsettled and recently settled activity.
     *
     * @return array{transaction_id:string,invoice:string,status:string,response_code:string,auth_code:string,account_type:string,account_number:string,transaction_type:string,submitted_at:string,amount:string}|null
     */
    public function findTransactionByInvoice(string $invoice): ?array
    {
        $this->lastError = null;
        $invoice = trim($invoice);
        if ($invoice === '') {
            $this->lastError = 'Missing invoice.';
            return null;
        }

        if ($this->loginId === '' || $this->transKey === '') {
            $this->lastError = 'Authorize.Net credentials are missing.';
            return null;
        }

        $candidate = $this->findInUnsettledTransactionsByInvoice($invoice);
        if ($candidate !== null) {
            return $candidate;
        }

        $candidate = $this->findInRecentSettledTransactionsByInvoice($invoice, 3);
        if ($candidate !== null) {
            return $candidate;
        }

        return null;
    }

    /**
     * @return array{transaction_id:string,invoice:string,status:string,response_code:string,auth_code:string,account_type:string,account_number:string,transaction_type:string,submitted_at:string,amount:string}|null
     */
    private function findInUnsettledTransactionsByInvoice(string $invoice): ?array
    {
        try {
            $req = new AnetAPI\GetUnsettledTransactionListRequest();
            $req->setMerchantAuthentication($this->auth());

            $controller = new AnetController\GetUnsettledTransactionListController($req);
            $resp = $controller->executeWithApiResponse($this->envConst());
            if (!$resp) {
                return null;
            }

            $messages = $resp->getMessages();
            if (!$messages || $messages->getResultCode() !== 'Ok') {
                return null;
            }

            $transactions = $resp->getTransactions();
            if (!is_array($transactions)) {
                return null;
            }

            foreach ($transactions as $tx) {
                if (trim((string)$tx->getInvoiceNumber()) !== $invoice) {
                    continue;
                }

                return [
                    'transaction_id' => (string)$tx->getTransId(),
                    'invoice' => (string)$tx->getInvoiceNumber(),
                    'status' => strtolower((string)$tx->getTransactionStatus()),
                    'response_code' => '',
                    'auth_code' => '',
                    'account_type' => (string)$tx->getAccountType(),
                    'account_number' => (string)$tx->getAccountNumber(),
                    'transaction_type' => '',
                    'submitted_at' => '',
                    'amount' => (string)$tx->getSettleAmount(),
                ];
            }
        } catch (\Throwable $e) {
            log_message(
                'warning',
                'Authorize.Net unsettled transaction lookup failed: {class}: {message}',
                ['class' => $e::class, 'message' => $e->getMessage()]
            );
        }

        return null;
    }

    /**
     * @return array{transaction_id:string,invoice:string,status:string,response_code:string,auth_code:string,account_type:string,account_number:string,transaction_type:string,submitted_at:string,amount:string}|null
     */
    private function findInRecentSettledTransactionsByInvoice(string $invoice, int $daysBack): ?array
    {
        try {
            $listReq = new AnetAPI\GetSettledBatchListRequest();
            $listReq->setMerchantAuthentication($this->auth());
            $utcNow = new \DateTime('now', new \DateTimeZone('UTC'));
            $utcStart = clone $utcNow;
            $utcStart->modify('-' . max(1, $daysBack) . ' days');
            $listReq->setFirstSettlementDate($utcStart);
            $listReq->setLastSettlementDate($utcNow);

            $listController = new AnetController\GetSettledBatchListController($listReq);
            $listResp = $listController->executeWithApiResponse($this->envConst());
            if (!$listResp) {
                return null;
            }

            $messages = $listResp->getMessages();
            if (!$messages || $messages->getResultCode() !== 'Ok') {
                return null;
            }

            $batches = $listResp->getBatchList();
            if (!is_array($batches)) {
                return null;
            }

            foreach ($batches as $batch) {
                $batchId = (string)$batch->getBatchId();
                if ($batchId === '') {
                    continue;
                }

                $txReq = new AnetAPI\GetTransactionListRequest();
                $txReq->setMerchantAuthentication($this->auth());
                $txReq->setBatchId($batchId);

                $txController = new AnetController\GetTransactionListController($txReq);
                $txResp = $txController->executeWithApiResponse($this->envConst());
                if (!$txResp) {
                    continue;
                }

                $txMessages = $txResp->getMessages();
                if (!$txMessages || $txMessages->getResultCode() !== 'Ok') {
                    continue;
                }

                $transactions = $txResp->getTransactions();
                if (!is_array($transactions)) {
                    continue;
                }

                foreach ($transactions as $tx) {
                    if (trim((string)$tx->getInvoiceNumber()) !== $invoice) {
                        continue;
                    }

                    return [
                        'transaction_id' => (string)$tx->getTransId(),
                        'invoice' => (string)$tx->getInvoiceNumber(),
                        'status' => strtolower((string)$tx->getTransactionStatus()),
                        'response_code' => '',
                        'auth_code' => '',
                        'account_type' => (string)$tx->getAccountType(),
                        'account_number' => (string)$tx->getAccountNumber(),
                        'transaction_type' => '',
                        'submitted_at' => '',
                        'amount' => (string)$tx->getSettleAmount(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            log_message(
                'warning',
                'Authorize.Net settled transaction lookup failed: {class}: {message}',
                ['class' => $e::class, 'message' => $e->getMessage()]
            );
        }

        return null;
    }
}
