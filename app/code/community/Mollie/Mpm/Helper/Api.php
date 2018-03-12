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

            $apiMethods = $api->methods->all();
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
        } catch (Mollie_API_Exception $e) {
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
     * @return bool|Mollie_API_Client|null
     * @throws Mollie_API_Exception
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

        $this->setAutoLoader();
        $mollieApi = new Mollie_API_Client;
        $mollieApi->setApiKey($this->_apiKey);
        $mollieApi->addVersionString('Magento/' . $this->getMagentoVersion());
        $mollieApi->addVersionString('MollieMagento/' . $this->getModuleVersion());
        $this->_mollieApi = $mollieApi;

        return $this->_mollieApi;
    }

    /**
     * Check if API is installed.
     *
     * @return bool
     */
    public function checkApiInstalled()
    {
        $libDir = Mage::getBaseDir('lib');
        if (!file_exists($libDir . '/Mollie/src/Mollie/API/Autoloader.php')) {
            $this->addLog('checkApiInstalled [ERR]', 'Mollie API not installed');
            return false;
        }

        return true;
    }

    /**
     * Set AutoLoader.
     */
    public function setAutoLoader()
    {
        $autoloaderCallbacks = spl_autoload_functions();
        $originalAutoload = null;

        foreach ($autoloaderCallbacks as $callback) {
            if (is_array($callback) && $callback[0] instanceof Varien_Autoload) {
                $originalAutoload = $callback;
            }
        }

        if (!is_null($originalAutoload)) {
            spl_autoload_unregister($originalAutoload);
            require_once Mage::getBaseDir('lib') . '/Mollie/src/Mollie/API/Autoloader.php';
            spl_autoload_register($originalAutoload);
        } else {
            require_once Mage::getBaseDir('lib') . '/Mollie/src/Mollie/API/Autoloader.php';
        }
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
            foreach ($this->getMollieAPI()->issuers->all() as $issuer) {
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
     * @return Mollie_API_CompatibilityChecker
     */
    public function getMollieCompatibilityChecker()
    {
        $this->setAutoLoader();
        $mollieSelfTest = new Mollie_API_CompatibilityChecker;
        return $mollieSelfTest;
    }

    /**
     * @return string
     */
    public function getPhpApiErrorMessage()
    {
        $url = '<a href="https://github.com/mollie/Magento/wiki/Troubleshooting" target="_blank">GitHub Wiki</a>';
        $error = 'The Mollie API client for PHP is not installed, for more information 
            about this issue see our ' . $url . ' troubleshooting page.';

        return $error;
    }
}
