<?php

abstract class Mollie_Mpm_Model_Method_DiscontinuedAbstract extends Mollie_Mpm_Model_Method_Abstract
{
    protected $_canUseInternal = false;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;

    public function isAvailable($quote = null)
    {
        return false;
    }
}
