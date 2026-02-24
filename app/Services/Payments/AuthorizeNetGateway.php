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
}
