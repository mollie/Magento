<?php

class Mollie_Mpm_Test_Helper_PaymentFeeTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    /**
     * @var Mage_Sales_Model_Quote_Address
     */
    private $address;

    protected function setUp()
    {
        parent::setUp();

        $methodCode = Mollie_Mpm_Model_Method_Klarnasliceit::METHOD_CODE;
        $path = sprintf(Mollie_Mpm_Helper_PaymentFee::PAYMENT_FEE_SURCHARGE_PATH, $methodCode);
        Mage::app()->getStore()->setConfig($path, '1,95');

        $quote = Mage::getModel('sales/quote');
        $payment = $quote->getPayment();
        $payment->setMethod($methodCode);

        $this->address = Mage::getModel('sales/quote_address');
        $this->address->setQuote($quote);
    }

    public function testCalculatesTheCorrectAmountIncludingTax()
    {
        /** @var Mollie_Mpm_Helper_PaymentFee $instance */
        $instance = Mage::helper('mpm/paymentFee');

        $result = $instance->getPaymentFeeInludingTax($this->address);

        $this->assertEquals(1.95, $result);
    }

    public function testCalculatesTheCorrectAmountExcludingTax()
    {
        /** @var Mollie_Mpm_Helper_PaymentFee $instance */
        $instance = Mage::helper('mpm/paymentFee');

        $result = $instance->getPaymentFeeWithoutTax($this->address);

        $this->assertEquals(1.6115702479339, $result);
    }

    public function testCalculatesTheCorrectTaxAmount()
    {
        /** @var Mollie_Mpm_Helper_PaymentFee $instance */
        $instance = Mage::helper('mpm/paymentFee');

        $result = $instance->getPaymentFeeTax($this->address);

        $this->assertEquals(0.33842975206612, $result);
    }

    public function methodSupportsPaymentFeeProvider()
    {
        return [
            ['mollie_klarnapaylater', true],
            ['mollie_klarnasliceit', true],
            ['doesnotexists', false],
        ];
    }

    /**
     * @dataProvider methodSupportsPaymentFeeProvider
     */
    public function testMethodSupportsPaymentFee($method, $expected)
    {
        /** @var Mollie_Mpm_Helper_PaymentFee $instance */
        $instance = Mage::helper('mpm/paymentFee');

        $this->assertSame($expected, $instance->methodSupportsPaymentFee($method));
    }
}
