<?php

class Mollie_Mpm_Test_Helper_WebhookUrlTest extends Mollie_Mpm_Test_TestHelpers_TestCase
{
    const CUSTOM_URL = 'https://abc-def.trycloudflare.com/mpm/api/webhook/';

    public function testItReturnsTheDefaultWebhookUrlWhenNothingIsConfigured()
    {
        $this->assertStringContainsString('mpm/api/webhook', $this->getWebhookUrl());
    }

    public function testItReturnsTheDefaultWebhookUrlWhenWebhooksAreEnabled()
    {
        $this->givenConfig(Mollie_Mpm_Model_Adminhtml_System_Config_Source_WebhookUrlOptions::ENABLED, self::CUSTOM_URL, 'test');

        $this->assertStringContainsString('mpm/api/webhook', $this->getWebhookUrl());
    }

    public function testItReturnsTheCustomWebhookUrl()
    {
        $this->givenConfig(Mollie_Mpm_Model_Adminhtml_System_Config_Source_WebhookUrlOptions::CUSTOM_URL, self::CUSTOM_URL, 'live');

        $this->assertSame(self::CUSTOM_URL, $this->getWebhookUrl());
    }

    public function testItReturnsTheDefaultWebhookUrlWhenTheCustomUrlIsEmpty()
    {
        $this->givenConfig(Mollie_Mpm_Model_Adminhtml_System_Config_Source_WebhookUrlOptions::CUSTOM_URL, '  ', 'test');

        $this->assertStringContainsString('mpm/api/webhook', $this->getWebhookUrl());
    }

    public function testItReturnsNoWebhookUrlWhenDisabledInTestMode()
    {
        $this->givenConfig(Mollie_Mpm_Model_Adminhtml_System_Config_Source_WebhookUrlOptions::DISABLED, '', 'test');

        $this->assertSame('', $this->getWebhookUrl());
    }

    public function testItIgnoresDisabledWebhooksInLiveMode()
    {
        $this->givenConfig(Mollie_Mpm_Model_Adminhtml_System_Config_Source_WebhookUrlOptions::DISABLED, '', 'live');

        $this->assertStringContainsString('mpm/api/webhook', $this->getWebhookUrl());
    }

    private function givenConfig($useWebhooks, $customWebhookUrl, $modus)
    {
        $store = Mage::app()->getStore();
        $store->setConfig(Mollie_Mpm_Helper_Data::XPATH_USE_WEBHOOKS, $useWebhooks);
        $store->setConfig(Mollie_Mpm_Helper_Data::XPATH_CUSTOM_WEBHOOK_URL, $customWebhookUrl);
        $store->setConfig(Mollie_Mpm_Helper_Data::XPATH_API_MODUS, $modus);
    }

    private function getWebhookUrl()
    {
        /** @var Mollie_Mpm_Helper_Data $helper */
        $helper = Mage::helper('mpm');

        return $helper->getWebhookUrl();
    }
}
