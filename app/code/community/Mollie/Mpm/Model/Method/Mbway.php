<?php

class Mollie_Mpm_Model_Method_Mbway extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_mbway';
    const PAYMENT_METHOD = 'Mbway';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
