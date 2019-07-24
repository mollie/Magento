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

class Mollie_Mpm_Model_Client_Payments extends Mage_Payment_Model_Method_Abstract
{

    const CHECKOUT_TYPE = 'payment';

    /**
     * @var Mollie_Mpm_Helper_Data
     */
    public $mollieHelper;

    /**
     *
     */
    public function __construct()
    {
        parent::_construct();
        $this->mollieHelper = Mage::helper('mpm');
    }

    /**
     * @param Mage_Sales_Model_Order      $order
     *
     * @return null|string
     * @throws Mage_Core_Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function startTransaction(Mage_Sales_Model_Order $order)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $apiKey = $this->mollieHelper->getApiKey($storeId);
        $mollieApi = $this->mollieHelper->getMollieAPI($apiKey);
        $additionalData = $order->getPayment()->getAdditionalInformation();

        $transactionId = $order->getMollieTransactionId();
        if (!empty($transactionId) && !preg_match('/^ord_\w+$/', $transactionId)) {
            $payment = $mollieApi->payments->get($transactionId);
            return $payment->getCheckoutUrl();
        }

        $paymentToken = $this->mollieHelper->getPaymentToken();
        $method = $this->mollieHelper->getMethodCode($order);
        $paymentData = array(
            'amount'         => $this->mollieHelper->getOrderAmountByOrder($order),
            'description'    => $this->mollieHelper->getPaymentDescription($method, $order, $storeId),
            'billingAddress' => $this->getAddressLine($order->getBillingAddress()),
            'redirectUrl'    => $this->mollieHelper->getReturnUrl($orderId, $paymentToken, $storeId),
            'webhookUrl'     => $this->mollieHelper->getWebhookUrl($storeId),
            'method'         => $method,
            'issuer'         => isset($additionalData['selected_issuer']) ? $additionalData['selected_issuer'] : null,
            'metadata'       => array(
                'order_id'      => $orderId,
                'store_id'      => $order->getStoreId(),
                'payment_token' => $paymentToken
            ),
            'locale'         => $this->mollieHelper->getLocaleCode($storeId, self::CHECKOUT_TYPE)
        );

        if ($method == 'banktransfer') {
            $paymentData['billingEmail'] = $order->getCustomerEmail();
            $paymentData['dueDate'] = $this->mollieHelper->getBanktransferDueDate($storeId);
        }

        if ($method == 'przelewy24') {
            $paymentData['billingEmail'] = $order->getCustomerEmail();
        }

        if (!$order->getIsVirtual() && $order->hasData('shipping_address_id')) {
            $paymentData['shippingAddress'] = $this->getAddressLine($order->getShippingAddress());
        }

        $paymentData = $this->mollieHelper->validatePaymentData($paymentData);
        $this->mollieHelper->addTolog('request', $paymentData);

        $payment = $mollieApi->payments->create($paymentData, array('include' => 'details.qrCode'));
        $this->processResponse($order, $payment);

        return $payment->getCheckoutUrl();
    }

    /**
     * @param Mage_Sales_Model_Order_Address $address
     *
     * @return array
     */
    public function getAddressLine(Mage_Sales_Model_Order_Address $address)
    {
        return array(
            'streetAndNumber' => rtrim(implode(' ', $address->getStreet()), ' '),
            'postalCode'      => $address->getPostcode(),
            'city'            => $address->getCity(),
            'region'          => $address->getRegion(),
            'country'         => $address->getCountryId(),
        );
    }

    /**
     * @param Mage_Sales_Model_Order        $order
     * @param \Mollie\Api\Resources\Payment $payment
     *
     * @throws Mage_Core_Exception
     */
    public function processResponse(Mage_Sales_Model_Order $order, Mollie\Api\Resources\Payment $payment)
    {
        $this->mollieHelper->addTolog('response', $payment);
        $order->getPayment()->setAdditionalInformation('checkout_url', $payment->getCheckoutUrl());
        $order->getPayment()->setAdditionalInformation('checkout_type', self::CHECKOUT_TYPE);
        $order->getPayment()->setAdditionalInformation('payment_status', $payment->status);

        if (isset($paymentData->expiresAt)) {
            $order->getPayment()->setAdditionalInformation('expires_at', $payment->expiresAt);
        }

        if (!empty($payment->details->qrCode->source)) {
            $order->getPayment()->setAdditionalInformation('qr_source', $payment->details->qrCode);
        }

        $status = $this->mollieHelper->getStatusPending($order->getStoreId());
        $order->addStatusToHistory($status, $this->mollieHelper->__('Customer redirected to Mollie'), false);
        $order->setMollieTransactionId($payment->id)->save();
    }

    /**
     * @param Mage_Sales_Model_Order      $order
     * @param string                      $type
     * @param null                        $paymentToken
     *
     * @return array
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function processTransaction(Mage_Sales_Model_Order $order, $type = 'webhook', $paymentToken = null)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $apiKey = $this->mollieHelper->getApiKey($storeId);
        $mollieApi = $this->mollieHelper->getMollieAPI($apiKey);
        $transactionId = $order->getMollieTransactionId();
        $paymentData = $mollieApi->payments->get($transactionId);
        $this->mollieHelper->addTolog($type, $paymentData);

        $status = $paymentData->status;
        $order->getPayment()->setAdditionalInformation('payment_status', $status)->save();
        $refunded = isset($paymentData->_links->refunds) ? true : false;

        if ($status == 'paid' && !$refunded) {
            $amount = $paymentData->amount->value;
            $currency = $paymentData->amount->currency;
            $orderAmount = $this->mollieHelper->getOrderAmountByOrder($order);
            if ($currency != $orderAmount['currency']) {
                $msg = array('success' => false, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type);
                $this->mollieHelper->addTolog('error', $this->mollieHelper->__('Currency does not match.'));
                return $msg;
            }

            $payment = $order->getPayment();
            if ($paymentData->details !== null) {
                $payment->setAdditionalInformation('details', json_encode($paymentData->details));
            }

            if (!$payment->getIsTransactionClosed() && $type == 'webhook') {
                if ($order->isCanceled()) {
                    $order = $this->mollieHelper->uncancelOrder($order);
                }

                if (abs($amount - $orderAmount['value']) < 0.01) {
                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode($order->getBaseCurrencyCode());
                    $payment->setIsTransactionClosed(true);
                    $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);

                    if ($paymentData->settlementAmount !== null) {
                        if ($paymentData->amount->currency != $paymentData->settlementAmount->currency) {
                            $message = $this->mollieHelper->__(
                                'Mollie: Captured %s, Settlement Amount %s',
                                $paymentData->amount->currency . ' ' . $paymentData->amount->value,
                                $paymentData->settlementAmount->currency . ' ' . $paymentData->settlementAmount->value
                            );
                            $order->addStatusHistoryComment($message);
                            $order->save();
                        }
                    }
                }

                if (!$order->getEmailSent()) {
                    $order->sendNewOrderEmail()->setEmailSent(true)->save();
                }

                if ($order->hasInvoices()) {
                    if (!$order->getIsVirtual() && $order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) {
                        $defaultStatusProcessing = $this->mollieHelper->getStatusProcessing($storeId);
                        if ($defaultStatusProcessing && ($defaultStatusProcessing != $order->getStatus())) {
                            $order->setStatus($defaultStatusProcessing)->save();
                        }
                    }

                    /** @var Mage_Sales_Model_Order_Invoice $invoice */
                    $invoice = $payment->getCreatedInvoice();
                    $sendInvoice = $this->mollieHelper->sendInvoice($storeId);
                    if ($invoice && $sendInvoice && !$invoice->getEmailSent()) {
                        $invoice->setEmailSent(true)->sendEmail()->save();
                    }
                }
            }

            $msg = array('success' => true, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addTolog('success', $msg);
            $this->checkCheckoutSession($order, $paymentToken, $paymentData, $type);
            return $msg;
        }

        if ($refunded) {
            $msg = array('success' => true, 'status' => 'refunded', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($status == 'open') {
            if ($paymentData->method == 'banktransfer' && !$order->getEmailSent()) {
                $order->sendNewOrderEmail()->setEmailSent(true)->save();
                $message = $this->mollieHelper->__('New order email sent');
                if (!$statusPending = $this->mollieHelper->getStatusPendingBanktransfer($storeId)) {
                    $statusPending = $order->getStatus();
                }

                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                $order->addStatusToHistory($statusPending, $message, true);
                $order->save();
            }

            $msg = array('success' => true, 'status' => 'open', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addTolog('success', $msg);
            $this->checkCheckoutSession($order, $paymentToken, $paymentData, $type);
            return $msg;
        }

        if ($status == 'pending') {
            $msg = array('success' => true, 'status' => 'pending', 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($status == 'canceled' || $status == 'failed' || $status == 'expired') {
            if ($type == 'webhook') {
                $this->mollieHelper->registerCancellation($order, $status);
            }

            $msg = array('success' => false, 'status' => $status, 'order_id' => $orderId, 'type' => $type);
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        return array();
    }

    /**
     * Check if there is an active checkout session and if not, create this based on the payment data.
     * Validates the PaymentToken of the return url with the meta data PaymentToken.
     * Issue #72: https://github.com/mollie/Magento/issues/72
     *
     * @param Mage_Sales_Model_Order $order
     * @param $paymentToken
     * @param $paymentData
     * @param $type
     */
    public function checkCheckoutSession($order, $paymentToken, $paymentData, $type)
    {
        if ($type == 'webhook') {
            return;
        }

        /** @var Mage_Checkout_Model_Session $session */
        $session = Mage::getSingleton('checkout/session');
        if ($type != 'webhook' && ($session->getLastOrderId() != $order->getId())) {
            if ($paymentToken && isset($paymentData->metadata->payment_token)) {
                if ($paymentToken == $paymentData->metadata->payment_token) {
                    $session->setLastQuoteId($order->getQuoteId())
                        ->setLastSuccessQuoteId($order->getQuoteId())
                        ->setLastOrderId($order->getId())
                        ->setLastRealOrderId($order->getIncrementId());
                }
            }
        }
    }

}
