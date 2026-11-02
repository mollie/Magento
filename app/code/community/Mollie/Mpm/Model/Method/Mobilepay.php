<?php

class Mollie_Mpm_Model_Method_Mobilepay extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_mobilepay';
    const PAYMENT_METHOD = 'Mobilepay';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
