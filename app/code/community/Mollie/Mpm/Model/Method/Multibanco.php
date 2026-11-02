<?php

class Mollie_Mpm_Model_Method_Multibanco extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_multibanco';
    const PAYMENT_METHOD = 'Multibanco';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
