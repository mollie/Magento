<?php
/**
 * @covers Mollie_Mpm_ApiController
 */
class Mollie_Mpm_ApiControllerReportActionTest extends MagentoPlugin_TestCase
{
	/**
	 * @var Mollie_Mpm_ApiController|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $controller;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $request;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $api_helper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $data_helper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $order_model;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $api_model;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $payment_model;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $store;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $order;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $transaction;

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 1337;

	public function setUp()
	{
		parent::setUp();

		$this->controller = $this->getMock("Mollie_Mpm_ApiController", array("getRequest", "_showException", "_saveInvoice"), array());

		/**
		 * id is passed in from Mollie, must be checked in this code.
		 */
		$this->request = $this->getMock("stdClass", array("getParam"));
		$this->request->expects($this->atLeastOnce())
			->method("getParam")
			->will($this->onConsecutiveCalls(0, self::TRANSACTION_ID));

		$this->controller->expects($this->any())
			->method("getRequest")
			->will($this->returnValue($this->request));

		$this->api_helper  = $this->getMock("Mollie_Mpm_Helper_Api", array("getErrorMessage", "checkPayment", "getPaidStatus", "getAmount", "getBankStatus"), array(), "", FALSE);
		$this->data_helper = $this->getMock("stdClass", array("getOrderIdByTransactionId", "getConfig"));
		$this->directory   = $this->getMock("stdClass", array("currencyConvert"));
		$this->store       = $this->getMock("stdClass", array("getBaseCurrencyCode", "getCurrentCurrencyCode"));
		$this->transaction = $this->getMock("stdClass", array("setTxnType", "setIsClosed", "save"));

		/*
		 * Mage::helper() method
		 */
		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(array(
			array("mpm", $this->data_helper),
			array("mpm/api", $this->api_helper),
			array("directory", $this->directory),
		)));

		/*
		 * Mage::getStore()
		 */
		$this->mage->expects($this->any())
			->method("app")
			->will($this->returnValue($this->mage));
		$this->mage->expects($this->any())
			->method("getStore")
			->will($this->returnValue($this->store));

		$this->store->expects($this->any())
			->method('getBaseCurrencyCode')
			->will($this->returnValue('EUR'));
		$this->store->expects($this->any())
			->method('getCurrentCurrencyCode')
			->will($this->returnValue('EUR'));

		/*
		 * Models.
		 */
		$this->payment_model = $this->getMock("Mage_Sales_Model_Order_Payment", array("setMethod", "setTransactionId", "setIsTransactionClosed", "addTransaction", "getTransaction"));
		$this->api_model   = $this->getMock("Mollie_Mpm_Model_Api", array("updatePayment"), array(), "", FALSE);
		$this->order_model   = $this->getMock("stdClass", array("load"));

		/*
		 * Mage::getModel() method
		 */
		$this->mage->expects($this->any())
			->method("getModel")
			->will($this->returnValueMap(array(
			array("mpm/api", $this->api_model),
			array("sales/order", $this->order_model),
			array("sales/order_payment", $this->payment_model),
		)));

		$this->order = $this->getMock("Mage_Sales_Model_Order", array("getData", "setPayment", "setTotalPaid", "setBaseTotalPaid", "getGrandTotal", "getAllItems", "setState", "sendNewOrderEmail", "setEmailSent", "cancel", "save"));
		$this->order->expects($this->any())
			->method("setState")
			->will($this->returnValue($this->order));
	}

	protected function expectsCheckPayment($returnValue)
	{
		/*
		 * Validate payment status with Mollie
		 */
		$this->api_helper->expects($this->once())
			->method("checkPayment")
			->with(self::TRANSACTION_ID)
			->will($this->returnValue($returnValue));
	}

	protected function expectsPaidStatus($returnValue)
	{
		/*
		   * Payment status must be checked
		   */
		$this->api_helper->expects($this->once())
			->method("getPaidStatus")
			->will($this->returnValue($returnValue));
	}

	protected function expectOrderState($expected_state)
	{
		/*
		 * Status must be checked with the order.
		 */
		$this->order->expects($this->once())
			->method("getData")
			->with("status")
			->will($this->returnValue($expected_state));
	}

	protected function expectsOrderModelCanBeloaded($success)
	{
		$this->data_helper->expects($this->once())
			->method("getOrderIdByTransactionId")
			->with(self::TRANSACTION_ID)
			->will($this->returnValue(self::ORDER_ID));

		$this->order_model->expects($this->once())
			->method("load")
			->with(self::ORDER_ID)
			->will($this->returnValue($success ? $this->order : NULL));
	}

	protected function expectBankStatus($bank_status)
	{
		$this->api_helper->expects($this->atLeastOnce())
			->method("getBankStatus")
			->will($this->returnValue($bank_status));
	}

	protected function expectsPaymentSetupCorrectly()
	{
		/*
		 * Make sure Payment is stored correctly
		 */
		$this->payment_model->expects($this->once())->method("setMethod")->with("Mollie")->will($this->returnValue($this->payment_model));
		$this->payment_model->expects($this->once())->method("setTransactionId")->with(self::TRANSACTION_ID)->will($this->returnValue($this->payment_model));
		$this->payment_model->expects($this->once())->method("setIsTransactionClosed")->with(TRUE)->will($this->returnValue($this->payment_model));

		/*
		 * Payment must be added to order
		 */
		$this->order->expects($this->once())
			->method("setPayment")
			->with($this->payment_model);
	}

	protected function expectOrderSaved()
	{
		$this->order->expects($this->atLeastOnce())
			->method("save");
	}

	protected function expectsOrderAmount($string_amount)
	{
		/*
		 * Put in the amounts
		 */
		$this->order->expects($this->atLeastOnce())
			->method("getGrandTotal")
			->will($this->returnValue($string_amount)); // Is a string, for realsies.
	}

	protected function expectsMollieAmount($amount)
	{
		$this->api_helper->expects($this->atLeastOnce())
			->method("getAmount")
			->will($this->returnValue($amount));
	}

	protected function expectsConversionAmount($amount)
	{
		$this->directory->expects($this->atLeastOnce())
			->method("currencyConvert")
			->will($this->returnValue($amount));
	}

	protected function expectsTransaction($transaction)
	{
		$this->payment_model->expects($this->once())
			->method("getTransaction")
			->will($this->returnValue($transaction));
	}

	public function testEverythingGoesGreat()
	{
		$this->data_helper->expects($this->any())
			->method("getConfig")
			->will($this->returnValue(true));

		$this->expectsOrderModelCanBeloaded(TRUE);
		$this->expectOrderState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

		$this->expectsCheckPayment(TRUE);
		$this->expectsPaidStatus(TRUE);

		$this->expectsPaymentSetupCorrectly();

		$this->order->expects($this->once())
			->method("setState")
			->with(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING, Mollie_Mpm_Model_Api::PAYMENT_FLAG_PROCESSED, TRUE);

		$this->order->expects($this->once())
			->method("setTotalPaid")
			->with(500.15);

		$this->order->expects($this->once())
			->method("setBaseTotalPaid")
			->with(500.15);

		$this->expectsMollieAmount(500.15);
		$this->expectsConversionAmount(500.15);

		$this->expectsTransaction($this->transaction);

		$this->expectOrderSaved();

		/*
		 * We may send an email is everything is successfull
		 */
		$this->order->expects($this->any())
			->method("sendNewOrderEmail")
			->will($this->returnValue($this->order));
		$this->order->expects($this->any())
			->method("setEmailSent")
			->with(TRUE);

		/*
		 * Skip items for now
		 */
		$this->order->expects($this->once())
			->method("getAllItems")
			->will($this->returnValue(array()));

		$this->api_model->expects($this->once())
			->method("updatePayment");

		$this->order->expects($this->never())
			->method("cancel");

		$this->controller->_construct();
		$this->controller->webhookAction();
	}

	public function testNotPaid ()
	{
		$this->expectsOrderModelCanBeloaded(TRUE);
		$this->expectOrderState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

		$this->expectsCheckPayment(TRUE);

		$this->expectsPaidStatus(FALSE);

		$this->expectsPaymentSetupCorrectly();

		$this->expectBankStatus("Cancelled");

		$this->api_model->expects($this->once())
			->method("updatePayment")
			->with(self::TRANSACTION_ID, "Cancelled");

		$this->order->expects($this->once())
			->method("cancel");

		$this->order->expects($this->once())
			->method("setState")
			->with(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, Mollie_Mpm_Model_Api::PAYMENT_FLAG_CANCELD, FALSE);

		$this->expectOrderSaved();

		$this->controller->_construct();
		$this->controller->webhookAction();
	}

	public function testExceptionThrownWhenCheckPaymentFails()
	{
		$this->expectsOrderModelCanBeloaded(TRUE);
		$this->expectOrderState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		$this->expectsCheckPayment(FALSE);

		$this->api_helper->expects($this->once())
			->method("getErrorMessage")
			->will($this->returnValue("The flux capacitors are over capacity"));

		$exception = new Test_Exception();

		$this->mage->expects($this->once())
			->method("throwException")
			->with("The flux capacitors are over capacity")
			->will($this->throwException($exception));

		$this->mage->expects($this->once())
			->method("log")
			->with($exception)
			->will($this->throwException($exception)); // Throw it again, we don't want to test _showException here.

		$this->setExpectedException("Test_Exception");

		$this->controller->_construct();
		$this->controller->webhookAction();
	}
}
