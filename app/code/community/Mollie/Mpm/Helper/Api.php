<?php

/**
 * Copyright (c) 2012-2014, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
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
 * @copyright   Copyright (c) 2012-2014 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 */

class Mollie_Mpm_Helper_Api
{
	protected $api_key                = NULL;
	protected $amount                 = 0;
	protected $description            = NULL;
	protected $redirect_url           = NULL;
	protected $payment_url            = NULL;
	protected $transaction_id         = NULL;
	protected $paid_status            = FALSE;
	protected $status                 = '';
	protected $consumer_info          = array();
	protected $error_message          = '';
	protected $_cached_methods        = NULL;
	protected $bank_transfer_due_date = NULL;
	protected $issuers                = array();
	protected $skip_issuers           = FALSE;

	/**
	 * Mollie_Mpm_Helper_Api constructor.
	 */
	public function __construct ()
	{
		$this->setApiKey(Mage::helper('mpm')->getApiKey());
		$this->setBankTransferDueDateDays(Mage::Helper('mpm')->getBankTransferDueDateDays());
	}

	/**
	 * Get the current version of the module
	 *
	 * @return string
	 */
	public function getExtensionVersion ()
	{
		return (string) Mage::getConfig()->getModuleConfig('Mollie_Mpm')->version;
	}

	/**
	 * @param $property
	 *
	 * @return array|NULL|string
	 */
	public function __get ($property)
	{
		if ($property === 'methods')
		{
			// Fetch Payment Methods
			if (empty($this->_cached_methods))
			{
				$this->_cached_methods = $this->getPaymentMethods();
			}

			return $this->_cached_methods;
		}

		return NULL;
	}

	/**
	 * @param int                    $amount
	 * @param string                 $description
	 * @param Mage_Sales_Model_Order $order
	 * @param string                 $redirect_url
	 * @param string                 $method
	 * @param string                 $issuer
	 *
	 * @return bool
	 */
	public function createPayment ($amount, $description, $order, $redirect_url, $method, $issuer)
	{
		if (!$this->setAmount($amount))
		{
			$this->error_message = "Het opgegeven bedrag \"$amount\" is ongeldig";

			return FALSE;
		}

		if (!$this->setRedirectURL($redirect_url))
		{
			$this->error_message = "De opgegeven redirect URL \"$redirect_url\" is onjuist";

			return FALSE;
		}

		$this->setDescription($description);

		try
		{
			$api = $this->_getMollieAPI();
		}
		catch (Mollie_API_Exception $e)
		{
			$this->error_message = $e->getMessage();

			return FALSE;
		}

		$store = Mage::app()->getStore();

		$params = array(
			"amount"       => $this->getAmount(),
			"description"  => $this->getDescription(),
			"redirectUrl"  => $this->getRedirectURL(),
			"method"       => $method,
			"issuer"       => (empty($issuer) ? NULL : $issuer),
			"metadata"     => array(
				"order_id" => $order->getId(),
				"store_id" => $store->getId(),
			),
			"locale"       => $this->getLocaleCode(),
			"webhookUrl"   => $this->getWebhookURL(),
		);

		if($method == "banktransfer" && $this->getBankTransferDueDateDays())
		{
			$params += array(
				"dueDate"      => $this->getBankTransferDueDateDays(),
				"billingEmail" => $order->getCustomerEmail(),
			);
		}

		if ($billing = $order->getBillingAddress())
		{
			$params += array(
				"billingCity"    => $billing->getCity(),
				"billingRegion"  => $billing->getRegion(),
				"billingPostal"  => $billing->getPostcode(),
				"billingCountry" => $billing->getCountryId(),
			);
		}

		if ($shipping = $order->getShippingAddress())
		{
			$params += array(
				"shippingAddress" => $shipping->getStreetFull(),
				"shippingCity"    => $shipping->getCity(),
				"shippingRegion"  => $shipping->getRegion(),
				"shippingPostal"  => $shipping->getPostcode(),
				"shippingCountry" => $shipping->getCountry(),
			);
		}

		try
		{
			$payment = $api->payments->create($params);
		}
		catch (Mollie_API_Exception $e)
		{
			try
			{
				if ($e->getField() == "webhookUrl")
				{
					unset($params["webhookUrl"]);

					$payment = $api->payments->create($params);
				}
				else
				{
					throw $e;
				}
			}
			catch (Mollie_API_Exception $e)
			{
				$this->error_message = __METHOD__ . ' said: Unable to set up payment. Reason: ' . $e->getMessage();
				Mage::log($this->error_message);

				return FALSE;
			}
		}

		$this->setTransactionId($payment->id);
		$this->payment_url = (string) $payment->getPaymentUrl();

		return TRUE;
	}

	/**
	 * @param $transaction_id
	 *
	 * @return bool
	 * @throws Mollie_API_Exception
	 */
	public function checkPayment ($transaction_id)
	{
		if (!$this->setTransactionId($transaction_id))
		{
			$this->error_message = "Er is een onjuist transactie ID opgegeven";

			return FALSE;
		}

		try
		{
			$api = $this->_getMollieAPI();
		}
		catch (Mollie_API_Exception $e)
		{
			$this->error_message = $e->getMessage();

			return FALSE;
		}

		$payment = $api->payments->get($transaction_id);

		$this->paid_status = (bool) $payment->isPaid();
		$this->status =  (string) $payment->status;
		$this->amount = (float) $payment->amount;
		$this->consumer_info = (array) (isset($payment->details) ? $payment->details : array());

		return TRUE;
	}

	/**
	 * @param NULL|string $key
	 *
	 * @return Mollie_API_Client
	 * @throws Mollie_API_Exception
	 */
	public function _getMollieAPI($key = NULL)
	{
		$this->_setAutoLoader();

		$key = ($key === NULL) ? $this->getApiKey() : $key;
		$api = new Mollie_API_Client;

		$api->setApiKey($key);
		$api->addVersionString('Magento/' . Mage::getVersion());
		$api->addVersionString('MollieMagento/' . $this->getExtensionVersion());

		return $api;
	}

	/**
	 * Inserts the Mollie autoloader as first choice
	 *
	 * @return void
	 */
	public function _setAutoLoader ()
	{
		if (!file_exists(Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Autoloader.php"))
		{
			return;
		}

		$autoloader_callbacks = spl_autoload_functions();
		$original_autoload    = NULL;

		foreach($autoloader_callbacks as $callback)
		{
			if(is_array($callback) && $callback[0] instanceof Varien_Autoload)
			{
				$original_autoload = $callback;
			}
		}

		if (!is_null($original_autoload))
		{
			spl_autoload_unregister($original_autoload);
			require_once Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Autoloader.php";
			spl_autoload_register($original_autoload);
		}
		else
		{
			require_once Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Autoloader.php";
		}
	}

	/**
	 * @param $api_key
	 *
	 * @return bool
	 */
	public function setApiKey ($api_key)
	{
		if (is_null($api_key))
		{
			return FALSE;
		}

		return ($this->api_key = $api_key);
	}

	/**
	 * @return NULL
	 */
	public function getApiKey ()
	{
		return $this->api_key;
	}

	/**
	 * @param $days
	 *
	 * @return bool|string
	 */
	public function setBankTransferDueDateDays ($days)
	{
		if (is_null($days) || !is_numeric($days))
		{
			return FALSE;
		}

		return ($this->bank_transfer_due_date = date("Y-m-d", strtotime("+" . $days . " day")));
	}

	/**
	 * @return int
	 */
	public function getBankTransferDueDateDays ()
	{
		return $this->bank_transfer_due_date;
	}

	/**
	 * @param $amount
	 *
	 * @return bool|float|int
	 */
	public function setAmount ($amount)
	{
		if (!is_double($amount) && !is_int($amount))
		{
			return FALSE;
		}

		if ($amount <= 0)
		{
			return FALSE;
		}

		return ($this->amount = $amount);
	}

	/**
	 * @return int
	 */
	public function getAmount ()
	{
		return $this->amount;
	}

	/**
	 * @param $description
	 *
	 * @return string
	 */
	public function setDescription ($description)
	{
		return ($this->description = $description);
	}

	/**
	 * @return string
	 */
	public function getDescription ()
	{
		return $this->description;
	}

	/**
	 * @param $redirect_url
	 *
	 * @return bool|string
	 */
	public function setRedirectURL ($redirect_url)
	{
		if (empty($redirect_url))
		{
			return FALSE;
		}

		return ($this->redirect_url = $redirect_url);
	}

	/**
	 * @return string
	 */
	public function getRedirectURL ()
	{
		return $this->redirect_url;
	}

	/**
	 * @param $transaction_id
	 *
	 * @return bool|string
	 */
	public function setTransactionId ($transaction_id)
	{
		if (empty($transaction_id))
		{
			return FALSE;
		}

		return ($this->transaction_id = $transaction_id);
	}

	/**
	 * @return string
	 */
	public function getTransactionId ()
	{
		return $this->transaction_id;
	}

	/**
	 * @return string
	 */
	public function getPaymentURL ()
	{
		return $this->payment_url;
	}

	/**
	 * @return bool|string
	 */
	public function getPaidStatus ()
	{
		return $this->paid_status;
	}

	/**
	 * @return string
	 */
	public function getBankStatus ()
	{
		return $this->status;
	}

	/**
	 * @return array
	 */
	public function getConsumerInfo ()
	{
		return $this->consumer_info;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage ()
	{
		return $this->error_message;
	}

	/**
	 * @return string
	 */
	public function getWebhookURL ()
	{
		$store_code = Mage::app()->getStore()->getCode();
		$store_url  = Mage::app()->getStore($store_code)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

		$webhook_url = str_replace('/admin/', $store_url, Mage::getUrl('mpm/api/webhook'));

		return $webhook_url . '?___store=' . $store_code;
	}

	/**
	 * @return array|string
	 */
	public function getPaymentMethods ()
	{
		try
		{
			$api         = $this->_getMollieAPI();
			$api_methods = $api->methods->all();
			$all_methods = Mage::helper('mpm')->getStoredMethods();

			foreach ($all_methods as $index => $stored_method)
			{
				$all_methods[$index]['available'] = FALSE;

				foreach ($api_methods as $api_method)
				{
					if ($stored_method['method_id'] === $api_method->id)
					{
						$all_methods[$index]['available'] = TRUE;
						break;
					}
				}
			}

			$sort_order = -32;

			foreach ($api_methods as $api_method)
			{
				$api_method->available = FALSE;

				foreach ($all_methods as $i => $s)
				{
					if ($api_method->id === $s['method_id'])
					{
						// recognised method, put in correct order
						$api_method->available = TRUE;
						$api_method->method_id = $api_method->id;
						$api_method->sort_order = $sort_order;
						$all_methods[$i] = (array) $api_method;
						break;
					}
				}

				if (!$api_method->available)
				{
					// newly added method, add to end of array
					$api_method->available = TRUE;
					$api_method->method_id = $api_method->id;
					$api_method->sort_order = $sort_order;
					$all_methods[] = (array) $api_method;
				}

				$sort_order++;
			}

			Mage::helper('mpm')->setStoredMethods($all_methods);

			return $all_methods;
		}
		catch (Mollie_API_Exception $e)
		{
			Mage::log(__METHOD__ . '() failed to fetch available payment methods. API said: ' . $e->getMessage() . ' (' . $e->getCode() . ') ' );

			if (strpos($e->getMessage(), "Unable to communicate with Mollie") === 0)
			{
				return Mage::helper('core')->__('The payment service is currently unavailable.');
			}
			else
			{
				return Mage::helper('core')->__('The module is configured incorrectly.');
			}
		}
		catch (Exception $e)
		{
			Mage::log($e);

			return Mage::helper('core')->__('There was an error:') . '<br />' . $e->getMessage();
		}
	}

	/**
	 * @param $code
	 *
	 * @return mixed
	 */
	public function getMethodByCode ($code)
	{
		$method_id = (int) str_replace('mpm_void_', '', $code);

		return $this->methods[$method_id];
	}

	/**
	 * @return string
	 */
	public function getLocaleCode ()
	{
		/**
		 * @var string The current store locale in Magento.
		 */
		$storeLocale = substr(Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId()), 0,2);

		/**
		 * @var array Supported locales in the Mollie API.
		 */
		$supportedLocales = array(
			"de",
			"en",
			"es",
			"fr",
			"be",
			"nl",
		);

		/**
		 * Checks if the current $storeLocale is inside the $supportedLocales array.
		 * If not, fallback to English to avoid exceptions from our API that will
		 * abort the payment with a exception. We do not want that to happen, right?
		 */
		if (in_array($storeLocale, $supportedLocales))
		{
			return $storeLocale;
		}
		else
		{
			return 'en';
		}
	}

    /**
     * @return array
     */
	public function getIssuers ()
    {
        if (count($this->issuers) == 0 && $this->skip_issuers == FALSE)
        {
            try
            {
                foreach ($this->_getMollieAPI()->issuers->all() as $issuer)
                {
                    if (!array_key_exists($issuer->method, $this->issuers))
                    {
                        $this->issuers[$issuer->method] = array();
                    }
                    $this->issuers[$issuer->method][] = $issuer;
                }
            }
            catch (Exception $e)
            {
                /*
                 * Since we've got an exception, stop trying to get issuers for now. This will prevent more exceptions
                 * and in the case of a timeout, an even longer wait.
                 */
                $this->skip_issuers = TRUE;

                Mage::logException($e);
                Mage::log('Unable to retrieve payment methods for Mollie, please refer to exception log for details.');
            }
        }
        return $this->issuers;
    }
}
