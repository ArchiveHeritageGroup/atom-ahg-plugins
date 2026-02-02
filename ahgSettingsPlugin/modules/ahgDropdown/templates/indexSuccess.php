<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i><?php echo __('Dropdown Manager') ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted small"><?php echo __('Manage controlled vocabularies (dropdowns) used throughout the system.') ?></p>
            <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createTaxonomyModal">
                <i class="fas fa-plus me-2"></i><?php echo __('Create Taxonomy') ?>
            </button>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1><i class="fas fa-list-alt me-2"></i><?php echo __('Dropdown Manager') ?></h1>
<p class="lead text-muted"><?php echo __('Manage controlled vocabularies for form dropdowns') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="dropdown-manager">
    <?php if (empty($taxonomies)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No taxonomies found.') ?>
        <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#createTaxonomyModal">
            <?php echo __('Create your first taxonomy') ?>
        </button>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Taxonomy') ?></th>
                    <th><?php echo __('Code') ?></th>
                    <th class="text-center"><?php echo __('Terms') ?></th>
                    <th class="text-end"><?php echo __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taxonomies as $tax): ?>
                <tr>
                    <td>
                        <a href="<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'edit', 'taxonomy' => $tax->taxonomy]) ?>" class="fw-bold text-decoration-none">
                            <?php echo esc_entities($tax->taxonomy_label) ?>
                        </a>
                    </td>
                    <td><code><?php echo esc_entities($tax->taxonomy) ?></code></td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?php echo $termCounts[$tax->taxonomy] ?? 0 ?></span>
                    </td>
                    <td class="text-end">
                        <a href="<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'edit', 'taxonomy' => $tax->taxonomy]) ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit Terms') ?>">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="renameTaxonomy('<?php echo esc_entities($tax->taxonomy) ?>', '<?php echo esc_entities($tax->taxonomy_label) ?>')" title="<?php echo __('Rename') ?>">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTaxonomy('<?php echo esc_entities($tax->taxonomy) ?>')" title="<?php echo __('Delete') ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<!-- Create Taxonomy Modal -->
<div class="modal fade" id="createTaxonomyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i><?php echo __('Create Taxonomy') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Display Name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newTaxonomyLabel" placeholder="e.g., Condition Status">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo __('Code') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newTaxonomyCode" placeholder="e.g., condition_status">
                    <div class="form-text"><?php echo __('Lowercase letters, numbers, and underscores only') ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-success" onclick="createTaxonomy()"><?php echo __('Create') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Rename Taxonomy Modal -->
<div class="modal fade" id="renameTaxonomyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i><?php echo __('Rename Taxonomy') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="renameTaxonomyCode">
                <div class="mb-3">
                    <label class="form-label"><?php echo __('New Display Name') ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="renameTaxonomyLabel">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                <button type="button" class="btn btn-warning" onclick="doRenameTaxonomy()"><?php echo __('Rename') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
function createTaxonomy() {
    var label = document.getElementById('newTaxonomyLabel').value.trim();
    var code = document.getElementById('newTaxonomyCode').value.trim();

    if (!label || !code) {
        alert('<?php echo __('Please fill in all required fields') ?>');
        return;
    }

    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'create']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'code=' + encodeURIComponent(code) + '&label=' + encodeURIComponent(label)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || '<?php echo __('Error creating taxonomy') ?>');
        }
    });
}

function renameTaxonomy(code, label) {
    document.getElementById('renameTaxonomyCode').value = code;
    document.getElementById('renameTaxonomyLabel').value = label;
    new bootstrap.Modal(document.getElementById('renameTaxonomyModal')).show();
}

function doRenameTaxonomy() {
    var code = document.getElementById('renameTaxonomyCode').value;
    var label = document.getElementById('renameTaxonomyLabel').value.trim();

    if (!label) {
        alert('<?php echo __('Please enter a name') ?>');
        return;
    }

    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'rename']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'taxonomy=' + encodeURIComponent(code) + '&label=' + encodeURIComponent(label)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || '<?php echo __('Error renaming taxonomy') ?>');
        }
    });
}

function deleteTaxonomy(code) {
    if (!confirm('<?php echo __('Are you sure you want to delete this taxonomy and all its terms?') ?>')) {
        return;
    }

    fetch('<?php echo url_for(['module' => 'ahgDropdown', 'action' => 'deleteTaxonomy']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'taxonomy=' + encodeURIComponent(code) + '&hard_delete=1'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || '<?php echo __('Error deleting taxonomy') ?>');
        }
    });
}

// Auto-generate code from label
document.getElementById('newTaxonomyLabel').addEventListener('input', function() {
    var code = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    document.getElementById('newTaxonomyCode').value = code;
});
</script>
<?php end_slot() ?>
