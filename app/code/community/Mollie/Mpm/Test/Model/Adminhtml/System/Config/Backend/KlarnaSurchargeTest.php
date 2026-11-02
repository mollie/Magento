<?php

class Mollie_Mpm_Test_Model_Adminhtml_System_Config_Backend_KlarnaSurchargeTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    const PATH = 'payment/mollie_klarna/payment_surcharge';

    protected function tearDown(): void
    {
        Mage::getModel('core/config')->deleteConfig(self::PATH);

        parent::tearDown();
    }

    public function testItAllowsAKlarnaSurchargeAboveTheOldKlarnaMaximum()
    {
        $field = $this->createFieldBackend();
        $field->setValue('2.50');

        $field->save();

        $this->assertSame('2.50', $this->readSavedValue());
    }

    private function createFieldBackend()
    {
        $fieldConfig = Mage::getSingleton('adminhtml/config')
            ->getSection('payment')->groups->mollie_klarna->fields->payment_surcharge;
        $backendModel = isset($fieldConfig->backend_model) ? (string)$fieldConfig->backend_model : 'core/config_data';

        return Mage::getModel($backendModel)
            ->setPath(self::PATH)
            ->setScope('default')
            ->setScopeId(0);
    }

    private function readSavedValue()
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        return $connection->fetchOne(
            'SELECT value FROM ' . Mage::getSingleton('core/resource')->getTableName('core/config_data')
            . ' WHERE path = ? AND scope = ? AND scope_id = 0',
            array(self::PATH, 'default')
        );
    }
}
