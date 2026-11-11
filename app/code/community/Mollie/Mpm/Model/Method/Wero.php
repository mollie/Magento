<?php

class Mollie_Mpm_Model_Method_Wero extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_wero';
    const PAYMENT_METHOD = 'Wero';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
