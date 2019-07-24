<?php

class Mollie_Mpm_Test_Helper_DataTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function generatesTheCorrectPaymentDescriptionProvider()
    {
        return [
            ['{storename} - {ordernumber} Order', 'Store Name - Default - 999 Order'],
            ['{customerCompany} Order', 'Acme Company Order'],
            ['{customerName} Order', 'John I. Doe Order'],
        ];
    }

    /**
     * @dataProvider generatesTheCorrectPaymentDescriptionProvider
     */
    public function testGeneratesTheCorrectPaymentDescription($description, $expected)
    {
        Mage::app()->getStore()->setConfig('payment/mollie_ideal/payment_description', $description);

        $order = Mage::getModel('sales/order');
        $order->setIncrementId(999);

        $address = Mage::getModel('sales/quote_address');
        $address->setAddressType('billing');
        $address->setCompany('Acme Company');
        $address->setFirstname('John');
        $address->setMiddlename('I.');
        $address->setLastname('Doe');

        $addressList = $order->getAddressesCollection();
        $addressList->addItem($address);

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getPaymentDescription('ideal', $order);

        $this->assertEquals($expected, $result);
    }

    public function trimsTheCustomerNameCorrectProvider()
    {
        return [
            'fullname' => [['John', 'I.', 'Doe'], 'John I. Doe'],
            'no middlename' => [['John', null, 'Doe'], 'John Doe'],
            'no middle and lastname' => [['John', null, null], 'John'],
            'only lastname' => [[null, null, 'Doe'], 'Doe'],
        ];
    }

    /**
     * @dataProvider trimsTheCustomerNameCorrectProvider
     */
    public function testTrimsTheCustomerNameCorrect($names, $expected)
    {
        Mage::app()->getStore()->setConfig('payment/mollie_ideal/payment_description', '{customerName}');

        $order = Mage::getModel('sales/order');
        $order->setIncrementId(999);

        $address = Mage::getModel('sales/quote_address');
        $address->setAddressType('billing');
        $address->setFirstname($names[0]);
        $address->setMiddlename($names[1]);
        $address->setLastname($names[2]);

        $addressList = $order->getAddressesCollection();
        $addressList->addItem($address);

        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');

        $result = $instance->getPaymentDescription('ideal', $order);

        $this->assertEquals($expected, $result);
    }
}
