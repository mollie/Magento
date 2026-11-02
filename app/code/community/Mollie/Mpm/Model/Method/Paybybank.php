<?php

class Mollie_Mpm_Model_Method_Paybybank extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_paybybank';
    const PAYMENT_METHOD = 'Paybybank';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
