<?php

class Mollie_Mpm_Block_Adminhtml_Sales_Invoice_PaymentFee extends Mage_Sales_Block_Order_Invoice_Totals
{
    public function initTotals()
    {
        $order = $this->getOrder();
        $parent = $this->getParentBlock();

        $fee = $order->getMollieMpmPaymentFee();
        $baseFee = $order->getBaseMollieMpmPaymentFee();

        $tax = $order->getMollieMpmPaymentFeeTax();
        $baseTax = $order->getBaseMollieMpmPaymentFeeTax();

        if (!(float)$fee) {
            return $this;
        }

        $total = new Varien_Object();
        $total->setLabel(__('Mollie Payment Fee'));
        $total->setValue($fee + $tax);
        $total->setBaseValue($baseFee + $baseTax);
        $total->setCode('mollie_mpm_payment_fee');

        $parent->addTotalBefore($total, 'shipping');

        return $this;
    }
}
