<?php

/**
 * Copyright (c) 2012, Mollie B.V.
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
 * @version     v3.5.0
 * @copyright   Copyright (c) 2012 Mollie B.V. (http://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 *
 **/

class Mollie_Mpm_Model_Idl extends Mage_Payment_Model_Method_Abstract
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
	 * @var Mollie_Mpm_Helper_Idl
	 */
	protected $_ideal;

	protected $_code					= "mpm_idl";
	protected $_formBlockType			= 'mpm/payment_idl_form';
	protected $_infoBlockType			= 'mpm/payment_idl_info';
	protected $_paymentMethod			= 'iDEAL';
	protected $_isGateway				= TRUE;
	protected $_canAuthorize			= TRUE;
	protected $_canUseCheckout			= TRUE;
	protected $_canUseInternal			= FALSE;
	protected $_canUseForMultishipping	= TRUE;
	protected $_canRefund				= FALSE;

	// Payment statusses
	const IDL_SUCCESS                   = 'Success';
	const IDL_CANCELLED                 = 'Cancelled';
	const IDL_FAILURE                   = 'Failure';
	const IDL_EXPIRED                   = 'Expired';
	const IDL_CHECKEDBEFORE             = 'CheckedBefore';

	// Payment flags
	const PAYMENT_FLAG_PROCESSED		= "De betaling is ontvangen en verwerkt";
	const PAYMENT_FLAG_RETRY			= "De consument probeert het bedrag nogmaals af te rekenen";
	const PAYMENT_FLAG_CANCELD			= "De consument heeft de betaling geannuleerd";
	const PAYMENT_FLAG_PENDING			= "Afwachten tot de betaling binnen is";
	const PAYMENT_FLAG_EXPIRED			= "De betaling is verlopen doordat de consument niets met de betaling heeft gedaan";
	const PAYMENT_FLAG_INPROGRESS		= "De klant is doorverwezen naar de geselecteerde bank";
	const PAYMENT_FLAG_FAILED			= "De betaling is niet gelukt (er is geen verdere informatie beschikbaar)";
	const PAYMENT_FLAG_FRAUD			= "Het totale bedrag komt niet overeen met de afgerekende bedrag. (Mogelijke fraude)";
	const PAYMENT_FLAG_DCHECKED			= "De betaalstatus is al een keer opgevraagd";
	const PAYMENT_FLAG_UNKOWN			= "Er is een onbekende fout opgetreden";

	/**
	 * Build constructor (must be a normal constructor, not a Magento _construct() method.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_ideal  = Mage::Helper('mpm/idl');
		$this->_table  = Mage::getSingleton('core/resource')->getTableName('mollie_payments');
		$this->_mysqlr = Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->_mysqlw = Mage::getSingleton('core/resource')->getConnection('core_write');
	}
	/**
	 * Get checkout session namespace
	 *
	 * @return Mage_Checkout_Model_Session
	 */
	protected function _getCheckout() {
		return Mage::getSingleton('checkout/session');
	}
	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote() {
		return $this->_getCheckout()->getQuote();
	}

	/**
	 * Check whether payment method can be used
	 *
	 * @param Mage_Sales_Model_Quote
	 * @return bool
	 */
    public function isAvailable($quote = NULL)
	{
		$enabled = (bool) Mage::Helper('mpm/data')->getConfig('idl', 'active');

		if (!$enabled)
		{
			return FALSE;
		}

		return parent::isAvailable($quote);
	}

	/**
	 * Can this method be used for multishipping
	 *
	 * @return bool
	 */
	public function canUseForMultishipping() {
		return TRUE;
	}
	/**
	 * iDEAL kan alleen in NL gebruikt worden dus word ook alleen maar geactiveerd als de billing country NL is, NIET de shipping country
	 * 
	 * @return bool
	 */
	public function canUseForCountry($country)
	{
		if (!parent::canUseForCountry($country))
		{
			return FALSE;
		}

		if ($country !== 'NL') {
			return FALSE;
		}

		return TRUE;
	}
	/**
	 * iDEAL is only active if 'EURO' is currency
	 *
	 * @param string $currencyCode
	 * @return bool
	 */
	public function canUseForCurrency($currencyCode)
	{
		if (!parent::canUseForCurrency($currencyCode))
		{
			return FALSE;
		}

		if ($currencyCode !== 'EUR') {
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
	public function assignData($data)
	{
		if ( !($data instanceof Varien_Object) ) {
			$data = new Varien_Object($data);
		}

		if(strlen(Mage::registry('bank_id')) == 0)
		{
			foreach ($this->_ideal->getBanks() as $id => $name)
			{
				if ($data->getBankid() == $id)
				{
					Mage::register('bank_id', $id);
					Mage::register('bank_name', $name);
					return $this;
				}
			}
		}

		return $this;
	}

	/**
	 * Redirects the client on click 'Place Order' to selected iDEAL bank
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl(
			'mpm/idl/payment',
			array(
				'_secure' => TRUE,
				'_query' => array(
					'bank_id' => Mage::registry('bank_id')
				)
			)
		);
	}

	/**
	 * Stores the payment information in the mollie_payments table.
	 *
	 * @param null $order_id The order's Id
	 * @param null $transaction_id TransactionID, provided by Mollie (32 char md5 hash)
	 * @param string $method
	 */
	public function setPayment ($order_id = NULL, $transaction_id = NULL, $method = 'idl')
	{
		if (is_null($order_id) || is_null($transaction_id)) {
			Mage::throwException('Ongeldige order_id of transaction_id...');
		}

		$data  = array(
			'order_id'       => $order_id,
			'transaction_id' => $transaction_id,
			'method'         => $method,
		);

		$this->_mysqlw->insert($this->_table, $data);
	}

	public function updatePayment ($transaction_id = NULL, $bank_status = NULL, array $customer = NULL)
	{
		if (is_null($transaction_id) || is_null($bank_status)) {
			Mage::throwException('Geen transaction_id en/of bank_status gevonden...');
		}

		$data = array(
			'bank_status'  => $bank_status,
		);

		if ($customer)
		{
			$data['bank_account'] = $customer['consumerAccount'];
		}

		$where = sprintf("transaction_id = %s", $this->_mysqlw->quote($transaction_id));

		$this->_mysqlw->update($this->_table, $data, $where);
	}
}
