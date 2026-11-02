<?php

class Mollie_Mpm_Model_Method_Vipps extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_vipps';
    const PAYMENT_METHOD = 'Vipps';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
