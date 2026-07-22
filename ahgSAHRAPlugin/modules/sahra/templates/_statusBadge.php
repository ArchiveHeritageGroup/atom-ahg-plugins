<?php
$__colors = [
    'draft' => 'secondary',
    'pending_supervisor' => 'warning',
    'supervisor_approved' => 'info',
    'supervisor_rejected' => 'danger',
    'submitted_to_sahra' => 'primary',
    'sahra_issued' => 'success',
    'active' => 'success',
    'sahra_rejected' => 'danger',
    'expired' => 'dark',
    'revoked' => 'danger',
    'closed' => 'secondary',
];
$__labels = \AhgSAHRA\Services\SahraPermitService::STATUS_LABELS;
$__c = $__colors[$status] ?? 'secondary';
?>
<span class="badge bg-<?php echo $__c; ?><?php echo in_array($__c, ['warning', 'info', 'light'], true) ? ' text-dark' : ''; ?>"><?php echo htmlspecialchars($__labels[$status] ?? $status); ?></span>
