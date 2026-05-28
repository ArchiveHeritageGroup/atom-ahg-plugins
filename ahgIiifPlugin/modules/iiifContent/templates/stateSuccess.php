<?php
/**
 * stateSuccess
 * Fallback HTML view for content state (when no object match found).
 * Shows the decoded state as formatted JSON with a "Open in Viewer" button.
 */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>
<?php echo get_partial('layout_start', ['title' => 'Content State']) ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">IIIF Content State</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-share-alt me-2"></i>Shared View</span>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($format ?? 'long'); ?></span>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h5 class="card-title">Content State</h5>
                <pre class="bg-dark text-light p-3 rounded" style="max-height:400px;overflow:auto;font-size:0.85rem;"><?php
                    echo htmlspecialchars(json_encode($contentState ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                ?></pre>
            </div>

            <?php if (!empty($contentState['canonical']['source']['id'])): ?>
            <div class="mt-3">
                <a href="<?php echo htmlspecialchars($contentState['canonical']['source']['id']); ?>"
                   class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Open in Viewer
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script <?php echo $nonceAttr; ?>>
(function() {
    // Auto-apply content state to any IIIF viewer on the page
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('state') || urlParams.get('cs');
    if (token) {
        // Remove token from URL to keep URL clean
        urlParams.delete('state');
        urlParams.delete('cs');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }
})();
</script>

<?php echo get_partial('layout_end') ?>
