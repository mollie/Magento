<?php

class Mollie_Mpm_Model_Method_Payconiq extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_payconiq';
    const PAYMENT_METHOD = 'Payconiq';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
