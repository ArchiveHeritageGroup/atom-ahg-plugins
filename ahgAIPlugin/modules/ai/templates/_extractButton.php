<?php
/**
 * NER Extract Button - Include on information object show page
 * Usage: include_component('ahgNer', 'extractButton', ['resource' => $resource])
 */
?>
<div class="ner-extract-section mb-3">
    <button type="button" class="btn btn-outline-primary w-100" id="nerExtractBtn" onclick="extractEntities(<?php echo $resource->id ?>)">
        <i class="bi bi-cpu me-1"></i>Extract Entities (NER)
    </button>
    
    <div id="nerResults" class="mt-3" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-1"></i>Extracted Entities</span>
                <button class="btn btn-sm btn-success" onclick="approveAll()">Approve All</button>
            </div>
            <div class="card-body" id="nerResultsBody"></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function extractEntities(objectId) {
    const btn = document.getElementById('nerExtractBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Extracting...';
    
    fetch(`/ner/extract/${objectId}`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cpu me-1"></i>Extract Entities (NER)';
            
            if (!data.success) {
                alert('Error: ' + (data.error || 'Extraction failed'));
                return;
            }
            
            displayResults(data.entities, data.entity_count, data.processing_time_ms);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cpu me-1"></i>Extract Entities (NER)';
            alert('Error: ' + err.message);
        });
}

function displayResults(entities, count, time) {
    const container = document.getElementById('nerResults');
    const body = document.getElementById('nerResultsBody');
    
    if (count === 0) {
        body.innerHTML = '<div class="text-muted text-center">No entities found</div>';
        container.style.display = 'block';
        return;
    }
    
    let html = `<p class="text-muted small">Found ${count} entities in ${time}ms</p>`;
    
    const icons = { PERSON: 'bi-person', ORG: 'bi-building', GPE: 'bi-geo-alt', DATE: 'bi-calendar' };
    const colors = { PERSON: 'primary', ORG: 'success', GPE: 'info', DATE: 'warning' };
    
    for (const [type, items] of Object.entries(entities)) {
        if (!items.length) continue;
        
        html += `<div class="mb-2"><strong><i class="${icons[type]} me-1"></i>${type}</strong><br>`;
        html += items.map(i => `<span class="badge bg-${colors[type]} me-1 mb-1">${i}</span>`).join('');
        html += '</div>';
    }
    
    html += '<hr><a href="/ner/review" class="btn btn-sm btn-primary">Review & Link Entities</a>';
    
    body.innerHTML = html;
    container.style.display = 'block';
}
</script>
