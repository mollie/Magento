<?php

class Mollie_Mpm_Model_Method_Blik extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_blik';
    const PAYMENT_METHOD = 'Blik';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
