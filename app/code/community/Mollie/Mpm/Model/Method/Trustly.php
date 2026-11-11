<?php

class Mollie_Mpm_Model_Method_Trustly extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_trustly';
    const PAYMENT_METHOD = 'Trustly';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
