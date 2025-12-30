<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item active">Valuations</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-dollar-sign text-primary me-2"></i>Valuations<?php if (isset($object)): ?> - <?php echo $object->title; ?><?php endif; ?></h1>
    <?php if (isset($objectId)): ?>
        <a href="<?php echo url_for(['module' => 'gallery', 'action' => 'createValuation', 'object_id' => $objectId]); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Valuation</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><?php if (!isset($objectId)): ?><th>Object</th><?php endif; ?><th>Type</th><th>Value</th><th>Date</th><th>Appraiser</th><th>Valid Until</th><th>Current</th></tr></thead>
            <tbody>
                <?php if (empty($valuations)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No valuations found</td></tr>
                <?php else: ?>
                    <?php foreach ($valuations as $v): ?>
                        <tr>
                            <?php if (!isset($objectId)): ?><td><?php echo $v->object_title ?? 'Object #' . $v->object_id; ?></td><?php endif; ?>
                            <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $v->valuation_type)); ?></span></td>
                            <td><strong><?php echo $v->currency; ?> <?php echo number_format($v->value_amount, 2); ?></strong></td>
                            <td><?php echo $v->valuation_date; ?></td>
                            <td><?php echo $v->appraiser_name ?: '-'; ?><?php if ($v->appraiser_organization): ?><br><small class="text-muted"><?php echo $v->appraiser_organization; ?></small><?php endif; ?></td>
                            <td><?php echo $v->valid_until ?: '-'; ?></td>
                            <td><?php echo $v->is_current ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
