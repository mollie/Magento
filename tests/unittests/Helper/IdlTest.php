<?php
/**
 * @covers Mollie_Mpm_Helper_Idl
 */
class Mollie_Mpm_Helper_IdlTest extends MagentoPlugin_TestCase
{
	protected static $banks_xml = <<< EOX
<?xml version="1.0" ?>
<response>
	<bank>
		<bank_id>1234</bank_id>
		<bank_name>Test bank 1</bank_name>
	</bank>
	<bank>
		<bank_id>0678</bank_id>
		<bank_name>Test bank 2</bank_name>
	</bank>
	<message>This is the current list of banks and their ID's that currently support iDEAL-payments</message>
</response>
EOX;

	protected static $check_payment_xml = <<< EOX
<?xml version="1.0"?>
<response>
	<order>
		<transaction_id>1234567890</transaction_id>
		<amount>1000</amount>
		<currency>EUR</currency>
		<payed>true</payed>
		<message>This iDEAL-order has successfuly been payed for, and this is the first time you check it.</message>
	</order>
</response>
EOX;

	protected static $check_payment_notpayed = <<< EOX
<?xml version="1.0" ?>
<response>
	<order>
		<transaction_id>1234567890</transaction_id>
		<amount>2095</amount>
		<currency>EUR</currency>
		<payed>false</payed>
		<message>This iDEAL-order wasn't payed for, or was already checked by you. (We give payed=true only once, for your protection)</message>
		<status>CheckedBefore</status>
	</order>

</response>
EOX;

	protected static $check_payment_cancelled = <<< EOX
<?xml version="1.0" ?>
<response>
	<order>
		<transaction_id>1234567890</transaction_id>
		<amount>118</amount>
		<currency>EUR</currency>
		<payed>false</payed>
		<message>This iDEAL-order wasn't payed for, or was already checked by you. (We give payed=true only once, for your protection)</message>
		<status>Cancelled</status>
	</order>
</response>
EOX;

	protected static $create_payment_xml = <<< EOX
<?xml version="1.0"?>
<response>
	<order>
		<transaction_id>1234567890</transaction_id>
		<amount>1000</amount>
		<currency>EUR</currency>
		<URL>http://bankurl.com/?transaction_id=1234567890</URL>
		<message>Your iDEAL-payment has succesfuly been setup. Your customer should visit the given URL to make the payment</message>
	</order>
</response>
EOX;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $HelperData;

	protected function setUp()
	{
		parent::setUp();

		$this->HelperData = $this->getMock("stdClass", array("getPartnerid", "getProfilekey", "getTestModeEnabled"), array());

		$this->HelperData->expects($this->any())
			->method("getPartnerid")
			->will($this->returnValue(1001));

		$this->HelperData->expects($this->any())
			->method("getProfilekey")
			->will($this->returnValue("decafbad"));

		$this->HelperData->expects($this->any())
			->method("getTestModeEnabled")
			->will($this->returnValue(TRUE));

		$this->mage->expects($this->any())
			->method("Helper")
			->with("mpm/data")
			->will($this->returnValue($this->HelperData));
	}

	public function testBankListActionReturnsArrayOfBanks()
	{
		$expectedBanks = array (
			'1234' => 'Test bank 1',
			'0678' => 'Test bank 2'
		);

		$iDEAL = $this->getMock("Test_Mollie_Mpm_Helper_Idl", array("_sendRequest"), array(1001));
		$iDEAL->expects($this->once())
			->method("_sendRequest")
			->will($this->returnValue(self::$banks_xml));

		$banks = $iDEAL->getBanks();
		
		$this->assertEquals($banks, $expectedBanks);
	}

	public function testCreatePaymentActionRequiresParameters()
	{
		$iDEAL = $this->getMock("Test_Mollie_Mpm_Helper_Idl", array("_sendRequest"), array(1001));
		$iDEAL->expects($this->any())
			->method("_sendRequest")
			->will($this->returnValue(""));

		$parameters = array (
			'bank_id' => '0031',
			'amount' => '1000',
			'description' => 'Description', 
			'return_url' => 'http://customer.local/return.php', 
			'report_url' => 'http://customer.local/report.php'
		);
				
		foreach (array('bank_id','amount','description','return_url','report_url') as $parameter)
		{
			$testParameters = $parameters;
			$testParameters[$parameter] = NULL;
			
			$result = call_user_func_array(array($iDEAL, 'createPayment'), $testParameters);
			
			$this->assertFalse($result);
			$this->assertNotEmpty($iDEAL->getErrorMessage());
		}
	}

	public function testCreatePaymentActionFailureSetsErrorVariables()
	{
		$iDEAL = $this->getMock("Test_Mollie_Mpm_Helper_Idl", array("_sendRequest"), array(1001));

		$iDEAL->expects($this->once())
			->method("_sendRequest")
			->will($this->returnValue(
				"<?xml version=\"1.0\" ?>
				<response>
					<item type=\"error\">
						<errorcode>-3</errorcode>
						<message>The Report URL you have specified has an issue</message>
					</item>
				</response>"
			));

		$result = $iDEAL->createPayment(
			'0031',
			'1000',
			'Description',
			'http://customer.local/return.php',
			'http://customer.local/report.php'
		);
		
		$this->assertFalse($result);		
		$this->assertEquals($iDEAL->getErrorMessage(), 'The Report URL you have specified has an issue');
		$this->assertEquals($iDEAL->getErrorCode(), '-3');
		
	}
	
	public function testCheckPaymentActionChecksTransactionId()
	{
		$iDEAL = new Test_Mollie_Mpm_Helper_Idl(1001);
		$result = $iDEAL->checkPayment(NULL);

		$this->assertFalse($result);
		$this->assertEquals("Er is een onjuist transactie ID opgegeven", $iDEAL->getErrorMessage());
	}

	public function testCheckPaymentActionCheckedBeforeDetectedNotPaid ()
	{
		$iDEAL = $this->getMock("Test_Mollie_Mpm_Helper_Idl", array("_sendRequest"), array(1001));

		$iDEAL->expects($this->once())
			->method("_sendRequest")
			->will($this->returnValue(self::$check_payment_notpayed));

		$result = $iDEAL->checkPayment("1234567890");
		$this->assertTrue($result);
		$this->assertFalse($iDEAL->getPaidStatus());
		$this->assertEquals("CheckedBefore", $iDEAL->getBankStatus());
	}

	public function testCheckPaymentActionCancelledDetectedNotPaid ()
	{
		$iDEAL = $this->getMock("Test_Mollie_Mpm_Helper_Idl", array("_sendRequest"), array(1001));

		$iDEAL->expects($this->once())
			->method("_sendRequest")
			->will($this->returnValue(self::$check_payment_cancelled));

		$result = $iDEAL->checkPayment("1234567890");
		$this->assertTrue($result);
		$this->assertFalse($iDEAL->getPaidStatus());
		$this->assertEquals("Cancelled", $iDEAL->getBankStatus());
	}

	public function testGetPaidStatusReturnsFalseIfCheckPaymentFails ()
	{
		$iDEAL = $this->getMock("Test_Mollie_Mpm_Helper_Idl", array("_sendRequest"), array(1001));

		$this->assertFalse($iDEAL->getPaidStatus());

		$iDEAL->expects($this->once())
			->method("_sendRequest")
			->will($this->returnValue(""));

		$this->assertFalse($iDEAL->checkPayment("0123456789"));

		$this->assertFalse($iDEAL->getPaidStatus());
	}

	public function testAPIErrorDetectedCorrectly ()
	{
		$iDEAL = new Test_Mollie_Mpm_Helper_Idl(1001);

		$xml = new SimpleXMLElement("<?xml version=\"1.0\" ?>
		<response>
			<item type=\"error\">
				<errorcode>42</errorcode>
				<message>The flux capacitator is over capacity</message>
			</item>
		</response>");

		$this->assertTrue($iDEAL->_XMLisError($xml));
	}

	public function testNormalXmlIsNotAnError()
	{
		$iDEAL = new Test_Mollie_Mpm_Helper_Idl(1001);

		$xml = new SimpleXMLElement(self::$banks_xml);

		$this->assertFalse($iDEAL->_XMLisError($xml));
	}

	public function testBankErrorDetectedCorrectly()
	{
		$iDEAL = new Test_Mollie_Mpm_Helper_Idl(1001);

		$xml = new SimpleXMLElement("<?xml version=\"1.0\" ?>
		<response>
			<order>
				<transaction_id></transaction_id>
				<amount></amount>
				<currency></currency>
				<URL>https://www.mollie.nl/files/idealbankfailure.html</URL>
				<error>true</error>
				<message>Your iDEAL-payment has not been setup because of a temporary technical error at the bank</message>
			</order>
		</response>");

		$this->assertTrue($iDEAL->_XMLisError($xml));
	}

	public function testInvalidXmlDetected ()
	{
		$iDEAL = new Test_Mollie_Mpm_Helper_Idl(1001);
		$this->assertFalse($iDEAL->_XMLtoObject("invalid xml"));
		$this->assertEquals(-2, $iDEAL->getErrorCode());
		$this->assertEquals("Kon XML resultaat niet verwerken", $iDEAL->getErrorMessage());
	}
}

/**
 * @ignore
 */
class Test_Mollie_Mpm_Helper_Idl extends Mollie_Mpm_Helper_Idl {

	/**
	 * Make public so we can test it.
	 *
	 * @param SimpleXMLElement $xml
	 * @return bool
	 */
	public function _XMLisError(SimpleXMLElement $xml)
	{
		return parent::_XMLisError($xml);
	}

	/**
	 * Make public so we can test it.
	 *
	 * @param $xml
	 * @return bool|object
	 */
	public function _XMLtoObject($xml)
	{
		return parent::_XMLtoObject($xml);
	}
}