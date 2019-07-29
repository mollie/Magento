<?php

class Mollie_Mpm_Test_Helper_DataTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        Mage::app()->getStore()->setConfig(\Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, 'en_GB');
    }

    public function testGetLocaleCodeWithFixedLocale()
    {
        Mage::app()->getStore()->setConfig(\Mollie_Mpm_Helper_Data::XPATH_LOCALE, 'en_US');

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    public function testGetLocaleCodeWithAutomaticDetectionAndAValidLocale()
    {
        Mage::app()->getStore()->setConfig(\Mollie_Mpm_Helper_Data::XPATH_LOCALE, '');

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    public function testGetLocaleCodeWithAutomaticDetectionAndAInvalidLocale()
    {
        Mage::app()->getStore()->setConfig(\Mollie_Mpm_Helper_Data::XPATH_LOCALE, '');

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    public function testGetLocaleCodeBasedOnTheStoreLocaleWithAValidValue()
    {
        Mage::app()->getStore()->setConfig(\Mollie_Mpm_Helper_Data::XPATH_LOCALE, 'store');

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    public function testGetLocaleCanReturnNull()
    {
        Mage::app()->getStore()->setConfig(\Mollie_Mpm_Helper_Data::XPATH_LOCALE, '');

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getLocaleCode(null, 'payment');

        $this->assertNull($result);
    }
}
