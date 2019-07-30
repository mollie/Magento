<?php

class Mollie_Mpm_Model_Totals_PaymentFee_Adminhtml_Quote extends Mollie_Mpm_Model_Totals_PaymentFee_Quote
{
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getMollieMpmPaymentFee();
        if (floatval($amount)) {
            $tax = $address->getMollieMpmPaymentFeeTax();

            $address->addTotal(
                array(
                    'code' => $this->getCode(),
                    'title' => Mage::helper('mpm')->__('Mollie Payment Fee'),
                    'value' => $amount + $tax,
                )
            );
        }

        return $this;
    }
}
