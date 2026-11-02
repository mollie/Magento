<?php

class Mollie_Mpm_Model_Method_Twint extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_twint';
    const PAYMENT_METHOD = 'Twint';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
