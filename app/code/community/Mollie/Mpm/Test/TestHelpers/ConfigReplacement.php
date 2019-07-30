<?php
class Mollie_Mpm_Test_TestHelpers_ConfigReplacement extends Mage_Core_Model_Config
{
    /**
     * @var array
     */
    private $modelMocks = [];

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
}
