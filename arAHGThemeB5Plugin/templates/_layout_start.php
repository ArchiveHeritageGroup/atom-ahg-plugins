<!DOCTYPE html>
<html lang="<?php echo $sf_user->getCulture(); ?>"
      dir="<?php echo sfCultureInfo::getInstance($sf_user->getCulture())->direction; ?>"
      media="<?php echo isset($_GET['media']) ? htmlspecialchars($_GET['media'], ENT_QUOTES, 'UTF-8') : 'screen'; ?>">
  <head>
    <?php echo get_partial('default/googleAnalytics'); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include_title(); ?>
    <?php echo get_component('default', 'tagManager', ['code' => 'script']); ?>
    <?php if (file_exists($staticPath = sfConfig::get('app_static_path').DIRECTORY_SEPARATOR.'favicon.ico')) { ?>
      <?php $faviconLoc = sfConfig::get('app_static_alias').'/favicon.ico'; ?>
    <?php } else { ?>
      <?php $faviconLoc = public_path('favicon.ico'); ?>
    <?php } ?>
    <link rel="shortcut icon" href="<?php echo $faviconLoc; ?>">
    <?php
    // Load Dominion base assets first
    ?>
    <script defer src="/dist/js/vendor.bundle.0f9010a8851c963bf1c1.js"></script>
    <script defer src="/dist/js/arDominionB5Plugin.bundle.f35dfa6a8681aaac95aa.js"></script>
    <link href="/dist/css/arDominionB5Plugin.bundle.a287608095b6ba1a60b5.css" rel="stylesheet">
    <?php
    // Find and load AHG assets dynamically
    $distCss = sfConfig::get('sf_web_dir') . '/dist/css';
    $distJs = sfConfig::get('sf_web_dir') . '/dist/js';
    
    if (is_dir($distCss)) {
        foreach (glob($distCss . '/arAHGThemeB5Plugin.bundle.*.css') as $file) {
            if (strpos($file, '.map') === false) {
                echo '<link href="/dist/css/' . basename($file) . '" rel="stylesheet">';
                break;
            }
        }
    }
    
    if (is_dir($distJs)) {
        foreach (glob($distJs . '/arAHGThemeB5Plugin.bundle.*.js') as $file) {
            if (strpos($file, '.map') === false && strpos($file, '.LICENSE') === false) {
                echo '<script defer src="/dist/js/' . basename($file) . '"></script>';
                break;
            }
        }
    }
    ?>
    <?php echo get_component_slot('css'); ?>
  </head>
  <body class="d-flex flex-column min-vh-100 <?php echo $sf_context->getModuleName(); ?> <?php echo $sf_context->getActionName(); ?><?php echo sfConfig::get('app_show_tooltips') ? ' show-edit-tooltips' : ''; ?>">
    <?php echo get_component('default', 'tagManager', ['code' => 'noscript']); ?>
    <?php echo get_partial('header'); ?>
    <?php include_slot('pre'); ?>
