<?php

/** @var Mage_Sales_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

$installer->getConnection()->addIndex(
    $installer->getTable('sales/order'),
    $this->getIdxName('sales/order', ['mollie_transaction_id']),
    ['mollie_transaction_id']
);

$installer->endSetup();