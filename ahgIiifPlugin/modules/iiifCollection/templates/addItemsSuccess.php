<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo esc_entities($collection->display_name) ?></h5>
        </div>
        <div class="card-body">
            <a href="<?php echo url_for(['module' => 'iiifCollection', 'action' => 'view', 'id' => $collection->id]) ?>" class="btn btn-outline-primary w-100">
                <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Collection') ?>
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Add External Manifest') ?></h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Manifest URI') ?></label>
                    <input type="url" class="form-control form-control-sm" name="manifest_uri" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Label') ?></label>
                    <input type="text" class="form-control form-control-sm" name="label">
                </div>
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <i class="fas fa-plus me-2"></i><?php echo __('Add External') ?>
                </button>
            </form>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1><i class="fas fa-plus-circle me-2"></i><?php echo __('Add Items to Collection') ?></h1>
<h2><?php echo esc_entities($collection->display_name) ?></h2>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="add-items-form">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-search me-2"></i><?php echo __('Search & Add Objects') ?></h5>
        </div>
        <div class="card-body">
            <form method="post" id="addItemsForm">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Search for objects') ?></label>
                    <input type="text" class="form-control" id="objectSearchInput" 
                           placeholder="<?php echo __('Type to search by title or identifier...') ?>"
                           autocomplete="off">
                    <div id="searchResults" class="list-group mt-2" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Selected Items') ?></label>
                    <div id="selectedItems" class="border rounded p-2" style="min-height: 50px;">
                        <span class="text-muted" id="noSelection"><?php echo __('No items selected') ?></span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg" id="addBtn" disabled>
                    <i class="fas fa-plus me-2"></i><?php echo __('Add Selected Items to Collection') ?>
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($collection->items)): ?>
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i><?php echo __('Current Items') ?> (<?php echo count($collection->items) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th><?php echo __('Title') ?></th><th><?php echo __('Identifier') ?></th></tr></thead>
                <tbody>
                    <?php foreach ($collection->items as $item): ?>
                    <tr>
                        <td><?php echo esc_entities($item->label ?: $item->object_title ?: __('Untitled')) ?></td>
                        <td><code><?php echo esc_entities($item->identifier ?: '-') ?></code></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var searchInput = document.getElementById('objectSearchInput');
    var searchResults = document.getElementById('searchResults');
    var selectedItems = document.getElementById('selectedItems');
    var noSelection = document.getElementById('noSelection');
    var addBtn = document.getElementById('addBtn');
    var form = document.getElementById('addItemsForm');
    var selected = {};
    var searchTimeout;
    
    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(function() {
            fetch('/index.php/object/autocomplete?q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    searchResults.innerHTML = '';
                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function(item) {
                            if (!selected[item.id]) {
                                var div = document.createElement('a');
                                div.href = '#';
                                div.className = 'list-group-item list-group-item-action';
                                div.innerHTML = '<strong>' + (item.title || 'Untitled') + '</strong>' + 
                                    (item.identifier ? ' <code class="ms-2">' + item.identifier + '</code>' : '');
                                div.onclick = function(e) {
                                    e.preventDefault();
                                    addToSelected(item);
                                    searchInput.value = '';
                                    searchResults.innerHTML = '';
                                };
                                searchResults.appendChild(div);
                            }
                        });
                    } else {
                        searchResults.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                    }
                })
                .catch(function(err) {
                    console.error('Search error:', err);
                    searchResults.innerHTML = '<div class="list-group-item text-danger">Error searching</div>';
                });
        }, 300);
    });
    
    function addToSelected(item) {
        if (selected[item.id]) return;
        selected[item.id] = item;
        noSelection.style.display = 'none';
        
        var badge = document.createElement('span');
        badge.className = 'badge bg-success me-2 mb-1 p-2';
        badge.id = 'sel-' + item.id;
        badge.innerHTML = (item.title || 'Untitled') + 
            ' <i class="fas fa-times ms-1" style="cursor:pointer"></i>' +
            '<input type="hidden" name="object_ids[]" value="' + item.id + '">';
        badge.querySelector('i').onclick = function() {
            delete selected[item.id];
            badge.remove();
            updateUI();
        };
        selectedItems.appendChild(badge);
        updateUI();
    }
    
    function updateUI() {
        var count = Object.keys(selected).length;
        addBtn.disabled = count === 0;
        noSelection.style.display = count === 0 ? '' : 'none';
    }
})();
</script>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
#selectedItems { display: flex; flex-wrap: wrap; gap: 5px; }
#searchResults .list-group-item:hover { background: #e9ecef; }
</style>
<?php end_slot() ?>
