<?php
/**
 * @covers Mollie_Mpm_Model_Idl
 */
class Mollie_Mpm_Model_IdlTest extends MagentoPlugin_TestCase
{
	public function testIsNotAvailableIfDisabledInAdmin()
	{
		$data = $this->getMock("stdClass", array("getConfig"));

		$data->expects($this->once())
			->method("getConfig")
			->with("idl","active")
			->will($this->returnValue(FALSE));

		$this->mage->expects($this->any())
			->method("Helper")
			->with("mpm/data")
			->will($this->returnValue($data));

		$model = new Mollie_Mpm_Model_Idl();
		$this->assertFalse($model->isAvailable());
	}

	public function testIsAvailableIfEnabledInAdmin()
	{
		$data = $this->getMock("stdClass", array("getConfig"));

		$data->expects($this->once())
			->method("getConfig")
			->with("idl","active")
			->will($this->returnValue(TRUE));

		$this->mage->expects($this->any())
			->method("Helper")
			->with("mpm/data")
			->will($this->returnValue($data));

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