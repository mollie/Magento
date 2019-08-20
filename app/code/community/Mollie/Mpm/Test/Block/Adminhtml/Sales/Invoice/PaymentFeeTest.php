<?php


class Mollie_Mpm_Block_Adminhtml_Sales_Invoice_PaymentFeeTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testAddsTheTotal()
    {
        /** @var Mollie_Mpm_Block_Adminhtml_Sales_Invoice_PaymentFee $block */
        $block = Mage::app()->getLayout()->createBlock('mpm/adminhtml_sales_invoice_paymentFee');

        $order = Mage::getModel('sales/order');
        $order->setMollieMpmPaymentFee(1.95);
        $block->setOrder($order);

        $parentBlockMock = $this->createMock('Mage_Sales_Block_Order_Totals');
        $parentBlockMock->expects($this->once())->method('addTotalBefore')->with($this->callback( function ($total) {
            $this->assertEquals(1.95, $total->getValue());
            $this->assertEquals('mollie_mpm_payment_fee', $total->getCode());

            return true;
        }));

        $block->setParentBlock($parentBlockMock);

        $block->initTotals();
    }

    public function testDoesNotShownWhenThereIsNoPaymentFee()
    {
        /** @var Mollie_Mpm_Block_Adminhtml_Sales_Invoice_PaymentFee $block */
        $block = Mage::app()->getLayout()->createBlock('mpm/adminhtml_sales_invoice_paymentFee');

        $order = Mage::getModel('sales/order');
        $order->setMollieMpmPaymentFee('0.000');
        $block->setOrder($order);

        $parentBlockMock = $this->createMock('Mage_Sales_Block_Order_Totals');
        $parentBlockMock->expects($this->never())->method('addTotalBefore');
        $block->setParentBlock($parentBlockMock);

        $block->initTotals();
    }
}
