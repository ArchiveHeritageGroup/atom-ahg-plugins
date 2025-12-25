<?php
if ($restrictions->isEmpty()) return;

$restrictionLabels = [
    'closure' => ['Closed', 'danger'],
    'partial_closure' => ['Partially Closed', 'warning'],
    'permission_only' => ['Permission Required', 'warning'],
    'researcher_only' => ['Researchers Only', 'info'],
    'onsite_only' => ['Onsite Only', 'info'],
    'no_copying' => ['No Copying', 'secondary'],
    'no_publication' => ['No Publication', 'secondary'],
    'time_embargo' => ['Time Embargo', 'dark'],
    'popia_restricted' => ['POPIA Restricted', 'danger'],
    'legal_hold' => ['Legal Hold', 'danger'],
];
?>
<div class="donor-restrictions-section mt-2">
    <small class="text-muted d-block mb-1">
        <i class="fas fa-user-shield me-1"></i>Donor Restrictions:
    </small>
    <?php foreach ($restrictions as $r): ?>
        <?php $label = $restrictionLabels[$r->restriction_type] ?? [ucwords(str_replace('_', ' ', $r->restriction_type)), 'secondary']; ?>
        <span class="badge bg-<?php echo $label[1]; ?> me-1 mb-1">
            <?php echo $label[0]; ?>
            <?php if ($r->end_date): ?>
                <small>(until <?php echo date('M Y', strtotime($r->end_date)); ?>)</small>
            <?php endif; ?>
        </span>
    <?php endforeach; ?>
</div>
