<?php
/**
 * @covers Mollie_Mpm_Helper_Data
 */
class Mollie_Mpm_Helper_DataTest extends MagentoPlugin_TestCase
{
	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|Mollie_Mpm_Helper_Data
	 */
	protected $HelperData;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $resource;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $writeconn;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $readconn;

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 1337;

	const STORE_ID = 0;

	protected function setUp()
	{
		parent::setUp();

		$this->HelperData = $this->getMock("Mollie_Mpm_Helper_Data", array("getCurrentStore"));
		$this->HelperData->expects($this->any())
			->method("getCurrentStore")
			->will($this->returnValue(self::STORE_ID));

		$this->mage->expects($this->any())
			->method("Helper")
			->with("mpm")
			->will($this->returnValue($this->HelperData));

		$this->resource = $this->getMock("stdClass", array("getConnection", "getTableName"));
		$this->mage->expects($this->any())
			->method("getSingleton")
			->will($this->returnValueMap(array(
				array("core/resource", $this->resource)
		)));

		$this->readconn = $this->getMock("stdClass", array("fetchAll", "quote"));
		$this->writeconn = $this->getMock("stdClass", array("fetchAll", "quote"));

		$this->resource->expects($this->any())
			->method("getConnection")
			->will($this->returnValueMap(array(
				array("core_read", $this->readconn),
				array("core_write", $this->writeconn),
		)));

		/*
		 * Stubs that are fake quote-ers, does not really escape but just quote.
		 */
		$this->readconn->expects($this->any())
			->method("quote")
			->will($this->returnCallback(function ($arg) { return "'$arg'"; }));
        $this->writeconn->expects($this->any())
			->method("quote")
			->will($this->returnCallback(function ($arg) { return "'$arg'"; }));

		$this->resource->expects($this->any())
			->method("getTableName")
			->will($this->returnArgument(0));
	}

	public function testGetConfigWithInvalidKeyReturnsNull()
	{
		$this->mage->expects($this->never())
			->method("getStoreConfig");

		$this->assertNull($this->HelperData->getConfig("api", "foo"));
	}

	public function testGetVersion()
	{

		$xml = simplexml_load_string('
			<modules>
				<Mollie_Mpm>
					<active>true</active>
					<codePool>community</codePool>
					<depends>
						<Mage_Payment />
					</depends>
					<version>4.2.2</version>
				</Mollie_Mpm>
			</modules>
		');

		$config = $this->getMock("stdClass", array("getNode"));
		$config->expects($this->once())
			->method("getNode")
			->will($this->returnValue($xml));

		$this->mage->expects($this->once())
			->method("getConfig")
			->will($this->returnValue($config));

		$this->assertEquals("4.2.2", $this->HelperData->getModuleVersion());
	}

	public function testGetStatusById()
	{
		$this->readconn->expects($this->once())
			->method("fetchAll")
			->with("SELECT `bank_status`, `updated_at` FROM `mollie_payments` WHERE `transaction_id` = '1bba1d8fdbd8103b46151634bdbe0a60'")
			->will($this->returnValue(array(array("bank_status" => Mollie_Mpm_Model_Api::STATUS_PAID))))
		;

		$this->writeconn->expects($this->never())
			->method("fetchAll");

		$this->assertEquals(array("bank_status" => Mollie_Mpm_Model_Api::STATUS_PAID), $this->HelperData->getStatusById(self::TRANSACTION_ID));
	}

	public function testGetOrderIdByTransactionId()
	{
		$this->readconn->expects($this->once())
			->method("fetchAll")
			->with("SELECT `order_id` FROM `mollie_payments` WHERE `transaction_id` = '1bba1d8fdbd8103b46151634bdbe0a60'")
			->will($this->returnValue(array(array("order_id" => self::ORDER_ID))))
		;

		$this->writeconn->expects($this->never())
			->method("fetchAll");

		$this->assertEquals(self::ORDER_ID, $this->HelperData->getOrderIdByTransactionId(self::TRANSACTION_ID));
	}

	public function testGetOrderIdByTransactionIdNotFound()
	{
		$this->readconn->expects($this->once())
			->method("fetchAll")
			->with("SELECT `order_id` FROM `mollie_payments` WHERE `transaction_id` = '1bba1d8fdbd8103b46151634bdbe0a60'")
			->will($this->returnValue(array()))
		;

		$this->writeconn->expects($this->never())
			->method("fetchAll");

		$this->assertNull($this->HelperData->getOrderIdByTransactionId(self::TRANSACTION_ID));
	}

	public function testGetApikey()
	{
		$this->mage->expects($this->once())
			->method("getStoreConfig")
			->with("payment/mollie/apikey", self::STORE_ID)
			->will($this->returnValue("decafbad"));

		$this->assertEquals("decafbad", $this->HelperData->getApiKey());
	}
}
