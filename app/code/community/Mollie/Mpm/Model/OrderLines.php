<?php
/**
 * Copyright (c) 2012-2019, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category    Mollie
 * @package     Mollie_Mpm
 * @author      Mollie B.V. (info@mollie.nl)
 * @copyright   Copyright (c) 2012-2019 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD-License 2
 */

class Mollie_Mpm_Model_OrderLines extends Mage_Core_Model_Abstract
{

    /**
     * Mollie Helper
     *
     * @var Mollie_Mpm_Helper_Data
     */
    public $mollieHelper;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->mollieHelper = Mage::helper('mpm');
    }

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mpm/orderLines');
    }

    /**
     * Get Order lines of Order
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderLines(Mage_Sales_Model_Order $order)
    {
        $forceBaseCurrency = $this->mollieHelper->useBaseCurrency($order->getStoreId());
        $currency = $forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();
        $orderLines = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {

            /**
             * The total amount of the line, including VAT and discounts
             * Should Match: (unitPrice × quantity) - discountAmount
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $this->_getTotalAmountOrderItem($item, $forceBaseCurrency);

            /**
             * The total discount amount of the line.
             */
            $discountAmount = $this->_getDiscountAmountOrderItem($item, $forceBaseCurrency);

            /**
             * The price of a single item including VAT in the order line.
             * Calculated back from the totalAmount + discountAmount to overcome rounding issues.
             */
            $unitPrice = round(($totalAmount + $discountAmount) / $item->getQtyOrdered(), 2);

            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we calculate this instead of using $item->getTaxAmount() to overcome
             * any rouding issues.
             */
            $vatAmount = round($totalAmount * ($item->getTaxPercent() / (100 + $item->getTaxPercent())), 2);

            $orderLine = array(
                'item_id'     => $item->getId(),
                'type'        => $item->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                'name'        => $this->getProductName($item->getName()),
                'quantity'    => round($item->getQtyOrdered()),
                'unitPrice'   => $this->mollieHelper->getAmountArray($currency, $unitPrice),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'vatRate'     => sprintf("%.2f", $item->getTaxPercent()),
                'vatAmount'   => $this->mollieHelper->getAmountArray($currency, $vatAmount),
                'sku'         => $item->getProduct()->getSku(),
                'productUrl'  => $item->getProduct()->getProductUrl()
            );

            if ($discountAmount) {
                $orderLine['discountAmount'] = $this->mollieHelper->getAmountArray($currency, $discountAmount);
            }

            $orderLines[] = $orderLine;

            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                /** @var Mage_Sales_Model_Order_Item $childItem */
                foreach ($item->getChildrenItems() as $childItem) {
                    $orderLines[] = [
                        'item_id'     => $childItem->getId(),
                        'type'        => $childItem->getProduct()->getTypeId() != 'downloadable' ? 'physical' : 'digital',
                        'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $childItem->getName()),
                        'quantity'    => round($item->getQtyOrdered()),
                        'unitPrice'   => $this->mollieHelper->getAmountArray($currency, 0),
                        'totalAmount' => $this->mollieHelper->getAmountArray($currency, 0),
                        'vatRate'     => sprintf("%.2f", $childItem->getTaxPercent()),
                        'vatAmount'   => $this->mollieHelper->getAmountArray($currency, 0),
                        'sku'         => $childItem->getProduct()->getSku(),
                        'productUrl'  => $childItem->getProduct()->getProductUrl()
                    ];
                }
            }
        }

        if (!$order->getIsVirtual()) {
            /**
             * The total amount of the line, including VAT and discounts
             * NOTE: TotalAmount can differ from actutal Total Amount due to rouding in tax or exchange rate
             */
            $totalAmount = $this->_getTotalAmountShipping($order, $forceBaseCurrency);

            $vatRate = $this->_getShippingVatRate($order);

            /**
             * The amount of VAT on the line.
             * Should Match: totalAmount × (vatRate / (100 + vatRate)).
             * Due to Mollie API requirements, we recalculare this from totalAmount
             */
            $vatAmount = round($totalAmount * ($vatRate / (100 + $vatRate)), 2);

            $orderLine = array(
                'item_id'     => '',
                'type'        => 'shipping_fee',
                'name'        => preg_replace("/[^A-Za-z0-9 -]/", "", $order->getShippingDescription()),
                'quantity'    => 1,
                'unitPrice'   => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $totalAmount),
                'vatRate'     => sprintf("%.2f", $vatRate),
                'vatAmount'   => $this->mollieHelper->getAmountArray($currency, $vatAmount),
                'sku'         => $order->getShippingMethod()
            );

            $orderLines[] = $orderLine;
        }

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $paymentFeeHelper */
        $paymentFeeHelper = Mage::helper('mpm/orderLines_paymentFee');
        if ($paymentFeeHelper->orderHasPaymentFee($order)) {
            $orderLines[] = $paymentFeeHelper->getOrderLine($order);
        }

        $this->saveOrderLines($orderLines, $order);
        foreach ($orderLines as &$orderLine) {
            unset($orderLine['item_id']);
        }

        return $orderLines;
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @param                             $forceBaseCurrency
     *
     * @return float
     */
    protected function _getTotalAmountOrderItem(Mage_Sales_Model_Order_Item $item, $forceBaseCurrency)
    {
        if ($forceBaseCurrency) {
            return $item->getBaseRowTotal()
                - $item->getBaseDiscountAmount()
                + $item->getBaseTaxAmount()
                + $item->getBaseHiddenTaxAmount();
        }

        return $item->getRowTotal()
            - $item->getDiscountAmount()
            + $item->getTaxAmount()
            + $item->getHiddenTaxAmount();
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @param                             $forceBaseCurrency
     *
     * @return float
     */
    protected function _getDiscountAmountOrderItem(Mage_Sales_Model_Order_Item $item, $forceBaseCurrency)
    {
        if ($forceBaseCurrency) {
            return $item->getBaseDiscountAmount();
        }

        return $item->getDiscountAmount();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param                        $forceBaseCurrency
     *
     * @return float
     */
    protected function _getTotalAmountShipping(Mage_Sales_Model_Order $order, $forceBaseCurrency)
    {
        if ($forceBaseCurrency) {
            return $order->getBaseShippingAmount()
                + $order->getBaseShippingTaxAmount()
                + $order->getBaseShippingHiddenTaxAmount();
        }

        return $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $order->getShippingHiddenTaxAmount();
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return double
     */
    protected function _getShippingVatRate(Mage_Sales_Model_Order $order)
    {
        $taxPercentage = 0;
        if ($order->getShippingAmount() > 0) {
            $taxPercentage = ($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100;
        }

        return $taxPercentage;
    }

    /**
     * @param                        $orderLines
     * @param Mage_Sales_Model_Order $order
     */
    public function saveOrderLines($orderLines, Mage_Sales_Model_Order $order)
    {
        foreach ($orderLines as $line) {
            $this->setData($line)->setOrderId($order->getId())->save();
        }
    }

    /**
     * @param                        $orderLines
     * @param Mage_Sales_Model_Order $order
     *
     * @throws Mage_Core_Exception
     */
    public function linkOrderLines($orderLines, Mage_Sales_Model_Order $order)
    {
        $key = 0;
        $orderLinesCollection = $this->getOrderLinesByOrderId($order->getId());

        foreach ($orderLinesCollection as $orderLineRow) {
            if (!isset($orderLines[$key])) {
                Mage::throwException('Could not save Order Lines. Error: order line not found');
            }

            if ($orderLines[$key]->sku != $orderLineRow->getSku()) {
                Mage::throwException('Could not save Order Lines. Error: sku\'s do not match');
            }

            $orderLineRow->setLineId($orderLines[$key]->id)->save();
            $key++;
        }
    }

    /**
     * @param $orderId
     *
     * @return Mollie_Mpm_Model_Resource_OrderLines_Collection
     */
    public function getOrderLinesByOrderId($orderId)
    {
        return $this->getCollection()->addFieldToFilter('order_id', array('eq' => $orderId));
    }

    /**
     * @param      $orderLines
     * @param bool $paid
     */
    public function updateOrderLinesByWebhook($orderLines, $paid = false)
    {
        foreach ($orderLines as $line) {
            $orderLineRow = $this->getOrderLineByLineId($line->id);

            if ($paid) {
                $orderLineRow->setQtyPaid($line->quantity);
            }

            $orderLineRow->setQtyShipped($line->quantityShipped)
                ->setQtyCanceled($line->quantityCanceled)
                ->setQtyRefunded($line->quantityRefunded)
                ->save();
        }
    }

    /**
     * @param $lineId
     *
     * @return Mollie_Mpm_Model_Resource_OrderLines
     */
    public function getOrderLineByLineId($lineId)
    {
        return $this->load($lineId, 'line_id');
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     */
    public function shipAllOrderLines(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $orderId = $shipment->getOrderId();
        $orderLinesCollection = $this->getOrderLinesByOrderId($orderId);
        foreach ($orderLinesCollection as $orderLineRow) {
            $qtyOrdered = $orderLineRow->getQtyOrdered();
            $orderLineRow->setQtyShipped($qtyOrdered)->save();
        }
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     *
     * @return array
     */
    public function getShipmentOrderLines(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $orderLines = array();

        /** @var Mage_Sales_Model_Order_Shipment_Item $item */
        foreach ($shipment->getItemsCollection() as $item) {
            if (!$item->getQty()) {
                continue;
            }

            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            $orderLines[] = array('id' => $lineId, 'quantity' => round($item->getQty()));
        }

        return array('lines' => $orderLines);
    }

    /**
     * @param $itemId
     *
     * @return Mollie_Mpm_Model_Resource_OrderLines
     */
    public function getOrderLineByItemId($itemId)
    {
        $orderLine = $this->getCollection()
            ->addFieldToFilter('item_id', array('eq' => $itemId))
            ->addFieldToFilter('line_id', array('notnull' => true))
            ->getLastItem();

        return $orderLine;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param                                   $addShipping
     *
     * @return array
     */
    public function getCreditmemoOrderLines(Mage_Sales_Model_Order_Creditmemo $creditmemo, $addShipping)
    {
        $orderLines = array();

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItemId = $item->getOrderItemId();
            $lineId = $this->getOrderLineByItemId($orderItemId)->getLineId();
            if ($lineId) {
                $orderLines[] = array('id' => $lineId, 'quantity' => round($item->getQty()));
            }
        }

        /** @var Mollie_Mpm_Helper_PaymentFee $paymentFeeHelper */
        $paymentFeeHelper = Mage::helper('mpm/paymentFee');

        /** @var Mollie_Mpm_Helper_OrderLines_PaymentFee $orderlinesPaymentFeeHelper */
        $orderlinesPaymentFeeHelper = Mage::helper('mpm/orderLines_paymentFee');
        if ($orderlinesPaymentFeeHelper->creditmemoHasPaymentFee($creditmemo) &&
            !$paymentFeeHelper->hasItemsLeftToRefund($creditmemo)
        ) {
            $orderLines[] = $orderlinesPaymentFeeHelper->getCreditmemoOrderLine($creditmemo);
        }

        if ($addShipping) {
            $orderId = $creditmemo->getOrderId();
            $shippingFeeItemLine = $this->getShippingFeeItemLineOrder($orderId);
            $orderLines[] = array('id' => $shippingFeeItemLine->getLineId(), 'quantity' => 1);
        }

        return array('lines' => $orderLines);
    }

    /**
     * @param $orderId
     *
     * @return mixed
     */
    public function getShippingFeeItemLineOrder($orderId)
    {
        $shippingLine = $this->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->addFieldToFilter('type', array('eq' => 'shipping_fee'))
            ->getLastItem();

        return $shippingLine;
    }

    /**
     * @param $orderId
     *
     * @return mixed
     */
    public function getSurchargeItemLineOrder($orderId)
    {
        $shippingLine = $this->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->addFieldToFilter('type', array('eq' => 'surcharge'))
            ->getLastItem();

        return $shippingLine;
    }

    /**
     * @param $orderId
     *
     * @return int
     */
    public function getOpenForShipmentQty($orderId)
    {
        $qty = 0;
        $orderLinesCollection = $this->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->addFieldToFilter('type', array('eq' => 'physical'))
            ->addExpressionFieldToSelect(
                'open',
                'SUM(qty_ordered - qty_shipped - qty_refunded)',
                array('qty_ordered', 'qty_shipped', 'qty_refunded')
            );
        $orderLinesCollection->getSelect()->group('order_id');

        foreach ($orderLinesCollection as $orderLineRow) {
            if ($orderLineRow->getOpen() > 0) {
                $qty += $orderLineRow->getOpen();
            }
        }

        return $qty;
    }

    /**
     * @param $orderId
     *
     * @return int
     */
    public function getOpenForRefundQty($orderId)
    {
        $qty = 0;
        $orderLinesCollection = $this->getCollection()
            ->addFieldToFilter('order_id', array('eq' => $orderId))
            ->addFieldToFilter('type', array('in' => array('physical', 'digital')))
            ->addExpressionFieldToSelect(
                'open',
                'SUM(qty_ordered - qty_refunded)',
                array('qty_ordered', 'qty_refunded')
            );
        $orderLinesCollection->getSelect()->group('order_id');

        foreach ($orderLinesCollection as $orderLineRow) {
            if ($orderLineRow->getOpen() > 0) {
                $qty += $orderLineRow->getOpen();
            }
        }

        return $qty;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getProductName($name)
    {
        $search = explode(',', 'ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,Ø,Å,Á,À,Â,Ä,È,É,Ê,Ë,Í,Î,Ï,Ì,Ò,Ó,Ô,Ö,Ú,Ù,Û,Ü,Ÿ,Ç,Æ,Œ');
        $replace = explode(',', 'c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,o,O,A,A,A,A,A,E,E,E,E,I,I,I,I,O,O,O,O,U,U,U,U,Y,C,AE,OE');
        $name = str_replace($search, $replace, $name);

        return preg_replace('/[^A-Za-z0-9 -]/', '', $name);
    }
}
