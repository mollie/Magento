<?php
/**
 * @covers Mollie_Mpm_Model_Api
 */
class Mollie_Mpm_Model_ApiTest extends MagentoPlugin_TestCase
{
	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|Mollie_Mpm_Helper_Api
	 */
	protected $apihelper;

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

		$this->datahelper = $this->getMock("Mollie_Mpm_Helper_Data", array("getConfig"));
		$this->apihelper  = $this->getMock("Mollie_Mpm_Helper_Api", array("createPayment", "getTransactionId", "getPaymentURL"), array(), "", FALSE);

		/*
		 * Mage::Helper() method
		 */
		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(array(
			array("mpm", $this->datahelper),
			array("mpm/api", $this->apihelper),
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
			->with("mollie", "active")
			->will($this->returnValue(FALSE));

		$model = new Mollie_Mpm_Model_Api();
		$this->assertFalse($model->isAvailable());
	}

	/**
	 * Regression test: it was NOT allowed before. Now it is.
	 */
	public function testCanUseForOtherCurrencyThanEUR()
	{
		$model = new Mollie_Mpm_Model_Api();
		$this->assertTrue($model->canUseForCurrency("EUR"));
		$this->assertTrue($model->canUseForCurrency("USD"));
	}

	public function testSetPaymentDoesNotAcceptNull()
	{
		$this->mage->expects($this->once())
			->method("throwException")
			->with('Ongeldig order_id of transaction_id...')
			->will($this->throwException(new Exception("NO_NULL", 400)));

		$this->setExpectedException("Exception", "NO_NULL", 400);

		$model = new Mollie_Mpm_Model_Api;
		$model->setPayment(NULL, NULL);
	}

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 1337;

	public function testSetPaymentWorksPerfectly()
	{
		$this->writeconn->expects($this->once())
			->method("insert")
			->with("mollie_payments", array("order_id" => self::ORDER_ID, "transaction_id" => self::TRANSACTION_ID, "bank_status" => "open", "method" => "api", "created_at" => "2013-12-11 10:09:09"));

		$this->writeconn->expects($this->never())
			->method("update");

		$this->readconn->expects($this->never())
			->method("insert");

		/** @var $model Mollie_Mpm_Model_Api|PHPUnit_Framework_MockObject_MockObject */
		$model = $this->getMock("Mollie_Mpm_Model_Api", array("getCurrentDate"));
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

		/** @var $model Mollie_Mpm_Model_Api|PHPUnit_Framework_MockObject_MockObject */
		$model = $this->getMock("Mollie_Mpm_Model_Api", array("getCurrentDate"));
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

		/** @var $model Mollie_Mpm_Model_Api|PHPUnit_Framework_MockObject_MockObject */
		$model = $this->getMock("Mollie_Mpm_Model_Api", array("getCurrentDate"));
		$model->expects($this->once())
			->method("getCurrentDate")
			->will($this->returnValue("2013-12-11 10:09:09"));

		$model->updatePayment(self::TRANSACTION_ID, "Success", array("consumerAccount" => "123456789"));
	}
}
