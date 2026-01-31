<?php
/**
 * AI Description Suggestion Button
 *
 * Include on information object show page to allow generating AI descriptions.
 * Usage: include_component('ai', 'suggestButton', ['resource' => $resource])
 */
?>
<div class="ai-suggest-section mb-3">
    <button type="button" class="btn btn-outline-primary w-100" id="aiSuggestBtn" onclick="openSuggestModal(<?php echo $resource->id ?>)">
        <i class="bi bi-magic me-1"></i>Suggest Description (AI)
    </button>
</div>

<!-- Suggestion Modal -->
<div class="modal fade" id="suggestModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-magic me-2"></i>AI Description Suggestion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="suggestModalClose"></button>
            </div>
            <div class="modal-body" id="suggestModalBody">
                <!-- Loading state -->
                <div id="suggestLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted">Generating description suggestion...</p>
                    <small class="text-muted">This may take 30-60 seconds depending on content length</small>
                </div>

                <!-- Result state -->
                <div id="suggestResult" style="display: none;">
                    <!-- Metadata summary -->
                    <div class="alert alert-info mb-3" id="suggestMeta"></div>

                    <!-- Side-by-side comparison -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-file-text me-1"></i>Current Description
                                </div>
                                <div class="card-body">
                                    <div id="existingText" class="small" style="max-height: 400px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <i class="bi bi-magic me-1"></i>AI Suggestion
                                    <span class="badge bg-light text-primary float-end" id="suggestModel"></span>
                                </div>
                                <div class="card-body">
                                    <textarea id="suggestedText" class="form-control" rows="15" style="font-size: 0.9rem;"></textarea>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="bi bi-pencil me-1"></i>You can edit before approving
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Review notes -->
                    <div class="mt-3">
                        <label class="form-label small">Review Notes (optional)</label>
                        <textarea id="reviewNotes" class="form-control" rows="2" placeholder="Add notes about your decision..."></textarea>
                    </div>
                </div>

                <!-- Error state -->
                <div id="suggestError" style="display: none;">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span id="suggestErrorMsg"></span>
                    </div>
                    <button class="btn btn-outline-primary" onclick="retrySuggest()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Retry
                    </button>
                </div>
            </div>
            <div class="modal-footer" id="suggestFooter" style="display: none;">
                <div class="me-auto small text-muted" id="suggestStats"></div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger" onclick="rejectSuggestion()">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
                <button type="button" class="btn btn-success" onclick="approveSuggestion()">
                    <i class="bi bi-check-circle me-1"></i>Approve & Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var currentSuggestion = null;
var currentObjectId = null;

function openSuggestModal(objectId) {
    currentObjectId = objectId;
    currentSuggestion = null;

    // Reset modal state
    document.getElementById('suggestLoading').style.display = 'block';
    document.getElementById('suggestResult').style.display = 'none';
    document.getElementById('suggestError').style.display = 'none';
    document.getElementById('suggestFooter').style.display = 'none';

    // Open modal
    var modal = new bootstrap.Modal(document.getElementById('suggestModal'));
    modal.show();

    // Generate suggestion
    generateSuggestion(objectId);
}

function generateSuggestion(objectId) {
    fetch('/ai/suggest/' + objectId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        document.getElementById('suggestLoading').style.display = 'none';

        if (!data.success) {
            document.getElementById('suggestError').style.display = 'block';
            document.getElementById('suggestErrorMsg').textContent = data.error || 'Failed to generate suggestion';
            return;
        }

        currentSuggestion = data;

        // Display results
        document.getElementById('suggestResult').style.display = 'block';
        document.getElementById('suggestFooter').style.display = 'flex';

        // Metadata summary
        var meta = '<strong>Template:</strong> ' + (data.template_name || 'Default');
        if (data.has_ocr) {
            meta += ' | <span class="text-success"><i class="bi bi-file-earmark-text me-1"></i>OCR text included</span>';
        }
        document.getElementById('suggestMeta').innerHTML = meta;

        // Existing text
        var existingEl = document.getElementById('existingText');
        if (data.existing_text && data.existing_text.trim()) {
            existingEl.innerHTML = '<pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">' + escapeHtml(data.existing_text) + '</pre>';
        } else {
            existingEl.innerHTML = '<em class="text-muted">No existing description</em>';
        }

        // Suggested text (editable)
        document.getElementById('suggestedText').value = data.suggested_text;

        // Model badge
        document.getElementById('suggestModel').textContent = data.model_used || 'AI';

        // Stats
        var stats = [];
        if (data.tokens_used) stats.push(data.tokens_used + ' tokens');
        if (data.generation_time_ms) stats.push((data.generation_time_ms / 1000).toFixed(1) + 's');
        document.getElementById('suggestStats').textContent = stats.join(' | ');
    })
    .catch(function(err) {
        document.getElementById('suggestLoading').style.display = 'none';
        document.getElementById('suggestError').style.display = 'block';
        document.getElementById('suggestErrorMsg').textContent = err.message;
    });
}

function retrySuggest() {
    document.getElementById('suggestLoading').style.display = 'block';
    document.getElementById('suggestError').style.display = 'none';
    generateSuggestion(currentObjectId);
}

function approveSuggestion() {
    if (!currentSuggestion) return;

    var editedText = document.getElementById('suggestedText').value.trim();
    var notes = document.getElementById('reviewNotes').value.trim();

    // Check if text was edited
    var wasEdited = editedText !== currentSuggestion.suggested_text;

    var btn = document.querySelector('.btn-success');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    fetch('/ai/suggest/' + currentSuggestion.suggestion_id + '/decision', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            decision: 'approve',
            edited_text: wasEdited ? editedText : null,
            notes: notes || null
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert('Error: ' + (data.error || 'Failed to save'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Approve & Save';
            return;
        }

        // Success - close modal and refresh
        document.getElementById('suggestModalClose').click();

        // Show success message
        showToast('Description saved successfully', 'success');

        // Refresh page to show updated content
        setTimeout(function() {
            location.reload();
        }, 1000);
    })
    .catch(function(err) {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Approve & Save';
    });
}

function rejectSuggestion() {
    if (!currentSuggestion) return;

    var notes = document.getElementById('reviewNotes').value.trim();

    if (!confirm('Reject this suggestion? It will be marked as rejected but kept for reference.')) {
        return;
    }

    var btn = document.querySelector('.btn-outline-danger');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Rejecting...';

    fetch('/ai/suggest/' + currentSuggestion.suggestion_id + '/decision', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            decision: 'reject',
            notes: notes || null
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert('Error: ' + (data.error || 'Failed to reject'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Reject';
            return;
        }

        // Close modal
        document.getElementById('suggestModalClose').click();
        showToast('Suggestion rejected', 'warning');
    })
    .catch(function(err) {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Reject';
    });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    type = type || 'info';
    var toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = '<div class="toast show bg-' + type + ' text-white" role="alert">' +
        '<div class="toast-body"><i class="bi bi-' + (type === 'success' ? 'check-circle' : 'info-circle') + ' me-2"></i>' + message + '</div></div>';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
}
</script>
