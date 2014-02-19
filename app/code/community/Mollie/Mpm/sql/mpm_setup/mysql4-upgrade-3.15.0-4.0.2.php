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

$installer->run("
	UPDATE `{$installer->getTable('core_config_data')}` SET `path` = REPLACE(`path`, 'mollie/idl', 'payment/api') WHERE `path` LIKE '%mollie/idl%';
	DELETE FROM `{$installer->getTable('core_config_data')}` WHERE `path` = 'mollie/settings/partnerid';
	DELETE FROM `{$installer->getTable('core_config_data')}` WHERE `path` = 'mollie/settings/profilekey';
	REPLACE INTO `{$installer->getTable('core_config_data')}` SET `path` = 'payment/mollie/description', `value` = 'Order %';
");

/*
 * Tabel Betaalmethodes
 */
$table = $installer->getTable('mollie_methods');
$installer->run(
	sprintf("
		CREATE TABLE IF NOT EXISTS `%s` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `method_id` varchar(32) NOT NULL DEFAULT '',
		  `description` varchar(32) NOT NULL DEFAULT '',
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
		$table
	)
);
if (!$installer->tableExists($table))
{
	echo("
		<div style='background:white;border:3px solid red;padding:10px;'><b style='color:red;'>Insufficient SQL rights to create the $table table! Please make sure you have sufficient access rights to install modules and/or run this query manually:</b><br /><pre>" .
		sprintf("
			CREATE TABLE IF NOT EXISTS `%s` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `method_id` varchar(32) NOT NULL DEFAULT '',
			  `description` varchar(32) NOT NULL DEFAULT '',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			$table
		) .
		"</pre><br/>(If you do not know how to run SQL queries, please contact your hosting provider)</div>");
}