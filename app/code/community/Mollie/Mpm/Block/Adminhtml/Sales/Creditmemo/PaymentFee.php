<?php

class Mollie_Mpm_Block_Adminhtml_Sales_Creditmemo_PaymentFee extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals
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

        if (!Mage::helper('mpm/paymentFee')->isFullOrLastPartialCreditmemo($this->getCreditmemo())) {
            return $this;
        }

        $total = new Varien_Object();
        $total->setLabel(__('Mollie Payment Fee'))
            ->setValue($fee + $tax)
            ->setBaseValue($baseFee + $baseTax)
            ->setCode('mollie_mpm_payment_fee');

        $parent->addTotalBefore($total, 'shipping');

        return $this;
    }
}
