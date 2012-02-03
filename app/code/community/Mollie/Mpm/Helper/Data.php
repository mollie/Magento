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
 * @version     v2.0.0
 * @copyright   Copyright (c) 2012 Mollie B.V. (http://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 * 
 **/

class Mollie_Mpm_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * Get payment bank status by transaction_id
	 *
	 * @return array
	 */
	public function getStatusById($transaction_id)
	{
		$status = Mage::getSingleton('core/resource')
					->getConnection('core_read')
					->fetchAll(
						sprintf(
							"SELECT `bank_status` FROM `%s` WHERE `transaction_id` = '%s'",
							Mage::getSingleton('core/resource')->getTableName('mollie_payments'),
							$transaction_id
						)
					);

		return $status[0];
	}

	/**
	 * Get order_id by transaction_id
	 * 
	 * @return array
	 */
	public function getOrderById($transaction_id)
	{
		$id = Mage::getSingleton('core/resource')
					->getConnection('core_read')
					->fetchAll(
						sprintf(
							"SELECT `order_id` FROM `%s` WHERE `transaction_id` = '%s'",
							Mage::getSingleton('core/resource')->getTableName('mollie_payments'),
							$transaction_id
						)
					);

		return $id[0];
	}

	/**
	 * Gets partner ID from `config_core_data`
	 *
	 * @return string
	 */
	public function getPartnerid()
	{
		return Mage::getStoreConfig("mollie/settings/partnerid");
	}

	/**
	 * Gets profile key from `config_core_data`
	 *
	 * @return string
	 */
	public function getProfilekey()
	{
		return Mage::getStoreConfig("mollie/settings/profilekey");
	}

	/**
	 * Get store config
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getConfig($pm = NULL, $key = NULL)
	{
		$arr = array('active', 'testmode', 'description', 'minvalue');
		$paymentmethods = array('idl');

		if(in_array($key, $arr) && in_array($pm, $paymentmethods))
			return Mage::getStoreConfig("mollie/{$pm}/{$key}");

		return NULL;
	}

}
