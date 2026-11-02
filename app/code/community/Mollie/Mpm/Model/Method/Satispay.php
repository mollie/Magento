<?php

class Mollie_Mpm_Model_Method_Satispay extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_satispay';
    const PAYMENT_METHOD = 'Satispay';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;
}
