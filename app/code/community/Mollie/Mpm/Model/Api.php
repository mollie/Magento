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
 *
 **/

class Mollie_Mpm_Model_Api extends Mage_Payment_Model_Method_Abstract
{
	/**
	 * Database connection for reading.
	 *
	 * @var Varien_Db_Adapter_Pdo_Mysql
	 */
	protected $_mysqlr;

	/**
	 * Database connection for writing.
	 *
	 * @var Varien_Db_Adapter_Pdo_Mysql
	 */
	protected $_mysqlw;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $_table;

	/**
	 * Payment method index.
	 *
	 * @var int
	 */
	protected $_index;

	/**
	 * @var Mollie_Mpm_Helper_Api
	 */
	protected $_api;

	protected $_code                    = "mpm_api";
	protected $_infoBlockType           = 'mpm/payment_api_info';
	protected $_formBlockType           = 'mpm/payment_api_form';
	protected $_paymentMethod           = 'Mollie';
	protected $_isGateway               = TRUE;
	protected $_canAuthorize            = TRUE;
	protected $_canUseCheckout          = TRUE;
	protected $_canUseInternal          = TRUE;
	protected $_canUseForMultishipping  = FALSE; // wouldn't work without event capturing anyway
	protected $_canRefund               = TRUE;
	protected $_canRefundInvoicePartial = TRUE;
	protected $_canCapture              = FALSE;

	// Payment statusses
	const STATUS_OPEN      = "open";
	const STATUS_PENDING   = "pending";
	const STATUS_CANCELLED = "cancelled";
	const STATUS_EXPIRED   = "expired";
	const STATUS_PAID      = "paid";

	// Payment flags
	const PAYMENT_FLAG_PROCESSED  = "De betaling is ontvangen en verwerkt";
	const PAYMENT_FLAG_RETRY      = "De consument probeert het bedrag nogmaals af te rekenen";
	const PAYMENT_FLAG_CANCELD    = "De consument heeft de betaling geannuleerd";
	const PAYMENT_FLAG_PENDING    = "Afwachten tot de betaling binnen is";
	const PAYMENT_FLAG_EXPIRED    = "De betaling is verlopen doordat de consument niets met de betaling heeft gedaan";
	const PAYMENT_FLAG_INPROGRESS = "De klant is doorverwezen naar de geselecteerde bank";
	const PAYMENT_FLAG_FAILED     = "De betaling is niet gelukt (er is geen verdere informatie beschikbaar)";
	const PAYMENT_FLAG_FRAUD      = "Het totale bedrag komt niet overeen met de afgerekende bedrag. (Mogelijke fraude)";
	const PAYMENT_FLAG_DCHECKED   = "De betaalstatus is al een keer opgevraagd";
	const PAYMENT_FLAG_UNKOWN     = "Er is een onbekende fout opgetreden";

	/**
	 * Build constructor (must be a normal constructor, not a Magento _construct() method.
	 */
	public function __construct ()
	{
		parent::__construct();

		$this->_api    = Mage::helper('mpm/api');
		$resource      = Mage::getSingleton('core/resource');
		$this->_table  = $resource->getTableName('mollie_payments');
		$this->_mysqlr = $resource->getConnection('core_read');
		$this->_mysqlw = $resource->getConnection('core_write');
	}

	/**
	 * @param string          $field
	 * @param null|int|string $storeId
	 *
	 * @return mixed
	 */
	public function getConfigData ($field, $storeId = null)
	{
		if ($this->isValidIndex())
		{
			if ($field === "min_order_total")
			{
				return $this->_api->methods[$this->_index]['amount']->minimum;
			}

			if ($field === "max_order_total")
			{
				return $this->_api->methods[$this->_index]['amount']->maximum;
			}

			if ($field === "sort_order")
			{
				$sortOrder = Mage::helper('mpm/data')->getConfig('mpm_void_' . str_pad($this->_index, 2, "0", STR_PAD_LEFT), $field, $storeId);

				return $sortOrder ?: $this->_api->methods[$this->_index]['sort_order'];
			}

			if ($field === "title")
			{
				return Mage::helper('core')->__($this->_api->methods[$this->_index]['description']);
			}
		}

		if ($field === "active")
		{
			return $this->_isAvailable();
		}

		if ($field === "title")
		{
			return Mage::helper('core')->__('{Reserved}');
		}

		return parent::getConfigData($field, $storeId);
	}


	/**
	 * Override parent getTitle in order to translate the config.xml title (thank you magento)
	 */
	public function getTitle ()
	{
		// If there was an error, inform the user
		if (is_string($this->_api->methods))
		{
			return Mage::helper('core')->__($this->_api->methods);
		}

		// If this is a void field to be filled, fill it
		if ($this->isValidIndex())
		{
			$title = Mage::helper('mpm/data')->getConfig('mpm_void_' . str_pad($this->_index, 2, "0", STR_PAD_LEFT), 'title');

			return $title ?: Mage::helper('core')->__($this->_api->methods[$this->_index]['description']);
		}

		// Otherwise, translate the title from config.xml
		return Mage::helper('core')->__(parent::getTitle());
	}

	/**
	 * Get checkout session namespace
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckout ()
	{
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote ()
	{
		return $this->_getCheckout()->getQuote();
	}

	/**
	 * Check whether payment method can be used
	 *
	 * @param Mage_Sales_Model_Quote
	 * @return bool
	 */
	public function isAvailable ($quote = NULL)
	{
		if (!$this->_isAvailable())
		{
			return FALSE;
		}

		return parent::isAvailable($quote);
	}

	/**
	 * Really check whether payment method can be used
	 *
	 * @return bool
	 */
	public function _isAvailable ()
	{
		$enabled = (bool) Mage::helper('mpm')->getConfig('mollie', 'active');

		if (!$enabled)
		{
			return FALSE;
		}

		if (!$this->isValidIndex())
		{
			return FALSE;
		}

		if (!$this->_api->methods[$this->_index]['available'])
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Can this method be used for multishipping
	 *
	 * @return bool
	 */
	public function canUseForMultishipping ()
	{
		return FALSE;
	}

	/**
	 * @param string $currencyCode
	 *
	 * @return bool
	 */
	public function canUseForCurrency($currencyCode)
	{
		if (!parent::canUseForCurrency($currencyCode))
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * On click payment button, this function is called to assign data
	 *
	 * @param mixed $data
	 * @return self
	 */
	public function assignData ($data)
	{
		if (!($data instanceof Varien_Object))
		{
			$data = new Varien_Object($data);
		}

		if(strlen(Mage::registry('method_id')) == 0)
		{
			$method = $this->_api->getMethodByCode($data->_data['method']);

			Mage::register('method_id', $method['method_id']);
			Mage::register('issuer', Mage::app()->getRequest()->getParam($this->_code . '_issuer'));
		}

		return $this;
	}

	/**
	 * Redirects the client on click 'Place Order' to the payment screen
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl ()
	{
		return Mage::getUrl(
			'mpm/api/payment',
			array(
				'_secure' => TRUE,
				'_query' => array(
					'method_id' => Mage::registry('method_id'),
					'issuer' => Mage::registry('issuer'),
				)
			)
		);
	}

	/**
	 * Get the current date in SQL format.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	protected function getCurrentDate ()
	{
		return date("Y-m-d H:i:s");
	}

	/**
	 * Stores the payment information in the mollie_payments table.
	 *
	 * @param null $order_id The order's Id
	 * @param null $transaction_id TransactionID, provided by Mollie (32 char md5 hash)
	 * @param string $method
	 */
	public function setPayment ($order_id = NULL, $transaction_id = NULL, $method = 'api')
	{
		if (is_null($order_id) || is_null($transaction_id))
		{
			Mage::throwException('Ongeldig order_id of transaction_id...');
		}

		$data = array(
			'order_id'       => $order_id,
			'transaction_id' => $transaction_id,
			'bank_status'    => self::STATUS_OPEN,
			'method'         => $method,
			'created_at'     => $this->getCurrentDate(),
		);

		$this->_mysqlw->insert($this->_table, $data);
	}

	/**
	 * @param null $transaction_id
	 * @param null $bank_status
	 * @param array|null $customer
	 *
	 * @throws Mage_Core_Exception
	 * @throws Zend_Db_Adapter_Exception
	 */
	public function updatePayment ($transaction_id = NULL, $bank_status = NULL, array $customer = NULL)
	{
		if (is_null($transaction_id) || is_null($bank_status))
		{
			Mage::throwException('Geen transaction_id en/of bank_status gevonden...');
		}

		$data = array(
			'bank_status'  => $bank_status,
			'updated_at'   => $this->getCurrentDate(),
		);

		if ($customer && isset($customer['consumerAccount']))
		{
			$data['bank_account'] = $customer['consumerAccount'];
		}

		$where = sprintf("transaction_id = %s", $this->_mysqlw->quote($transaction_id));

		$this->_mysqlw->update($this->_table, $data, $where);
	}

	/**
	 * @return bool
	 */
	public function isValidIndex ()
	{
		if (!is_array($this->_api->methods))
		{
			return FALSE;
		}

		return isset($this->_index) && $this->_index >= 0 && $this->_index < sizeof($this->_api->methods);
	}

	/**
	 * @param Varien_Object $payment
	 * @param float $amount
	 *
	 * @return $this
	 * @throws Mage_Core_Exception
	 * @throws Mollie_API_Exception
	 */
	public function refund (Varien_Object $payment, $amount)
	{
		// fetch order and transaction info
		$order = $payment->getOrder();
		$row   = $this->_mysqlr->fetchRow(
			'SELECT * FROM `' . $this->_table . '` WHERE `order_id` = ' . intval($order->entity_id),
			array(),
			Zend_Db::FETCH_ASSOC
		);

		$transaction_id = $row['transaction_id'];

		// fetch payment info
		$mollie = $this->_api->_getMollieAPI();
		$mollie_payment = $mollie->payments->get($transaction_id);

		// attempt a refund
		try
		{
			$mollie->payments->refund($mollie_payment, $amount);
		}
		catch (Exception $e)
		{
			Mage::throwException('Impossible to create a refund for this transaction. Details: ' . $e->getMessage() . '<br />');
		}

		return $this;
	}
}
