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
}