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

class Mollie_Mpm_Model_Resource_OrderLines extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
     */
    public function _construct()
    {
        $this->_init('mpm/orderLines', 'id');
    }

    /**
     * @param Mage_Core_Model_Abstract $object
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if ($qty = $object->getData('quantity')) {
            $object->setData('qty_ordered', $qty);
        }

        if ($unitPrice = $object->getData('unitPrice')) {
            $unitPriceValue = isset($unitPrice['value']) ? $unitPrice['value'] : '';
            $object->setData('unit_price', $unitPriceValue);
        }

        if ($discountAmount = $object->getData('discountAmount')) {
            $discountAmountValue = isset($discountAmount['value']) ? $discountAmount['value'] : '';
            $object->setData('discount_amount', $discountAmountValue);
        }

        if ($totalAmount = $object->getData('totalAmount')) {
            $totalAmountValue = isset($totalAmount['value']) ? $totalAmount['value'] : '';
            $totalAmountCurrency = isset($totalAmount['currency']) ? $totalAmount['currency'] : '';
            $object->setData('total_amount', $totalAmountValue);
            $object->setData('currency', $totalAmountCurrency);
        }

        if ($vatRate = $object->getData('vatRate')) {
            $object->setData('vat_rate', $vatRate);
        }

        if ($vatAmount = $object->getData('vatAmount')) {
            $vatAmountValue = isset($vatAmount['value']) ? $vatAmount['value'] : '';
            $object->setData('vat_amount', $vatAmountValue);
        }

        return parent::_beforeSave($object);
    }

}