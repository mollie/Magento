<?php
/**
 * @covers Mollie_Mpm_ApiController
 */
class Mollie_Mpm_ApiControllerReturnActionTest extends MagentoPlugin_TestCase
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
	protected $data_helper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $core_session_singleton;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $order_model;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $checkout_session_singleton;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $quote;

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 1337;

	public function setUp()
	{
		parent::setUp();

		$this->controller = $this->getMock("Mollie_Mpm_ApiController", array("getRequest", "_redirect", "_restoreCart", "loadLayout", "_showException"), array());

		/**
		 * transaction_id is passed in from Mollie, must be checked in this code.
		 */
		$this->request = $this->getMock("stdClass", array("getParam"));
		$this->request->expects($this->atLeastOnce())
			->method("getParam")
			->with("order_id")
			->will($this->returnValue(self::ORDER_ID));

		$this->controller->expects($this->any())
			->method("getRequest")
			->will($this->returnValue($this->request));

		$this->data_helper = $this->getMock("stdClass", array("getOrderIdByTransactionId", "getTransactionIdByOrderId", "getStatusById"));

		/*
		 * Mage::Helper() method
		 */
		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(array(
				array("mpm", $this->data_helper),
			)));

		$this->order_model = $this->getMock("Mage_Sales_Model_Order", array("getData", "setPayment", "getGrandTotal", "getAllItems", "setState", "sendNewOrderEmail", "setEmailSent", "cancel", "save", "load"));

		$this->quote = $this->getMock("stdClass");

		$this->core_session_singleton = $this->getMock("Mage_Core_Model_Session", array("unsRestoreCart", "getRestoreCart"));

		$this->core_session_singleton->expects($this->any())
			->method("getRestoreCart")
			->will($this->returnValue(true));

		$this->checkout_session_singleton   = $this->getMock("Mage_Checkout_Model_Session", array("getQuote", "getMollieQuoteId"));

		$this->checkout_session_singleton->expects($this->any())
			->method("getQuote")
			->will($this->returnValue($this->quote));

		/*
		 * Mage::getModel() method
		 */
		$this->mage->expects($this->any())
			->method("getModel")
			->will($this->returnValueMap(array(
			    array("sales/order", $this->order_model),
		    )));

		/*
		 * Mage::getSingleton() method
		 */
		$this->mage->expects($this->any())
			->method("getSingleton")
			->will($this->returnValueMap(array(
				array("core/session", $this->core_session_singleton),
				array("checkout/session", $this->checkout_session_singleton),
			)));
	}

	protected function expectSessionSingleton()
	{
		$this->core_session_singleton->expects($this->once())
			->method("getRestoreCart")
			->will($this->returnValue(TRUE));

		$this->core_session_singleton->expects($this->once())
			->method("unsRestoreCart");
	}

	protected function expectOrderState($expected_state)
	{
		/*
		 * Status must be checked with the order.
		 */
		$this->order_model->expects($this->once())
			->method("getData")
			->with("status")
			->will($this->returnValue($expected_state));
	}

	protected function expectsOrderModelCanBeloaded()
	{
		$this->data_helper->expects($this->any())
			->method("getOrderIdByTransactionId")
			->with(self::TRANSACTION_ID)
			->will($this->returnValue(self::ORDER_ID));
		$this->data_helper->expects($this->any())
			->method("getTransactionIdByOrderId")
			->with(self::ORDER_ID)
			->will($this->returnValue(self::TRANSACTION_ID));
	}

	protected function expectOrderSaved()
	{
		$this->order_model->expects($this->once())
			->method("save");
	}

	protected function expectsOrderAmount($string_amount)
	{
		/*
		 * Put in the amounts
		 */
		$this->order_model->expects($this->atLeastOnce())
			->method("getGrandTotal")
			->will($this->returnValue($string_amount)); // Is a string, for realsies.
	}

	public function testEverythingGoesGreat()
	{
		$this->expectSessionSingleton();

		$this->expectsOrderModelCanBeloaded();

		$this->data_helper->expects($this->once())
			->method("getStatusById")
			->with(self::TRANSACTION_ID)
			->will($this->returnValue(array(
				'bank_status' => Mollie_Mpm_Model_Api::STATUS_PAID,
				'updated_at' => '2014-04-26',
			)));

		$this->quote->items_count = 0;

		$this->controller->expects($this->never())
			->method("_restoreCart");

		$this->controller->expects($this->once())
			->method("_redirect")
			->with('checkout/onepage/success', array('_secure' => true));

		$this->controller->_construct();
		$this->controller->returnAction();
	}

	public function testPaymentPending()
	{
		$this->expectsOrderModelCanBeloaded();

		$this->data_helper->expects($this->once())
			->method("getStatusById")
			->with(self::TRANSACTION_ID)
			->will($this->returnValue(array(
				'bank_status' => Mollie_Mpm_Model_Api::STATUS_PENDING,
				'updated_at' => '2014-04-26',
			)));

		$this->quote->items_count = 0;

		$this->controller->expects($this->never())
			->method("_restoreCart");

		$this->controller->expects($this->once())
			->method("_redirect")
			->with('checkout/onepage/success', array('_secure' => true));

		$this->controller->_construct();
		$this->controller->returnAction();
	}

	public function testPaymentCancelled()
	{
		$this->expectSessionSingleton();

		$this->expectsOrderModelCanBeloaded();

		$this->data_helper->expects($this->once())
			->method("getStatusById")
			->with(self::TRANSACTION_ID)
			->will($this->returnValue(array(
						'bank_status' => Mollie_Mpm_Model_Api::STATUS_CANCELLED,
						'updated_at' => '2014-04-26',
					)));

		$this->controller->expects($this->once())
			->method("_restoreCart");

		$this->controller->expects($this->once())
			->method("_redirect")
			->with('checkout/onepage/failure', array('_secure' => true));

		$this->controller->_construct();
		$this->controller->returnAction();
	}
}
