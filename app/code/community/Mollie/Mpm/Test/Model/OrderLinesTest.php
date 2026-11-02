<?php

class Mollie_Mpm_Test_Model_OrderLinesTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    public function testItKeepsSpecialCharactersInProductNames()
    {
        $order = $this->createOrder();
        $order->addItem($this->createItem('Süßkartoffel'));

        $names = $this->getLineNames($order);

        $this->assertSame('Süßkartoffel', $names[0]);
    }

    public function testItKeepsNonLatinCharactersInProductNames()
    {
        $order = $this->createOrder();
        $order->addItem($this->createItem('Łódź Ζεύς Матрёшка'));

        $names = $this->getLineNames($order);

        $this->assertSame('Łódź Ζεύς Матрёшка', $names[0]);
    }

    public function testItKeepsCombiningAccentsInDecomposedProductNames()
    {
        $order = $this->createOrder();
        $order->addItem($this->createItem("Ku\u{0308}che"));

        $names = $this->getLineNames($order);

        $this->assertSame("Ku\u{0308}che", $names[0]);
    }

    public function testItKeepsSpecialCharactersInBundleChildNames()
    {
        $order = $this->createOrder();
        $bundle = $this->createItem('Bundle für Känguru')->setProductType(Mage_Catalog_Model_Product_Type::TYPE_BUNDLE);
        $bundle->addChildItem($this->createItem('Känguru'));
        $order->addItem($bundle);

        $names = $this->getLineNames($order);

        $this->assertSame(array('Bundle für Känguru', 'Känguru'), array_slice($names, 0, 2));
    }

    public function testItKeepsSpecialCharactersInTheShippingDescription()
    {
        $order = $this->createOrder()->setShippingDescription('Versand - Österreich');
        $order->addItem($this->createItem('Product'));

        $names = $this->getLineNames($order);

        $this->assertSame('Versand - Österreich', $names[1]);
    }

    public function testItStillRemovesPunctuationFromProductNames()
    {
        $order = $this->createOrder();
        $order->addItem($this->createItem('T-shirt (XL) & "cap"'));

        $names = $this->getLineNames($order);

        $this->assertSame('T-shirt XL  cap', $names[0]);
    }

    public function testItFallsBackToAsciiCharactersWhenTheNameIsNotValidUtf8()
    {
        $order = $this->createOrder();
        $order->addItem($this->createItem("Caf\xE9 latte"));

        $names = $this->getLineNames($order);

        $this->assertSame('Caf latte', $names[0]);
    }

    private function getLineNames(Mage_Sales_Model_Order $order)
    {
        /** @var Mollie_Mpm_Model_OrderLines $orderLines */
        $orderLines = Mage::getModel('mpm/orderLines');

        return array_map(
            function (array $line) {
                return $line['name'];
            },
            $orderLines->getOrderLines($order)
        );
    }

    private function createOrder()
    {
        return Mage::getModel('sales/order')
            ->setStoreId(Mage::app()->getDefaultStoreView()->getId())
            ->setOrderCurrencyCode('EUR')
            ->setBaseCurrencyCode('EUR')
            ->setShippingDescription('Flat Rate - Fixed');
    }

    private function createItem($name)
    {
        return Mage::getModel('sales/order_item')
            ->setName($name)
            ->setSku('sku')
            ->setProductType(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
            ->setProduct(Mage::getModel('catalog/product')->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE))
            ->setQtyOrdered(1)
            ->setRowTotalInclTax(10)
            ->setBaseRowTotalInclTax(10)
            ->setTaxPercent(21);
    }
}
