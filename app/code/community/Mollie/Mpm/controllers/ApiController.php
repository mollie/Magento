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

class Mollie_Mpm_ApiController extends Mage_Core_Controller_Front_Action
{

    /**
     * Visible error message for redirect error.
     */
    const REDIRECT_ERR_MSG = 'An error occured while processing your payment request, please try again later.';
    /**
     * Visible error message for return error.
     */
    const RETURN_ERR_MSG = 'An error occured while processing your payment, please try again with other method.';
    /**
     * Visible error message for cancelled transaction.
     */
    const RETURN_CANCEL_MSG = 'Payment cancelled, please try again.';

    /**
     * Mollie API Helper.
     *
     * @var Mollie_Mpm_Helper_API
     */
    public $mollieHelper;
    /**
     * Mollie Model.
     *
     * @var Mollie_Mpm_Model_Mollie
     */
    public $mollieModel;


    /**
     * Constructor.
     */
    public function _construct()
    {
        $this->mollieHelper = Mage::helper('mpm');
        $this->mollieModel = Mage::getModel('mpm/mollie');
        parent::_construct();
    }

    /**
     * Payment Action.
     */
    public function paymentAction()
    {
        try {
            $order = $this->mollieHelper->getOrderFromSession();
            /** @var $order Mage_Sales_Model_Order */

            if (!$order) {
                $this->mollieHelper->setError(self::REDIRECT_ERR_MSG);
                $this->mollieHelper->addToLog('error', 'Order not found in session.');
                $this->_redirect('checkout/cart');
                return;
            }

            $methodInstance = $order->getPayment()->getMethodInstance();
            $redirectUrl = $methodInstance->startTransaction($order);
            if (!empty($redirectUrl)) {
                $this->_redirectUrl($redirectUrl);
                return;
            } else {
                $this->mollieHelper->setError(self::REDIRECT_ERR_MSG);
                $this->mollieHelper->addToLog('error', 'Missing Redirect Url');
                $this->mollieHelper->restoreCart();
                $this->_cancelUnprocessedOrder($order, 'Missing Redirect Url');
                $this->_redirect('checkout/cart');
                return;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->setError(self::REDIRECT_ERR_MSG);
            $this->mollieHelper->addToLog('error', $e->getMessage());
            $this->mollieHelper->restoreCart();
            $this->_cancelUnprocessedOrder($order, $e->getMessage());
            $this->_redirect('checkout/cart');
            return;
        }
    }

    /**
     * Cancel an order that has been sent to Mollie but somehow did not get a transaction id
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $message  If provided, add this message to the order status history comment
     * @return void
     */
    protected function _cancelUnprocessedOrder(Mage_Sales_Model_Order $order, $message = null)
    {
        if (empty($order->getMollieTransactionId())) {
            try {
                $historyMessage = Mage::helper('mpm')->__('Canceled because an error occurred while redirecting the customer to Mollie');
                if ($message) {
                    $historyMessage .= ":<br>\n" . Mage::helper('core')->escapeHtml($message);
                }
                $order->cancel();
                $order->addStatusHistoryComment($historyMessage);
                $order->save();
                $this->mollieHelper->addToLog('info', sprintf('Canceled order %s', $order->getIncrementId()));
            } catch (Exception $e) {
                $this->mollieHelper->addToLog('error', sprintf('Cannot cancel order %s: %s', $order->getIncrementId(), $e->getMessage()));
                Mage::logException($e);
            }
        }
    }

    /**
     * Webhook Action.
     */
    public function webhookAction()
    {
        $params = $this->getRequest()->getParams();

        if (!empty($params['testByMollie'])) {
            return;
        }

        if (!empty($params['id'])) {
            try {
                $orderId = $this->mollieModel->getOrderIdByTransactionId($params['id']);
                if ($orderId) {
                    $this->mollieModel->processTransaction($orderId, 'webhook');
                }
            } catch (\Exception $e) {
                $this->mollieHelper->addToLog('error', $e->getMessage());
                Mage::logException($e);

                $this->getResponse()->setHttpResponseCode(503);
            }
        }
    }

    /**
     * Return Action.
     */
    public function returnAction()
    {
        $orderId = $this->getRequest()->getParam('order_id', null);
        $paymentToken = $this->getRequest()->getParam('payment_token', null);

        if ($orderId === null) {
            $this->mollieHelper->setError(self::RETURN_ERR_MSG);
            $this->mollieHelper->addToLog('error', 'Invalid return, missing order_id param.');
            $this->_redirect('checkout/cart');
        }

        try {
            $status = $this->mollieModel->processTransaction($orderId, 'success', $paymentToken);
        } catch (\Exception $e) {
            $this->mollieHelper->setError(self::RETURN_ERR_MSG);
            $this->mollieHelper->addToLog('error', $e->getMessage());
            $this->mollieHelper->restoreCart();
            $this->_redirect('checkout/cart');
            return;
        }

        if (!empty($status['success'])) {
            $this->_redirect('checkout/onepage/success?utm_nooverride=1');
            return;
        } else {
            if (isset($status['status']) && $status['status'] == 'canceled') {
                $this->mollieHelper->setError(self::RETURN_CANCEL_MSG);
            } else {
                $this->mollieHelper->setError(self::RETURN_ERR_MSG);
            }

            $this->mollieHelper->restoreCart();
            $this->_redirect('checkout/cart');
            return;
        }
    }

    /**
     * Redirect Action.
     */
    public function redirectAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
