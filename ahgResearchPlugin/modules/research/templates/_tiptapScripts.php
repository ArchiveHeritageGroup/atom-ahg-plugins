<?php
/**
 * _tiptapScripts.php - Partial to load TipTap bundle + local CSS/JS
 * Include once per template: <?php include_partial('research/tiptapScripts') ?>
 */
$_nonce = sfConfig::get('csp_nonce', '');
$_na = $_nonce ? preg_replace('/^nonce=/', 'nonce="', $_nonce) . '"' : '';
$_nonceValue = $_nonce ? preg_replace('/^nonce=/', '', $_nonce) : '';
?>
<!-- TipTap CSS -->
<link rel="stylesheet" href="/plugins/ahgResearchPlugin/web/css/research-tiptap.css">
<!-- TipTap builds its own <style> element at editor construction. A script
     cannot inherit the page nonce, so a style it creates carries none and CSP
     refuses it under style-src - the same fault as the accessibility helper,
     but here the library supports a nonce (Editor option injectNonce, which its
     injectCSS() passes to the element), so the fix is to hand it one rather
     than to move the CSS out. Published here because the value is only
     available server-side. -->
<script <?php echo $_na; ?>>window.AHG_CSP_NONCE = <?php echo json_encode($_nonceValue); ?>;</script>
<!-- TipTap Bundle (v2.27.2 — core + starter-kit + extensions, bundled with esbuild) -->
<script src="/plugins/ahgResearchPlugin/web/js/tiptap.bundle.min.js" <?php echo $_na; ?>></script>
<!-- Research TipTap Wrapper -->
<script src="/plugins/ahgResearchPlugin/web/js/research-tiptap.js" <?php echo $_na; ?>></script>
