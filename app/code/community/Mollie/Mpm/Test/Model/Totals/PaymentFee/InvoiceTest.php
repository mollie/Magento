<?php

class Mollie_Mpm_Test_Model_Totals_PaymentFee_InvoiceTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testAddsTheFeeToTheGrandTotals()
    {
        $model = Mage::getModel('sales/order_invoice');

        $model->setGrandTotal(10);
        $model->setBaseGrandTotal(10);

        $model->setMollieMpmPaymentFee(1);
        $model->setBaseMollieMpmPaymentFee(1);

        $instance = Mage::getModel('mpm/totals_paymentFee_invoice');
        $instance->collect($model);

        $this->assertEquals(11, $model->getGrandTotal());
        $this->assertEquals(11, $model->getBaseGrandTotal());
    }
}
