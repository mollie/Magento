<?php
/**
 * Copyright (c) 2012-2019, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
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
 * @copyright   Copyright (c) 2012-2019 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD-License 2
 */

class Mollie_Mpm_Adminhtml_MollieController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Mollie Test Helper.
     *
     * @var Mollie_Mpm_Helper_Test
     */
    public $testsHelper;
    /**
     * Mollie API Model.
     *
     * @var Mollie_Mpm_Helper_Data
     */
    public $mollieHelper;

    /**
     * Construct.
     */
    public function _construct()
    {
        $this->testsHelper = Mage::helper('mpm/test');
        $this->mollieHelper = Mage::helper('mpm');
        parent::_construct();
    }

    /**
     * API-Key Test Action
     */
    public function apitestAction()
    {
        $apiCheck = $this->mollieHelper->checkApiInstalled();
        if (!$apiCheck) {
            $msg = '<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>';
            return Mage::app()->getResponse()->setBody($msg);
        }

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();
        $testKey = $request->getParam('test_key');
        $liveKey = $request->getParam('live_key');
        $results = $this->testsHelper->getMethods($testKey, $liveKey);

        return Mage::app()->getResponse()->setBody(implode('<br/>', $results));
    }

    /**
     * Selftest Action
     */
    public function selftestAction()
    {
        $apiCheck = $this->mollieHelper->checkApiInstalled();
        if (!$apiCheck) {
            $msg = '<span class="mollie-error">' . $this->mollieHelper->getPhpApiErrorMessage() . '</span>';
            return Mage::app()->getResponse()->setBody($msg);
        }

        $results = $this->testsHelper->compatibilityChecker();

        return Mage::app()->getResponse()->setBody(implode('<br/>', $results));
    }

    /**
     * External Redirect to Mollie Account.
     */
    public function profilesAction()
    {
        $this->_redirectUrl('https://www.mollie.nl/beheer/account/profielen/');
    }

    /**
     * External Redirect to Mollie Beheer.
     */
    public function dashboardAction()
    {
        $this->_redirectUrl('https://www.mollie.nl/beheer');
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/mollie/mollie');
    }
}