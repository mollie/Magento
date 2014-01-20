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
 * @version     v4.0.0
 * @copyright   Copyright (c) 2012-2014 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 * 
 **/

$installer = $this;
$installer->startSetup();

/*
 * Mollie tabel maken.
 */
$installer->run(
	sprintf("CREATE TABLE IF NOT EXISTS `%s` (
		`order_id` int(11) NOT NULL,
		`method` varchar(3) NOT NULL,
		`transaction_id` varchar(32) NOT NULL,
		`bank_account` varchar(15) NOT NULL,
		`bank_status` varchar(20) NOT NULL,
		`created_at` datetime NOT NULL,
		`updated_at` datetime DEFAULT NULL,
		 UNIQUE KEY `transaction_id` (`transaction_id`),
		 KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
		$installer->getTable('mollie_payments')
	) . "REPLACE INTO `{$installer->getTable('core_config_data')}` SET `path` = 'payment/mollie/description', `value` = 'Order %';"
);

/*
 * Een waarschuwing in de beheerder-sectie geven dat de Mollie instellingen ingesteld moeten worden.
 */
if(strlen(Mage::getStoreConfig("mollie/settings/apikey")) === 0)
{
	$installer->run(
		sprintf("INSERT INTO `%s` (`severity`, `date_added`, `title`, `description`, `url`, `is_read`, `is_remove`)
			VALUES ('4', '%s', 'Go to System -> Configuration -> Payment Methods and enter your Mollie settings to enable the payment method',
			'Your Mollie settings need to be set. Before doing so, your customers will not be able to use the Mollie payment methods',
			'https://www.mollie.nl/', '0', '0');",
			$installer->getTable('adminnotification_inbox'),
			date("Y/m/d H:i:s", time())
		)
	);
}

// Clear cache
// Mage::app()->cleanCache();
// Mage::app()->getCache()->clean();
// Mage::app()->getCacheInstance()->flush();

$installer->endSetup();
