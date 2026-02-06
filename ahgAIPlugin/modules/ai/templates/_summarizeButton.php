<?php
/**
 * AI Summarize Button - Include on information object show page
 * Usage: include_component('ahgNer', 'summarizeButton', ['resource' => $resource])
 */
?>
<div class="ai-summarize-section mb-3">
    <button type="button" class="btn btn-outline-info w-100" id="aiSummarizeBtn" onclick="generateSummary(<?php echo $resource->id ?>)">
        <i class="bi bi-file-text me-1"></i>Generate Summary (AI)
    </button>
    <div id="summaryResult" class="mt-2" style="display: none;"></div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function generateSummary(objectId) {
    const btn = document.getElementById('aiSummarizeBtn');
    const resultDiv = document.getElementById('summaryResult');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating summary...';
    resultDiv.style.display = 'none';
    
    fetch(`/ner/summarize/${objectId}`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-file-text me-1"></i>Generate Summary (AI)';
            
            if (!data.success) {
                resultDiv.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i>${data.error || 'Summary generation failed'}</div>`;
                resultDiv.style.display = 'block';
                return;
            }
            
            // Show success notification
            const savedMsg = data.saved ? 'Saved to Scope & Content' : 'Generated (not saved)';
            resultDiv.innerHTML = `
                <div class="alert alert-success py-2">
                    <i class="bi bi-check-circle me-1"></i><strong>${savedMsg}</strong>
                    <small class="d-block text-muted mt-1">
                        Source: ${data.source} | ${data.summary_length} chars | ${data.processing_time_ms}ms
                    </small>
                </div>
                <div class="card">
                    <div class="card-header py-2">
                        <small><i class="bi bi-file-text me-1"></i>Generated Summary</small>
                    </div>
                    <div class="card-body py-2">
                        <small>${data.summary}</small>
                    </div>
                </div>
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh Page
                    </button>
                </div>
            `;
            resultDiv.style.display = 'block';
            
            // Show browser notification if supported
            if (data.saved && Notification.permission === 'granted') {
                new Notification('Summary Generated', {
                    body: 'Scope & Content field has been updated.',
                    icon: '/plugins/ahgThemeB5Plugin/web/images/logo.png'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-file-text me-1"></i>Generate Summary (AI)';
            resultDiv.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i>${err.message}</div>`;
            resultDiv.style.display = 'block';
        });
}

// Request notification permission on load
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
</script>
