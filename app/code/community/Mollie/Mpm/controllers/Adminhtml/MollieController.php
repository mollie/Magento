<?php
/**
 * Copyright (c) 2012-2018, Mollie B.V.
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
 * @copyright   Copyright (c) 2012-2018 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD-License 2
 */

class Mollie_Mpm_Adminhtml_MollieController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Mollie API Helper.
     *
     * @var Mollie_Mpm_Helper_Api
     */
    public $mollieHelper;
    /**
     * Mollie API Model.
     *
     * @var Mollie_Mpm_Model_Api
     */
    public $mollieApiModel;

    /**
     * Construct.
     */
    public function _construct()
    {
        $this->mollieHelper = Mage::helper('mpm/api');
        $this->mollieApiModel = Mage::getModel('mpm/api');
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

        $results = array();
        $apiKey = Mage::app()->getRequest()->getParam('apikey');

        if (empty($apiKey)) {
            $msg = $this->mollieHelper->__('API Key: Empty value');
            $results[] = sprintf('<span class="mollie-error">%s</span>', $msg);
        } else {
            if (!preg_match('/^(live|test)_\w{30,}$/', $apiKey)) {
                $msg = $this->mollieHelper->__('API Key: Should start with "test_" or "live_"');
                $results[] = sprintf('<span class="mollie-error">%s</span>', $msg);
            } else {
                try {
                    $mollieApi = $this->mollieHelper->getMollieAPI($apiKey);
                    $mollieApi->methods->all();
                    $msg = $this->mollieHelper->__('API Key: Success!');
                    $results[] = sprintf('<span class="mollie-success">%s</span>', $msg);
                } catch (\Exception $e) {
                    $msg = $this->mollieHelper->__('API Key: %s', $e->getMessage());
                    $results[] = sprintf('<span class="mollie-error">%s</span>', $msg);
                }
            }
        }

        $msg = implode('<br/>', $results);
        Mage::app()->getResponse()->setBody($msg);
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

        $results = array();
        $compatibilityChecker = $this->mollieHelper->getMollieCompatibilityChecker();

        if (!$compatibilityChecker->satisfiesPhpVersion()) {
            $minPhpVersion = $compatibilityChecker::MIN_PHP_VERSION;
            $msg = $this->mollieHelper->__('Error: The client requires PHP version >= %s, you have %s.', $minPhpVersion, PHP_VERSION);
            $results[] = sprintf('<span class="mollie-error">%s</span>', $msg);
        } else {
            $msg = $this->mollieHelper->__('Success: PHP version: %s.', PHP_VERSION);
            $results[] = sprintf('<span class="mollie-success">%s</span>', $msg);
        }

        if (!$compatibilityChecker->satisfiesJsonExtension()) {
            $msg = $this->mollieHelper->__('Error: PHP extension JSON is not enabled, please enable.');
            $results[] = sprintf('<span class="mollie-error">%s</span>', $msg);
        } else {
            $msg = $this->mollieHelper->__('Success: JSON is enabled.');
            $results[] = sprintf('<span class="mollie-success">%s</span>', $msg);
        }

        $msg = implode('<br/>', $results);
        Mage::app()->getResponse()->setBody($msg);
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