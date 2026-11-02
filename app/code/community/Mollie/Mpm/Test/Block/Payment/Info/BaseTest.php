<?php

class Mollie_Mpm_Test_Block_Payment_Info_BaseTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    /**
     * @var string[]
     */
    private $raisedErrors = array();

    protected function setUp(): void
    {
        parent::setUp();

        $this->raisedErrors = array();
        set_error_handler(function ($level, $message) {
            $this->raisedErrors[] = $message;

            return true;
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        parent::tearDown();
    }

    public function testItReturnsEmptyDetailsWhenThePaymentHasNoDetails()
    {
        $block = $this->createBlockWithDetails(null);

        $this->assertSame('', $block->getcardLabel());
        $this->assertNull($block->getConsumerName());
        $this->assertNull($block->getIban());
        $this->assertNull($block->getBic());
        $this->assertSame(array(), $this->raisedErrors);
    }

    public function testItReturnsEmptyDetailsWhenTheDetailsAreNotValidJson()
    {
        $block = $this->createBlockWithDetails('not json');

        $this->assertSame('', $block->getcardLabel());
        $this->assertNull($block->getConsumerName());
        $this->assertNull($block->getIban());
        $this->assertNull($block->getBic());
        $this->assertSame(array(), $this->raisedErrors);
    }

    public function testItReturnsEmptyDetailsWhenTheDetailsMissTheRequestedKeys()
    {
        $block = $this->createBlockWithDetails(json_encode(array('somethingElse' => 'value')));

        $this->assertSame('', $block->getcardLabel());
        $this->assertNull($block->getConsumerName());
        $this->assertNull($block->getIban());
        $this->assertNull($block->getBic());
        $this->assertSame(array(), $this->raisedErrors);
    }

    public function testItReturnsTheCardLabelFromTheDetails()
    {
        $block = $this->createBlockWithDetails(json_encode(array('cardLabel' => 'Mastercard')));

        $this->assertSame('Mastercard', $block->getcardLabel());
    }

    public function testItReturnsTheConsumerDetailsFromTheDetails()
    {
        $block = $this->createBlockWithDetails(json_encode(array(
            'consumerName' => 'T. TEST',
            'consumerAccount' => 'NL53INGB0654422370',
            'consumerBic' => 'INGBNL2A',
        )));

        $this->assertSame('T. TEST', $block->getConsumerName());
        $this->assertSame('NL53INGB0654422370', $block->getIban());
        $this->assertSame('INGBNL2A', $block->getBic());
    }

    private function createBlockWithDetails($details)
    {
        $info = Mage::getModel('payment/info');
        $info->setAdditionalInformation('details', $details);

        /** @var Mollie_Mpm_Block_Payment_Info_Base $block */
        $block = $this->getBlock('mpm/payment_info_base');
        $block->setInfo($info);

        return $block;
    }
}
