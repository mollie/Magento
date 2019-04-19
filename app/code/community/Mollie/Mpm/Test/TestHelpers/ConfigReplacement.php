<?php
class Mollie_Mpm_Test_TestHelpers_ConfigReplacement extends Mage_Core_Model_Config
{
    /**
     * @var array
     */
    private $modelMocks = [];

    /**
     * @var array
     */
    private $nodeMockValues = [];

    public function addModelMock($class, $mock)
    {
        $this->modelMocks[$class] = $mock;
    }

    public function getModelInstance($modelClass = '', $constructArguments = array())
    {
        if (isset($this->modelMocks[$modelClass])) {
            return $this->modelMocks[$modelClass];
        }

        return parent::getModelInstance($modelClass, $constructArguments);
    }

    public function setNode($path, $value)
    {
        $this->nodeMockValues[$path] = $value;
    }

    public function getNode($path = null, $scope = '', $scopeCode = null)
    {
        if (isset($this->nodeMockValues[$path])) {
            return $this->nodeMockValues[$path];
        }

        return parent::getNode($path, $scope, $scopeCode);
    }

    public function setConfigValue($path, $value)
    {
        $this->nodeMockValues['stores/admin/' . $path] = $value;
    }
}