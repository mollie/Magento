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

    public function testDoesNotCancelAnOrderWhoseTransactionIsAlreadyPaid()
    {
        $order = $this->getMockBuilder('Mage_Sales_Model_Order');
        $order->setMethods(['getMollieTransactionId', 'getPayment', 'cancel', 'getIncrementId']);
        $order = $order->getMock();

        $order->method('getMollieTransactionId')->willReturn('tr_paid123');
        $order->method('getIncrementId')->willReturn('100000001');
        $order->method('getPayment')->willThrowException(new \Exception('[TEST] No payment method available'));

        $order->expects($this->never())->method('cancel');

        $helper = $this->getHelper('mpm');
        $helper->method('getStoreConfig')->willReturn(true);
        $helper->method('getOrderFromSession')->willReturn($order);
        $helper->method('getApiKey')->willReturn('test_dummyapikeydummyapikeydummyapikey');
        $helper->method('getMollieAPI')->willReturn($this->mockMollieApiReturningStatus('paid'));

        /** @var Mollie_Mpm_ApiController $instance */
        $instance = $this->loadController('Mollie_Mpm_ApiController', array('id' => 123));
        $instance->paymentAction();
    }

    public function testStillCancelsAnOrderWhoseTransactionIsNotPaid()
    {
        $order = $this->getMockBuilder('Mage_Sales_Model_Order');
        $order->setMethods(['getMollieTransactionId', 'getPayment', 'cancel', 'save', 'addStatusHistoryComment', 'getIncrementId']);
        $order = $order->getMock();

        $order->method('getMollieTransactionId')->willReturn('tr_open123');
        $order->method('getIncrementId')->willReturn('100000002');
        $order->method('getPayment')->willThrowException(new \Exception('[TEST] No payment method available'));

        $order->expects($this->once())->method('cancel');

        $helper = $this->getHelper('mpm');
        $helper->method('getStoreConfig')->willReturn(true);
        $helper->method('getOrderFromSession')->willReturn($order);
        $helper->method('getApiKey')->willReturn('test_dummyapikeydummyapikeydummyapikey');
        $helper->method('getMollieAPI')->willReturn($this->mockMollieApiReturningStatus('open'));

        /** @var Mollie_Mpm_ApiController $instance */
        $instance = $this->loadController('Mollie_Mpm_ApiController', array('id' => 123));
        $instance->paymentAction();
    }

    /**
     * Builds a stand-in for the Mollie API client whose payments endpoint reports the given status.
     *
     * @param string $status
     * @return object
     */
    private function mockMollieApiReturningStatus($status)
    {
        $payment = new stdClass;
        $payment->status = $status;

        $endpoint = $this->getMockBuilder('stdClass')->addMethods(['get'])->getMock();
        $endpoint->method('get')->willReturn($payment);

        $api = new stdClass;
        $api->payments = $endpoint;
        $api->orders = $endpoint;

        return $api;
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