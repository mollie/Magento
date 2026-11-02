<?php

class Mollie_Mpm_Test_Model_Method_DiscontinuedMethodsTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public static function discontinuedMethodProvider()
    {
        return array(
            array('mollie_bitcoin'),
            array('mollie_giropay'),
            array('mollie_inghomepay'),
            array('mollie_sofort'),
            array('mollie_klarnapaylater'),
            array('mollie_klarnapaynow'),
            array('mollie_klarnasliceit'),
        );
    }

    /**
     * @dataProvider discontinuedMethodProvider
     */
    public function testItStillLoadsTheMethodForExistingOrders($code)
    {
        $method = Mage::helper('payment')->getMethodInstance($code);

        $this->assertInstanceOf('Mollie_Mpm_Model_Method_Abstract', $method);
    }

    /**
     * @dataProvider discontinuedMethodProvider
     */
    public function testItCannotBeUsedForNewOrders($code)
    {
        $method = Mage::helper('payment')->getMethodInstance($code);

        $this->assertFalse($method->canUseCheckout());
        $this->assertFalse($method->canUseInternal());
        $this->assertFalse($method->canUseForMultishipping());
    }

    /**
     * @dataProvider discontinuedMethodProvider
     */
    public function testItHasNoAdminConfiguration($code)
    {
        $groups = Mage::getSingleton('adminhtml/config')->getSection('payment')->groups;

        $this->assertFalse(isset($groups->$code));
    }
}
