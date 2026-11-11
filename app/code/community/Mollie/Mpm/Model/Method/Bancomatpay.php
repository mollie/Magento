<?php

class Mollie_Mpm_Model_Method_Bancomatpay extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_bancomatpay';
    const PAYMENT_METHOD = 'Bancomatpay';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
