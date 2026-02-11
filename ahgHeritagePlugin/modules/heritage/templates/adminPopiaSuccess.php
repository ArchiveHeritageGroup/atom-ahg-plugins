<?php
/**
 * Heritage Admin POPIA Flags.
 */

decorate_with('layout_2col');

$flags = $flagData['flags'] ?? [];
$total = $flagData['total'] ?? 0;
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-shield-alt-exclamation me-2"></i>POPIA/Privacy Flags
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'popia']); ?>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Statistics</h6>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span>Unresolved</span>
            <span class="badge bg-warning"><?php echo $stats['unresolved'] ?? 0; ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>Critical</span>
            <span class="badge bg-danger"><?php echo $stats['critical'] ?? 0; ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>High</span>
            <span class="badge bg-warning"><?php echo $stats['high'] ?? 0; ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Resolved (This Month)</span>
            <span class="badge bg-success"><?php echo $stats['resolved_this_month'] ?? 0; ?></span>
        </div>
    </div>
</div>
<?php end_slot(); ?>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <select class="form-select" name="severity">
                    <option value="">All Severities</option>
                    <option value="critical" <?php echo $sf_request->getParameter('severity') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                    <option value="high" <?php echo $sf_request->getParameter('severity') === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo $sf_request->getParameter('severity') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo $sf_request->getParameter('severity') === 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="flag_type">
                    <option value="">All Types</option>
                    <option value="personal_info">Personal Information</option>
                    <option value="sensitive">Sensitive Data</option>
                    <option value="children">Children's Data</option>
                    <option value="health">Health Information</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Unresolved Flags</h5>
        <span class="badge bg-warning"><?php echo number_format($total); ?> flags</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($flags)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-shield-alt-check fs-1 mb-3 d-block text-success"></i>
            <p>No unresolved privacy flags.</p>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($flags as $flag): ?>
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col-md-1">
                        <?php
                        $severityColors = ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary'];
                        $color = $severityColors[$flag->severity] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?> text-uppercase"><?php echo $flag->severity; ?></span>
                    </div>
                    <div class="col-md-5">
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $flag->slug]); ?>" target="_blank">
                            <?php echo esc_specialchars($flag->object_title ?? $flag->slug ?? 'Item'); ?>
                        </a>
                        <br>
                        <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $flag->flag_type)); ?></small>
                    </div>
                    <div class="col-md-4">
                        <?php if ($flag->description): ?>
                        <small><?php echo esc_specialchars(substr($flag->description, 0, 100)); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal"
                                data-flag-id="<?php echo $flag->id; ?>">
                            <i class="fas fa-check me-1"></i>Resolve
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Resolve Privacy Flag</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_action" value="resolve">
                    <input type="hidden" name="flag_id" id="resolve_flag_id">
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea class="form-control" name="resolution_notes" id="resolution_notes" rows="3"
                                  placeholder="Describe what action was taken to resolve this flag..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Resolved</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('resolveModal').addEventListener('show.bs.modal', function(event) {
    document.getElementById('resolve_flag_id').value = event.relatedTarget.getAttribute('data-flag-id');
});
</script>
