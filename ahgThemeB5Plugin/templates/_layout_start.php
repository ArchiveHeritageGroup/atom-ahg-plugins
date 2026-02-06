<!DOCTYPE html>
<html lang="<?php echo $sf_user->getCulture(); ?>" dir="<?php echo sfCultureInfo::getInstance($sf_user->getCulture())->direction; ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include_title(); ?>
    <?php echo get_component('default', 'tagManager', ['code' => 'script']); ?>
    <link rel="shortcut icon" href="<?php echo public_path('favicon.ico'); ?>">
    <?php
    // Dynamically find webpack bundles (no hardcoded hashes)
    $distPath = sfConfig::get('sf_web_dir').'/dist';

    // Vendor JS bundle
    $vendorJs = glob($distPath.'/js/vendor.bundle.*.js');
    if (!empty($vendorJs)) {
        echo '<script defer src="/dist/js/'.basename($vendorJs[0]).'"></script>';
    }

    // Theme JS bundle
    $themeJs = glob($distPath.'/js/ahgThemeB5Plugin.bundle.*.js');
    if (!empty($themeJs)) {
        echo '<script defer src="/dist/js/'.basename($themeJs[0]).'"></script>';
    }

    // Theme CSS bundle
    $themeCss = glob($distPath.'/css/ahgThemeB5Plugin.bundle.*.css');
    if (!empty($themeCss)) {
        echo '<link href="/dist/css/'.basename($themeCss[0]).'" rel="stylesheet">';
    }
    ?>
    <?php echo get_component_slot('css'); ?>
  </head>
  <body class="d-flex flex-column min-vh-100 <?php echo $sf_context->getModuleName(); ?> <?php echo $sf_context->getActionName(); ?><?php echo sfConfig::get('app_show_tooltips') ? ' show-edit-tooltips' : ''; ?>">
    <?php echo get_component('default', 'tagManager', ['code' => 'noscript']); ?>
    <?php echo get_partial('header'); ?>
    <?php include_slot('pre'); ?>
