<?php
/**
 * Heritage Admin Embargoes.
 */

decorate_with('layout_2col');

// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) {
    if (is_array($val)) return $val;
    if ($val instanceof Traversable) return iterator_to_array($val);
    return [];
};

$embargoRaw = $toArray($embargoData ?? []);
$embargoes = $toArray($embargoRaw['embargoes'] ?? []);
$total = $embargoRaw['total'] ?? 0;
$expiringEmbargoes = $toArray($expiringEmbargoes ?? []);
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-lock me-2"></i>Embargo Management
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'embargoes']); ?>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Statistics</h6>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span>Active</span>
            <span class="badge bg-danger"><?php echo $stats['active'] ?? 0; ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Expiring Soon</span>
            <span class="badge bg-warning"><?php echo $stats['expiring_soon'] ?? 0; ?></span>
        </div>
    </div>
</div>

<?php if (!empty($expiringEmbargoes)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h6 class="mb-0 text-warning"><i class="fas fa-clock me-2"></i>Expiring Soon</h6>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach (array_slice($expiringEmbargoes, 0, 5) as $exp): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?php echo esc_specialchars($exp->title ?? $exp->slug ?? 'Item'); ?></span>
            <small class="text-muted"><?php echo date('M d', strtotime($exp->end_date)); ?></small>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php end_slot(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Embargoes</h5>
        <span class="badge bg-danger"><?php echo number_format($total); ?> active</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($embargoes)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-unlock fs-1 mb-3 d-block"></i>
            <p>No active embargoes.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>End Date</th>
                        <th>Auto-Release</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($embargoes as $embargo): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $embargo->slug]); ?>" target="_blank">
                                <?php echo esc_specialchars($embargo->title ?? $embargo->slug ?? 'Item'); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $typeColors = ['full' => 'danger', 'digital_only' => 'warning', 'metadata_hidden' => 'info'];
                            $color = $typeColors[$embargo->embargo_type] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst(str_replace('_', ' ', $embargo->embargo_type)); ?></span>
                        </td>
                        <td>
                            <?php if ($embargo->end_date): ?>
                            <?php echo date('Y-m-d', strtotime($embargo->end_date)); ?>
                            <?php else: ?>
                            <span class="text-muted">Indefinite</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($embargo->auto_release): ?>
                            <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                            <i class="fas fa-times-circle text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline" onsubmit="return confirm('Remove this embargo?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="embargo_id" value="<?php echo $embargo->id; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-unlock"></i>
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
