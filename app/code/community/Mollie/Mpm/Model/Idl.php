<?php

class Mollie_Mpm_Model_Idl extends Mollie_Mpm_Model_Api
{
	// This class is here for backward-compatibility reasons. If it isn't here, viewing old iDeal orders throws a payment method unavailable error
	protected $_code = "mpm_idl";
	protected $_index = 0;
}
