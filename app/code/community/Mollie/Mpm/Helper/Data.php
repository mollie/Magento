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

    const XPATH_MODULE_ENABLED = 'payment/mollie/active';
    const XPATH_API_KEY = 'payment/mollie/apikey';
    const XPATH_DESCRIPTION = 'payment/mollie/description';
    const XPATH_SHOW_IMAGES = 'payment/mollie/show_images';
    const XPATH_SHOW_IDEAL_ISSUERS = 'payment/mollie/show_bank_list';
    const XPATH_SHOW_GIFTCARD_ISSUERS = 'payment/mollie/show_giftcard_list';
    const XPATH_BANKTRANSFER_DUE_DAYS = 'payment/mollie/banktransfer_due_date_days';
    const XPATH_LOCALE = 'payment/mollie/locale';
    const XPATH_LOADING_SCREEN = 'payment/mollie/loading_screen';
    const XPATH_IMPORT_PAYMENT_INFO = 'payment/mollie/import_payment_info';
    const XPATH_ORDER_STATUS_PENDING = 'payment/mollie/order_status_pending';
    const XPATH_ORDER_STATUS_PROCESSING = 'payment/mollie/order_status_processing';
    const XPATH_SKIP_INVOICE = 'payment/mollie/skip_invoice';
    const XPATH_SKIP_ORDER_EMAIL = 'payment/mollie/skip_order_mails';
    const XPATH_SKIP_INVOICE_EMAIL = 'payment/mollie/skip_invoice_mails';
    const XPATH_DEBUG = 'payment/mollie/debug';
    const XPATH_METHOD_TITLE = 'payment/{method}/title';
    const XPATH_METHOD_SORT_ORDER = 'payment/{method}/sort_order';
    const XPATH_METHOD_SPECIFICCOUNTRY = 'payment/{method}/specificcountry';
    const XPATH_METHOD_ALLOWSPECIFIC = 'payment/{method}/allowspecific';

    /**
     * @var null
     */
    public $debug = null;

    /**
     * Module Enabled Check.
     *
     * @param null $storeId
     * @param null $websiteId
     *
     * @return bool
     */
    public function isModuleEnabled($storeId = null, $websiteId = null)
    {
        $active = $this->getStoreConfig(self::XPATH_MODULE_ENABLED, $storeId, $websiteId);
        if (!$active) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId, $websiteId);
        if (empty($apiKey)) {
            return false;
        }

        return true;
    }

    /**
     * Get Store config value based on StoreId, WebsiteId or current.
     *
     * @param     $path
     * @param int $storeId
     * @param int $websiteId
     *
     * @return mixed
     */
    public function getStoreConfig($path, $storeId = null, $websiteId = null)
    {
        if ($websiteId > 0) {
            try {
                $value = Mage::app()->getWebsite($websiteId)->getConfig($path);
            } catch (\Exception $e) {
                $this->addLog('getStoreConfig [ERR]', $e->getMessage());
                $value = null;
            }
        } elseif ($storeId > 0) {
            $value = Mage::getStoreConfig($path, $storeId);
        } else {
            $value = Mage::getStoreConfig($path);
        }

        return trim($value);
    }

    /**
     * @param $function
     * @param $data
     */
    public function addLog($function, $data)
    {
        if ($this->debug === null) {
            $this->debug = $this->getStoreConfig(self::XPATH_DEBUG);
        }

        if ($this->debug) {
            if (is_array($data)) {
                $log = $function . ': ' . json_encode($data, true);
            } elseif (is_object($data)) {
                $log = $function . ': ' . json_encode($data, true);
            } else {
                $log = $function . ': ' . $data;
            }

            Mage::log($log, null, 'mollie.log');
        }
    }

    /**
     * ApiKey value based on StoreId, WebsiteId or current.
     *
     * @param null $storeId
     * @param null $websiteId
     *
     * @return mixed
     */
    public function getApiKey($storeId = null, $websiteId = null)
    {
        return $this->getStoreConfig(self::XPATH_API_KEY, $storeId, $websiteId);
    }

    /**
     * Module version number.
     *
     * @return mixed
     */
    public function getModuleVersion()
    {
        return Mage::getConfig()->getNode('modules')->children()->Mollie_Mpm->version;
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
     * @param      $method
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMethodSortOrder($method, $storeId = null)
    {
        if (strpos($method, 'mpm_void_') === false) {
            $method = 'mpm_void_' . str_pad($method, 2, "0", STR_PAD_LEFT);
        }

        return $this->getStoreConfig(str_replace('{method}', $method, self::XPATH_METHOD_SORT_ORDER), $storeId);
    }

    /**
     * @param      $method
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMethodTitle($method, $storeId = null)
    {
        if (strpos($method, 'mpm_void_') === false) {
            $method = 'mpm_void_' . str_pad($method, 2, "0", STR_PAD_LEFT);
        }

        return $this->getStoreConfig(str_replace('{method}', $method, self::XPATH_METHOD_TITLE), $storeId);
    }

    /**
     * @param      $method
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMethodSpecificCountry($method, $storeId = null)
    {
        if (strpos($method, 'mpm_void_') === false) {
            $method = 'mpm_void_' . str_pad($method, 2, "0", STR_PAD_LEFT);
        }

        $config = $this->getStoreConfig(str_replace('{method}', $method, self::XPATH_METHOD_SPECIFICCOUNTRY), $storeId);
        return explode(',', $config);
    }

    /**
     * @param      $method
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMethodAllowSpecific($method, $storeId = null)
    {
        if (strpos($method, 'mpm_void_') === false) {
            $method = 'mpm_void_' . str_pad($method, 2, "0", STR_PAD_LEFT);
        }

        return $this->getStoreConfig(str_replace('{method}', $method, self::XPATH_METHOD_ALLOWSPECIFIC), $storeId);
    }

    /**
     * @return mixed
     */
    public function getCountryOptionArray()
    {
        return Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
    }

    /**
     * Get selected storeId in admin config.
     *
     * @return int
     */
    public function getConfigStoreId()
    {
        $storeId = (int)Mage::app()->getRequest()->getParam('store', 0);
        return $storeId;
    }

    /**
     * Get selected websiteId in admin config.
     *
     * @return int
     */
    public function getConfigWebsiteId()
    {
        $websiteId = (int)Mage::app()->getRequest()->getParam('website', 0);
        return $websiteId;
    }

    /**
     * @return false|string
     */
    public function getCurrentMysqlDate()
    {
        return Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return mixed
     */
    public function getDescription($order)
    {
        $incrementId = $order->getIncrementId();
        $default = $this->getStoreConfig(self::XPATH_DESCRIPTION);
        return str_replace('%', $incrementId, $default);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function showImages($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_SHOW_IMAGES, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function showIdealIssuers($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_SHOW_IDEAL_ISSUERS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function showGiftcardIssuers($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_SHOW_GIFTCARD_ISSUERS, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStatusPending($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_ORDER_STATUS_PENDING, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStatusProcessing($storeId = null)
    {
        return $this->getStoreConfig(self::XPATH_ORDER_STATUS_PROCESSING, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function sendInvoiceEmail($storeId = null)
    {
        if ($this->getStoreConfig(self::XPATH_SKIP_INVOICE_EMAIL, $storeId)) {
            return false;
        }

        return true;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getBankTransferDueDateDays($storeId = null)
    {
        $offset = $this->getStoreConfig(self::XPATH_BANKTRANSFER_DUE_DAYS, $storeId);
        if ($offset > 0) {
            return date("Y-m-d", strtotime("+" . $offset . " day"));
        } else {
            return date("Y-m-d", strtotime("+14 days"));
        }
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function importPaymentInfo($storeId = null)
    {
        return (boolean)$this->getStoreConfig(self::XPATH_IMPORT_PAYMENT_INFO, $storeId);
    }

    /**
     * @return mixed
     */
    public function useLoadingScreen()
    {
        return $this->getStoreConfig(self::XPATH_LOADING_SCREEN);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function sendOrderEmail($storeId = null)
    {
        if ($this->getStoreConfig(self::XPATH_SKIP_ORDER_EMAIL, $storeId)) {
            return false;
        }

        return true;
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    public function invoiceOrder($storeId = null)
    {
        if ($this->getStoreConfig(self::XPATH_SKIP_INVOICE, $storeId)) {
            return false;
        }

        return true;
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
     * @return string
     */
    public function getWebhookUrl()
    {
        return Mage::getUrl('mpm/api/webhook');
    }

    /**
     * @param $orderId
     *
     * @return string
     */
    public function getReturnUrl($orderId)
    {
        return Mage::getUrl('mpm/api/return', array('_query' => 'order_id=' . $orderId . '&utm_nooverride=1'));
    }

    /**
     * @return string
     */
    public function getCartUrl()
    {
        return Mage::getUrl('checkout/cart');
    }

    /**
     * @return string
     */
    public function getLocaleCode()
    {
        $locale = $this->getStoreConfig(self::XPATH_LOCALE);

        if (!$locale) {
            return '';
        }

        if ($locale == 'store') {
            $localeCode = Mage::app()->getLocale()->getLocaleCode();
            if (in_array($localeCode, $this->getSupportedLocal())) {
                return $localeCode;
            } else {
                return '';
            }
        }

        return $locale;
    }

    /**
     * List of supported local codes Mollie.
     *
     * @return array
     */
    public function getSupportedLocal()
    {
        return array('en_US', 'de_AT', 'de_CH', 'de_DE', 'es_ES', 'fr_BE', 'fr_FR', 'nl_BE', 'nl_NL');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getOrderAmount($order)
    {
        $grandTotal = '';

        if ($order->getBaseCurrencyCode() === 'EUR') {
            $grandTotal = $order->getBaseGrandTotal();
        } elseif ($order->getOrderCurrencyCode() === 'EUR') {
            $grandTotal = $order->getGrandTotal();
        } else {
            $this->addLog('getOrderAmount [ERR]', 'Neither Base nor Order currency is in Euros.');
            Mage::throwException('Neither Base nor Order currency is in Euros.');
        }

        return $grandTotal;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return string
     * @throws Zend_Currency_Exception
     */
    public function formatGrandTotal($order)
    {
        return Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->toCurrency($order->getGrandTotal());
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
     * @param $issuer
     *
     * @return mixed
     */
    public function getBankByIssuer($issuer)
    {
        $banks = array(
            "ideal_ABNANL2A" => "ABN AMRO",
            "ideal_ASNBNL21" => "ASN Bank",
            "ideal_BUNQNL2A" => "Bunq",
            "ideal_INGBNL2A" => "ING",
            "ideal_KNABNL2H" => "Knab",
            "ideal_RABONL2U" => "Rabobank",
            "ideal_RBRBNL21" => "RegioBank",
            "ideal_SNSBNL2A" => "SNS Bank",
            "ideal_TRIONL2U" => "Triodos Bank",
            "ideal_FVLBNL22" => "van Lanschot",
            "ideal_TESTNL99" => "Test Bank"
        );

        if (isset($banks[$issuer])) {
            return $banks[$issuer];
        }
    }

}
