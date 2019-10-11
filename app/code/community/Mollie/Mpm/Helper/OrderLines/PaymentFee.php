<?php


class Mollie_Mpm_Helper_OrderLines_PaymentFee
{
    /**
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function orderHasPaymentFee(Mage_Sales_Model_Order $order)
    {
        if ($order->getMollieMpmPaymentFee() && $order->getMollieMpmPaymentFee() != 0) {
            return true;
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return bool
     */
    public function creditmemoHasPaymentFee(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        if ($creditmemo->getMollieMpmPaymentFee() && $creditmemo->getMollieMpmPaymentFee() != 0) {
            return true;
        }

        return false;
    }

    public function getOrderLine(Mage_Sales_Model_Order $order)
    {
        $this->validateOrder($order);

        /** @var Mollie_Mpm_Helper_Data $mollieHelper */
        $mollieHelper = Mage::helper('mpm');

        $paymentFee = $order->getMollieMpmPaymentFee();
        $paymentFeeTax = $order->getMollieMpmPaymentFeeTax();
        $totalPaymentFee = $paymentFee + $paymentFeeTax;
        $vatRate = ($paymentFeeTax / $paymentFee) * 100;

        return [
            'type' => 'surcharge',
            'name' => Mage::helper('mpm')->__('Payment Fee'),
            'quantity' => 1,
            'unitPrice' => $mollieHelper->getAmountArray($order->getOrderCurrencyCode(), $totalPaymentFee),
            'totalAmount' => $mollieHelper->getAmountArray($order->getOrderCurrencyCode(), $totalPaymentFee),
            'vatRate' => round($vatRate, 2),
            'vatAmount' => $mollieHelper->getAmountArray($order->getOrderCurrencyCode(), $paymentFeeTax),
            'sku' => 'surcharge',
        ];
    }

    public function getCreditmemoOrderLine(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        /** @var Mollie_Mpm_Model_OrderLines $helper */
        $helper = Mage::getModel('mpm/orderLines');
        $orderLine = $helper->getSurchargeItemLineOrder($creditmemo->getOrderId());

        return [
            'id' => $orderLine->getLineId(),
        ];
    }

    private function validateOrder(Mage_Sales_Model_Order $order)
    {
        if (
            !$order->getMollieMpmPaymentFee() ||
            !$order->getMollieMpmPaymentFeeTax() ||
            !$order->getBaseMollieMpmPaymentFee() ||
            !$order->getBaseMollieMpmPaymentFeeTax()
        ) {
            throw new Mollie_Mpm_Exceptions_OrderMissingPaymentFee('The order is missing the paymentFee fields');
        }
    }
}
