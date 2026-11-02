<?php

class Mollie_Mpm_Model_Method_Swish extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_swish';
    const PAYMENT_METHOD = 'Swish';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
