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

/**
 * Copyright (c) 2012-2014, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS\'\' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category    Mollie
 * @package     Mollie_Mpm
 * @author      Mollie B.V. (info@mollie.nl)
 * @copyright   Copyright (c) 2012-2014 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Berkeley Software Distribution License (BSD-License 2)
 **/

class Mollie_Mpm_Model_Void'.$I.' extends Mollie_Mpm_Model_Api
{
	/**
	 * @var string
	 */
	protected $_code = "mpm_void_'.$I.'";

	/**
	 * @var int
	 */
	protected $_index = '.$i.';
}
';
	if ($mkfiles)
	{
		file_put_contents($directory.'/Void'.$I.'.php', $file_content);
	}
}
echo $result;
