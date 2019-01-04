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

class Mollie_Mpm_Helper_Data extends Mage_Core_Helper_Abstract
{

    const MIN_API_VERSION = '2.1.0';
    const CURRENCIES_WITHOUT_DECIMAL = array('JPY');
    const XPATH_MODULE_ACTIVE = 'payment/mollie/active';
    const XPATH_API_MODUS = 'payment/mollie/type';
    const XPATH_LIVE_APIKEY = 'payment/mollie/apikey_live';
    const XPATH_TEST_APIKEY = 'payment/mollie/apikey_test';
    const XPATH_DEBUG = 'payment/mollie/debug';
    const XPATH_LOADING_SCREEN = 'payment/mollie/loading_screen';
    const XPATH_STATUS_PENDING = 'payment/mollie/order_status_pending';
    const XPATH_STATUS_PENDING_BANKTRANSFER = 'payment/mollie_banktransfer/order_status_pending';
    const XPATH_STATUS_PROCESSING = 'payment/mollie/order_status_processing';
    const XPATH_BANKTRANSFER_DUE_DAYS = 'payment/mollie_banktransfer/due_days';
    const XPATH_INVOICE_NOTIFY = 'payment/mollie/invoice_notify';
    const XPATH_LOCALE = 'payment/mollie/locale';
    const XPATH_IMAGES = 'payment/mollie/payment_images';
    const XPATH_USE_BASE_CURRENCY = 'payment/mollie/currency';
    const XPATH_PAYMENTLINK_ADD_MESSAGE = 'payment/mollie_paymentlink/add_message';
    const XPATH_ISSUER_LIST_TYPE = 'payment/%method%/issuer_list_type';
    const XPATH_PAYMENTLINK_MESSAGE = 'payment/mollie_paymentlink/message';
    const XPATH_API_METHOD = 'payment/%method%/method';

    /**
     * @var null
     */
    public $debug = null;
    /**
     * @var
     */
    public $apiKey = null;
    /**
     * @var
     */
    public $apiModus = null;
    /**
     * @var null
     */
    public $mollieMethods = null;
    /**
     * @var \Mollie\Api\MollieApiClient
     */
    public $mollieApi = null;

    /**
     * @deprecated
     *
     * @param null $storeId
     * @param null $websiteId
     *
     * @return bool
     */
    public function isModuleEnabled($storeId = null, $websiteId = null)
    {
        return $this->isAvailable($storeId);
    }

    /**
     * Availabiliy check, on Active, API Client & API Key
     *
     * @param $storeId
     *
     * @return bool
     */
    public function isAvailable($storeId = null)
    {
        $active = $this->getStoreConfig(self::XPATH_MODULE_ACTIVE, $storeId);
        if (!$active) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId);
        if (empty($apiKey)) {
            return false;
        }

        return true;
    }

    /**
     * EDITED
     * Get Store config value based on StoreId, WebsiteId or current.
     *
     * @param     $path
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = null)
    {
        if ($storeId > 0) {
            $value = Mage::getStoreConfig($path, $storeId);
        } else {
            $value = Mage::getStoreConfig($path);
        }

        return trim($value);
    }

    /**
     * ApiKey value based on StoreId or current.
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getApiKey($storeId = null)
    {
        if ($this->apiKey !== null) {
            return $this->apiKey;
        }

        $modus = $this->getModus($storeId);
        if ($modus == 'test') {
            $apiKey = trim($this->getStoreConfig(self::XPATH_TEST_APIKEY, $storeId));
            if (empty($apiKey)) {
                $this->addTolog('error', 'Mollie API key not set (test modus)');
            }

            if (!preg_match('/^test_\w+$/', $apiKey)) {
                $this->addTolog('error', 'Mollie set to test modus, but API key does not start with "test_"');
            }

            $this->apiKey = $apiKey;
        } else {
            $apiKey = trim($this->getStoreConfig(self::XPATH_LIVE_APIKEY, $storeId));
            if (empty($apiKey)) {
                $this->addTolog('error', 'Mollie API key not set (live modus)');
            }

            if (!preg_match('/^live_\w+$/', $apiKey)) {
                $this->addTolog('error', 'Mollie set to live modus, but API key does not start with "live_"');
            }

            $this->apiKey = $apiKey;
        }

        return $this->apiKey;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getModus($storeId = null)
    {
        if ($this->apiModus === null) {
            $this->apiModus = $this->getStoreConfig(self::XPATH_API_MODUS, $storeId);
        }

        return $this->apiModus;
    }

    /**
     * EDITED
     *
     * @param $type
     * @param $data
     */
    public function addToLog($type, $data)
    {
        if ($this->debug === null) {
            $this->debug = $this->getStoreConfig(self::XPATH_DEBUG);
        }

        if ($this->debug) {
            if (is_array($data)) {
                $log = $type . ': ' . json_encode($data, true);
            } elseif (is_object($data)) {
                $log = $type . ': ' . json_encode($data, true);
            } else {
                $log = $type . ': ' . $data;
            }

            Mage::log($log, null, 'mollie.log');
        }
    }

    /**
     * @return bool
     */
    public function useLoadingScreen()
    {
        return (bool)$this->getStoreConfig(self::XPATH_LOADING_SCREEN);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function useImage($storeId = null)
    {
        return (bool)$this->getStoreConfig(self::XPATH_IMAGES, $storeId);
    }

    /**
     * @param $method
     *
     * @return mixed
     */
    public function getIssuerListType($method)
    {
        $methodXpath = str_replace('%method%', $method, self::XPATH_ISSUER_LIST_TYPE);
        return $this->getStoreConfig($methodXpath);
    }

    /**
     * Method code for API
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    public function getMethodCode(Mage_Sales_Model_Order $order)
    {
        $methodCode = null;

        try {
            $method = $order->getPayment()->getMethodInstance()->getCode();
            if ($method != 'mollie_paymentlink') {
                $methodCode = str_replace('mollie_', '', $method);
            }
        } catch (\Exception $e) {
            $this->addToLog('error', $e->getMessage());
        }

        return $methodCode;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public function getApiMethod(Mage_Sales_Model_Order $order)
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $methodXpath = str_replace('%method%', $method, self::XPATH_API_METHOD);
        return $this->getStoreConfig($methodXpath, $order->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getPaymentToken()
    {
        return Mage::helper('core')->uniqHash();
    }

    /**
     * Build url for Redirect.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return Mage::getUrl('mpm/api/payment');
    }

    /**
     * @param null $storeId
     *
     * @return string
     */
    public function getWebhookUrl($storeId = null)
    {
        if ($storeId !== null) {
            return Mage::getUrl('mpm/api/webhook', array('_store' => $storeId));
        }

        return Mage::getUrl('mpm/api/webhook');
    }

    /**
     * @param      $orderId
     * @param      $paymentToken
     * @param null $storeId
     *
     * @return string
     */
    public function getReturnUrl($orderId, $paymentToken, $storeId = null)
    {
        if ($storeId !== null) {
            return Mage::getUrl(
                'mpm/api/return',
                array(
                    '_query' => 'order_id=' . $orderId . '&payment_token=' . $paymentToken . '&utm_nooverride=1',
                    '_store' => $storeId
                )
            );
        }

        return Mage::getUrl(
            'mpm/api/return',
            array('_query' => 'order_id=' . $orderId . '&payment_token=' . $paymentToken . '&utm_nooverride=1')
        );
    }

    /**
     * @return string
     */
    public function getCartUrl()
    {
        return Mage::getUrl('checkout/cart');
    }

    /**
     * Selected pending (payment) status for banktransfer
     *
     * @param int $storeId
     *
     * @return mixed
     */
    public function getStatusPendingBanktransfer($storeId = 0)
    {
        return $this->getStoreConfig(self::XPATH_STATUS_PENDING_BANKTRANSFER, $storeId);
    }

    /**
     * Selected processing status
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStatusProcessing($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_STATUS_PROCESSING, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function sendInvoice($storeId = null)
    {
        return (bool)$this->getStoreConfig(self::XPATH_INVOICE_NOTIFY, $storeId);
    }

    /**
     * @param     $checkoutUrl
     *
     * @return mixed
     */
    public function getPaymentLinkMessage($checkoutUrl)
    {
        if ($this->getStoreConfig(self::XPATH_PAYMENTLINK_ADD_MESSAGE)) {
            $message = $this->getStoreConfig(self::XPATH_PAYMENTLINK_MESSAGE);
            return str_replace('%link%', $checkoutUrl, $message);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderAmountByOrder(Mage_Sales_Model_Order $order)
    {
        if ($this->useBaseCurrency($order->getStoreId())) {
            return $this->getAmountArray($order->getBaseCurrencyCode(), $order->getBaseGrandTotal());
        }

        return $this->getAmountArray($order->getOrderCurrencyCode(), $order->getGrandTotal());
    }

    /**
     * @param $storeId
     *
     * @return bool
     */
    public function useBaseCurrency($storeId)
    {
        return (bool)$this->getStoreConfig(self::XPATH_USE_BASE_CURRENCY, $storeId);
    }

    /**
     * @param $currency
     * @param $value
     *
     * @return array
     */
    public function getAmountArray($currency, $value)
    {
        return array(
            "currency" => $currency,
            "value"    => $this->formatCurrencyValue($value, $currency)
        );
    }

    /**
     * @param $value
     * @param $currency
     *
     * @return string
     */
    public function formatCurrencyValue($value, $currency)
    {
        $decimalPrecision = 2;
        if (in_array($currency, self::CURRENCIES_WITHOUT_DECIMAL)) {
            $decimalPrecision = 0;
        }

        return number_format($value, $decimalPrecision, '.', '');
    }

    /**
     * Determine Locale
     *
     * @param int    $storeId
     * @param string $method
     *
     * @return mixed|null|string
     */
    public function getLocaleCode($storeId = null, $method = 'payment')
    {
        $locale = $this->getStoreConfig(self::XPATH_LOCALE, $storeId);

        if ($locale == 'store' || (!$locale && $method == 'order')) {
            $localeCode = Mage::app()->getLocale()->getLocaleCode();
            $supportedLocale = $this->getSupportedLocale();
            if (in_array($localeCode, $supportedLocale)){
                $locale = $localeCode;
            }
        }

        if ($locale) {
            return $locale;
        }

        /**
         * Orders Api has a strict requirement for Locale Code,
         * so if no local is set or can be resolved en_US will be returned.
         */
        return ($method == 'order') ? 'en_US' : null;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getBankTransferDueDate($storeId = null)
    {
        $offset = $this->getStoreConfig(self::XPATH_BANKTRANSFER_DUE_DAYS, $storeId);
        if ($offset > 0) {
            return date("Y-m-d", strtotime("+" . $offset . " day"));
        } else {
            return date("Y-m-d", strtotime("+14 days"));
        }
    }

    /**
     * @return string
     */
    public function getPhpApiErrorMessage()
    {
        return $this->__(
            'The Mollie API (v2) client is not installed, see check our <a href="%s" target="_blank">GitHub Wiki troubleshooting</a> page for a solution.',
            'https://github.com/mollie/Magento/wiki/The-Mollie-API-client-for-PHP-is-not-installed'
        );
    }

    /***
     * @param array $paymentData
     *
     * @return mixed
     */
    public function validatePaymentData($paymentData)
    {
        if (isset($paymentData['billingAddress'])) {
            foreach ($paymentData['billingAddress'] as $k => $v) {
                if ((empty($v)) && ($k != 'region')) {
                    unset($paymentData['billingAddress']);
                }
            }
        }

        if (isset($paymentData['shippingAddress'])) {
            foreach ($paymentData['shippingAddress'] as $k => $v) {
                if ((empty($v)) && ($k != 'region')) {
                    unset($paymentData['shippingAddress']);
                }
            }
        }

        return array_filter($paymentData);
    }

    /***
     * @param array $orderData
     *
     * @return mixed
     */
    public function validateOrderData($orderData)
    {
        if (isset($orderData['billingAddress'])) {
            foreach ($orderData['billingAddress'] as $k => $v) {
                if (empty($v)) {
                    unset($orderData['billingAddress'][$k]);
                }
            }
        }

        if (isset($orderData['shippingAddress'])) {
            foreach ($orderData['shippingAddress'] as $k => $v) {
                if (empty($v)) {
                    unset($orderData['shippingAddress'][$k]);
                }
            }
        }

        return array_filter($orderData);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    public function isPaidUsingMollieOrdersApi(Mage_Sales_Model_Order $order)
    {
        try {
            $methodInstance = $order->getPayment()->getMethodInstance();
        } catch (\Exception $e) {
            $this->addToLog('error', $e->getMessage());
            return false;
        }

        if (!$methodInstance instanceof Mollie_Mpm_Model_Mollie) {
            return false;
        }

        $checkoutType = $this->getCheckoutType($order);
        if ($checkoutType != 'order') {
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    public function getCheckoutType(Mage_Sales_Model_Order $order)
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();
        if (isset($additionalData['checkout_type'])) {
            return $additionalData['checkout_type'];
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return Mage_Sales_Model_Order
     */
    public function uncancelOrder(Mage_Sales_Model_Order $order)
    {
        try {
            $status = $this->getStatusPending($order->getStoreId());
            $message = $this->__('Order uncanceled by webhook.');
            $state = Mage_Sales_Model_Order::STATE_NEW;
            $order->setState($state, $status, $message, false)->save();
            foreach ($order->getAllItems() as $item) {
                $item->setQtyCanceled(0)->save();
            }
        } catch (\Exception $e) {
            $this->addTolog('error', $e->getMessage());
        }

        return $order;
    }

    /**
     * Selected pending status
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStatusPending($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_STATUS_PENDING, $storeId);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param null                   $status
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function registerCancellation(Mage_Sales_Model_Order $order, $status = null)
    {
        if ($order->getId() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
            $comment = $this->__('The order was canceled');
            if ($status !== null) {
                $comment = $this->__('The order was canceled, reason: payment %s', $status);
            }

            $this->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $order->registerCancellation($comment)->save();
            return true;
        }

        return false;
    }

    /**
     * Restore Cart Session.
     */
    public function restoreCart()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (!empty($orderId)) {
            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $quoteId = $order->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quoteId)->setIsActive(true)->save();
            Mage::getSingleton('checkout/session')->replaceQuote($quote);
        }
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrderFromSession()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if (!empty($orderId)) {
            /** @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load($orderId);
            return $order;
        }
    }

    /**
     * @param $error
     */
    public function setError($error)
    {
        $msg = $this->__($error);
        Mage::getSingleton('core/session')->addError($msg);
    }

    /**
     * Check if API is installed.
     * If not, write to var/mollie.log and return false.
     * Also write to var/system.log due to error suppression (developer mode issue with autoload).
     *
     * @return bool
     */
    public function checkApiInstalled()
    {
        if (!@class_exists('\Mollie\Api\MollieApiClient')) {
            $msg = 'Could not load Mollie\Api\MollieApiClient';
            $this->addToLog('error', $msg);
            Mage::log($msg, null, 'system.log');
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param                        $method
     *
     * @return bool
     */
    public function isMethodAvailableForQuote($quote, $method)
    {
        $methodCode = str_replace('mollie_', '', $method);
        if ($methodCode == 'paymentlink') {
            return true;
        }

        $storeId = $quote ? $quote->getStoreId() : null;
        $availableMethods = $this->getAvailableMethods($storeId, $quote, 'orders', 'issuers');
        $availableMethodsArray = json_decode(json_encode($availableMethods), true);
        $available = array_search($methodCode, array_column($availableMethodsArray, 'id'));
        if ($available === false) {
            return false;
        }

        return true;
    }

    /**
     * @param        $storeId
     * @param null   $quote
     * @param string $resource
     * @param null   $include
     *
     * @return bool|\Mollie\Api\Resources\MethodCollection|null
     */
    public function getAvailableMethods($storeId, $quote = null, $resource = 'orders', $include = null)
    {
        if ($this->mollieMethods !== null) {
            return $this->mollieMethods;
        }

        if (!$apiKey = $this->getApiKey($storeId)) {
            return false;
        }

        try {
            $mollieApi = $this->getMollieAPI($apiKey);
            $amount = $quote !== null ? $this->getOrderAmountByQuote($quote) : null;
            if ($amount !== null && $amount['value'] > 0) {
                $this->mollieMethods = $mollieApi->methods->all(
                    array(
                        "resource"         => $resource,
                        "include"          => $include,
                        "amount[value]"    => $amount['value'],
                        "amount[currency]" => $amount['currency']
                    )
                );
            } else {
                $this->mollieMethods = $mollieApi->methods->all(
                    array(
                        "resource" => $resource,
                        "include"  => $include
                    )
                );
            }

            return $this->mollieMethods;
        } catch (\Exception $e) {
            $this->addTolog('error', $e->getMessage());
            return false;
        }
    }

    /**
     * @param $apiKey
     *
     * @return \Mollie\Api\MollieApiClient
     * @throws Mage_Core_Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     * @throws \Mollie\Api\Exceptions\IncompatiblePlatform
     */
    public function getMollieAPI($apiKey)
    {
        if ($this->mollieApi !== null) {
            return $this->mollieApi;
        }

        if (class_exists('Mollie\Api\MollieApiClient')) {
            $mollieApiClient = new \Mollie\Api\MollieApiClient();
            $mollieApiClient->setApiKey($apiKey);
            $mollieApiClient->addVersionString('Magento/' . $this->getMagentoVersion());
            $mollieApiClient->addVersionString('MollieMagento/' . $this->getExtensionVersion());
            $this->mollieApi = $mollieApiClient;
            return $this->mollieApi;
        } else {
            $msg = $this->__('Could not load Mollie Api.');
            Mage::throwException($msg);
        }
    }

    /**
     * Magento version number.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Extension version number.
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        return Mage::getConfig()->getNode('modules')->children()->Mollie_Mpm->version;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    public function getOrderAmountByQuote(Mage_Sales_Model_Quote $quote)
    {
        if ($this->useBaseCurrency($quote->getStoreId())) {
            return $this->getAmountArray($quote->getBaseCurrencyCode(), $quote->getBaseGrandTotal());
        }

        return $this->getAmountArray($quote->getQuoteCurrencyCode(), $quote->getGrandTotal());
    }

    /**
     * @param $code
     *
     * @return mixed
     */
    public function getMethodByCode($code)
    {
        if (strpos($code, 'mollie_') !== false) {
            $code = str_replace('mollie_', '', $code);
        }

        $availableMethodsArray = json_decode(json_encode($this->mollieMethods), true);
        $key = array_search($code, array_column($availableMethodsArray, 'id'));
        if ($key !== false) {
            return $this->mollieMethods[$key];
        }
    }

    /**
     * @return array
     */
    public function getSupportedLocale()
    {
        return array(
            'en_US',
            'nl_NL',
            'nl_BE',
            'fr_FR',
            'fr_BE',
            'de_DE',
            'de_AT',
            'de_CH',
            'es_ES',
            'ca_ES',
            'pt_PT',
            'it_IT',
            'nb_NO',
            'sv_SE',
            'fi_FI',
            'da_DK',
            'is_IS',
            'hu_HU',
            'pl_PL',
            'lv_LV',
            'lt_LT'
        );
    }
}
