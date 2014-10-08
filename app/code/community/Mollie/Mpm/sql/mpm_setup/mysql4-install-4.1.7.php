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

$installer = $this;
$installer->startSetup();

$payment_table = $installer->getTable('mollie_payments');
$method_table = $installer->getTable('mollie_methods');
$order_table = $installer->getTable('sales_flat_order_payment');

/*
 * Tabel Betalingen
 */
$installer->run(
	sprintf("
		CREATE TABLE IF NOT EXISTS `%s` (
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
		$payment_table
	) . "REPLACE INTO `{$installer->getTable('core_config_data')}` SET `path` = 'payment/mollie/description', `value` = 'Order %';"
);
if (!$installer->tableExists($payment_table))
{
	echo("
		<div style='background:white;border:3px solid red;padding:10px;'><b style='color:red;'>Insufficient SQL rights to create the $payment_table table! Please make sure you have sufficient access rights to install modules and/or run this query manually:</b><br /><pre>" .
		sprintf("
			CREATE TABLE IF NOT EXISTS `%s` (
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
			$payment_table
		) .
		"</pre><br />(If you do not know how to run SQL queries, please contact your hosting provider)</div>");
}

/*
 * Tabel Betaalmethodes
 */

if ($installer->tableExists($method_table))
{
	$installer->run(
		sprintf("DROP TABLE IF EXISTS `%s`", $method_table)
	);
}

$installer->run(
	sprintf("
		CREATE TABLE IF NOT EXISTS `%s` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `method_id` varchar(32) NOT NULL DEFAULT '',
		  `description` varchar(32) NOT NULL DEFAULT '',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `method_id` (`method_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
		$method_table
	)
);


if (!$installer->tableExists($method_table))
{
	echo("
		<div style='background:white;border:3px solid red;padding:10px;'><b style='color:red;'>Insufficient SQL rights to create the $method_table table! Please make sure you have sufficient access rights to install modules and/or run this query manually:</b><br /><pre>" .
		sprintf("
			CREATE TABLE IF NOT EXISTS `%s` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `method_id` varchar(32) NOT NULL DEFAULT '',
			  `description` varchar(32) NOT NULL DEFAULT '',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `method_id` (`method_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			$method_table
		) .
		"</pre><br />(If you do not know how to run SQL queries, please contact your hosting provider)</div>");
}

// update sales_flat_order_payment
for ($i = 0; $i < 10; $i++)
{
	$installer->run("UPDATE `".$order_table."` SET `method` = 'mpm_void_0".$i."' WHERE `method` = 'mpm_void_".$i."';");
}

$installer->endSetup();