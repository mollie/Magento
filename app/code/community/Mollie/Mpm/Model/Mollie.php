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

class Mollie_Mpm_Model_Mollie extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Enable Rety on payment API of order API fails
     */
    const RETRY_FLAG = true;

    /**
     * @var Mollie_Mpm_Helper_Data
     */
    public $mollieHelper;
    /**
     * @var Mollie_Mpm_Model_Api
     */
    public $mollieApi;
    /**
     * @var Mollie_Mpm_Model_Client_Orders
     */
    public $ordersApi;
    /**
     * @var Mollie_Mpm_Model_Client_Payments
     */
    public $paymentsApi;

    /**
     *
     */
    public function __construct()
    {
        parent::_construct();
        $this->mollieHelper = Mage::helper('mpm');
        $this->ordersApi = Mage::getModel('mpm/client_orders');
        $this->paymentsApi = Mage::getModel('mpm/client_payments');
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (!$this->mollieHelper->isAvailable($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        if (!$this->mollieHelper->isMethodAvailableForQuote($quote, $this->_code)) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool|null|string
     * @throws Mage_Core_Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function startTransaction(Mage_Sales_Model_Order $order)
    {
        $transactionResult = null;

        $storeId = $order->getStoreId();
        if (!$apiKey = $this->mollieHelper->getApiKey($storeId)) {
            return false;
        }

        $method = $this->mollieHelper->getApiMethod($order);
        if ($method == 'order') {
            try {
                $transactionResult = $this->ordersApi->startTransaction($order);
            } catch (\Exception $e) {
                $methodCode = $this->mollieHelper->getMethodCode($order);
                if (self::RETRY_FLAG && $methodCode != 'klarnapaylater' && $methodCode != 'klarnasliceit') {
                    $this->mollieHelper->addTolog('error', $e->getMessage());
                    $transactionResult = $this->paymentsApi->startTransaction($order);
                } else {
                    Mage::throwException($e->getMessage());
                }
            }
        } else {
            $transactionResult = $this->paymentsApi->startTransaction($order);
        }

        return $transactionResult;
    }

    /**
     * @param        $orderId
     * @param string $type
     * @param null   $paymentToken
     *
     * @return array
     * @throws Mage_Core_Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function processTransaction($orderId, $type = 'webhook', $paymentToken = null)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (empty($order)) {
            $msg = array('error' => true, 'msg' => 'Order not found');
            $this->mollieHelper->addTolog('error', $msg);
            return $msg;
        }

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = array('error' => true, 'msg' => 'Transaction ID not found');
            $this->mollieHelper->addTolog('error', $msg);
            return $msg;
        }

        $storeId = $order->getStoreId();
        if (!$apiKey = $this->mollieHelper->getApiKey($storeId)) {
            $msg = array('error' => true, 'msg' => 'API Key not found');
            $this->mollieHelper->addTolog('error', $msg);
            return $msg;
        }

        $method = $this->mollieHelper->getApiMethod($order);
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        try {
            $connection->beginTransaction();

            if ($method == 'order' && preg_match('/^ord_\w+$/', $transactionId)) {
                return $this->ordersApi->processTransaction($order, $type, $paymentToken);
            } else {
                return $this->paymentsApi->processTransaction($order, $type, $paymentToken);
            }
        } catch (\Exception $exception) {
            $connection->rollback();
            throw $exception;
        } finally {
            $this->commitOrder($order);
            $connection->commit();
        }
    }

    /**
     * Redirects the client on click 'Place Order' to the payment screen.
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        if ($this->mollieHelper->useLoadingScreen()) {
            return Mage::getUrl('mpm/api/redirect', array('_secure' => true));
        } else {
            return Mage::getUrl('mpm/api/payment', array('_secure' => true));
        }
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param Mage_Sales_Model_Order          $order
     *
     * @return Mollie_Mpm_Model_Client_Orders
     * @throws Mage_Core_Exception
     */
    public function createShipment(Mage_Sales_Model_Order_Shipment $shipment, Mage_Sales_Model_Order $order)
    {
        return $this->ordersApi->createShipment($shipment, $order);
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment       $shipment
     * @param Mage_Sales_Model_Order_Shipment_Track $track
     * @param Mage_Sales_Model_Order                $order
     *
     * @return Mollie_Mpm_Model_Client_Orders
     * @throws Mage_Core_Exception
     */
    public function updateShipmentTrack(Mage_Sales_Model_Order_Shipment $shipment, Mage_Sales_Model_Order_Shipment_Track $track, Mage_Sales_Model_Order $order)
    {
        return $this->ordersApi->updateShipmentTrack($shipment, $track, $order);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mollie_Mpm_Model_Client_Orders
     * @throws Mage_Core_Exception
     */
    public function cancelOrder(Mage_Sales_Model_Order $order)
    {
        return $this->ordersApi->cancelOrder($order);
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order            $order
     *
     * @throws Mage_Core_Exception
     */
    public function createOrderRefund(Mage_Sales_Model_Order_Creditmemo $creditmemo, Mage_Sales_Model_Order $order)
    {
        $this->ordersApi->createOrderRefund($creditmemo, $order);
    }

    /**
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();

        /**
         * Order Api does not use amount to refund, but refunds per itemLine
         * See SalesOrderCreditmemoAfter Observer for logic.
         */
        $checkoutType = $this->mollieHelper->getCheckoutType($order);
        if ($checkoutType == 'order') {
            Mage::register('online_refund', true);
            return $this;
        }

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = array('error' => true, 'msg' => $this->mollieHelper->__('Transaction ID not found'));
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $apiKey = $this->mollieHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = array('error' => true, 'msg' => $this->mollieHelper->__('Api key not found'));
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        try {
            $mollieApi = $this->mollieHelper->getMollieAPI($apiKey);
            $payment = $mollieApi->payments->get($transactionId);
            $payment->refund(
                array(
                "amount" => array(
                    "currency" => $order->getOrderCurrencyCode(),
                    "value"    => $this->mollieHelper->formatCurrencyValue($amount, $order->getOrderCurrencyCode())
                )
                )
            );
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            Mage::throwException($this->mollieHelper->__('Error: not possible to create an online refund: %s', $e->getMessage()));
        }

        return $this;
    }

    /**
     * Get order by TransactionId
     *
     * @param $transactionId
     *
     * @return mixed
     */
    public function getOrderIdByTransactionId($transactionId)
    {
        $orderId = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('mollie_transaction_id', $transactionId)
            ->getFirstItem()
            ->getId();

        if ($orderId) {
            return $orderId;
        }

        /**
         * Search for OrderId in old deprecated table for transactions created before v5.0
         */
        /** @var Mollie_Mpm_Model_Payments $oldPaymentModel */
        $oldPaymentModel = Mage::getModel('mpm/payments');
        if ($orderId = $oldPaymentModel->loadByTransactionId($transactionId)) {
            Mage::getModel('sales/order')->load($orderId)->setMollieTransactionId($transactionId)->save();
            return $orderId;
        }

        $this->mollieHelper->addTolog('error', $this->mollieHelper->__('No order found for transaction id %s', $transactionId));
        return false;
    }

    /**
     * When wrapping a $order->save() in a transaction, the update of the grid is postponed to a later action.
     * This function fixes 2 edge cases which prevents that the order grid is updated. Both are caused due to the fact
     * that we update the order in a transaction.
     *
     * 1. Sometimes people disable the `controller_action_postdispatch` log observer, which calls all
     *    afterCommitCallback methods outside of the transaction.
     *
     * 2. When disabling logging in the backend, these afterCommitCallback methods are also not called. This is due to
     *    the fact that the code for triggering these goes trough the log module.
     *
     * In both cases we call the `commit()` method of the order ourselves, so the afterCommitCallbacks are still called.
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function commitOrder(Mage_Sales_Model_Order $order)
    {
        $logObserver = Mage::getConfig()->getNode('frontend/events/controller_action_postdispatch/observers/log');
        $logObserverIsDisabled = $logObserver && $logObserver->type == 'disabled';
        $systemLogIsDisabled = $this->mollieHelper->getStoreConfig(Mage_Log_Helper_Data::XML_PATH_LOG_ENABLED) == '0';

        if (!$logObserverIsDisabled && !$systemLogIsDisabled) {
            return;
        }

        $order->getResource()->commit();
    }

}
