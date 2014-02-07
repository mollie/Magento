<?php // Use this script in cli mode to generate config.xml default->payment section and mpm/model files

if ($argc < 2)
{
	die ("Usage: generate_payment_slots.php [amount] [directory] or generate_payment_slots.php [amount]");
}
$result = '';
$mkfiles = false;

$amount = (int) $argv[1];
if ($argc >= 3) {
	$directory = $argv[2];
	if (is_dir($directory))
	{
		$mkfiles = true;
	}
}

for ($i = 0; $i < $amount; $i++)
{
	$I = ($i < 10) ? '0'.$i : $i;
	$result .= '
		<mpm_void_'.$I.' translate="title" module="Mollie_Mpm">
			<group>mollie</group>
			<active>1</active>
			<sort_order>'.(-$amount + $i).'</sort_order>
			<model>mpm/void'.$I.'</model>
			<currency>EUR</currency>
		</mpm_void_'.$I.'>
	';
	$file_content = '<?php

class Mollie_Mpm_Model_Void'.$I.' extends Mollie_Mpm_Model_Api
{
	protected $_code = "mpm_void_'.$I.'";
	protected $_index = '.$i.';
}
';
	if ($mkfiles)
	{
		file_put_contents($directory.'/Void'.$I.'.php', $file_content);
	}
}
echo $result;