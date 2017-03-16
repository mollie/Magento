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
 **/

class Mollie_Mpm_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $update_url    = 'https://github.com/mollie/Magento';
	public $should_update = 'maybe';

	/**
	 * Get the title for the given payment method
	 *
	 * @param string $id
	 * @param int $storeId
	 * @return string
	 */
	public function getMethodTitle($id, $storeId = NULL)
	{
		return Mage::getStoreConfig("payment/mollie_title/{$id}", $storeId ?: $this->getCurrentStore());
	}

	/**
	 * Get payment bank status by order_id
	 *
	 * @return array
	 */
	public function getStatusById ($transaction_id)
	{
		/** @var $connection Varien_Db_Adapter_Interface */
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$status     = $connection->fetchAll(
			sprintf(
				"SELECT `bank_status`, `updated_at` FROM `%s` WHERE `transaction_id` = %s",
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
	public function getOrderIdByTransactionId ($transaction_id)
	{
		/** @var $connection Varien_Db_Adapter_Interface */
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$id         = $connection->fetchAll(
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
	 * Get transaction_id by order_id
	 *
	 * @return int|null
	 */
	public function getTransactionIdByOrderId ($order_id)
	{
		/** @var $connection Varien_Db_Adapter_Interface */
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$id         = $connection->fetchAll(
			sprintf(
				"SELECT `transaction_id` FROM `%s` WHERE `order_id` = %s",
				Mage::getSingleton('core/resource')->getTableName('mollie_payments'),
				$connection->quote($order_id)
			)
		);

		if (sizeof($id) > 0)
		{
			return $id[0]['transaction_id'];
		}

		return NULL;
	}

	public function getStoredMethods ()
	{
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$methods    = $connection->fetchAll(
			sprintf(
				"SELECT * FROM `%s`",
				Mage::getSingleton('core/resource')->getTableName('mollie_methods')
			)
		);

		return $methods;
	}

	public function setStoredMethods (array $methods)
	{
		$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
		$table_name = Mage::getSingleton('core/resource')->getTableName('mollie_methods');

		foreach ($methods as $method)
		{
			$connection->query(sprintf(
				"INSERT INTO `%s` (`method_id`, `description`) VALUES (%s, %s) ON DUPLICATE KEY UPDATE `id`=`id`",
				$table_name,
				$connection->quote($method['method_id']),
				$connection->quote($method['description'])
			));
		}

		return $this;
	}

	/**
	 * Gets Api key from `config_core_data`
	 *
	 * @return string
	 */
	public function getApiKey ()
	{
		return trim(Mage::getStoreConfig("payment/mollie/apikey", $this->getCurrentStore()));
	}

	/**
	 * Gets Bank Transfer due date days key from `config_core_data`
	 *
	 * @return string
	 */
	public function getBankTransferDueDateDays ()
	{
		return trim(Mage::getStoreConfig("payment/mollie/banktransfer_due_date_days"));
	}

	/**
	 * Get store config
	 *
	 * @param string $paymentmethod
	 * @param string $key
     * @param int $storeId
	 *
	 * @return string
	 */
	public function getConfig ($paymentmethod = NULL, $key = NULL, $storeId = NULL)
	{
		$arr            = array('active', 'apikey', 'description', 'skip_invoice', 'skip_order_mails', 'skip_invoice_mails', 'show_images', 'show_bank_list', 'banktransfer_due_date_days');
		$paymentmethods = array('mollie');

		if(
			in_array($key, $arr) && in_array($paymentmethod, $paymentmethods)
			|| substr($paymentmethod, 0, 9) == 'mpm_void_'
		) {
			return Mage::getStoreConfig("payment/{$paymentmethod}/{$key}", $storeId ?: $this->getCurrentStore());
		}

		return NULL;
	}

	/**
	 * Gets selected store in admin
	 *
	 * @return string
	 */
	public function getCurrentStore ()
	{
		return Mage::app()->getStore()->getId();
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getModuleStatus ($method_count, $method_limit)
	{
		/* Precedence:
		 * 1) Missing files
		 * 2) Magento version
		 * 3) New version on github
		 * 4) Method limit
		 * 5) Disabled check
		 */

		$core = Mage::helper('core');

		// 1) Check missing files
		$needFiles = array();
		$modFiles  = array(
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Exception/IncompatiblePlatform.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Customer/Mandate.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Payment/Refund.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Customer.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Issuer.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/List.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Method.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Organization.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Payment.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Permission.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Profile.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Object/Settlement.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Customers/Mandates.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Customers/Payments.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Payments/Refunds.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Base.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Customers.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Issuers.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Methods.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Organizations.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Payments.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Permissions.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Profiles.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Settlements.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Resource/Undefined.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Autoloader.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/cacert.pem",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Client.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/CompatibilityChecker.php",
			Mage::getBaseDir('lib') . "/Mollie/src/Mollie/API/Exception.php",

			Mage::getRoot() .'/design/adminhtml/default/default/template/mollie/system/config/status.phtml',
			Mage::getRoot() .'/design/frontend/base/default/layout/mpm.xml',
			Mage::getRoot() .'/design/frontend/base/default/template/mollie/page/exception.phtml',
			Mage::getRoot() .'/design/frontend/base/default/template/mollie/page/fail.phtml',
			Mage::getRoot() .'/design/frontend/base/default/template/mollie/form/details.phtml',

			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Adminhtml/System/Config/Status.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Payment/Api/Form.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Block/Payment/Api/Info.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/controllers/ApiController.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/etc/adminhtml.xml',
			Mage::getRoot() .'/code/community/Mollie/Mpm/etc/config.xml',
			Mage::getRoot() .'/code/community/Mollie/Mpm/etc/system.xml',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Helper/Data.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Helper/Api.php',
			Mage::getRoot() .'/code/community/Mollie/Mpm/Model/Api.php',
		);

		for ($i = 0; $i < $method_limit; $i++)
		{
			$I = ($i < 10 ? '0'.$i : $i);
			$modFiles[] = Mage::getRoot() .'/code/community/Mollie/Mpm/Model/Void'.$I.'.php';
		}

		foreach ($modFiles as $file)
		{
			if(!file_exists($file))
			{
				$needFiles[] = '<span style="color:red">'.$file.'</span>';
			}
		}

		if (count($needFiles) > 0)
		{
			return '<b>'.$core->__('Missing file(s) detected!').'</b><br />' . implode('<br />', $needFiles);
		}

		// 2) Check magento version
		if ( version_compare(Mage::getVersion(), '1.4.1.0', '<'))
		{
			return '<b>'.$core->__('Version incompatible!').'</b><br />
				<span style="color:red">'.$core->__('Your Magento version is incompatible with this module!').'<br>
				- '.$core->__('Minimal version requirement: ').'1.4.1.x<br>
				- '.$core->__('Current version: ').Mage::getVersion() .'
				</span>
			';
		}

		// 3) Check github version
		if ($this->should_update === 'yes')
		{
			return '<b>'.$core->__('Status').'</b><br /><span style="color:#EB5E00">'.$core->__('Module status: Outdated!').'</span>';
		}

		// 4) Check method limit
		if ($method_count > $method_limit)
		{
			return '<b>'.$core->__('Module outdated!').'</b><br />
				<span style="color:#EB5E00">'.sprintf($core->__('Mollie currently provides %d payment methods, while this module only supports %d method slots.'), $method_count, $method_limit).'</span><br />
				'.$core->__('To enable all supported payment methods, get the latest Magento plugin from the <a href="https://www.mollie.nl/betaaldiensten/ideal/modules/" title="Mollie Modules">Mollie Modules list</a>.').'
				<br />
				If no newer version is available, please <a href="https://www.mollie.nl/bedrijf/contact" title="Mollie Support">contact Mollie BV</a>.
			';
		}

		// 5) Check if disabled
		if (!Mage::helper('mpm')->getConfig('mollie', 'active'))
		{
			return '<b>'.$core->__('Status').'</b><br /><span style="color:#EB5E00">'.$core->__('Module status: Disabled!').'</span>';
		}

		// All is fine
		return '<b>'.$core->__('Status').'</b><br /><span style="color:green">'.$core->__('Module status: OK!').'</span>';
	}

	/**
	 * Gets status from order_id
	 *
	 * @param int $order_id
	 * @return string|NULL
	 */
	public function getWaitingPayment($order_id)
	{
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

		if ($order['status'] == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
		{
			return '<span class="mpm_waiting_msg">'. Mage::helper('core')->__($this->__('Your order is awaiting payment before being released for processing and shipment.')) .'</span>';
		}

		return NULL;
	}

	public function getModuleVersion()
	{
		return Mage::getConfig()->getNode('modules')->children()->Mollie_Mpm->version;
	}

	/**
	 * @return string
	 */
	public function _getUpdateMessage()
	{
		$core = Mage::helper('core');
		$update_message = '';
		$update_xml = $this->_getUpdateXML();
		if ($update_xml === FALSE)
		{
			$this->should_update = 'maybe';
			$update_message = $core->__('Warning: Could not retrieve update xml file from github.', 'mollie');
		}
		else
		{
			/** @var SimpleXMLElement $tags */
			$tags = new SimpleXMLElement($update_xml);
			if (!empty($tags) && isset($tags->entry, $tags->entry[0], $tags->entry[0]->id))
			{
				$title = $tags->entry[0]->id;
				$latest_version = preg_replace("/[^0-9,.]/", "", substr($title, strrpos($title, '/')));
				$this_version = $this->getModuleVersion();
				if (!version_compare($this_version, $latest_version, '>='))
				{
					$update_message = sprintf(
						'<a href=%s/releases>' .
						$core->__('You are currently using version %s. We strongly recommend you to upgrade to the new version %s!', 'mollie') .
						'</a><br /><span style="font-size: small;">' .
						$core->__('Note: version information is accurate only when the cache is cleared.', 'mollie') .
						'</span>',
						$this->update_url, $this_version, $latest_version
					);
					$this->should_update = 'yes';
				}
				else
				{
					$this->should_update = 'no';
				}
			}
			else
			{
				$this->should_update = 'maybe';
				$update_message = $core->__('Warning: Update xml file from github follows an unexpected format.', 'mollie');
			}
		}
		return $update_message;
	}

	/**
	 * @return string
	 */
	protected function _getUpdateXML()
	{
		return @file_get_contents($this->update_url . '/releases.atom');
	}
}
