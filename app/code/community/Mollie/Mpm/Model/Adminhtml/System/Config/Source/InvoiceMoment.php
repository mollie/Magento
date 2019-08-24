<?php

class Mollie_Mpm_Model_Adminhtml_System_Config_Source_InvoiceMoment
{
    const ON_SHIPMENT = 'shipment';
    const ON_AUTHORIZE = 'authorize';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => static::ON_AUTHORIZE,
                'label' => __('On Authorize'),
            ),
            array(
                'value' => static::ON_SHIPMENT,
                'label' => __('On Shipment'),
            )
        );
    }
}
