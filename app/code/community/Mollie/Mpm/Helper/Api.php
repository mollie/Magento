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
 * ----------------------------------------------------------------------------------------------------
 *
 * @category    Mollie
 * @package     Mollie_Mpm
 * @author      Mollie B.V. (info@mollie.nl)
 * @copyright   Copyright (c) 2012-2014 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 *
 * ----------------------------------------------------------------------------------------------------
 *
 **/
class Mollie_Mpm_Helper_Api
{
	const PLUGIN_VERSION = '4.2.1';

	protected $api_key = null;
	protected $amount = 0;
	protected $description = null;
	protected $redirect_url = null;
	protected $payment_url = null;
	protected $transaction_id = null;
	protected $paid_status = false;
	protected $status= '';
	protected $consumer_info = array();
	protected $error_message = '';
	protected $_cached_methods = NULL;

	public function __construct()
	{
		// Set Api Key
		$this->setApiKey(Mage::helper('mpm')->getApiKey());
	}

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
	 * Zet een betaling klaar bij de bank en maak de betalings URL beschikbaar
	 *
	 * @return boolean
	 */
	public function createPayment($amount, $description, $order, $redirect_url, $method, $issuer)
	{
		if (!$this->setAmount($amount))
		{
			$this->error_message = "Het opgegeven bedrag \"$amount\" is ongeldig";
			return false;
		}

		if (!$this->setRedirectURL($redirect_url))
		{
			$this->error_message = "De opgegeven redirect URL \"$redirect_url\" is onjuist";
			return false;
		}

		$this->setDescription($description);

		try {
			$api = $this->_getMollieAPI();
		} catch (Mollie_API_Exception $e) {
			$this->error_message = $e->getMessage();
			return false;
		}


		$params = array(
			"amount"				=> $this->getAmount(),
			"description"			=> $this->getDescription(),
			"redirectUrl"			=> $this->getRedirectURL(),
			"method"				=> $method,
			"issuer"				=> (empty($issuer) ? null : $issuer),
			"metadata"				=> array(
				"order_id"			=> $order->getId(),
			),
			"webhookUrl"				=> $this->getWebhookURL(),
		);


		if ($billing = $order->getBillingAddress())
		{
			$params += array(
				"billingCity"			=> $billing->getCity(),
				"billingRegion"			=> $billing->getRegion(),
				"billingPostal"			=> $billing->getPostcode(),
				"billingCountry"		=> $billing->getCountryId(),
			);
		}

		if ($shipping = $order->getShippingAddress())
		{
			$params += array(
				"shippingAddress"	=> $shipping->getStreetFull(),
				"shippingCity"		=> $shipping->getCity(),
				"shippingRegion"	=> $shipping->getRegion(),
				"shippingPostal"	=> $shipping->getPostcode(),
				"shippingCountry"	=> $shipping->getCountry(),
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
				return false;
			}
		}


		$this->setTransactionId($payment->id);
		$this->payment_url = (string) $payment->getPaymentUrl();

		return true;
	}

	// Kijk of er daadwerkelijk betaald is
	public function checkPayment($transaction_id)
	{
		if (!$this->setTransactionId($transaction_id))
		{
			$this->error_message = "Er is een onjuist transactie ID opgegeven";
			return false;
		}

		try {
			$api = $this->_getMollieAPI();
		} catch (Mollie_API_Exception $e) {
			$this->error_message = $e->getMessage();
			return false;
		}

		$payment = $api->payments->get($transaction_id);
		$this->paid_status = (bool) $payment->isPaid();
		$this->status =  (string) $payment->status;
		$this->amount = (float) $payment->amount;
		$this->consumer_info = (array) (isset($payment->details) ? $payment->details : array());

		return true;
	}

	/**
	 * @param null|string $key
	 * @return Mollie_API_Client
	 * @throws Mollie_API_Exception
	 */
	public function _getMollieAPI($key = null)
	{
		$this->_setAutoLoader();
		$key = ($key === null) ? $this->getApiKey() : $key;
		$api = new Mollie_API_Client;
		$api->setApiKey($key);
		$api->addVersionString('Magento/' . Mage::getVersion());
		$api->addVersionString('MollieMagento/' . self::PLUGIN_VERSION);
		return $api;
	}

	/**
	 * Inserts the Mollie autoloader as first choice
	 * @return void
	 */
	public function _setAutoLoader()
	{
		if (!file_exists(Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Autoloader.php"))
		{
			return;
		}
		$autoloader_callbacks = spl_autoload_functions();
		$original_autoload = null;
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

	/* Getters en setters */

	public function setApiKey($api_key)
	{
		if (is_null($api_key))
		{
			return false;
		}

		return ($this->api_key = $api_key);
	}

	public function getApiKey()
	{
		return $this->api_key;
	}

	public function setAmount($amount)
	{
		if (!is_double($amount) && !is_int($amount))
		{
			return false;
		}
		if ($amount <= 0)
		{
			return false;
		}
		return ($this->amount = $amount);
	}

	public function getAmount()
	{
		return $this->amount;
	}

	public function setDescription ($description)
	{
		return ($this->description = $description);
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setRedirectURL($redirect_url)
	{
		if (empty($redirect_url))
		{
			return false;
		}

		return ($this->redirect_url = $redirect_url);
	}

	public function getRedirectURL()
	{
		return $this->redirect_url;
	}

	public function setTransactionId($transaction_id)
	{
		if (empty($transaction_id))
		{
			return false;
		}

		return ($this->transaction_id = $transaction_id);
	}

	public function getTransactionId()
	{
		return $this->transaction_id;
	}

	public function getPaymentURL()
	{
		return $this->payment_url;
	}

	public function getPaidStatus()
	{
		return $this->paid_status;
	}

	public function getBankStatus()
	{
		return $this->status;
	}

	public function getConsumerInfo()
	{
		return $this->consumer_info;
	}

	public function getErrorMessage()
	{
		return $this->error_message;
	}

	/**
	 * @return string
	 */
	public function getWebhookURL()
	{
		$store_code = Mage::app()->getStore()->getCode();
		$store_url_part = empty($store_code) ? '/' : '/' . $store_code . '/';

		$webhook_url = str_replace('/admin/', $store_url_part, Mage::getUrl('mpm/api/webhook'));

		return $webhook_url . '?___store=' . $store_code;
	}

	public function getPaymentMethods()
	{
		try
		{
			$api = $this->_getMollieAPI();
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

	public function getMethodByCode($code)
	{
		$method_id = (int) str_replace('mpm_void_', '', $code);
		return $this->methods[$method_id];
	}
}
