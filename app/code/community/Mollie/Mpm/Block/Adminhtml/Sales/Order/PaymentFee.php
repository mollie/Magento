<?php

class Mollie_Mpm_Block_Adminhtml_Sales_Order_PaymentFee extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    public function initTotals()
    {
        $order = $this->getOrder();
        $parent = $this->getParentBlock();

        $fee = $order->getMollieMpmPaymentFee();
        $baseFee = $order->getBaseMollieMpmPaymentFee();

        $tax = $order->getMollieMpmPaymentFeeTax();
        $baseTax = $order->getBaseMollieMpmPaymentFeeTax();

        if (!(float)$fee || !(float)$baseFee) {
            return $this;
        }

        $total = new Varien_Object();
        $total->setLabel(__('Payment Fee'))
            ->setValue($fee + $tax)
            ->setBaseValue($baseFee + $baseTax)
            ->setCode('mollie_mpm_payment_fee');

        $parent->addTotalBefore($total, 'shipping');

        return $this;
    }
}
