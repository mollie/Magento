<?php

if (strpos(__DIR__, '.modman') !== false) {
    require_once(dirname(__DIR__) . '/../../../../../../../../../app/Mage.php');
} elseif (strpos(__DIR__, 'vendor') !== false) {
    require_once(dirname(__DIR__) . '/../../../../../../../../../src/app/Mage.php');
} else {
    require_once(__DIR__ . '/../../../../../../../../Mage.php');
}
ini_set('display_errors', true);
error_reporting(-1);