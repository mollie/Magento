<?php

/**
 * Copyright (c) 2012-2014, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category    Mollie
 * @package     Mollie_Mpm
 * @author      Mollie B.V. (info@mollie.nl)
 * @copyright   Copyright (c) 2012-2014 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 **/

class Mollie_Mpm_Block_Payment_Api_Form extends Mage_Payment_Block_Form
{
	protected $_helper = null;
	protected $_apiHelper = null;
	protected $_issuers = [];

	public function _construct()
	{
		parent::_construct();

		$this->setTemplate('mollie/form/details.phtml');
	}

	/**
	 * @return Mollie_Mpm_Helper_Api
	 */
	public function getHelper()
	{
		if($this->_helper == null) {
			$this->_helper = Mage::helper('mpm');
		}

		return $this->_helper;
	}

	public function getMethod()
	{
		return $this->getHelper()->getMethodByCode($this->getMethodCode());
	}

	public function getApiHelper()
	{
		if($this->_apiHelper == null) {
			$this->_apiHelper = Mage::helper('mpm/api');
		}

		return $this->_apiHelper;
	}

	public function getMollieAPI()
	{
		return $this->getApiHelper()->_getMollieAPI();
	}

	public function getIssuers()
	{
		try {
			$apiIssuers = $this->getMollieAPI()->issuers;

			foreach ($apiIssuers->all() as $issuer) {
				if (!array_key_exists($issuer->method, $this->_issuers)) {
					$this->_issuers[$issuer->method] = array();
				}

				$this->_issuers[$issuer->method][] = $issuer;
			}
		} catch (Exception $ex) {
			Mage::logException($ex);
			Mage::log('Unable to retrieve payment methods for Mollie, please refer to exception log for details.');
		}

		return $this->_issuers;
	}
}
