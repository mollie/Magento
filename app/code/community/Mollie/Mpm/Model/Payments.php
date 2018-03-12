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

class Mollie_Mpm_Model_Payments extends Mage_Core_Model_Abstract
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
        $this->_init('mpm/payments');
    }

    /**
     * @param $request
     * @param $payment
     */
    public function setPayment($request, $payment)
    {
        $data = array(
            'order_id'       => $request['metadata']['order_id'],
            'transaction_id' => $payment->id,
            'bank_status'    => 'open',
            'method'         => $request['method'],
            'issuer'         => $request['issuer'],
            'created_at'     => $this->mollieHelper->getCurrentMysqlDate()
        );

        $this->mollieHelper->addLog('setPayment', $data);
        $this->setData($data)->save();
    }

    /**
     * @param      $orderId
     * @param      $status
     * @param      $paymentData
     */
    public function updatePayment($orderId, $status, $paymentData)
    {
        if (!$orderId) {
            return;
        }

        $data = array(
            'order_id'    => $orderId,
            'bank_status' => $status,
            'updated_at'  => $this->mollieHelper->getCurrentMysqlDate()
        );

        if (!empty($paymentData->details->consumerAccount)) {
            $data['bank_account'] = $paymentData->details->consumerAccount;
        }

        if (!empty($paymentData->details->consumerName)) {
            $data['consumer_name'] = $paymentData->details->consumerName;
        }

        if (!empty($paymentData->details->cardHolder)) {
            $data['consumer_name'] = $paymentData->details->cardHolder;
        }

        if (!empty($paymentData->details->consumerBic)) {
            $data['consumer_bic'] = $paymentData->details->consumerBic;
        }

        if (!empty($paymentData->details->cardLabel)) {
            $data['issuer'] = $paymentData->details->cardLabel;
        }

        if (!empty($paymentData->issuer)) {
            $data['issuer'] = $paymentData->issuer;
        }

        $this->mollieHelper->addLog('updatePayment', $data);
        $this->setData($data)->save();
    }

    /**
     * Get OrderId from TransactionID from Payment Table.
     *
     * @param $transactionId
     *
     * @return mixed
     */
    public function getOrderIdByTransactionId($transactionId)
    {
        return $this->load($transactionId, 'transaction_id')->getOrderId();
    }

    /**
     * Get TransactionID from OrderId from Payment Table.
     *
     * @param $orderId
     *
     * @return mixed
     */
    public function getTransactionIdByOrderId($orderId)
    {
        return $this->load($orderId, 'order_id')->getTransactionId();
    }

    /**
     * Load Payment data by TransactionId.
     *
     * @param $transactionId
     *
     * @return mixed
     */
    public function loadByTransactionId($transactionId)
    {
        $payment = $this->load($transactionId, 'transaction_id');
        return $payment;
    }
}
