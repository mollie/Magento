<?php

class Mollie_Mpm_Model_Adminhtml_System_Config_Backend_SurchargeValidation extends Mage_Core_Model_Config_Data
{
    const MAX_SURCHARGE = 1.95;

    public function save()
    {
        if ($this->getValue() > 1.95) {
            Mage::throwException(
                Mage::helper('mpm')->__(
                    'The payment surcharge cannot exceed &euro; %s',
                    static::MAX_SURCHARGE
                )
            );
        }

        return parent::save();
    }
}
