<?php

use Mollie_Mpm_Model_Adminhtml_System_Config_Backend_SurchargeValidation as SurchargeValidation;

class Mollie_Mpm_Test_Model_Adminhtml_System_Config_Backend_SurchargeValidation extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testThrowsErrorWhenTheAmountIsExceeded()
    {
        /** @var SurchargeValidation $instance */
        $instance = Mage::getModel('mpm/adminhtml_system_config_backend_surchargeValidation');
        $instance->setValue(SurchargeValidation::MAX_SURCHARGE + 1);

        try {
            $instance->save();
        } catch (Mage_Core_Exception $exception) {
            $this->assertEquals('The payment surcharge cannot exceed &euro; 1.95', $exception->getMessage());
            return;
        }

        $this->fail('An Mage_Core_Exception exception is expected, but we got none');
    }

    public function testSavesTheValueWhenTheAmountIsCorrect()
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('core/config_data');

        $path = 'payment/mollie_fakepaymentmethod/fakeconfig';

        try {
            /** @var SurchargeValidation $instance */
            $instance = Mage::getModel('mpm/adminhtml_system_config_backend_surchargeValidation');
            $instance->setData(array(
                'scope' => 'default',
                'scope_id' => 0,
                'path' => $path,
            ));

            $instance->setValue(SurchargeValidation::MAX_SURCHARGE);

            $instance->save();

            $result = $connection->fetchOne(
                'select * from ' . $tableName . ' where path = :path limit 1',
                ['path' => $path]
            );

            $this->assertNotFalse($result);
        } finally {
            $connection->delete($tableName, ['path = ?' => $path]);
        }
    }
}
