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

class Mollie_Mpm_Model_Method_Deprecated_Abstract extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Availability options
     */
    protected $_canUseInternal = false;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    /**
     * Refund Pre-v5.x Orders.
     *
     * This function is restored to create online refunds for old orders.
     * See Mollie_Mpm_Model_Mollie for current implemenation.
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /** @var Mollie_Mpm_Model_Payments $payments */
        $payments = Mage::getModel('mpm/payments');

        /** @var Mollie_Mpm_Helper_Data $helper */
        $helper = Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        $transactionId = $payments->getTransactionIdByOrderId($order->getId());

        if (empty($transactionId)) {
            $msg = array('error' => true, 'msg' => $helper->__('Transaction ID not found'));
            $helper->addTolog('error', $msg);
            return $this;
        }

        $apiKey = $helper->getApiKey($order->getStoreId());
        if (empty($apiKey)) {
            $msg = array('error' => true, 'msg' => $helper->__('Api key not found'));
            $helper->addTolog('error', $msg);
            return $this;
        }

        try {
            $mollieApi = $helper->getMollieAPI($apiKey);
            $payment = $mollieApi->payments->get($transactionId);
            $payment->refund(
                array(
                    "amount" => array(
                        "currency" => $order->getOrderCurrencyCode(),
                        "value"    => $helper->formatCurrencyValue($amount, $order->getOrderCurrencyCode())
                    )
                )
            );
        } catch (\Exception $e) {
            $helper->addTolog('error', $e->getMessage());
            Mage::throwException($helper->__('Error: not possible to create an online refund: %s', $e->getMessage()));
        }

        return $this;
    }
}