<?php


class Mollie_Mpm_Block_Checkout_PaymentFee extends Mage_Checkout_Block_Total_Default
{
    protected $_template = 'mollie/mpm/payment/totals/paymentFee/quote.phtml';

    public function getPaymentFeeIncludingTax()
    {
        $helper = Mage::helper('mpm/paymentFee');

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        return $helper->getPaymentFeeInludingTax($quote->getBillingAddress());
    }
}