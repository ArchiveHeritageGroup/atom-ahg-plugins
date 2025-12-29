<?php

$helperDir = dirname(__FILE__).'/../lib/helper/';

// Load all plugin helpers
$helpers = [
    'AhgLaravelHelper.php',
    'AhgMediaHelper.php',
    'DigitalObjectViewerHelper.php',
    'IiifViewerHelper.php',
    'informationobjectHelper.php',
    'MediaHelper.php',
    'QubitHelper.php',
];

foreach ($helpers as $helper) {
    $path = $helperDir . $helper;
    if (file_exists($path)) {
        require_once $path;
    }
}
