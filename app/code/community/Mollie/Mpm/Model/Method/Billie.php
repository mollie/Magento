<?php

class Mollie_Mpm_Model_Method_Billie extends Mollie_Mpm_Model_Method_Abstract
{
    const METHOD_CODE = 'mollie_billie';
    const PAYMENT_METHOD = 'Billie';

    protected $_code = self::METHOD_CODE;

    protected $_paymentMethod = self::PAYMENT_METHOD;

    /**
     * Billie is for business customers only: Mollie requires a company name on the billing address.
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if ($quote !== null && !$this->hasBillingCompany($quote)) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    private function hasBillingCompany(Mage_Sales_Model_Quote $quote)
    {
        return trim((string)$quote->getBillingAddress()->getCompany()) !== '';
    }
}
