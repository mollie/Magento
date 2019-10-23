<?php


class Mollie_Mpm_Model_Totals_PaymentFee_Quote extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    // @codingStandardsIgnoreLine
    protected $_code = 'mollie_mpm_payment_fee';

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        if (!$this->_getAddressItems($address)) {
            return $this;
        }

        $address->setMollieMpmPaymentFee(0);
        $address->setBaseMollieMpmPaymentFee(0);

        /** @var Mollie_Mpm_Helper_PaymentFee $paymentFeeHelper */
        $paymentFeeHelper = Mage::helper('mpm/paymentFee');

        $quote = $address->getQuote();
        $methodCode = $quote->getPayment()->getMethod();
        if (!$paymentFeeHelper->methodSupportsPaymentFee($methodCode)) {
            return $this;
        }

        $paymentFeeWithoutTax = $paymentFeeHelper->getPaymentFeeWithoutTax($address);
        if (!$paymentFeeWithoutTax) {
            return $this;
        }

        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        $address->setMollieMpmPaymentFee($address->getQuote()->getStore()->convertPrice($paymentFeeWithoutTax));
        $address->setBaseMollieMpmPaymentFee($paymentFeeWithoutTax);
        $address->setGrandTotal($address->getGrandTotal() + $address->getMollieMpmPaymentFee());
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBaseMollieMpmPaymentFee());

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getMollieMpmPaymentFee();
        if ($amount) {
            $address->addTotal(
                array(
                    'code' => $this->getCode(),
                    'title' => Mage::helper('mpm')->__('Payment Fee'),
                    'value' => $amount
                )
            );
        }

        return $this;
    }
}
