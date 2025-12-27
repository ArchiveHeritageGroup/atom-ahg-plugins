<?php
// Find dist bundles dynamically
$distCss = sfConfig::get('sf_web_dir') . '/dist/css';
$distJs = sfConfig::get('sf_web_dir') . '/dist/js';

$ahgCss = '';
$ahgJs = '';
$vendorJs = '';

// Find AHG CSS bundle
if (is_dir($distCss)) {
    foreach (glob($distCss . '/arAHGThemeB5Plugin.bundle.*.css') as $file) {
        if (!strpos($file, '.map')) {
            $ahgCss = '/dist/css/' . basename($file);
            break;
        }
    }
}

// Find JS bundles
if (is_dir($distJs)) {
    foreach (glob($distJs . '/arAHGThemeB5Plugin.bundle.*.js') as $file) {
        if (!strpos($file, '.map') && !strpos($file, '.LICENSE')) {
            $ahgJs = '/dist/js/' . basename($file);
            break;
        }
    }
    foreach (glob($distJs . '/vendor.bundle.*.js') as $file) {
        if (!strpos($file, '.map') && !strpos($file, '.LICENSE')) {
            $vendorJs = '/dist/js/' . basename($file);
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $sf_user->getCulture(); ?>" dir="<?php echo sfCultureInfo::getInstance($sf_user->getCulture())->direction; ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include_title(); ?>
    <?php echo get_component('default', 'tagManager', ['code' => 'script']); ?>
    <link rel="shortcut icon" href="<?php echo public_path('favicon.ico'); ?>">
    <link rel="stylesheet" href="/plugins/arAHGThemeB5Plugin/css/ahg-settings.css">
    <link rel="stylesheet" href="/plugins/arAHGThemeB5Plugin/css/ahg-generated.css">
    <?php if ($vendorJs): ?><script defer src="<?php echo $vendorJs; ?>"></script><?php endif; ?>
    <?php if ($ahgJs): ?><script defer src="<?php echo $ahgJs; ?>"></script><?php endif; ?>
    <?php if ($ahgCss): ?><link href="<?php echo $ahgCss; ?>" rel="stylesheet"><?php endif; ?>
    <?php echo get_component_slot('css'); ?>
  </head>
  <body class="d-flex flex-column min-vh-100 <?php echo $sf_context->getModuleName(); ?> <?php echo $sf_context->getActionName(); ?>">
    <?php echo get_component('default', 'tagManager', ['code' => 'noscript']); ?>
    <?php echo get_partial('header'); ?>
    <?php include_slot('pre'); ?>
