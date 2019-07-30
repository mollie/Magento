<?php


class Mollie_Mpm_Model_Totals_PaymentFee_InvoicePdf extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    public function getAmount()
    {
        $source = $this->getSource();

        /**
         * Normally you can only use 1 field on the PDF invoice/creditmemo, but the payment fee is split into 2 values:
         * - fee excluding tax
         * - fee tax
         *
         * So we need to add them together to come to the correct result.
         */
        return $source->getDataUsingMethod('mollie_mpm_payment_fee') +
            $source->getDataUsingMethod('mollie_mpm_payment_fee_tax');
    }
}
