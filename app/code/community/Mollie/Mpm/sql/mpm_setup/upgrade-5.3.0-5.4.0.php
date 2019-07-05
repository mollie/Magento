<?php

/** @var Mage_Sales_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->startSetup();
$connection = $installer->getConnection();

$tables = [
    'sales/order',
    'sales/quote_address',
    'sales/invoice',
    'sales/creditmemo',
];

foreach ($tables as $tableName) {
    $table = $this->getTable($tableName);
    $definition = 'decimal(12,4) null default null';

    $connection->addColumn($table, 'mollie_mpm_payment_fee', $definition);
    $connection->addColumn($table, 'base_mollie_mpm_payment_fee', $definition);
    $connection->addColumn($table, 'mollie_mpm_payment_fee_tax', $definition);
    $connection->addColumn($table, 'base_mollie_mpm_payment_fee_tax', $definition);
}

$installer->endSetup();