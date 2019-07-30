<?php

class Mollie_Mpm_Model_Totals_PaymentFee_CreditmemoTax extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        if (!Mage::helper('mpm/paymentFee')->isFullOrLastPartialCreditmemo($creditmemo)) {
            return $this;
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getMollieMpmPaymentFeeTax());
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBaseMollieMpmPaymentFeeTax());

        return $this;
    }
}
