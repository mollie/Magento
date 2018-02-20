<?php
/**
 * Copyright (c) 2012-2018, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
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
 * @copyright   Copyright (c) 2012-2018 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD-License 2
 */

/** @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$installer->deleteConfigData('payment/mollie/description');

$installer->run(
    sprintf(
        "CREATE TABLE IF NOT EXISTS `%s` (
          `order_id` int(11) NOT NULL,
          `method` varchar(20) NOT NULL,
          `transaction_id` varchar(32) NOT NULL,
          `bank_account` varchar(34) NOT NULL,
          `bank_status` varchar(20) NOT NULL,
          `consumer_name` varchar(255) NOT NULL,
          `consumer_bic` varchar(34) NOT NULL,          
          `issuer` varchar(255) NOT NULL, 
          `created_at` datetime NOT NULL,
          `updated_at` datetime DEFAULT NULL,
          UNIQUE KEY `transaction_id` (`transaction_id`),
          KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        $installer->getTable('mollie_payments')
    )
);

$installer->run(
    sprintf(
        "CREATE TABLE IF NOT EXISTS `%s` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `method_id` varchar(32) NOT NULL DEFAULT '',
          `description` varchar(32) NOT NULL DEFAULT '',
          PRIMARY KEY (`id`),
          UNIQUE KEY `method_id` (`method_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
        $installer->getTable('mollie_methods')
    )
);

for ($i = 0; $i < 10; $i++) {
    $installer->run(
        sprintf(
            "UPDATE `%s` SET `method` = 'mpm_void_0" . $i . "' WHERE `method` = 'mpm_void_" . $i . "';",
            $installer->getTable('sales_flat_order_payment')
        )
    );
}

$installer->endSetup();