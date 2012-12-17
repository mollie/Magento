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
 * @version     v3.12.0
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
		/** @var $connection Varien_Db_Adapter_Interface */
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$status = $connection->fetchAll(
						sprintf(
							"SELECT `bank_status` FROM `%s` WHERE `transaction_id` = %s",
							Mage::getSingleton('core/resource')->getTableName('mollie_payments'),
							$connection->quote($transaction_id)
						)
					);

		return $status[0];
	}

	/**
	 * Get order_id by transaction_id
	 * 
	 * @return int|null
	 */
	public function getOrderIdByTransactionId($transaction_id)
	{
		/** @var $connection Varien_Db_Adapter_Interface */
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$id = $connection->fetchAll(
						sprintf(
							"SELECT `order_id` FROM `%s` WHERE `transaction_id` = %s",
							Mage::getSingleton('core/resource')->getTableName('mollie_payments'),
							$connection->quote($transaction_id)
						)
					);

		if (sizeof($id) > 0)
		{
			return $id[0]['order_id'];
		}
		return NULL;
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
	 * Check if testmode is enabled.
	 */
	public function getTestModeEnabled()
	{
		return $this->getConfig('idl', 'testmode');
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

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getModuleStatus()
	{
		$needFiles = array();
		$modFiles  = array(
			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Adminhtml/System/Config/Status.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Payment/Idl/Fail.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Payment/Idl/Form.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Payment/Idl/Info.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/controllers/IdlController.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/etc/config.xml',
			Mage::getRoot() .'/code/community/Mollie/Mpm/etc/system.xml',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Helper/Data.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Helper/Idl.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Model/Idl.php',

			Mage::getRoot() .'/design/adminhtml/default/default/template/mollie/system/config/status.phtml',
			Mage::getRoot() .'/design/frontend/base/default/layout/mpm.xml',
			Mage::getRoot() .'/design/frontend/base/default/template/mollie/form/idl.phtml',
			Mage::getRoot() .'/design/frontend/base/default/template/mollie/page/exception.phtml',
			Mage::getRoot() .'/design/frontend/base/default/template/mollie/page/fail.phtml',
		);

		foreach ($modFiles as $file)
		{
			if(!file_exists($file)) {
				$needFiles[] = '<span style="color:red">'.$file.'</span>';
			}
		}

		if (count($needFiles) > 0) {
			return implode(" ", $needFiles);
		} else {
			return '<span style="color:green">Module werkt naar behoren!</span>';
		}
	}

	public function getModuleVersion()
	{
		return Mage::getConfig()->getNode('modules')->children()->Mollie_Mpm->version;
	}

}
