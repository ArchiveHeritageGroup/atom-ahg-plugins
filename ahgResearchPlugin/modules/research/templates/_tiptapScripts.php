<?php
/**
 * _tiptapScripts.php - Partial to load TipTap CDN scripts + local CSS/JS
 * Include once per template: <?php include_partial('research/tiptapScripts') ?>
 */
$_nonce = sfConfig::get('csp_nonce', '');
$_na = $_nonce ? preg_replace('/^nonce=/', 'nonce="', $_nonce) . '"' : '';
?>
<!-- TipTap CSS -->
<link rel="stylesheet" href="/plugins/ahgResearchPlugin/web/css/research-tiptap.css">
<!-- TipTap CDN -->
<script src="https://cdn.jsdelivr.net/npm/@tiptap/core@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/starter-kit@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-text-style@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-image@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-underline@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-text-align@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-color@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-highlight@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-link@2.11.7/dist/index.umd.js" <?php echo $_na; ?>></script>
<!-- Research TipTap Wrapper -->
<script src="/plugins/ahgResearchPlugin/web/js/research-tiptap.js" <?php echo $_na; ?>></script>
