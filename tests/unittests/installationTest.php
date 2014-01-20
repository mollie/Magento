<?php
	/**
	 * @covers Mollie_Mpm_Model_Api
	 */
class InstallationTest extends MagentoPlugin_TestCase
{
	public function getVersion()
	{
		$xml = simplexml_load_file(PROJECT_ROOT . "/app/code/community/Mollie/Mpm/etc/config.xml");
		return $xml->modules->Mollie_Mpm->version->__toString();
	}

	public function testInstallationFilesNamedCorrectly()
	{
		$version = $this->getVersion();

		$this->assertFileExists(PROJECT_ROOT . "/app/code/community/Mollie/Mpm/sql/mpm_setup/mysql4-install-{$version}.php");
		$this->assertFileExists(PROJECT_ROOT . "/app/code/community/Mollie/Mpm/sql/mpm_setup/mysql4-uninstall-{$version}.php");
	}
}