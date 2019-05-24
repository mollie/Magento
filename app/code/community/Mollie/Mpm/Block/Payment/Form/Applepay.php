<?php

class Mollie_Mpm_Block_Payment_Form_Applepay extends Mollie_Mpm_Block_Payment_Form
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('mollie/mpm/payment/form/applepay.phtml');
    }
}