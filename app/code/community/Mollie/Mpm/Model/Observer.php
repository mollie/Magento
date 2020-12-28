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

use Mage_Sales_Model_Order as Order;

class Mollie_Mpm_Model_Observer
{

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     */
    public function orderCancelAfter(Varien_Event_Observer $observer)
    {
        /** @var Mollie_Mpm_Model_Mollie $mollieModel */
        $mollieModel = Mage::getModel('mpm/mollie');

        /** @var Mollie_Mpm_Helper_Data $mollieHelper */
        $mollieHelper= Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getorder();

        if ($mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            $mollieModel->cancelOrder($order);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     */
    public function salesOrderCreditmemoSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mollie_Mpm_Model_Mollie $mollieModel */
        $mollieModel = Mage::getModel('mpm/mollie');

        /** @var Mollie_Mpm_Helper_Data $mollieHelper */
        $mollieHelper= Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        /** @var Mage_Sales_Model_Order $order */
        $order = $creditmemo->getOrder();

        if ($mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            $mollieModel->createOrderRefund($creditmemo, $order);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     */
    public function salesOrderShipmentSaveBefore(Varien_Event_Observer $observer)
    {
        /** @var Mollie_Mpm_Model_Mollie $mollieModel */
        $mollieModel = Mage::getModel('mpm/mollie');

        /** @var Mollie_Mpm_Helper_Data $mollieHelper */
        $mollieHelper= Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        /** @var Mage_Sales_Model_Order $order */
        $order = $shipment->getOrder();

        if ($mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            $mollieModel->createShipment($shipment, $order);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     */
    public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mollie_Mpm_Model_Mollie $mollieModel */
        $mollieModel = Mage::getModel('mpm/mollie');

        /** @var Mollie_Mpm_Helper_Data $mollieHelper */
        $mollieHelper= Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        $track = $observer->getEvent()->getTrack();

        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $track->getShipment();

        /** @var Mage_Sales_Model_Order $order */
        $order = $shipment->getOrder();

        if ($mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            $mollieModel->updateShipmentTrack($shipment, $track, $order);
        }
    }

    public function restoreQuoteWhenReturningFromMollie(Varien_Event_Observer $observer)
    {
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $session->getLastQuoteId();
        $orderId = $session->getLastOrderId();

        /** @var Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$quoteId || !in_array($order->getState(), array(Order::STATE_NEW, Order::STATE_PENDING_PAYMENT))) {
            return;
        }

        if (!$order->getPayment() || stripos($order->getPayment()->getMethod(), 'mollie') === false) {
            return;
        }

        try {
            $quote = Mage::getModel('sales/quote')->load($quoteId);

            if (!$quote->getIsActive()) {
                $quote->setIsActive(true)->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * The Mollie javascript library conflicts with he ancient prototype.js library, that's why we load the library
     * as soon as possible. When adding the script using xml it is always added to late. That's why we do it by
     * manipulating the html.
     *
     * @param Varien_Event_Observer $observer
     */
    public function injectMollieComponentsJs(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Block_Template $block */
        $block = $observer->getBlock();

        if (!$block instanceof Mage_Page_Block_Html_Head) {
            return;
        }

        if (stripos(Mage::app()->getRequest()->getControllerModule(), 'checkout') === false) {
            return;
        }

        /** @var \Varient_Object $transport */
        $transport = $observer->getData('transport');
        $html = $transport->getHtml();
        $html = preg_replace('#</title>#', '</title>' . PHP_EOL . '<script type="text/javascript" src="https://js.mollie.com/v1/mollie.js"></script>', $html, 1);

        $transport->setHtml($html);
    }
}
