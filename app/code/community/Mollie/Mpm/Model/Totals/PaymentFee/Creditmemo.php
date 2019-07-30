<?php

class Mollie_Mpm_Model_Totals_PaymentFee_Creditmemo extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        if (!Mage::helper('mpm/paymentFee')->isFullOrLastPartialCreditmemo($creditmemo)) {
            return $this;
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getMollieMpmPaymentFee());
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBaseMollieMpmPaymentFee());

        return $this;
    }
}
