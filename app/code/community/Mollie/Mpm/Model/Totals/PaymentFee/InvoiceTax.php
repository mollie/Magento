<?php

class Mollie_Mpm_Model_Totals_PaymentFee_InvoiceTax extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getMollieMpmPaymentFeeTax());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBaseMollieMpmPaymentFeeTax());

        return $this;
    }
}
