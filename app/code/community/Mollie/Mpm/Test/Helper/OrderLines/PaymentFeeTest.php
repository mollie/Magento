<?php


class PaymentFeeTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testTheOrderHasAPaymentFee()
    {
        $order = Mage::getModel('sales/order');
        $order->setMollieMpmPaymentFee(1.95);
        $order->setBaseMollieMpmPaymentFee(1.95);

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        $result = $instance->orderHasPaymentFee($order);

        $this->assertTrue($result);
    }

    public function testTheCreditmemoHasAPaymentFee()
    {
        $creditmemo = Mage::getModel('sales/order_creditmemo');
        $creditmemo->setMollieMpmPaymentFee(1.95);
        $creditmemo->setBaseMollieMpmPaymentFee(1.95);

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        $result = $instance->creditmemoHasPaymentFee($creditmemo);

        $this->assertTrue($result);
    }

    public function testTheOrderDoesNotHasAPaymentFee()
    {
        $order = Mage::getModel('sales/order');

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        $result = $instance->orderHasPaymentFee($order);

        $this->assertFalse($result);
    }

    public function testTheCreditmemoDoesNotHasAPaymentFee()
    {
        $creditmemo = Mage::getModel('sales/order_creditmemo');

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        $result = $instance->creditmemoHasPaymentFee($creditmemo);

        $this->assertFalse($result);
    }

    public function testGetOrderLine()
    {
        $paymentFeeIncludingTax = 1;
        $paymentFeeTax = ($paymentFeeIncludingTax / 121) * 21;
        $paymentFeeExcludingTax = $paymentFeeIncludingTax - $paymentFeeTax;

        $order = Mage::getModel('sales/order');
        $order->setOrderCurrencyCode('EUR');
        $order->setMollieMpmPaymentFee($paymentFeeExcludingTax);
        $order->setBaseMollieMpmPaymentFee($paymentFeeExcludingTax);
        $order->setMollieMpmPaymentFeeTax($paymentFeeTax);
        $order->setBaseMollieMpmPaymentFeeTax($paymentFeeTax);

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        $result = $instance->getOrderLine($order);

        $this->assertEquals('surcharge', $result['type']);
        $this->assertEquals(Mage::helper('mpm')->__('Payment Fee'), $result['name']);
        $this->assertEquals(1, $result['quantity']);
        $this->assertEquals('EUR', $result['unitPrice']['currency']);
        $this->assertEquals($paymentFeeIncludingTax, $result['unitPrice']['value']);
        $this->assertEquals($paymentFeeIncludingTax, $result['totalAmount']['value']);
        $this->assertEquals('EUR', $result['totalAmount']['currency']);
        $this->assertEquals(round(21, 2), $result['vatRate']);
        $this->assertEquals(round($paymentFeeTax, 2), $result['vatAmount']['value']);
        $this->assertEquals('EUR', $result['vatAmount']['currency']);
        $this->assertEquals('surcharge', $result['sku']);
    }

    public function testGetOrderLineThrowsAnExceptionWhenRequiredDataIsMissing()
    {
        $order = Mage::getModel('sales/order');

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        try {
            $instance->getOrderLine($order);
        } catch (Mollie_Mpm_Exceptions_OrderMissingPaymentFee $exception) {
            $this->assertEquals('The order is missing the paymentFee fields', $exception->getMessage());
            return;
        }

        $this->fail('We expected an exception but got none');
    }

    public function testGetCreditmemoOrderLine()
    {
        $orderLine = Mage::getModel('mpm/orderLines');
        $orderLine->setData('line_id', 'odl_abc123');

        $orderlinesHelperMock = $this->createMock('Mollie_Mpm_Model_OrderLines');
        $orderlinesHelperMock->method('getSurchargeItemLineOrder')->willReturn($orderLine);

        $this->addModelMock('mpm/orderLines', $orderlinesHelperMock);

        $creditmemo = Mage::getModel('sales/order_creditmemo');

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $instance */
        $instance = Mage::helper('mpm/orderLines_paymentFee');

        $result = $instance->getCreditmemoOrderLine($creditmemo);

        $this->assertEquals('odl_abc123', $result['id']);
    }
}
