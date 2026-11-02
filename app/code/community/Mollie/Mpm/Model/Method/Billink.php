<?php

class Mollie_Mpm_Model_Method_Billink extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_billink';
    const PAYMENT_METHOD = 'Billink';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
