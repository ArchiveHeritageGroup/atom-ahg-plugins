<!-- Add/Link Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for(['module' => 'vendor', 'action' => 'addTransactionItem', 'id' => $transactionRaw->id]); ?>" id="addItemForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-link me-2"></i>Link GLAM/DAM Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Start typing to search for an archival item to link to this vendor transaction.
                    </div>
                    
                    <!-- Autocomplete Search -->
                    <div class="mb-4">
                        <label class="form-label">Search GLAM Items *</label>
                        <input type="text" id="glamAutocomplete" class="form-control form-control-lg" 
                               placeholder="Type title or identifier to search..." autocomplete="off">
                        <input type="hidden" name="information_object_id" id="selectedItemId" required>
                        <div id="autocompleteResults" class="autocomplete-results"></div>
                    </div>

                    <!-- Selected Item Display -->
                    <div id="selectedItemCard" class="card bg-light mb-4" style="display: none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1" id="selectedTitle"></h6>
                                    <small class="text-muted">
                                        <span id="selectedIdentifier"></span>
                                        <span id="selectedLevel" class="badge bg-secondary ms-2"></span>
                                    </small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSelection()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="unit_cost" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Item Status</label>
                            <select name="item_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Service Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Notes specific to this item's service requirements"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addItemBtn" disabled>
                        <i class="fas fa-link me-1"></i>Link Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.autocomplete-results {
    position: absolute;
    z-index: 1050;
    width: calc(100% - 2rem);
    max-height: 300px;
    overflow-y: auto;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    display: none;
}
.autocomplete-results .autocomplete-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.autocomplete-results .autocomplete-item:hover,
.autocomplete-results .autocomplete-item.active {
    background-color: #e9ecef;
}
.autocomplete-results .autocomplete-item:last-child {
    border-bottom: none;
}
.autocomplete-results .autocomplete-item .item-title {
    font-weight: 500;
}
.autocomplete-results .autocomplete-item .item-meta {
    font-size: 0.85em;
    color: #6c757d;
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
let autocompleteTimeout;

document.getElementById('glamAutocomplete').addEventListener('input', function() {
    clearTimeout(autocompleteTimeout);
    const query = this.value.trim();
    const resultsDiv = document.getElementById('autocompleteResults');
    
    if (query.length < 2) {
        resultsDiv.style.display = 'none';
        return;
    }
    
    autocompleteTimeout = setTimeout(() => {
        fetch('/index.php/api/autocomplete/glam?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '';
                    data.forEach((item, index) => {
                        html += `<div class="autocomplete-item" onclick="selectGlamItem(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                            <div class="item-title">${escapeHtml(item.value)}</div>
                            <div class="item-meta">
                                ${item.identifier ? '<code>' + escapeHtml(item.identifier) + '</code> • ' : ''}
                                ${item.level ? '<span class="badge bg-secondary">' + escapeHtml(item.level) + '</span>' : ''}
                            </div>
                        </div>`;
                    });
                    resultsDiv.innerHTML = html;
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div class="autocomplete-item text-muted">No results found</div>';
                    resultsDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Autocomplete error:', error);
                resultsDiv.style.display = 'none';
            });
    }, 300);
});

function selectGlamItem(item) {
    document.getElementById('selectedItemId').value = item.id;
    document.getElementById('glamAutocomplete').value = '';
    document.getElementById('autocompleteResults').style.display = 'none';
    
    // Show selected item card
    document.getElementById('selectedTitle').textContent = item.value;
    document.getElementById('selectedIdentifier').textContent = item.identifier || '';
    document.getElementById('selectedLevel').textContent = item.level || '';
    document.getElementById('selectedLevel').style.display = item.level ? 'inline-block' : 'none';
    document.getElementById('selectedItemCard').style.display = 'block';
    
    document.getElementById('addItemBtn').disabled = false;
}

function clearSelection() {
    document.getElementById('selectedItemId').value = '';
    document.getElementById('selectedItemCard').style.display = 'none';
    document.getElementById('addItemBtn').disabled = true;
    document.getElementById('glamAutocomplete').focus();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close autocomplete on click outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#glamAutocomplete') && !e.target.closest('#autocompleteResults')) {
        document.getElementById('autocompleteResults').style.display = 'none';
    }
});

// Handle keyboard navigation
document.getElementById('glamAutocomplete').addEventListener('keydown', function(e) {
    const resultsDiv = document.getElementById('autocompleteResults');
    const items = resultsDiv.querySelectorAll('.autocomplete-item:not(.text-muted)');
    const activeItem = resultsDiv.querySelector('.autocomplete-item.active');
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!activeItem && items.length > 0) {
            items[0].classList.add('active');
        } else if (activeItem && activeItem.nextElementSibling) {
            activeItem.classList.remove('active');
            activeItem.nextElementSibling.classList.add('active');
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (activeItem && activeItem.previousElementSibling) {
            activeItem.classList.remove('active');
            activeItem.previousElementSibling.classList.add('active');
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeItem) {
            activeItem.click();
        }
    } else if (e.key === 'Escape') {
        resultsDiv.style.display = 'none';
    }
});
</script>
