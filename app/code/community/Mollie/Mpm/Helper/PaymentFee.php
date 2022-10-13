<?php

class Mollie_Mpm_Helper_PaymentFee extends Mage_Core_Helper_Abstract
{
    const PAYMENT_FEE_SURCHARGE_PATH = 'payment/%s/payment_surcharge';
    const PAYMENT_FEE_TAX_CLASS_PATH = 'payment/%s/payment_surcharge_tax_class';

    /**
     * @var array
     */
    private $taxCalculation = [];

    public function getPaymentFeeInludingTax(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();
        $store = $quote->getStore();
        $methodCode = $quote->getPayment()->getMethod();

        $value = Mage::getStoreConfig(
            sprintf(static::PAYMENT_FEE_SURCHARGE_PATH, $methodCode),
            $store
        );

        return (double)str_replace(',', '.', $value);
    }

    public function getPaymentFeeWithoutTax(Mage_Sales_Model_Quote_Address $address)
    {
        $fee = $this->getPaymentFeeInludingTax($address);
        $tax = $this->getPaymentFeeTax($address);

        return $fee - $tax;
    }

    public function getPaymentFeeTax(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();
        $method = $quote->getPayment()->getMethod();

        if (isset($this->taxCalculation[$method])) {
            return $this->taxCalculation[$method];
        }

        /** @var Mage_Tax_Model_Calculation $taxCalculationModel */
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $customerTaxClassId = $quote->getCustomerTaxClassId();
        $store = $quote->getStore();
        $request = $taxCalculationModel->getRateRequest(
            $address,
            $quote->getBillingAddress(),
            $customerTaxClassId,
            $store
        );

        $taxClassId = $this->getTaxClass($method);
        $request->setProductClassId($taxClassId, $store);

        $rate = $taxCalculationModel->getRate($request);

        $fee = $this->getPaymentFeeInludingTax($address);
        $result = $taxCalculationModel->calcTaxAmount(
            $fee,
            $rate,
            true,
            false
        );

        $this->taxCalculation[$method] = $result;

        return $result;
    }

    public function getVatRate($methodCode, $storeCode = null)
    {
        $store = Mage::app()->getStore($storeCode);
        $taxClassId = $this->getTaxClass($methodCode, $storeCode);

        /** @var Mage_Tax_Model_Calculation $taxCalculation */
        $taxCalculation = Mage::getSingleton('tax/calculation');

        $request = $taxCalculation->getRateRequest(null, null, null, $store);
        $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

        return $percent;
    }

    public function getTaxClass($methodCode, $storeCode = null)
    {
        return Mage::getStoreConfig(
            sprintf(static::PAYMENT_FEE_TAX_CLASS_PATH, $methodCode),
            Mage::app()->getStore($storeCode)
        );
    }

    public function isFullOrLastPartialCreditmemo(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $refundable = $orderItem->getQtyOrdered() - $orderItem->getQtyRefunded();

            if ($refundable != $item->getQty()) {
                return false;
            }
        }

        return true;
    }

    public function hasItemsLeftToRefund(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($creditmemo->getOrder()->getAllItems() as $item) {
            $refundable = $item->getQtyOrdered() - $item->getQtyRefunded();

            if ($refundable) {
                return true;
            }
        }

        return false;
    }

    public function methodSupportsPaymentFee($methodCode)
    {
        $supportedMethods = array(
            'mollie_klarnapaylater',
            'mollie_klarnapaynow',
            'mollie_klarnasliceit',
        );

        return in_array($methodCode, $supportedMethods);
    }
}
