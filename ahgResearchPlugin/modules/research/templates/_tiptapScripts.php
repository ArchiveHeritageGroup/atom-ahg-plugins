<?php
/**
 * _tiptapScripts.php - Partial to load TipTap bundle + local CSS/JS
 * Include once per template: <?php include_partial('research/tiptapScripts') ?>
 */
$_nonce = sfConfig::get('csp_nonce', '');
$_na = $_nonce ? preg_replace('/^nonce=/', 'nonce="', $_nonce) . '"' : '';
?>
<!-- TipTap CSS -->
<link rel="stylesheet" href="/plugins/ahgResearchPlugin/web/css/research-tiptap.css">
<!-- TipTap Bundle (v2.27.2 — core + starter-kit + extensions, bundled with esbuild) -->
<script src="/plugins/ahgResearchPlugin/web/js/tiptap.bundle.min.js" <?php echo $_na; ?>></script>
<!-- Research TipTap Wrapper -->
<script src="/plugins/ahgResearchPlugin/web/js/research-tiptap.js" <?php echo $_na; ?>></script>
