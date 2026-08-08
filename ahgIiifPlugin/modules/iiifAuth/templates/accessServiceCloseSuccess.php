<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .iiif-font-family-arial-sans-serif-6349 { font-family:Arial,sans-serif;text-align:center;padding:40px; }
</style>
<?php
/**
 * Auth 2.0 — Access service close page
 * Displayed after successful authentication. Closes the tab.
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<!DOCTYPE html>
<html>
<head><title>Authentication Complete</title></head>
<body class="iiif-font-family-arial-sans-serif-6349">
<h2>Authentication successful</h2>
<p>This window will close automatically.</p>
<script<?php echo $nonceAttr; ?>>
(function() {
    setTimeout(function() { window.close(); }, 500);
})();
</script>
</body>
</html>
