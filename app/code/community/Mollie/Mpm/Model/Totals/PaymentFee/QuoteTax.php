<?php

class Mollie_Mpm_Model_Totals_PaymentFee_QuoteTax extends Mage_Sales_Model_Quote_Address_Total_Tax
{
    protected $_code = 'mollie_mpm_payment_fee_tax';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        if (!$this->_getAddressItems($address)) {
            return $this;
        }

        $address->setMollieMpmPaymentFeeTax(0);
        $address->setBaseMollieMpmPaymentFeeTax(0);

        /** @var Mollie_Mpm_Helper_PaymentFee $paymentFeeHelper */
        $paymentFeeHelper = Mage::helper('mpm/paymentFee');

        $quote = $address->getQuote();
        $methodCode = $quote->getPayment()->getMethod();
        if (!$paymentFeeHelper->methodSupportsPaymentFee($methodCode)) {
            return $this;
        }

        /** @var Mollie_Mpm_Helper_PaymentFee $paymentFeeHelper */
        $paymentFeeHelper = Mage::helper('mpm/paymentFee');
        $paymentFeeTax = $paymentFeeHelper->getPaymentFeeTax($address);

        if (!$paymentFeeTax) {
            return $this;
        }

        $address->setMollieMpmPaymentFeeTax($quote->getStore()->convertPrice($paymentFeeTax));
        $address->setBaseMollieMpmPaymentFeeTax($paymentFeeTax);
        $address->setGrandTotal($address->getGrandTotal() + $address->getMollieMpmPaymentFeeTax());
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getMollieMpmPaymentFeeTax());

        return $this;
    }
}
