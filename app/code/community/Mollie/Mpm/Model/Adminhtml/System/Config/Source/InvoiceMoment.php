<?php
/**
 *    ______            __             __
 *   / ____/___  ____  / /__________  / /
 *  / /   / __ \/ __ \/ __/ ___/ __ \/ /
 * / /___/ /_/ / / / / /_/ /  / /_/ / /
 * \______________/_/\__/_/   \____/_/
 *    /   |  / / /_
 *   / /| | / / __/
 *  / ___ |/ / /_
 * /_/ _|||_/\__/ __     __
 *    / __ \___  / /__  / /____
 *   / / / / _ \/ / _ \/ __/ _ \
 *  / /_/ /  __/ /  __/ /_/  __/
 * /_____/\___/_/\___/\__/\___/
 *
 */

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
