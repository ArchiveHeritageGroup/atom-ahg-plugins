<?php
/**
 * Auth 2.0 — Access Token iframe response
 * Sends token data to parent window via postMessage.
 *
 * @var string $tokenData JSON-encoded token data
 * @var string $origin Target origin for postMessage
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<!DOCTYPE html>
<html>
<head><title>IIIF Auth Token</title></head>
<body>
<script<?php echo $nonceAttr; ?>>
(function() {
    var tokenData = <?php echo $tokenData; ?>;
    var origin = <?php echo json_encode($origin); ?>;
    if (window.parent && window.parent !== window) {
        window.parent.postMessage(tokenData, origin === '*' ? '*' : origin);
    }
})();
</script>
</body>
</html>
