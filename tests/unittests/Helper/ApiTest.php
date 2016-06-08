<?php
/**
 * @covers Mollie_Mpm_Helper_Api
 */
class Mollie_Mpm_Helper_ApiTest extends MagentoPlugin_TestCase
{
	/**
	 * @var Mollie_API_Client $api_client
	 * @var Mollie_Mpm_Helper_Api $api
	 * @var Mollie_Mpm_Helper_Data $data
	 */
	protected $api_client;
	protected $api;
	protected $data;
	protected $payment;

	protected function setUp()
	{
		parent::setUp();

		$this->data = $this->getMock("stdClass", array("getApiKey", "getBankTransferDueDateDays"), array());

		$this->data->expects($this->any())
			->method("getApiKey")
			->will($this->returnValue("test_decafbad"));

		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(
				array(
					array('mpm', $this->data),
					array('core', new Mage_Core_Helper_Data()),
				)
			));

		$this->api_client = $this->getMock("stdClass", array(), array());

		$this->payment = $this->getMock("stdClass", array("isPaid"), array());

		$this->api_client->payments = $this->getMock("stdClass", array("get"), array());
		$this->api_client->methods = $this->getMock("stdClass", array("all"), array());

		$this->api_client->payments->expects($this->any())
			->method("get")
			->will($this->returnValue($this->payment));

		$this->api = $this->getMock("Test_Mollie_Mpm_Helper_Api", array("_getMollieAPI", "__construct"), array(), '', false);

		$this->api->expects($this->any())
			->method("_getMollieAPI")
			->will($this->returnValue($this->api_client));
	}

	public function testCreatePaymentActionRequiresParameters()
	{
		$API = new Test_Mollie_Mpm_Helper_Api();

		$parameters = array (
			'amount' => 1000,
			'description' => 'Description',
			'order_id' => 1,
			'redirect_url' => 'http://customer.local/redirect.php',
			'method' => 'Method',
			'issuer' => '',
		);

		foreach (array('amount', 'redirect_url') as $parameter)
		{
			$testParameters = $parameters;
			$testParameters[$parameter] = NULL;

			$result = call_user_func_array(array($API, 'createPayment'), $testParameters);

			$this->assertFalse($result);
			$this->assertNotEmpty($API->getErrorMessage());
		}
	}

	public function testCheckPaymentActionChecksTransactionId()
	{
		$result = $this->api->checkPayment(NULL);

		$this->assertFalse($result);
		$this->assertEquals("Er is een onjuist transactie ID opgegeven", $this->api->getErrorMessage());
	}

	public function testCheckPaymentActionCancelledDetectedNotPaid ()
	{
		$this->payment->status = "cancelled";
		$this->payment->amount = 1000;

		$result = $this->api->checkPayment("1234567890");
		$this->assertTrue($result);
		$this->assertFalse($this->api->getPaidStatus());
	}

	public function testGetPaidStatusReturnsFalseIfCheckPaymentFails ()
	{
		$this->payment->status = "expired";
		$this->payment->amount = 1000;

		$result = $this->api->checkPayment("1234567890");
		$this->assertTrue($result);
		$this->assertFalse($this->api->getPaidStatus());
	}
}

/**
 * @ignore
 */
class Test_Mollie_Mpm_Helper_Api extends Mollie_Mpm_Helper_Api {

}