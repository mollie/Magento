<?php
/**
 * @covers Mollie_Mpm_Model_Idl
 */
class Mollie_Mpm_Model_IdlTest extends MagentoPlugin_TestCase
{
	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|Mollie_Mpm_Helper_Idl
	 */
	protected $idealhelper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|Mollie_Mpm_Helper_Data
	 */
	protected $datahelper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $resource;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $readconn;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $writeconn;

	public function setUp()
	{
		parent::setUp();

		$this->datahelper  = $this->getMock("Mollie_Mpm_Helper_Data", array("getConfig"));
		$this->idealhelper = $this->getMock("Mollie_Mpm_Helper_Idl", array("createPayment", "getTransactionId", "getBankURL"), array(), "", FALSE);

		/*
		 * Mage::Helper() method
		 */
		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(array(
			array("mpm/data", $this->datahelper),
			array("mpm/idl", $this->idealhelper),
		)));

		$this->resource = $this->getMock("stdClass", array("getConnection", "getTableName"));
		$this->mage->expects($this->any())
			->method("getSingleton")
			->will($this->returnValueMap(array(
			array("core/resource", $this->resource)
		)));

		$this->resource->expects($this->any())
			->method("getTableName")
			->will($this->returnArgument(0));

		$this->readconn = $this->getMock("stdClass", array("fetchAll", "quote", "update", "insert"));
		$this->writeconn = $this->getMock("stdClass", array("fetchAll", "quote", "update", "insert"));

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
	}

	public function testIsNotAvailableIfDisabledInAdmin()
	{
		$this->datahelper->expects($this->once())
			->method("getConfig")
			->with("idl","active")
			->will($this->returnValue(FALSE));

		$model = new Mollie_Mpm_Model_Idl();
		$this->assertFalse($model->isAvailable());
	}

	public function testIsAvailableIfEnabledInAdmin()
	{
		$this->datahelper->expects($this->once())
			->method("getConfig")
			->with("idl","active")
			->will($this->returnValue(TRUE));

		$model = new Mollie_Mpm_Model_Idl();
		$this->assertTrue($model->isAvailable());
	}

	public function testCannotUseForOtherCurrencyThanEUR()
	{
		$model = new Mollie_Mpm_Model_Idl();
		$this->assertTrue($model->canUseForCurrency("EUR"));
		$this->assertFalse($model->canUseForCurrency("USD"));
	}

	public function testCanUseWithMultiShipping()
	{
		$model = new Mollie_Mpm_Model_Idl();
		$this->assertTrue($model->canUseForMultishipping());
	}

	public function testSetPaymentDoesNotAcceptNull()
	{
		$this->mage->expects($this->once())
			->method("throwException")
			->with('Ongeldige order_id of transaction_id...')
			->will($this->throwException(new Test_Exception("NO_NULL", 400)));

		$this->setExpectedException("Test_Exception", "NO_NULL", 400);

		$model = new Mollie_Mpm_Model_Idl;
		$model->setPayment(NULL, NULL);
	}

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 1337;

	public function testSetPaymentWorksPerfectly()
	{
		$this->writeconn->expects($this->once())
			->method("insert")
			->with("mollie_payments", array("order_id" => self::ORDER_ID, "transaction_id" => self::TRANSACTION_ID, "method" => "idl", "created_at" => "2013-12-11 10:09:09",));

		$this->writeconn->expects($this->never())
			->method("update");

		$this->readconn->expects($this->never())
			->method("insert");

		/** @var $model Mollie_Mpm_Model_Idl|PHPUnit_Framework_MockObject_MockObject */
		$model = $this->getMock("Mollie_Mpm_Model_Idl", array("getCurrentDate"));
		$model->expects($this->once())
			->method("getCurrentDate")
			->will($this->returnValue("2013-12-11 10:09:09"));

		$model->setPayment(self::ORDER_ID, self::TRANSACTION_ID);
	}

	public function testUpdatePaymentWithCancelledPayment()
	{
		$this->writeconn->expects($this->once())
			->method("update")
			->with("mollie_payments", array(
			"bank_status" => "Cancelled",
			"updated_at" => "2013-12-11 10:09:09",
		), "transaction_id = '".self::TRANSACTION_ID."'");

		$this->writeconn->expects($this->never())
			->method("insert");

		$this->readconn->expects($this->never())
			->method("update");

		/** @var $model Mollie_Mpm_Model_Idl|PHPUnit_Framework_MockObject_MockObject */
		$model = $this->getMock("Mollie_Mpm_Model_Idl", array("getCurrentDate"));
		$model->expects($this->once())
			->method("getCurrentDate")
			->will($this->returnValue("2013-12-11 10:09:09"));

		$model->updatePayment(self::TRANSACTION_ID, "Cancelled");
	}

	public function testUpdatePaymentWithSuccessfullPayment()
	{
		$this->writeconn->expects($this->once())
			->method("update")
			->with("mollie_payments", array("bank_status" => "Success", "bank_account" => "123456789", "updated_at" => "2013-12-11 10:09:09",), "transaction_id = '".self::TRANSACTION_ID."'");

		$this->writeconn->expects($this->never())
			->method("insert");

		$this->readconn->expects($this->never())
			->method("update");

		/** @var $model Mollie_Mpm_Model_Idl|PHPUnit_Framework_MockObject_MockObject */
		$model = $this->getMock("Mollie_Mpm_Model_Idl", array("getCurrentDate"));
		$model->expects($this->once())
			->method("getCurrentDate")
			->will($this->returnValue("2013-12-11 10:09:09"));

		$model->updatePayment(self::TRANSACTION_ID, "Success", array("consumerAccount" => "123456789"));
	}
}