<?php

class Mollie_Mpm_Model_Totals_PaymentFee_InvoiceTax extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        // When this is the last invoice, Magento will use the tax from the order, which already includes the payment
        // fee tax minus the already invoiced tax. So don't add it again.
        if ($invoice->isLast()) {
            return $this;
        }

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getMollieMpmPaymentFeeTax());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBaseMollieMpmPaymentFeeTax());

        return $this;
    }
}
