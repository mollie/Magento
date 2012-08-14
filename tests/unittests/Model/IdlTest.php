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

	public function testCannotUseForOtherCountryThanNL()
	{
		$model = new Mollie_Mpm_Model_Idl();
		$this->assertTrue($model->canUseForCountry("NL"));
		$this->assertFalse($model->canUseForCountry("BE"));
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
}