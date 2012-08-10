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

	protected function setUp()
	{
		parent::setUp();

		$this->HelperData = new Mollie_Mpm_Helper_Data;

		$this->mage->expects($this->any())
			->method("Helper")
			->with("mpm/data")
			->will($this->returnValue($this->HelperData));

		$this->resource = $this->getMock("stdClass", array("getConnection", "getTableName"));
		$this->mage->expects($this->any())
			->method("getSingleton")
			->will($this->returnValueMap(array(
				array("core/resource", $this->resource)
		)));

		$this->readconn = $this->getMock("stdClass", array("fetchAll"));
		$this->writeconn = $this->getMock("stdClass", array("fetchAll"));

		$this->resource->expects($this->any())
			->method("getConnection")
			->will($this->returnValueMap(array(
				array("core_read", $this->readconn),
				array("core_write", $this->writeconn),
		)));

		$this->resource->expects($this->any())
			->method("getTableName")
			->will($this->returnArgument(0));
	}

	public function testGetStatusById()
	{
		$this->readconn->expects($this->once())
			->method("fetchAll")
			->with("SELECT `bank_status` FROM `mollie_payments` WHERE `transaction_id` = '1bba1d8fdbd8103b46151634bdbe0a60'")
			->will($this->returnValue(array(array("bank_status" => Mollie_Mpm_Model_Idl::IDL_SUCCESS))))
		;

		$this->writeconn->expects($this->never())
			->method("fetchAll");

		$this->assertEquals(array("bank_status" => Mollie_Mpm_Model_Idl::IDL_SUCCESS), $this->HelperData->getStatusById(self::TRANSACTION_ID));
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

	public function testGetPartnerid()
	{
		$this->mage->expects($this->once())
			->method("getStoreConfig")
			->with("mollie/settings/partnerid")
			->will($this->returnValue(1001));

		$this->assertEquals(1001, $this->HelperData->getPartnerid());
	}

	public function testGetProfilekey()
	{
		$this->mage->expects($this->once())
			->method("getStoreConfig")
			->with("mollie/settings/profilekey")
			->will($this->returnValue("decafbad"));

		$this->assertEquals("decafbad", $this->HelperData->getProfilekey());
	}

	public function testGetTestModeEnabled()
	{
		$this->mage->expects($this->once())
			->method("getStoreConfig")
			->with("mollie/idl/testmode")
			->will($this->returnValue(TRUE));

		$this->assertTrue($this->HelperData->getTestModeEnabled());
	}
}