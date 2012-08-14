<?php
/**
 * @covers Mollie_Mpm_IdlController
 */
class Mollie_Mpm_IdlControllerPaymentActionTest extends MagentoPlugin_TestCase
{
	/**
	 * @var Mollie_Mpm_IdlController|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $controller;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $request;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $idealhelper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $datahelper;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $order_model;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $ideal_model;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $payment_model;

	const TRANSACTION_ID = "1bba1d8fdbd8103b46151634bdbe0a60";

	const ORDER_ID = 1337;

	const ORDER_INCREMENT_ID = 100000001;

	const BANK_ID = "0999";

	const RETURN_URL = "http://mijnwebshop.local/index.php/magento/idl/return";
	const REPORT_URL = "http://mijnwebshop.local/index.php/magento/idl/report";
	const BANK_URL = "https://rabnpostbank.nl/ideal.jsp";

	public function setUp()
	{
		parent::setUp();

		$this->datahelper  = $this->getMock("stdClass", array("getConfig"));
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

		$this->controller = $this->getMock("Mollie_Mpm_IdlController", array("getRequest", "_redirectUrl", "_showException", "_getCheckout"), array());

		/**
		 * transaction_id is passed in from Mollie, must be checked in this code.
		 */
		$this->request = $this->getMock("stdClass", array("getParam"));

		$this->controller->expects($this->any())
			->method("getRequest")
			->will($this->returnValue($this->request));

		/*
		 * Models.
		 */
		$this->payment_model = $this->getMock("Mage_Sales_Model_Order_Payment", array("setMethod", "setTransactionId", "setIsTransactionClosed", "addTransaction"));

		$this->ideal_model   = $this->getMock("Mollie_Mpm_Model_Idl", array("setPayment"), array(), "", FALSE);

		$this->order_model   = $this->getMock("Mage_Sales_Model_Order", array("load", "loadByIncrementId", "save", "setState", "getId", "getIncrementId", "getGrandTotal", "setPayment"));

		$this->order_model->expects($this->any())
			->method("getId")
			->will($this->returnValue(self::ORDER_ID));

		/*
		 * Mage::getModel() method
		 */
		$this->mage->expects($this->any())
			->method("getModel")
			->will($this->returnValueMap(array(
			array("mpm/idl", $this->ideal_model),
			array("sales/order", $this->order_model),
			array("sales/order_payment", $this->payment_model),
		)));

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
		$this->datahelper->expects($this->atLeastOnce())
			->method("getConfig")
			->will($this->returnValueMap(array(
			array("idl", "description", "Bankafschrift order % deluxe"),
			array("idl", "minvalue", 118),
		)));
	}

	public function testPaymentSetupCorrectly()
	{
		$this->expectOrderIdInRequestPresent(TRUE);

		$this->order_model->expects($this->exactly(2))
			->method("setState")
//			->with(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,Mollie_Mpm_Model_Idl::PAYMENT_FLAG_RETRY, FALSE)
			->will($this->returnSelf());

		$this->order_model->expects($this->exactly(2))
			->method("save");

		$this->order_model->expects($this->atLeastOnce())
			->method("getGrandTotal")
			->will($this->returnValue("13.37"));

		$this->expectConfigRetrieved();

		$this->order_model->expects($this->atLeastOnce())
			->method("getIncrementId")
			->will($this->returnValue("ORD-12532"));

		$this->mage->expects($this->exactly(2))
			->method("getUrl")
			->will($this->returnValueMap(array(
				array("mpm/idl/return", self::RETURN_URL),
				array("mpm/idl/report", self::REPORT_URL),
		)));

		$this->idealhelper->expects($this->once())
			->method("createPayment")
			->with(self::BANK_ID, 1337, "Bankafschrift order ORD-12532 deluxe", self::RETURN_URL, self::REPORT_URL)
			->will($this->returnValue(TRUE));

		$this->idealhelper->expects($this->atLeastOnce())
			->method("getTransactionId")
			->will($this->returnValue(self::TRANSACTION_ID));

		$this->ideal_model->expects($this->once())
			->method("setPayment")
			->with(self::ORDER_ID, self::TRANSACTION_ID);

		$this->payment_model->expects($this->once())
			->method("setMethod")
			->with("iDEAL")
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

		$this->idealhelper->expects($this->atLeastOnce())
			->method("getBankURL")
			->will($this->returnValue(self::BANK_URL));

		$this->controller->expects($this->once())
			->method("_redirectUrl")
			->with(self::BANK_URL);

		$this->controller->_construct();
		$this->controller->paymentAction();
	}

	public function testOrderAmountLessThanMinimumGivesException()
	{
		$this->expectOrderIdInRequestPresent(TRUE);

		$this->order_model->expects($this->once())
			->method("setState")
			->with(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,Mollie_Mpm_Model_Idl::PAYMENT_FLAG_RETRY, FALSE)
			->will($this->returnSelf());

		$this->order_model->expects($this->once())
			->method("save");

		$this->order_model->expects($this->atLeastOnce())
			->method("getGrandTotal")
			->will($this->returnValue("1.17"));

		$this->expectConfigRetrieved();

		$this->mage->expects($this->once())
			->method("throwException")
			->with("Order bedrag (117 centen) is lager dan ingesteld (118 centen)")
			->will($this->throwException(new Test_Exception("TOOLOW")));

		$this->controller->expects($this->once())
			->method("_showException")
			->with("TOOLOW");

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