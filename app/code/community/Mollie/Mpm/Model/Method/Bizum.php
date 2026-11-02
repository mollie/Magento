<?php

class Mollie_Mpm_Model_Method_Bizum extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_bizum';
    const PAYMENT_METHOD = 'Bizum';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
