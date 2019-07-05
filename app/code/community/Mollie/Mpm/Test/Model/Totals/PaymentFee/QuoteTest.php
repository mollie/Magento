<?php

class Mollie_Mpm_Test_Model_Totals_PaymentFee_QuoteTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testDoesNotSetThePaymentFeeWhenTheAdddressHasNoItems()
    {
        $quote = Mage::getModel('sales/quote');
        $address = Mage::getModel('sales/quote_address');
        $address->setQuote($quote);

        /** @var Mollie_Mpm_Model_Totals_PaymentFee_Quote $instance */
        $instance = Mage::getModel('mpm/totals_paymentFee_quote');

        $instance->collect($address);

        $this->assertFalse($address->hasMollieMpmPaymentFee());
        $this->assertFalse($address->hasBaseMollieMpmPaymentFee());
    }

    public function testSetsToFeeToZeroWhenTheMethodIsNotSupported()
    {
        $quote = Mage::getModel('sales/quote');
        $quoteItem = Mage::getModel('sales/quote_item');

        $address = Mage::getModel('sales/quote_address');
        $address->setQuote($quote);
        $address->setData('cached_items_nonnominal', [$quoteItem]);

        /** @var Mollie_Mpm_Model_Totals_PaymentFee_Quote $instance */
        $instance = Mage::getModel('mpm/totals_paymentFee_quote');

        $instance->collect($address);

        $this->assertTrue($address->hasMollieMpmPaymentFee());
        $this->assertSame(0, $address->getMollieMpmPaymentFee());
        $this->assertTrue($address->hasBaseMollieMpmPaymentFee());
        $this->assertSame(0, $address->getBaseMollieMpmPaymentFee());
    }

    public function testSetsToFeeToZeroWhenThePaymentFeeIsNotSet()
    {
        $method = 'mollie_klarnapaylater';

        Mage::app()->getStore()->setConfig(sprintf('payment/%s/payment_surcharge', $method), 0);

        $quote = Mage::getModel('sales/quote');
        $quote->getPayment()->setMethod($method);
        $quoteItem = Mage::getModel('sales/quote_item');

        $address = Mage::getModel('sales/quote_address');
        $address->setQuote($quote);
        $address->setData('cached_items_nonnominal', [$quoteItem]);

        /** @var Mollie_Mpm_Model_Totals_PaymentFee_Quote $instance */
        $instance = Mage::getModel('mpm/totals_paymentFee_quote');

        $instance->collect($address);

        $this->assertTrue($address->hasMollieMpmPaymentFee());
        $this->assertSame(0, $address->getMollieMpmPaymentFee());
        $this->assertTrue($address->hasBaseMollieMpmPaymentFee());
        $this->assertSame(0, $address->getBaseMollieMpmPaymentFee());
    }

    public function testSetsThePaymentFeeCorrect()
    {
        $method = 'mollie_klarnapaylater';

        Mage::app()->getStore()->setConfig(sprintf('payment/%s/payment_surcharge', $method), 1);

        $quote = Mage::getModel('sales/quote');
        $quote->getPayment()->setMethod($method);
        $quoteItem = Mage::getModel('sales/quote_item');

        $address = Mage::getModel('sales/quote_address');
        $address->setQuote($quote);
        $address->setData('cached_items_nonnominal', [$quoteItem]);

        /** @var Mollie_Mpm_Model_Totals_PaymentFee_Quote $instance */
        $instance = Mage::getModel('mpm/totals_paymentFee_quote');

        $instance->collect($address);

        $this->assertTrue($address->hasMollieMpmPaymentFee());
        $this->assertSame(0.8264462809917356, $address->getMollieMpmPaymentFee());
        $this->assertTrue($address->hasBaseMollieMpmPaymentFee());
        $this->assertSame(0.8264462809917356, $address->getBaseMollieMpmPaymentFee());
    }
}
