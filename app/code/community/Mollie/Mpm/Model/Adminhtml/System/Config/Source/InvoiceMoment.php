<?php

class Mollie_Mpm_Model_Adminhtml_System_Config_Source_InvoiceMoment
{
    const ON_SHIPMENT = 'shipment';
    const ON_AUTHORIZE_PAID_BEFORE_SHIPMENT = 'authorize_paid_before_shipment';
    const ON_AUTHORIZE_PAID_AFTER_SHIPMENT = 'authorize_paid_after_shipment';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => static::ON_AUTHORIZE_PAID_BEFORE_SHIPMENT,
                'label' => __('On Authorize and set status Paid before shipment'),
            ),
            array(
                'value' => static::ON_AUTHORIZE_PAID_AFTER_SHIPMENT,
                'label' => __('On Authorize and set status Paid after shipment'),
            ),
            array(
                'value' => static::ON_SHIPMENT,
                'label' => __('On Shipment'),
            )
        );
    }
}
