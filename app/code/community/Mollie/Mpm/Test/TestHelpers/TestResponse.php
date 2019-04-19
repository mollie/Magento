<?php
class Mollie_Mpm_Test_TestHelpers_TestResponse extends Mage_Core_Controller_Response_Http
{
    public function canSendHeaders()
    {
        return true;
    }
}