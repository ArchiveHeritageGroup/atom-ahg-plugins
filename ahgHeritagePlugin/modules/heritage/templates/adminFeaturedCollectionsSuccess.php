<?php
/**
 * Admin Featured Collections Management.
 */

decorate_with('layout_2col');

$featured = (array) $featured;
$iiifCollections = (array) $iiifCollections;
$archivalCollections = (array) $archivalCollections;
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-star me-2"></i>Featured Collections
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'featured']); ?>
<?php end_slot(); ?>

<!-- Add Collection Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add Collection to Featured</h5>
    </div>
    <div class="card-body">
        <form action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminFeaturedCollections']); ?>" method="post">
            <input type="hidden" name="featured_action" value="add">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="source_type" class="form-label">Collection Type</label>
                    <select class="form-select" id="source_type" name="source_type" required onchange="toggleSourceOptions(this.value)">
                        <option value="">Select type...</option>
                        <option value="archival">Archival Collection (Fonds)</option>
                        <option value="iiif">IIIF Collection (Manifest)</option>
                    </select>
                </div>

                <div class="col-md-4" id="archival_select_wrapper" style="display: none;">
                    <label for="archival_source_id" class="form-label">Select Archival Collection</label>
                    <select class="form-select" id="archival_source_id" name="source_id_archival">
                        <option value="">Select collection...</option>
                        <?php foreach ($archivalCollections as $c): ?>
                        <option value="<?php echo $c->id; ?>"><?php echo esc_specialchars($c->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4" id="iiif_select_wrapper" style="display: none;">
                    <label for="iiif_source_id" class="form-label">Select IIIF Collection</label>
                    <select class="form-select" id="iiif_source_id" name="source_id_iiif">
                        <option value="">Select collection...</option>
                        <?php foreach ($iiifCollections as $c): ?>
                        <option value="<?php echo $c->id; ?>"><?php echo esc_specialchars($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input type="number" class="form-control" id="display_order" name="display_order" value="100" min="1">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="title" class="form-label">Override Title (Optional)</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Leave blank to use original">
                </div>
                <div class="col-md-6">
                    <label for="description" class="form-label">Override Description (Optional)</label>
                    <input type="text" class="form-control" id="description" name="description" placeholder="Leave blank to use original">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" id="add_btn" disabled>
                    <i class="fas fa-plus me-1"></i>Add to Featured
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Current Featured Collections -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Current Featured Collections</h5>
        <span class="badge bg-primary"><?php echo count($featured); ?> collections</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($featured)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox display-4 mb-3 d-block"></i>
            <p class="mb-0">No featured collections yet.</p>
            <p class="small">Add collections above to display them on the landing page.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Order</th>
                        <th>Collection</th>
                        <th>Type</th>
                        <th>Custom Title</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($featured as $item): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary"><?php echo $item->display_order; ?></span>
                        </td>
                        <td>
                            <strong><?php echo esc_specialchars($item->source_name); ?></strong>
                            <?php if ($item->source_slug): ?>
                            <br><small class="text-muted"><?php echo esc_specialchars($item->source_slug); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item->source_type === 'iiif'): ?>
                            <span class="badge bg-info"><i class="fas fa-layer-group me-1"></i>IIIF</span>
                            <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-archive me-1"></i>Archival</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item->title): ?>
                            <em><?php echo esc_specialchars($item->title); ?></em>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item->is_enabled): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-pause me-1"></i>Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminFeaturedCollections']); ?>" method="post" class="d-inline">
                                <input type="hidden" name="featured_action" value="toggle">
                                <input type="hidden" name="featured_id" value="<?php echo $item->id; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo $item->is_enabled ? 'Disable' : 'Enable'; ?>">
                                    <i class="fas <?php echo $item->is_enabled ? 'fa-pause' : 'fa-play'; ?>"></i>
                                </button>
                            </form>
                            <form action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminFeaturedCollections']); ?>" method="post" class="d-inline" onsubmit="return confirm('Remove this collection from featured?');">
                                <input type="hidden" name="featured_action" value="remove">
                                <input type="hidden" name="featured_id" value="<?php echo $item->id; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function toggleSourceOptions(type) {
    document.getElementById('archival_select_wrapper').style.display = type === 'archival' ? 'block' : 'none';
    document.getElementById('iiif_select_wrapper').style.display = type === 'iiif' ? 'block' : 'none';

    // Enable/disable the hidden source_id field
    document.getElementById('archival_source_id').disabled = type !== 'archival';
    document.getElementById('iiif_source_id').disabled = type !== 'iiif';

    // Update form name
    if (type === 'archival') {
        document.getElementById('archival_source_id').name = 'source_id';
        document.getElementById('iiif_source_id').name = 'source_id_iiif';
    } else if (type === 'iiif') {
        document.getElementById('iiif_source_id').name = 'source_id';
        document.getElementById('archival_source_id').name = 'source_id_archival';
    }

    updateAddButton();
}

function updateAddButton() {
    var type = document.getElementById('source_type').value;
    var hasSelection = false;

    if (type === 'archival') {
        hasSelection = document.getElementById('archival_source_id').value !== '';
    } else if (type === 'iiif') {
        hasSelection = document.getElementById('iiif_source_id').value !== '';
    }

    document.getElementById('add_btn').disabled = !hasSelection;
}

// Add change listeners
document.getElementById('archival_source_id').addEventListener('change', updateAddButton);
document.getElementById('iiif_source_id').addEventListener('change', updateAddButton);
</script>
