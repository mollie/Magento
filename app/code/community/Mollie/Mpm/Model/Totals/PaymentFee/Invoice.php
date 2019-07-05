<?php

class Mollie_Mpm_Model_Totals_PaymentFee_Invoice extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getMollieMpmPaymentFee());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBaseMollieMpmPaymentFee());

        return $this;
    }
}
