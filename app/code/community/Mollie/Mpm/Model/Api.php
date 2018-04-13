<?php
/**
 * Copyright (c) 2012-2018, Mollie B.V.
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
 * @copyright   Copyright (c) 2012-2018 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD-License 2
 */

class Mollie_Mpm_Model_Api extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Mollie Payments Model.
     *
     * @var Mollie_Mpm_Model_Payments
     */
    public $molliePaymentsModel;
    /**
     * Mollie Methods Model.
     *
     * @var Mollie_Mpm_Model_Methods
     */
    public $mollieMethodsModel;
    /**
     * Mollie API Helper.
     *
     * @var Mollie_Mpm_Helper_Api
     */
    public $mollieHelper;
    /**
     * Payment method index.
     *
     * @var int
     */
    protected $_index;

    /**
     * @var string
     */
    protected $_code = "mpm_api";
    protected $_infoBlockType = 'mpm/payment_info';
    protected $_formBlockType = 'mpm/payment_form';
    protected $_paymentMethod = 'Mollie';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture = true;

    /**
     * Mollie_Mpm_Model_Api constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->mollieHelper = Mage::helper('mpm/api');
        $this->molliePaymentsModel = Mage::getModel('mpm/payments');
        $this->mollieMethodsModel = Mage::getModel('mpm/methods');
    }

    /**
     * Check whether payment method can be used.
     *
     * @param Mage_Sales_Model_Quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $enabled = $this->mollieHelper->isModuleEnabled();
        if (!$enabled) {
            return false;
        }

        if (!$this->isValidIndex()) {
            return false;
        }

        if (!isset($this->mollieHelper->methods[$this->_index])) {
            return false;
        }

        if (!$this->mollieHelper->methods[$this->_index]['available']) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @return bool
     */
    public function isValidIndex()
    {
        if (!is_array($this->mollieHelper->methods)) {
            return false;
        }

        if (!isset($this->_index)) {
            return false;
        }

        if ($this->_index < 0) {
            return false;
        }

        if ($this->_index >= sizeof($this->mollieHelper->methods)) {
            return false;
        }

        return true;
    }

    /**
     * Get Config Method Title.
     */
    public function getTitle()
    {
        if (is_string($this->mollieHelper->methods)) {
            return $this->mollieHelper->__($this->mollieHelper->methods);
        }

        if ($this->isValidIndex()) {
            $title = $this->mollieHelper->getMethodTitle($this->_index);
            return $title ?: $this->mollieHelper->__($this->mollieHelper->methods[$this->_index]['description']);
        }

        return $this->mollieHelper->__(parent::getTitle());
    }

    /**
     * Get Config Method Image.
     */
    public function getImage()
    {
        if (!empty($this->mollieHelper->methods[$this->_index]['image'])) {
            return $this->mollieHelper->methods[$this->_index]['image']->normal;
        }
    }

    /**
     * @param string          $field
     * @param null|int|string $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ($this->isValidIndex()) {
            if ($field === "min_order_total") {
                return $this->mollieHelper->methods[$this->_index]['amount']->minimum;
            }

            if ($field === "max_order_total") {
                return $this->mollieHelper->methods[$this->_index]['amount']->maximum;
            }

            if ($field === "sort_order") {
                $sortOrder = $this->mollieHelper->getMethodSortOrder($this->_index);
                return $sortOrder ?: $this->mollieHelper->methods[$this->_index]['sort_order'];
            }

            if ($field === "title") {
                $title = $this->mollieHelper->getMethodTitle($this->_index);
                return $title ?: $this->mollieHelper->methods[$this->_index]['description'];
            }
        }

        if ($field === "title") {
            return $this->mollieHelper->__('{Reserved}');
        }

        return parent::getConfigData($field, $storeId);
    }

    /**
     * @param mixed $data
     *
     * @return $this|Mage_Payment_Model_Info
     * @throws Mage_Core_Exception
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        if (strlen(Mage::registry('method_id')) == 0) {
            $method = $this->mollieHelper->getMethodByCode($data->_data['method']);
            Mage::register('method_id', $method['method_id']);
            Mage::register('issuer', Mage::app()->getRequest()->getParam($this->_code . '_issuer'));
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return null|string
     * @throws Mage_Core_Exception
     * @throws Mollie_API_Exception
     * @throws Exception
     */
    public function startTransaction($order)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $issuer = Mage::app()->getRequest()->getParam('issuer');
        $method = Mage::app()->getRequest()->getParam('method_id');

        if ($transactionId = $this->molliePaymentsModel->getTransactionIdByOrderId($orderId)) {
            $msg = sprintf('Doubel request Order %s - TransactionId %s', $transactionId, $orderId);
            $this->mollieHelper->addLog('startTransaction [ERR]', $msg);
            $payment = $this->mollieHelper->getMollieAPI()->payments->get($transactionId);
            $paymentUrl = $payment->getPaymentUrl();
            $this->mollieHelper->addLog('startTransaction [ERR]', $paymentUrl);
            return $paymentUrl;
        }

        $request = array(
            "amount"      => $this->mollieHelper->getOrderAmount($order),
            "description" => $this->mollieHelper->getDescription($order),
            "redirectUrl" => $this->mollieHelper->getReturnUrl($orderId),
            "webhookUrl"  => $this->mollieHelper->getWebhookUrl(),
            "method"      => $method,
            "issuer"      => $issuer,
            "metadata"    => array(
                "order_id" => $orderId,
                "store_id" => $storeId,
            ),
            "locale"      => $this->mollieHelper->getLocaleCode()
        );

        if ($method == "banktransfer") {
            $request += array(
                "dueDate"      => $this->mollieHelper->getBankTransferDueDateDays(),
                "billingEmail" => $order->getCustomerEmail(),
            );
        }

        if ($billing = $order->getBillingAddress()) {
            $request += array(
                "billingCity"    => $billing->getCity(),
                "billingRegion"  => $billing->getRegion(),
                "billingPostal"  => $billing->getPostcode(),
                "billingCountry" => $billing->getCountryId(),
            );
        }

        if ($shipping = $order->getShippingAddress()) {
            $request += array(
                "shippingAddress" => $shipping->getData('street'),
                "shippingCity"    => $shipping->getCity(),
                "shippingRegion"  => $shipping->getRegion(),
                "shippingPostal"  => $shipping->getPostcode(),
                "shippingCountry" => $shipping->getCountry(),
            );
        }

        $this->mollieHelper->addLog('startTransaction [REQ]', $request);
        $payment = $this->mollieHelper->getMollieAPI()->payments->create($request);
        $this->mollieHelper->addLog('startTransaction [RESP]', $payment);
        $this->molliePaymentsModel->setPayment($request, $payment);
        $paymentUrl = $payment->getPaymentUrl();
        $transactionId = $payment->id;

        $payment = $order->getPayment();
        $payment->setTransactionId($transactionId)->setIsTransactionClosed(false);
        $order->setPayment($payment)->save();
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

        $message = $this->mollieHelper->__('Customer redirected to Mollie, url: %s', $paymentUrl);
        $status = $this->mollieHelper->getStatusPending();
        $order->addStatusToHistory($status, $message, false);
        $order->save();

        return $paymentUrl;
    }

    /**
     * Process Transaction (webhook / success)
     *
     * @param        $orderId
     * @param string $type
     *
     * @return array
     * @throws Exception
     * @throws Mollie_API_Exception
     */
    public function processTransaction($orderId, $type = 'webhook')
    {
        $msg = '';

        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (empty($order)) {
            $msg = array('error' => true, 'msg' => 'Order not found');
            $this->mollieHelper->addLog('processTransaction [ERR]', $msg);
            return $msg;
        }

        $storeId = $order->getStoreId();
        $transactionId = $this->molliePaymentsModel->getTransactionIdByOrderId($orderId);
        if (empty($transactionId)) {
            $msg = array('error' => true, 'msg' => 'Transaction ID not found');
            $this->mollieHelper->addLog('processTransaction [ERR]', $msg);
            return $msg;
        }

        $apiKey = $this->mollieHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = array('error' => true, 'msg' => 'Api key not found');
            $this->mollieHelper->addLog('processTransaction [ERR]', $msg);
            return $msg;
        }

        $paymentData = $this->mollieHelper->getMollieAPI()->payments->get($transactionId);

        if ($type == 'webhook') {
            $this->mollieHelper->addLog('processTransaction [WEBHOOK]', $paymentData);
        }

        if (($paymentData->isPaid() == true) && ($paymentData->isRefunded() == false)) {
            $payment = $order->getPayment();
            if (!$payment->getIsTransactionClosed() && $type == 'webhook') {

                $payment->setTransactionId($transactionId)->setIsTransactionClosed(true);
                $order->setPayment($payment);

                if (abs($paymentData->amount - $order->getGrandTotal()) < 0.01) {
                    $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                    $order->save();
                }

                $sendOrderEmail = $this->mollieHelper->sendOrderEmail($storeId);
                if (!$order->getEmailSent() && $sendOrderEmail) {
                    $order->sendNewOrderEmail()->setEmailSent(true)->save();
                }

                if ($this->mollieHelper->invoiceOrder($storeId)) {
                    $invoice = $this->invoiceOrder($order);
                    $sendInvoice = $this->mollieHelper->sendInvoiceEmail($storeId);

                    if (!$order->getIsVirtual()) {
                        $status = $this->mollieHelper->getStatusProcessing($storeId);
                        if ($status && ($status != $order->getStatus())) {
                            $message = $this->mollieHelper->__('Updated processing status');
                            $order->setState($order->getState(), $status, $message, false)->save();
                        }
                    }

                    if ($invoice && $sendInvoice && !$invoice->getEmailSent()) {
                        $invoice->setEmailSent(true)->sendEmail()->save();
                    }
                }
            }

            $msg = array('success' => true, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addLog('processTransaction [SUCC]', $msg);
        } elseif ($paymentData->isRefunded() == true) {
            $payment = $order->getPayment();
            if ($order->canCreditmemo() && $type == 'webhook') {
                $payment->setTransactionId($transactionId)
                    ->setIsTransactionClosed(true)
                    ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
                $order->setPayment($payment);
            }

            $msg = array('success' => true, 'status' => 'refunded', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addLog('processTransaction [SUCC]', $msg);
        } elseif ($paymentData->isOpen() == true) {
            if ($paymentData->method == 'banktransfer' && !$order->getEmailSent()) {
                $order->sendNewOrderEmail()->setEmailSent(true)->save();
                $status = $this->mollieHelper->getStatusPending($storeId);
                $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                $message = $this->mollieHelper->__('New order email sent');
                $order->setState($state, $status, $message, false)->save();
            }
            $msg = array('success' => true, 'status' => 'open', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addLog('processTransaction [SUCC]', $msg);
        } elseif ($paymentData->isPending() == true) {
            $msg = array('success' => true, 'status' => 'pending', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addLog('processTransaction [SUCC]', $msg);
        } elseif (!$paymentData->isOpen()) {
            if ($type == 'webhook') {
                $this->cancelOrder($order, $paymentData->status);
            }
            $msg = array('success' => false, 'status' => 'cancel', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addLog('processTransaction [SUCC]', $msg);
        }

        if (isset($msg['status'])) {
            $this->molliePaymentsModel->updatePayment($orderId, $msg['status'], $paymentData);
        }

        return $msg;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     * @throws Exception
     */
    public function invoiceOrder($order)
    {
        if ($order->canInvoice()) {
            /** @var Mage_Sales_Model_Service_Order $service */
            $service = Mage::getModel('sales/service_order', $order);
            $invoice = $service->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->pay()->save();
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
            return $invoice;
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @param null                   $status
     *
     * @return bool
     */
    public function cancelOrder($order, $status = null)
    {
        if ($order->getId() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
            try {
                $order->cancel();
                $comment = $this->mollieHelper->__('The order was canceled (status: %s)', $status);
                $this->mollieHelper->addlog('cancelOrder', $order->getIncrementId() . ' ' . $comment);
                $order->addStatusToHistory($order->getStatus(), $comment, false);
                $order->save();
                return true;
            } catch (Exception $e) {
                $this->mollieHelper->addlog('cancelOrder', $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    public function createCreditMemto($order)
    {
        if ($order->canCreditmemo()) {
            /** @var Mage_Sales_Model_Service_Order $service */
            $service = Mage::getModel('sales/service_order', $order);
            $creditmemo = $service->prepareCreditmemo();
            $creditmemo->register();
            $order->addRelatedObject($creditmemo);
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
            return Mage::getUrl(
                'mpm/api/redirect',
                array(
                    '_secure' => true,
                    '_query'  => array(
                        'method_id' => Mage::registry('method_id'),
                        'issuer'    => Mage::registry('issuer'),
                    )
                )
            );
        } else {
            return Mage::getUrl(
                'mpm/api/payment',
                array(
                    '_secure' => true,
                    '_query'  => array(
                        'method_id' => Mage::registry('method_id'),
                        'issuer'    => Mage::registry('issuer'),
                    )
                )
            );
        }
    }

    /**
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     * @throws Mage_Core_Exception
     * @throws Mollie_API_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = $payment->getOrder();

        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $transactionId = $this->molliePaymentsModel->getTransactionIdByOrderId($orderId);
        if (empty($transactionId)) {
            $msg = array('error' => true, 'msg' => 'Transaction not found');
            $this->mollieHelper->addLog('refund [ERR]', $msg);
            return $this;
        }
        $apiKey = $this->mollieHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = array('error' => true, 'msg' => 'Api key not found');
            $this->mollieHelper->addLog('refund [ERR]', $msg);
            return $this;
        }
        $api = $this->mollieHelper->getMollieAPI();
        try {
            $payment = $api->payments->get($transactionId);
            $msg = $this->mollieHelper->__(
                'Creditmemo for order %s',
                $order->getIncrementId()
            );
            $api->payments->refund($payment, array('amount' => $amount, 'description' => $msg));
        } catch (Exception $e) {
            $this->mollieHelper->addLog('refund [ERR]', $e->getMessage());
            Mage::throwException('Impossible to create a refund for this transaction. Details: ' . $e->getMessage() . '<br />');
        }
        return $this;
    }
}

