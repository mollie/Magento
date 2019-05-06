<?php

class Mollie_Mpm_Test_Model_Client_OrdersTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testUsesTheCorrectApiToResumeTheTransaction()
    {
        $apiMock = $this->createMock('\Mollie\Api\MollieApiClient');
        $ordersApiMock = $this->createMock('\Mollie\Api\Endpoints\OrderEndpoint');
        $apiMock->orders = $ordersApiMock;

        // Make sure the right API is called.
        $ordersApiMock->expects($this->once())->method('get')->willReturn(new \Mollie\Api\Resources\Order($apiMock));

        $mollieHelperMock = $this->getHelper('mpm');
        $mollieHelperMock->method('getMollieAPI')->willReturn($apiMock);

        $order = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->setMethods(['getMollieTransactionId', 'getPayment'])
            ->getMock();

        $order->method('getMollieTransactionId')->willReturn(123);
        $order->method('getPayment')->willReturn(new Mage_Sales_Model_Order_Payment);

        $instance = new Mollie_Mpm_Model_Client_Orders();
        $instance->startTransaction($order);
    }
}