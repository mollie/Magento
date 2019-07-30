<?php

class Mollie_Mpm_Block_Adminhtml_Sales_Order_Create_PaymentFee extends Mage_Adminhtml_Block_Sales_Order_Create_Totals
{
    protected $_template = 'mollie/mpm/order/create/totals/paymentfee.phtml';

    public function getValue()
    {
        $address = $this->getTotal()->getAddress();

        return $address->getMollieMpmPaymentFee() + $address->getMollieMpmPaymentFeeTax();
    }
}
