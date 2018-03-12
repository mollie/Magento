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

class Mollie_Mpm_Model_Adminhtml_System_Config_Source_Locale
{

    /**
     * @var array
     */
    public $options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = array(
                array('value' => '', 'label' => Mage::helper('mpm')->__('Autodetect')),
                array('value' => 'store', 'label' => Mage::helper('mpm')->__('Store Locale')),
                array('value' => 'en_US', 'label' => Mage::helper('mpm')->__('en_US')),
                array('value' => 'de_AT', 'label' => Mage::helper('mpm')->__('de_AT')),
                array('value' => 'de_CH', 'label' => Mage::helper('mpm')->__('de_CH')),
                array('value' => 'de_DE', 'label' => Mage::helper('mpm')->__('de_DE')),
                array('value' => 'es_ES', 'label' => Mage::helper('mpm')->__('es_ES')),
                array('value' => 'fr_BE', 'label' => Mage::helper('mpm')->__('fr_BE')),
                array('value' => 'nl_BE', 'label' => Mage::helper('mpm')->__('nl_BE')),
                array('value' => 'nl_NL', 'label' => Mage::helper('mpm')->__('nl_NL'))
            );
        }

        return $this->options;
    }
}