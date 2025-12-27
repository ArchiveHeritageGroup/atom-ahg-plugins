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
    <script defer src="/dist/js/vendor.bundle.7fd4785c4754082db86b.js"></script><script defer src="/dist/js/arAHGThemeB5Plugin.bundle.5143f3ea019729b8aaa0.js"></script><link href="/dist/css/arAHGThemeB5Plugin.bundle.7e0f865c257625fdb5ba.css" rel="stylesheet">
    <?php echo get_component_slot('css'); ?>
  </head>
  <body class="d-flex flex-column min-vh-100 <?php echo $sf_context->getModuleName(); ?> <?php echo $sf_context->getActionName(); ?>">
    <?php echo get_component('default', 'tagManager', ['code' => 'noscript']); ?>
    <?php echo get_partial('header'); ?>
    <?php include_slot('pre'); ?>
