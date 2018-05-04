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

class Mollie_Mpm_Helper_Api extends Mollie_Mpm_Helper_Data
{

    /**
     * Cached Methods.
     *
     * @var null
     */
    protected $_cachedMethods = null;
    /**
     * ApiKey.
     *
     * @var null
     */
    protected $_apiKey = null;
    /**
     * Mollie API.
     *
     * @var null
     */
    protected $_mollieApi = null;
    /**
     * Api Installed flag.
     *
     * @var bool
     */
    protected $_apiInstalled = null;
    /**
     * iDeal Skip Issuer list flag.
     *
     * @var bool
     */
    protected $_skipIdealIssuers = false;
    /**
     * Giftcard Skip Issuer list flag.
     *
     * @var bool
     */
    protected $_skipGiftcardIssuers = false;

    /**
     * @param $property
     *
     * @return array|NULL|string
     */
    public function __get($property)
    {
        if ($property === 'methods') {
            if (empty($this->_cachedMethods)) {
                $this->_cachedMethods = $this->getPaymentMethods();
            }

            return $this->_cachedMethods;
        }

        return null;
    }

    /**
     * Get array of available Payment Methods.
     *
     * @return array|string
     */
    public function getPaymentMethods()
    {
        /** @var Mollie_Mpm_Model_Methods $methodModel */
        $methodModel = Mage::getModel('mpm/methods');

        try {
            $api = $this->getMollieAPI();
            if (!$api) {
                return '';
            }

            $params = array();
            if (!Mage::app()->getStore()->isAdmin()) {
                /** @var Mage_Checkout_Model_Session $checkoutSession */
                $checkoutSession = Mage::getModel('checkout/session');
                if ($checkoutSession->getQuote()->getId()) {
                    $quote = $checkoutSession->getQuote();
                    $amount = $this->getOrderAmountByQuote($quote);
                    $params = array("amount[value]" => $amount['value'], "amount[currency]" => $amount['currency']);
                }
            }

            $apiMethods = $api->methods->all($params);
            $allMethods = $methodModel->getStoredMethods();
            $showImage = $this->showImages();

            foreach ($allMethods as $index => $storedMethod) {
                $allMethods[$index]['available'] = false;
                foreach ($apiMethods as $apiMethod) {
                    if ($storedMethod['method_id'] === $apiMethod->id) {
                        $allMethods[$index]['available'] = true;
                        break;
                    }
                }
            }

            $sortOrder = -32;

            foreach ($apiMethods as $apiMethod) {
                $apiMethod->available = false;
                foreach ($allMethods as $i => $s) {
                    if ($apiMethod->id === $s['method_id']) {
                        $apiMethod->available = true;
                        $apiMethod->method_id = $apiMethod->id;
                        $apiMethod->sort_order = $sortOrder;
                        if ($apiMethod->id == 'ideal') {
                            $apiMethod->issuers_title = $this->__('Select Bank');
                            $apiMethod->issuers = $this->getIdealIssuers();
                        }

                        if ($apiMethod->id == 'giftcard') {
                            $apiMethod->issuers_title = $this->__('Select Giftcard');
                            $apiMethod->issuers = $this->getGiftcardIssuers();
                        }

                        if (!$showImage) {
                            $apiMethod->image = '';
                        }

                        $allMethods[$i] = (array)$apiMethod;
                    }
                }

                if (!$apiMethod->available) {
                    $apiMethod->available = true;
                    $apiMethod->method_id = $apiMethod->id;
                    $apiMethod->sort_order = $sortOrder;
                    $allMethods[] = (array)$apiMethod;
                }

                $sortOrder++;
            }

            $methodModel->setStoredMethods($allMethods);
            return $allMethods;
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            $this->addLog('getPaymentMethods [ERR]', 'Faild, msg: ' . $e->getMessage() . ' (' . $e->getCode() . ')');
            if (strpos($e->getMessage(), "Unable to communicate with Mollie") === 0) {
                return Mage::helper('core')->__('The payment service is currently unavailable.');
            } else {
                return Mage::helper('core')->__('The module is configured incorrectly.');
            }
        } catch (Exception $e) {
            $this->addLog('getPaymentMethods [ERR]', $e);
            return Mage::helper('core')->__('There was an error:') . '<br />' . $e->getMessage();
        }
    }

    /**
     * @param null $apiKey
     *
     * @return bool|\Mollie\Api\MollieApiClient
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function getMollieAPI($apiKey = null)
    {
        if ($this->_apiInstalled === null) {
            if (!$this->checkApiInstalled()) {
                $this->_apiInstalled = false;
            } else {
                $this->_apiInstalled = true;
            }
        }

        if ($this->_apiInstalled != true) {
            return false;
        }

        if (!empty($apiKey)) {
            $this->_apiKey = $apiKey;
        }

        if (!empty($this->_mollieApi) && empty($apiKey)) {
            return $this->_mollieApi;
        }

        if (empty($this->_apiKey)) {
            $this->_apiKey = $this->getApiKey();
        }

        $mollieApiClient = new Mollie\Api\MollieApiClient();
        $mollieApiClient->setApiKey($this->_apiKey);
        $mollieApiClient->addVersionString('Magento/' . $this->getMagentoVersion());
        $mollieApiClient->addVersionString('MollieMagento/' . $this->getModuleVersion());
        $this->_mollieApi = $mollieApiClient;

        return $this->_mollieApi;
    }

    /**
     * Check if API is installed.
     *
     * @return bool
     */
    public function checkApiInstalled()
    {
        if (!class_exists('\Mollie\Api\MollieApiClient')) {
            return false;
        }

        return true;
    }

    /**
     * Get list of available iDeal Issuers.
     *
     * @return array
     */
    public function getIdealIssuers()
    {
        $issuers = array();
        if (!$this->showIdealIssuers()) {
            $this->_skipIdealIssuers = true;
            return $issuers;
        }

        try {
            $issuersList = $this->getMollieAPI()->methods->get('ideal', array("include" => "issuers"))->issuers;
            foreach ($issuersList as $issuer) {
                $issuers[] = $issuer;
            }
        } catch (Exception $e) {
            $this->_skipIdealIssuers = true;
            $this->addLog('getIdealIssuers [ERR]', $e->getMessage());
        }

        return $issuers;
    }

    /**
     * Get list of available Giftcard Issuers.
     *
     * @return array
     */
    public function getGiftcardIssuers()
    {
        $issuers = array();
        if (!$this->showGiftcardIssuers()) {
            $this->_skipGiftcardIssuers = true;
            return $issuers;
        }

        try {
            $issuersList = $this->getMollieAPI()->methods->get("giftcard", array("include" => "issuers"))->issuers;
            foreach ($issuersList as $issuer) {
                $issuers[] = $issuer;
            }
        } catch (Exception $e) {
            $this->_skipGiftcardIssuers = true;
            $this->addLog('getGiftcardIssuers [ERR]', $e->getMessage());
        }

        return $issuers;
    }

    /**
     * @param $code
     *
     * @return mixed
     */
    public function getMethodByCode($code)
    {
        if (strpos($code, 'mpm_void_') !== false) {
            $code = (int)str_replace('mpm_void_', '', $code);
        }

        return $this->methods[$code];
    }

    /**
     * @return \Mollie\Api\CompatibilityChecker
     */
    public function getMollieCompatibilityChecker()
    {
        $mollieSelfTest = new Mollie\Api\CompatibilityChecker();
        return $mollieSelfTest;
    }

    /**
     * @return string
     */
    public function getPhpApiErrorMessage()
    {
        return $this->__(
            'The Mollie API client is not installed, see check our <a href="%s" target="_blank">GitHub Wiki troubleshooting</a> page for a solution.',
            'https://github.com/mollie/Magento/wiki/The-Mollie-API-client-for-PHP-is-not-installed'
        );
    }
}
