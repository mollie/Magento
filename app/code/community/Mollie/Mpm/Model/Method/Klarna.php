<?php

class Mollie_Mpm_Model_Method_Klarna extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_klarna';
    const PAYMENT_METHOD = 'Klarna';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
