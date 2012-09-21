<?php

class failTemplateTest extends MagentoPlugin_TestCase
{
	const ORDER_ID = 1337;

	const ORDER_INCREMENT_ID = 100000001;

	public function testGeneratedHtml()
	{
		$template = $this->getMock("MagentoTemplate", array("getOrder", "getBanks"));

		$order = $this->getMock("Mage_Sales_Model_Order", array("getId", "getRealOrderId", "getGrandTotal"));

		$order->expects($this->never())
			->method("getRealOrderId");

		$order->expects($this->atLeastOnce())
			->method("getId")
			->will($this->returnValue(self::ORDER_ID));

		$template->expects($this->atLeastOnce())
			->method("getOrder")
			->will($this->returnValue($order));

		$template->expects($this->atLeastOnce())
			->method("getBanks")
			->will($this->returnValue(array (
			'1234' => 'Test bank 1',
			'0678' => 'Test bank 2'
		)));

		ob_start();
		$template->render("app/design/frontend/base/default/template/mollie/page/fail.phtml");
		$html = ob_get_clean(); ;

		$this->assertContains('<input type="hidden" name="order_id" value="1337" />', $html);
		$this->assertContains('<select name="bank_id"', $html);
		$this->assertContains('<option value="1234">Test bank 1</option>', $html);
		$this->assertContains('<option value="0678">Test bank 2</option>', $html);
	}
}

/**
 * Only here for some testing.
 *
 * @ignore
 */
class MagentoTemplate
{
	protected function getForm()
	{
		return "/path/to/form";
	}

	public function __($text)
	{
		return $text;
	}

	protected function escapeHtml($plaintext)
	{
		return htmlspecialchars($plaintext, ENT_QUOTES, "UTF-8");
	}

	public function render($filename)
	{
		include PROJECT_ROOT . DIRECTORY_SEPARATOR . $filename;
	}
}