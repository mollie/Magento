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

class Mollie_Mpm_Block_Payment_Info_Base extends Mage_Payment_Block_Info
{

    /**
     * @var Mollie_Mpm_Helper_Data
     */
    public $mollieHelper;

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('mollie/mpm/payment/info/base.phtml');
        $this->mollieHelper = Mage::helper('mpm');
    }

    /**
     * @return string
     */
    public function getCheckoutType()
    {
        try {
            $checkoutType = $this->getInfo()->getAdditionalInformation('checkout_type');
            return $checkoutType;
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getExpiresAt()
    {
        try {
            if ($expiresAt = $this->getInfo()->getAdditionalInformation('expires_at')) {
                return $expiresAt;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getPaymentLink()
    {
        if ($checkoutUrl = $this->getCheckoutUrl()) {
            return $this->mollieHelper->getPaymentLinkMessage($checkoutUrl);
        }
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        try {
            $checkoutUrl = $this->getInfo()->getAdditionalInformation('checkout_url');
            return $checkoutUrl;
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getPaymentStatus()
    {
        try {
            $paymentStatus = $this->getInfo()->getAdditionalInformation('payment_status');
            return $paymentStatus;
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }
    }

    /**
     * @return bool
     */
    public function isKlarnaMethod()
    {
        $klarnaMethodCodes = array(
            Mollie_Mpm_Model_Method_Klarnasliceit::METHOD_CODE,
            Mollie_Mpm_Model_Method_Klarnapaylater::METHOD_CODE
        );

        try {
            if (in_array($this->getInfo()->getMethod(), $klarnaMethodCodes)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return false;
    }

    /**
     * @return string
     */
    public function getPaymentImage()
    {
        $code = $this->getInfo()->getMethod();
        if (strpos($code, 'mollie_') !== false) {
            $code = str_replace('mollie_', '', $code);
        }

        return $this->getSkinUrl('mollie/mpm/images/' . $code . '.png');
    }

    public function getcardLabel()
    {
        try {
            $details = json_decode($this->getInfo()->getAdditionalInformation('details'));
            if (isset($details->cardLabel)) {
                return $details->cardLabel;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return '';
    }

    public function getIssuer()
    {
        try {
            $issuerCodeToName = [
                'ideal_ABNANL2A' => 'ABN AMRO',
                'ideal_INGBNL2A' => 'ING',
                'ideal_RABONL2U' => 'Rabobank',
                'ideal_ASNBNL21' => 'ASN Bank',
                'ideal_BUNQNL2A' => 'Bunq',
                'ideal_HANDNL2A' => 'Handelsbanken',
                'ideal_KNABNL2H' => 'Knab',
                'ideal_RBRBNL21' => 'Regiobank',
                'ideal_REVOLT21' => 'Revolut',
                'ideal_SNSBNL2A' => 'SNS Bank',
                'ideal_TRIONL2U' => 'Triodos',
                'ideal_FVLBNL22' => 'Van Lanschot',
            ];

            $issuer = $this->getInfo()->getAdditionalInformation('selected_issuer');
            if (array_key_exists($issuer, $issuerCodeToName)) {
                return $issuerCodeToName[$issuer];
            }

            return $issuer;
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getConsumerName()
    {
        try {
            $details = json_decode($this->getInfo()->getAdditionalInformation('details'), true);
            return $details['consumerName'];
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getIban()
    {
        try {
            $details = json_decode($this->getInfo()->getAdditionalInformation('details'), true);
            return $details['consumerAccount'];
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getBic()
    {
        try {
            $details = json_decode($this->getInfo()->getAdditionalInformation('details'), true);
            return $details['consumerBic'];
        } catch (\Exception $exception) {
            return null;
        }
    }
}
