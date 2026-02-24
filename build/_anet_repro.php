<?php
require __DIR__ . '/../vendor/autoload.php';

use net\authorize\api\contract\v1 as AnetAPI;

$txn = new AnetAPI\TransactionRequestType();
$txn->setTransactionType('authCaptureTransaction');
$txn->setAmount('10.00');

$req = new AnetAPI\GetHostedPaymentPageRequest();
$req->setTransactionRequest($txn);

try {
    $json = json_encode($req, JSON_THROW_ON_ERROR);
    echo "OK len=" . strlen($json) . PHP_EOL;
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
