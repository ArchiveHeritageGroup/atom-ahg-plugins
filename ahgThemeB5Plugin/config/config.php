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

// Helper directories, in order. ahgCorePlugin is searched as well as the theme's
// own: several helpers the theme's templates call (ahg_get_subject_access_points
// and friends) used to be required by absolute path from ahgUiOverridesPlugin,
// which fatalled on any site that did not have that plugin. They now live in
// core, which the theme can rely on, and every function there is guarded with
// function_exists so the original copy may still load alongside.
$helperDirs = [
    $helperDir,
    sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/helper/',
];

foreach ($helpers as $helper) {
    foreach ($helperDirs as $dir) {
        $path = $dir . $helper;

        if (file_exists($path)) {
            require_once $path;

            break;
        }
    }
}
