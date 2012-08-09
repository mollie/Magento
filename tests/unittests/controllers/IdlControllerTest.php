<?php
/**
 * @covers Mollie_Mpm_IdlController
 */
class Mollie_Mpm_IdlControllerTest extends MagentoPlugin_TestCase
{
	/**
	 * @var Mollie_Mpm_IdlController|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $controller;

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $request;

	public function setUp()
	{
		parent::setUp();

		$this->controller = $this->getMock("Mollie_Mpm_IdlController", array("getRequest"), array());

		$this->request = $this->getMock("stdClass", array("getParam"));

		$this->controller->expects($this->any())
			->method("getRequest")
			->will($this->returnValue($this->request));
	}

	public function testReportAction()
	{
		/*
		 * Validate payment status with Mollie
		 */
		$idealhelper = $this->getMock("Mollie_Mpm_Helper_Idl", array("checkPayment", "getPaidStatus", "getAmount"), array(), "", FALSE);
		$idealhelper->expects($this->once())
			->method("checkPayment")
			->with("1bba1d8fdbd8103b46151634bdbe0a60")
			->will($this->returnValue(TRUE));

		/*
		 * Payment status must be checked
		 */
		$idealhelper->expects($this->once())
			->method("getPaidStatus")
			->will($this->returnValue(TRUE));

		$data = $this->getMock("stdClass", array("getOrderIdByTransactionId"));
		$this->mage->expects($this->any())
			->method("Helper")
			->will($this->returnValueMap(array(
			array("mpm/data", $data),
			array("mpm/idl", $idealhelper),
		)));

		/*
		 * Make sure Payment is stored correctly
		 */
		$payment_model = $this->getMock("Mage_Sales_Model_Order_Payment", array("setMethod", "setTransactionId", "setIsTransactionClosed", "addTransaction"));
		$payment_model->expects($this->once())->method("setMethod")->with("iDEAL")->will($this->returnValue($payment_model));
		$payment_model->expects($this->once())->method("setTransactionId")->with("1bba1d8fdbd8103b46151634bdbe0a60")->will($this->returnValue($payment_model));
		$payment_model->expects($this->once())->method("setIsTransactionClosed")->with(TRUE)->will($this->returnValue($payment_model));

		/*
		 * If successfull, add a capture transaction
		 */
		$payment_model->expects($this->once())->method("addTransaction")->with(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

		/*
		 * Status must be checked with the order.
		 */
		$order = $this->getMock("Mage_Sales_Model_Order", array("getData", "setPayment", "getGrandTotal", "getAllItems", "setState", "sendNewOrderEmail", "setEmailSent", "save"));
		$order->expects($this->once())
			->method("getData")
			->with("status")
			->will($this->returnValue(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT));

		/*
		 * Put in the amounts
		 */
		$order->expects($this->atLeastOnce())
			->method("getGrandTotal")
			->will($this->returnValue("500.15")); // Is a string, for realsies.
		$idealhelper->expects($this->atLeastOnce())
			->method("getAmount")
			->will($this->returnValue(50015));

		/*
		 * Payment must be added to order
		 */
		$order->expects($this->once())
			->method("setPayment")
			->with($payment_model);

		$order->expects($this->once())
			->method("setState")
			->with(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING, Mollie_Mpm_Model_Idl::PAYMENT_FLAG_PROCESSED, TRUE);
		$order->expects($this->once())
			->method("save");

		/*
		 * We must send an email is everything is successfull
		 */
		$order->expects($this->once())
			->method("sendNewOrderEmail")
			->will($this->returnValue($order));
		$order->expects($this->once())
			->method("setEmailSent")
			->with(TRUE);

		/**
		 * Order must be loaded using Id retrieved from getOrderIdByTransactionId
		 */
		$order_model = $this->getMock("stdClass", array("loadByIncrementId"));
		$order_model->expects($this->once())
			->method("loadByIncrementId")
			->with(1337)
			->will($this->returnValue($order));

		/*
		 * Skip items for now
		 */
		$order->expects($this->once())
			->method("getAllItems")
			->will($this->returnValue(array()));

		$ideal_model = $this->getMock("Mollie_Mpm_Model_Idl", array("updatePayment"));

		$ideal_model->expects($this->once())
			->method("updatePayment")
			->with();

		$this->mage->expects($this->any())
			->method("getModel")
			->will($this->returnValueMap(array(
				array("mpm/idl", $ideal_model),
				array("sales/order", $order_model),
				array("sales/order_payment", $payment_model),
		)));

		/**
		 * transaction_id is passed in from Mollie, must be checked in this code.
		 */
		$this->request->expects($this->atLeastOnce())
			->method("getParam")
			->with("transaction_id")
			->will($this->returnValue("1bba1d8fdbd8103b46151634bdbe0a60"));

		$data->expects($this->once())
			->method("getOrderIdByTransactionId")
			->with("1bba1d8fdbd8103b46151634bdbe0a60")
			->will($this->returnValue(1337));

		$this->controller->_construct();
		$this->controller->reportAction();
	}
}