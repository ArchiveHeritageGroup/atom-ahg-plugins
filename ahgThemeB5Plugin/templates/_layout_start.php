<!DOCTYPE html>
<html lang="<?php echo $sf_user->getCulture(); ?>" dir="<?php echo sfCultureInfo::getInstance($sf_user->getCulture())->direction; ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    // SECURITY (M12): publish the CSRF token and auto-attach it as the
    // X-CSRF-TOKEN header to every same-origin, unsafe-method AJAX request
    // (fetch / XMLHttpRequest / jQuery). This covers all AhgController AJAX
    // endpoints centrally, without editing each call site. Enforcement is
    // governed by the `csrf_enforcement` setting (off | log | enforce).
    if (class_exists('\AtomFramework\Services\CsrfService')) {
        echo \AtomFramework\Services\CsrfService::getMetaTag();
        $csrfNonce = sfConfig::get('csp_nonce', '');
        $csrfNonceAttr = $csrfNonce ? ' '.preg_replace('/^nonce=/', 'nonce="', $csrfNonce).'"' : '';
        $csrfJs = <<<'CSRFJS'
(function(){var m=document.querySelector('meta[name="csrf-token"]');var t=m?m.getAttribute('content'):'';if(!t)return;var u=/^(POST|PUT|DELETE|PATCH)$/i;function so(x){try{return new URL(x,window.location.href).origin===window.location.origin;}catch(e){return true;}}if(window.fetch){var f=window.fetch;window.fetch=function(i,n){n=n||{};var url=(typeof i==='string')?i:(i&&i.url)||'';var mth=n.method||(typeof i==='object'&&i.method)||'GET';if(u.test(mth)&&so(url)){var h=new Headers(n.headers||(typeof i==='object'&&i.headers)||{});if(!h.has('X-CSRF-TOKEN'))h.set('X-CSRF-TOKEN',t);n.headers=h;}return f.call(this,i,n);};}var o=XMLHttpRequest.prototype.open,s=XMLHttpRequest.prototype.send;XMLHttpRequest.prototype.open=function(m2,url){this.__csrf=u.test(m2||'')&&so(url||'');return o.apply(this,arguments);};XMLHttpRequest.prototype.send=function(){if(this.__csrf){try{this.setRequestHeader('X-CSRF-TOKEN',t);}catch(e){}}return s.apply(this,arguments);};function af(fm){if(!fm||fm.tagName!=='FORM')return;if((fm.getAttribute('method')||'GET').toUpperCase()!=='POST')return;if(!so(fm.getAttribute('action')||window.location.href))return;if(fm.querySelector('input[name="_ahg_csrf_token"]'))return;if(fm.querySelector('input[name="_csrf_token"]'))return;var el=document.createElement('input');el.type='hidden';el.name='_ahg_csrf_token';el.value=t;fm.appendChild(el);}document.addEventListener('submit',function(e){af(e.target);},true);var fsub=HTMLFormElement.prototype.submit;HTMLFormElement.prototype.submit=function(){af(this);return fsub.apply(this,arguments);};})();
CSRFJS;
        echo "\n    <script{$csrfNonceAttr}>{$csrfJs}</script>";
    }
    ?>
    <?php include_title(); ?>
    <?php echo get_component('default', 'tagManager', ['code' => 'script']); ?>
    <link rel="shortcut icon" href="<?php echo public_path('favicon.ico'); ?>">
    <?php
    // Dynamically find webpack bundles (no hardcoded hashes)
    $distPath = sfConfig::get('sf_web_dir').'/dist';
    $bundleErrors = [];

    // Vendor JS bundle
    $vendorJs = glob($distPath.'/js/vendor.bundle.*.js');
    if (!empty($vendorJs)) {
        echo '<script defer src="/dist/js/'.basename($vendorJs[0]).'"></script>';
    } else {
        $bundleErrors[] = 'vendor.bundle.*.js not found in '.$distPath.'/js/';
    }

    // Theme JS bundle
    $themeJs = glob($distPath.'/js/ahgThemeB5Plugin.bundle.*.js');
    if (!empty($themeJs)) {
        echo '<script defer src="/dist/js/'.basename($themeJs[0]).'"></script>';
    } else {
        $bundleErrors[] = 'ahgThemeB5Plugin.bundle.*.js not found in '.$distPath.'/js/';
    }

    // Theme CSS bundle
    $themeCss = glob($distPath.'/css/ahgThemeB5Plugin.bundle.*.css');
    if (!empty($themeCss)) {
        echo '<link href="/dist/css/'.basename($themeCss[0]).'" rel="stylesheet">';
    } else {
        $bundleErrors[] = 'ahgThemeB5Plugin.bundle.*.css not found in '.$distPath.'/css/';
    }

    // Log errors if any bundles are missing
    if (!empty($bundleErrors)) {
        error_log('ahgThemeB5Plugin: Bundle loading errors - '.implode('; ', $bundleErrors));
    }
    ?>
    <?php echo get_component_slot('css'); ?>
  </head>
  <body class="d-flex flex-column min-vh-100 <?php echo $sf_context->getModuleName(); ?> <?php echo $sf_context->getActionName(); ?><?php echo sfConfig::get('app_show_tooltips') ? ' show-edit-tooltips' : ''; ?>">
    <?php echo get_component('default', 'tagManager', ['code' => 'noscript']); ?>
    <?php echo get_partial('header'); ?>
    <?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates/_accessibilityHelpers.php'); ?>
    <?php include_slot('pre'); ?>
