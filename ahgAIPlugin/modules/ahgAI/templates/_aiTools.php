<?php
/**
 * AI Tools Section - Combined NER + Summarization
 * Usage: include_component('ahgNer', 'aiTools', ['resource' => $resource])
 */
?>
<div class="ai-tools-section">
    <h5 class="mb-3"><i class="bi bi-cpu me-1"></i>AI Tools</h5>
    
    <!-- Generate Summary Button -->
    <div class="mb-2">
        <button type="button" class="btn btn-outline-info btn-sm w-100" id="aiSummarizeBtn" onclick="generateSummary(<?php echo $resource->id ?>)">
            <i class="bi bi-file-text me-1"></i>Generate Summary
        </button>
    </div>
    
    <!-- Extract Entities Button -->
    <div class="mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="nerExtractBtn" onclick="extractEntities(<?php echo $resource->id ?>)">
            <i class="bi bi-diagram-3 me-1"></i>Extract Entities
        </button>
    </div>
    
    <!-- Results Area -->
    <div id="aiResultsArea" class="mt-2" style="display: none;"></div>
</div>

<script>
function generateSummary(objectId) {
    const btn = document.getElementById('aiSummarizeBtn');
    const resultDiv = document.getElementById('aiResultsArea');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating...';
    
    fetch(`/ner/summarize/${objectId}`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-file-text me-1"></i>Generate Summary';
            
            if (!data.success) {
                showResult('danger', data.error || 'Summary generation failed');
                return;
            }
            
            const savedMsg = data.saved ? 'Summary saved to Scope & Content' : 'Generated (not saved)';
            showResult('success', `${savedMsg}<br><small class="text-muted">${data.processing_time_ms}ms</small>`, true);
            
            // Browser notification
            if (data.saved && 'Notification' in window && Notification.permission === 'granted') {
                new Notification('Summary Generated', {
                    body: 'Scope & Content field updated',
                    icon: '/favicon.ico'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-file-text me-1"></i>Generate Summary';
            showResult('danger', err.message);
        });
}

function extractEntities(objectId) {
    const btn = document.getElementById('nerExtractBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Extracting...';
    
    fetch(`/ner/extract/${objectId}`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-diagram-3 me-1"></i>Extract Entities';
            
            if (!data.success) {
                showResult('danger', data.error || 'Extraction failed');
                return;
            }
            
            showResult('success', `Found ${data.entity_count} entities<br><a href="/ner/review" class="small">Review & Link →</a>`);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-diagram-3 me-1"></i>Extract Entities';
            showResult('danger', err.message);
        });
}

function showResult(type, message, showRefresh = false) {
    const resultDiv = document.getElementById('aiResultsArea');
    let html = `<div class="alert alert-${type} py-2 small mb-0">${message}</div>`;
    if (showRefresh) {
        html += `<button class="btn btn-sm btn-outline-secondary mt-2 w-100" onclick="location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>`;
    }
    resultDiv.innerHTML = html;
    resultDiv.style.display = 'block';
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
</script>
