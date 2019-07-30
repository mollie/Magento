<?php
class Mollie_Mpm_Test_TestHelpers_TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        Mage::reset();

        Mage::app(
            'admin',
            'store',
            array('config_model' => 'Mollie_Mpm_Test_TestHelpers_ConfigReplacement')
        )->setResponse(new Mollie_Mpm_Test_TestHelpers_TestResponse);

        $this->getConfig()->setNode('global/log/core/writer_model', 'Mollie_Mpm_Test_TestHelpers_FakeLogWriter');
    }

    protected function loadController($class, $getParams = array(), $postParams = array())
    {
        $parts = explode('_', $class);
        $name = end($parts);
        require_once(__DIR__ . '/../../controllers/' . $name . '.php');

        $store = Mage::app()->getDefaultStoreView();
        $store->setConfig('web/url/redirect_to_base', false);
        $store->setConfig('web/url/use_store', false);

        Mage::app()->setCurrentStore($store->getCode());
        $this->registerSessions();

        $request = Mage::app()->getRequest();
        $request->setQuery($getParams);
        $request->setPost($postParams);

        $response = Mage::app()->getResponse();

        return new $class($request, $response);
    }

    public function registerSessions($modules = null)
    {
        foreach (array('core', 'customer', 'checkout', 'catalog', 'reports') as $module) {
            $class = $module . '/session';

            $session = $this->getMockBuilder(Mage::getConfig()->getModelClassName($class))
                ->disableOriginalConstructor()->getMock();

            $session->method('start')->willReturnSelf();
            $session->method('init')->willReturnSelf();

            $session->method('getMessages')->willReturn(
                Mage::getModel('core/message_collection')
            );

            $session->method('getSessionIdQueryParam')->willReturn(
                Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM
            );

            $this->setSingletonMock($class, $session);
            $this->addModelMock($class, $session);
        }

        $cookie = $this->createMock('Mage_Core_Model_Cookie');
        $cookie->method('get')->willReturn(serialize('dummy'));

        Mage::unregister('_singleton/core/cookie');
        Mage::register('_singleton/core/cookie', $cookie);

        $logVisitor = $this->createMock('Mage_Log_Model_Visitor');
        $this->addModelMock('log/visitor', $logVisitor);
    }

    public function setSingletonMock($modelClass, $mock)
    {
        $registryKey = '_singleton/' . $modelClass;
        Mage::unregister($registryKey);
        Mage::register($registryKey, $mock);
    }

    public function addModelMock($modelClass, $mock)
    {
        $this->getConfig()->addModelMock($modelClass, $mock);
    }

    public function getConfig()
    {
        return Mage::getConfig();
    }

    public function getLogMessages()
    {
        return Mollie_Mpm_Test_TestHelpers_FakeLogWriter::getMessages();
    }

    public function getHelper($name)
    {
        $helperClass = self::getConfig()->getHelperClassName($name);
        $mock = $this->createMock($helperClass);

        $registryKey = '_helper/' . $name;
        Mage::unregister($registryKey);
        Mage::register($registryKey, $mock);

        return $mock;
    }
}
