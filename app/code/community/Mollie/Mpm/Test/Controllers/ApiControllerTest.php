<?php
class Mollie_Mpm_Test_Controllers_ApiControllerTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testReturnsAn503StatusCodeOnError()
    {
        $mock = $this->createMock('Mollie_Mpm_Model_Mollie');
        $mock->method('getOrderIdByTransactionId')->willThrowException(new \Exception('[TEST] Something went wrong'));

        $this->getConfig()->addModelMock('mpm/mollie', $mock);

        /** @var Mollie_Mpm_ApiController $instance */
        $instance = $this->loadController('Mollie_Mpm_ApiController', array('id' => 123));
        $instance->webhookAction();

        $response = Mage::app()->getResponse();

        $this->assertEquals(503, $response->getHttpResponseCode());

        $messages = $this->getLogMessages();
        $this->assertTrue(
            in_array('error: [TEST] Something went wrong', $messages),
            'We expect a message to be logged'
        );
    }

    public function testCancelUnprocessedOrder()
    {
        $order = $this->getMockBuilder('Mage_Sales_Model_Order');
        $order->setMethods(['getMollieTransactionId', 'getPayment', 'cancel', 'save', 'addStatusHistoryComment']);
        $order = $order->getMock();

        $order->method('getMollieTransactionId')->willReturn('123');
        $order->method('getPayment')->willThrowException(new \Exception('[TEST] No payment method available'));

        $order->expects($this->once())->method('cancel');
        $order->expects($this->once())->method('addStatusHistoryComment')->with(
            ":<br>\n[TEST] No payment method available"
        );

        $helper = $this->getHelper('mpm');
        $helper->method('getStoreConfig')->willReturn(true);
        $helper->method('getOrderFromSession')->willReturn($order);

        /** @var Mollie_Mpm_ApiController $instance */
        $instance = $this->loadController('Mollie_Mpm_ApiController', array('id' => 123));
        $instance->paymentAction();
    }

    public function testDoesNotCancelledWhenDisabled()
    {
        $order = $this->getMockBuilder('Mage_Sales_Model_Order');
        $order->setMethods(['getMollieTransactionId', 'getPayment', 'cancel']);
        $order = $order->getMock();

        $order->method('getMollieTransactionId')->willReturn('123');
        $order->method('getPayment')->willThrowException(new \Exception('[TEST] No payment method available'));

        $order->expects($this->never())->method('cancel');

        $helper = $this->getHelper('mpm');
        $helper->method('getStoreConfig')->willReturn(false);
        $helper->method('getOrderFromSession')->willReturn($order);

        /** @var Mollie_Mpm_ApiController $instance */
        $instance = $this->loadController('Mollie_Mpm_ApiController', array('id' => 123));
        $instance->paymentAction();
    }
}