<?php

use App\Controllers\PaymentsController;
use App\Services\Payments\AuthorizeNetGateway;
use CodeIgniter\Test\CIUnitTestCase;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\MessagesType;
use net\authorize\api\contract\v1\MessagesType\MessageAType as ApiMessage;
use net\authorize\api\contract\v1\TransactionResponseType;
use net\authorize\api\contract\v1\TransactionResponseType\MessagesAType\MessageAType as TransactionMessage;

/**
 * @internal
 */
final class PaymentsControllerAuthorizeNetTest extends CIUnitTestCase
{
    public function testSuccessfulTransactionIsNotOverriddenByTopLevelI00001Message(): void
    {
        $response = new CreateTransactionResponse();

        $apiMessages = new MessagesType();
        $apiMessages->setResultCode('Ok');
        $apiMessages->addToMessage(
            (new ApiMessage())
                ->setCode('I00001')
                ->setText('Successful.')
        );
        $response->setMessages($apiMessages);

        $transaction = new TransactionResponseType();
        $transaction->setResponseCode('1');
        $transaction->setTransId('1234567890');
        $transaction->addToMessages(
            (new TransactionMessage())
                ->setCode('1')
                ->setDescription('This transaction has been approved.')
        );
        $response->setTransactionResponse($transaction);

        $result = $this->invokeExtractAuthorizeNetChargeResult($response, new AuthorizeNetGateway('production', 'login', 'key'));

        $this->assertTrue($result['ok']);
        $this->assertSame('1234567890', $result['transId']);
        $this->assertSame('', $result['message']);
    }

    public function testTopLevelErrorMessageStillReturnsFailure(): void
    {
        $response = new CreateTransactionResponse();

        $apiMessages = new MessagesType();
        $apiMessages->setResultCode('Error');
        $apiMessages->addToMessage(
            (new ApiMessage())
                ->setCode('E00027')
                ->setText('The transaction was unsuccessful.')
        );
        $response->setMessages($apiMessages);

        $result = $this->invokeExtractAuthorizeNetChargeResult($response, new AuthorizeNetGateway('production', 'login', 'key'));

        $this->assertFalse($result['ok']);
        $this->assertSame('', $result['transId']);
        $this->assertSame('E00027: The transaction was unsuccessful.', $result['message']);
    }

    public function testHeldForReviewMessageIsNormalizedForUsers(): void
    {
        $response = new CreateTransactionResponse();

        $transaction = new TransactionResponseType();
        $transaction->setResponseCode('4');
        $transaction->setTransId('987654321');
        $transaction->addToMessages(
            (new TransactionMessage())
                ->setCode('252')
                ->setDescription('Your order has been received. Thank you for your business!')
        );
        $response->setTransactionResponse($transaction);

        $result = $this->invokeExtractAuthorizeNetChargeResult($response, new AuthorizeNetGateway('production', 'login', 'key'));

        $this->assertFalse($result['ok']);
        $this->assertSame('987654321', $result['transId']);
        $this->assertSame(
            'Your payment was received by the processor but is being held for manual review. We have not marked this entry as paid yet. Please contact support if you need immediate confirmation.',
            $result['message']
        );
    }

    private function invokeExtractAuthorizeNetChargeResult(CreateTransactionResponse $response, AuthorizeNetGateway $gateway): array
    {
        $controller = new PaymentsController();
        $method = new ReflectionMethod(PaymentsController::class, 'extractAuthorizeNetChargeResult');
        $method->setAccessible(true);

        /** @var array{ok:bool,transId:string,message:string} $result */
        $result = $method->invoke($controller, $response, $gateway);

        return $result;
    }
}
