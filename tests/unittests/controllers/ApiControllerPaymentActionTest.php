<?php
/**
 * @covers Mollie_Mpm_ApiController
 */
class Mollie_Mpm_ApiControllerPaymentActionTest extends MagentoPlugin_TestCase
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
	protected $core_session_singleton;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $store;

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 16;

	const ORDER_INCREMENT_ID = 100000016;

	const BANK_ID = "0999";

	const PAYMENT_URL = "https://rabnpostbank.nl/api.php.aspx?jsp";

	public function setUp()
	{
		parent::setUp();

		$this->data_helper = $this->getMock("stdClass", array("getConfig"));
		$this->api_helper = $this->getMock("Mollie_Mpm_Helper_Api", array("createPayment", "getTransactionId", "getPaymentURL"), array(), "", FALSE);

		/*
		 * Mage::helper() method
		 */
		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(array(
				array("mpm/data", $this->data_helper),
				array("mpm/api", $this->api_helper),
			)));

		$this->controller = $this->getMock("Mollie_Mpm_ApiController", array("getRequest", "_redirectUrl", "_showException", "_getCheckout"), array());

		/**
		 * transaction_id is passed in from Mollie, must be checked in this code.
		 */
		$this->request = $this->getMock("stdClass", array("getParam", "isPost", "getPost"));

		$this->controller->expects($this->any())
			->method("getRequest")
			->will($this->returnValue($this->request));

		$this->request->expects($this->any())
			->method("isPost")
			->will($this->returnValue(true));

		/*
		 * Models.
		 */
		$this->payment_model = $this->getMock("Mage_Sales_Model_Order_Payment", array("setMethod", "setTransactionId", "setIsTransactionClosed", "addTransaction"));

		$this->api_model   = $this->getMock("Mollie_Mpm_Model_Api", array("setPayment"), array(), "", FALSE);

		$this->order_model   = $this->getMock("Mage_Sales_Model_Order", array("load", "loadByIncrementId", "save", "setState", "getId", "getIncrementId", "setPayment", "getBaseGrandTotal", "getGrandTotal", "getBaseCurrencyCode", "getOrderCurrencyCode"));

		$this->order_model->expects($this->any())
			->method("getId")
			->will($this->returnValue(self::ORDER_ID));

		$this->order_model->expects($this->any())
			->method("getIncrementId")
			->will($this->returnValue(self::ORDER_INCREMENT_ID));

		$this->order_model->expects($this->any())
			->method("getOrderCurrencyCode")
			->will($this->returnValue('EUR'));

		$this->core_session_singleton   = $this->getMock("Mage_Core_Model_Session", array("setData", "getData", "setRestoreCart", "getRestoreCart"));

		$this->core_session_singleton->expects($this->any())
			->method("getRestoreCart")
			->will($this->returnValue(true));

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

		/*
		 * Mage::getSingleton() method
		 */
		$this->mage->expects($this->any())
			->method("getSingleton")
			->will($this->returnValueMap(array(
				array("core/session", $this->core_session_singleton),
			)));

		$this->store = $this->getMock("stdClass", array("getBaseCurrencyCode", "getCurrentCurrencyCode", "getCode", "getBaseUrl"));
	}

	protected function expectOrderIdInRequestPresent($present)
	{
		$this->request->expects($this->any())
			->method("getParam")
			->will($this->returnValueMap(array(
			array("order_id", $present ? self::ORDER_ID : NULL),
			array("bank_id", self::BANK_ID),
		)));

		if ($present)
		{
			$this->order_model->expects($this->any())
				->method("load")
				->with(self::ORDER_ID)
				->will($this->returnSelf());
		}
	}

	protected function expectConfigRetrieved()
	{
		$this->data_helper->expects($this->atLeastOnce())
			->method("getConfig")
			->will($this->returnValueMap(array(
				array("mollie", "description", "Bankafschrift order % deluxe"),
			)));
	}

	public function testPaymentSetupCorrectly()
	{
		$this->expectOrderIdInRequestPresent(TRUE);

		$this->mage->expects($this->any())
			->method("app")
			->will($this->returnValue($this->mage));

		$this->mage->expects($this->any())
			->method("getStore")
			->will($this->returnValue($this->store));

		$this->store->expects($this->any())
			->method("getCode")
			->will($this->returnValue('code'));

		$this->order_model->expects($this->exactly(2))
			->method("setState")
			->will($this->returnSelf());

		$this->order_model->expects($this->exactly(2))
			->method("save");

		$this->order_model->expects($this->atLeastOnce())
			->method("getGrandTotal")
			->will($this->returnValue("13.37"));

		$this->expectConfigRetrieved();

		$this->mage->expects($this->any())
			->method("getUrl")
			->will($this->returnValue(self::PAYMENT_URL));

		$this->api_helper->expects($this->once())
			->method("createPayment")
			->with(13.37, 'Bankafschrift order ' . self::ORDER_INCREMENT_ID . ' deluxe', $this->order_model, Mage::getUrl('mpm/api/return') . '?order_id=' . self::ORDER_ID . '&utm_nooverride=1', null)
			->will($this->returnValue(TRUE));

		$this->api_helper->expects($this->atLeastOnce())
			->method("getTransactionId")
			->will($this->returnValue(self::TRANSACTION_ID));

		$this->api_model->expects($this->once())
			->method("setPayment")
			->with(self::ORDER_ID, self::TRANSACTION_ID);

		$this->payment_model->expects($this->once())
			->method("setMethod")
			->with("Mollie")
			->will($this->returnSelf());

		$this->payment_model->expects($this->once())
			->method("setTransactionId")
			->with(self::TRANSACTION_ID)
			->will($this->returnSelf());

		$this->payment_model->expects($this->once())
			->method("setIsTransactionClosed")
			->with(FALSE)
			->will($this->returnSelf());

		$this->order_model->expects($this->once())
			->method("setPayment")
			->with($this->payment_model);

		$this->api_helper->expects($this->atLeastOnce())
			->method("getPaymentURL")
			->will($this->returnValue(self::PAYMENT_URL));

		$this->core_session_singleton->expects($this->once())
			->method("setRestoreCart")
			->with(TRUE);


		$this->controller->expects($this->once())
			->method("_redirectUrl")
			->with(self::PAYMENT_URL);

		$this->controller->_construct();
		$this->controller->paymentAction();
	}

	public function testOrderLoadedByIncrementIdIfNoOrderIdInPost()
	{
		$this->expectOrderIdInRequestPresent(FALSE);

		$checkout = $this->getMock("stdClass", array("getLastRealOrderId"));
		$checkout->expects($this->once())
			->method("getLastRealOrderId")
			->will($this->returnValue(self::ORDER_INCREMENT_ID));

		$this->controller->expects($this->atLeastOnce())
			->method("_getCheckout")
			->will($this->returnValue($checkout));

		$this->order_model->expects($this->once())
			->method("loadByIncrementId")
			->with(self::ORDER_INCREMENT_ID)
			->will($this->throwException(new Test_Exception("STOP", 400))); // Stop testing from here on.

		$this->setExpectedException("Test_Exception", "STOP", 400);

		$this->controller->_construct();
		$this->controller->paymentAction();
	}
}