<?php

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;

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

    public function getLastRelevantStatusProvider()
    {
        return [
            [['expired'], 'expired'],
            [['expired', 'paid'], 'paid'],
            [['paid', 'expired'], 'paid'],
            [['authorized', 'paid', 'expired'], 'paid'],
        ];
    }

    /**
     * @param $statuses
     * @param $expected
     * @dataProvider getLastRelevantStatusProvider
     */
    public function testGetLastRelevantStatus($statuses, $expected)
    {
        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');
        $order = new Order($this->createMock(MollieApiClient::class));
        $order->_embedded = new \stdClass;
        $order->_embedded->payments = [];
        foreach ($statuses as $status) {
            $payment = new \stdClass;
            $payment->status = $status;
            $order->_embedded->payments[] = $payment;
        }

        $status = $instance->getLastRelevantStatus($order);
        $this->assertEquals($expected, $status);
    }

    public function testReturnsNullIfNoPaymentsAreAvailable()
    {
        /** @var Mollie_Mpm_Helper_Data $instance */
        $instance = Mage::helper('mpm');
        $order = new Order($this->createMock(MollieApiClient::class));
        $status = $instance->getLastRelevantStatus($order);
        $this->assertNull($status);
    }

    public function testGetInvoiceMomentReturnsAuthorizeForNonKlarnaMethods()
    {
        /** @var Mollie_Mpm_Helper_data $instance */
        $instance = Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = Mage::getModel('sales/order_payment');
        $payment->setMethod('mollie_ideal');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setPayment($payment);

        $this->assertEquals('authorize_paid_after_shipment', $instance->getInvoiceMoment($order));
    }

    public function testGetInvoiceMomentReturnsTheSettingIfTheMethodIsKlarna()
    {
        Mage::app()->getStore()->setConfig('payment/mollie_klarnasliceit/invoice_moment', 'authorize_paid_after_shipment');

        /** @var Mollie_Mpm_Helper_data $instance */
        $instance = Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = Mage::getModel('sales/order_payment');
        $payment->setMethod('mollie_klarnasliceit');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setPayment($payment);

        $this->assertEquals('authorize_paid_after_shipment', $instance->getInvoiceMoment($order));
    }

    /**
     * @param $invoiceMoment
     * @param $expected
     * @throws Mage_Core_Model_Store_Exception
     *
     * @testWith ["shipment", false]
     *           ["authorize_paid_before_shipment", true]
     *           ["authorize_paid_after_shipment", true]
     */
    public function testIsInvoiceMomentOnAuthorize($invoiceMoment, $expected)
    {
        Mage::app()->getStore()->setConfig('payment/mollie_klarnasliceit/invoice_moment', $invoiceMoment);

        /** @var Mollie_Mpm_Helper_data $instance */
        $instance = Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = Mage::getModel('sales/order_payment');
        $payment->setMethod('mollie_klarnasliceit');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setPayment($payment);

        $this->assertEquals($expected, $instance->isInvoiceMomentOnAuthorize($order));
    }

    /**
     * @testWith ["authorize_paid_before_shipment", "2"]
     *           ["authorize_paid_after_shipment", "1"]
     */
    public function testGetInvoiceMomentPaidStatus($invoiceMoment, $expected)
    {
        Mage::app()->getStore()->setConfig('payment/mollie_klarnasliceit/invoice_moment', $invoiceMoment);

        /** @var Mollie_Mpm_Helper_data $instance */
        $instance = Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = Mage::getModel('sales/order_payment');
        $payment->setMethod('mollie_klarnasliceit');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setPayment($payment);

        $this->assertEquals($expected, $instance->getInvoiceMomentPaidStatus($order));
    }

    public function testGetInvoiceMomentPaidStatusThrowsAnExceptionWhenAWrongMomentIsSet()
    {
        Mage::app()->getStore()->setConfig('payment/mollie_klarnasliceit/invoice_moment', 'shipment');

        /** @var Mollie_Mpm_Helper_data $instance */
        $instance = Mage::helper('mpm');

        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = Mage::getModel('sales/order_payment');
        $payment->setMethod('mollie_klarnasliceit');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->setPayment($payment);

        try {
            $instance->getInvoiceMomentPaidStatus($order);
            $this->fail('We expected an exception but got none');
        } catch (Exception $exception) {
            $this->assertEquals('Invoice moment not supported: shipment', $exception->getMessage());
        }
    }
}
