<?php

class Mollie_Mpm_Model_Adminhtml_System_Config_Source_WebhookUrlOptions
{
    const ENABLED = 'enabled';
    const CUSTOM_URL = 'custom_url';
    const DISABLED = 'disabled';

    public function toOptionArray()
    {
        $helper = Mage::helper('mpm');

        return array(
            array(
                'value' => static::ENABLED,
                'label' => $helper->__('Enabled'),
            ),
            array(
                'value' => static::CUSTOM_URL,
                'label' => $helper->__('Custom URL'),
            ),
            array(
                'value' => static::DISABLED,
                'label' => $helper->__('Disabled'),
            ),
        );
    }
}
