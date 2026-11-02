<?php

class Mollie_Mpm_Test_PaymentMethodSortOrderTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testItSortsThePaymentMethodsInTheCheckoutAlphabetically()
    {
        $methods = array();
        foreach ($this->getMollieMethodDefaults() as $code => $defaults) {
            $methods[$code] = array((int)$defaults->sort_order, (string)$defaults->title);
        }

        $this->assertSame($this->sortByTitle($methods), $this->sortBySortOrder($methods));
    }

    public function testItSortsThePaymentMethodsInTheAdminAlphabetically()
    {
        $defaults = $this->getMollieMethodDefaults();
        $methods = array();
        foreach (Mage::getSingleton('adminhtml/config')->getSection('payment')->groups->children() as $code => $group) {
            if (isset($defaults[$code])) {
                $methods[$code] = array((int)$group->sort_order, (string)$defaults[$code]->title);
            }
        }

        $this->assertGreaterThan(20, count($methods));
        $this->assertSame($this->sortByTitle($methods), $this->sortBySortOrder($methods));
    }

    /**
     * Read from the module's config.xml: the merged config also contains sort orders a store saved itself.
     */
    private function getMollieMethodDefaults()
    {
        $moduleConfig = new Varien_Simplexml_Config(Mage::getConfig()->getModuleDir('etc', 'Mollie_Mpm') . DS . 'config.xml');
        $defaults = array();
        foreach ($moduleConfig->getNode('default/payment')->children() as $code => $method) {
            if (strpos($code, 'mollie_') === 0 && isset($method->title)) {
                $defaults[$code] = $method;
            }
        }

        return $defaults;
    }

    private function sortBySortOrder(array $methods)
    {
        uasort($methods, function (array $first, array $second) {
            return $first[0] - $second[0];
        });

        return array_keys($methods);
    }

    private function sortByTitle(array $methods)
    {
        uasort($methods, function (array $first, array $second) {
            return strcasecmp($first[1], $second[1]);
        });

        return array_keys($methods);
    }
}
