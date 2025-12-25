<?php
// Unescape Symfony output decorator and convert to array recursively
$access = json_decode(json_encode(sfOutputEscaper::unescape($access ?? [])), true);
$userContext = json_decode(json_encode(sfOutputEscaper::unescape($userContext ?? [])), true);

if (empty($access)) return;
if (empty($access['classification']) && empty($access['restrictions']) && empty($access['embargo'])) return;
$classColors = [
    'PUBLIC' => 'success', 'INTERNAL' => 'info', 'CONFIDENTIAL' => 'primary',
    'SECRET' => 'warning', 'TOP_SECRET' => 'danger',
];
?>
<div class="access-control-banner mb-3 p-2 border rounded bg-light">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if (!empty($access['classification'])): ?>
            <?php $code = $access['classification']['code'] ?? 'PUBLIC'; ?>
            <span class="badge bg-<?php echo $classColors[$code] ?? 'secondary'; ?>">
                <i class="fas fa-shield-alt me-1"></i><?php echo esc_entities($access['classification']['name'] ?? $code); ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($access['donor_restrictions'])): foreach ($access['donor_restrictions'] as $r): ?>
            <span class="badge bg-warning text-dark">
                <i class="fas fa-user-shield me-1"></i><?php echo esc_entities(ucwords(str_replace('_', ' ', $r['type'] ?? ''))); ?>
            </span>
        <?php endforeach; endif; ?>
        <?php if (!empty($access['embargo'])): ?>
            <span class="badge bg-secondary">
                <i class="fas fa-clock me-1"></i>Embargoed until <?php echo date('M j, Y', strtotime($access['embargo']['end_date'])); ?>
            </span>
        <?php endif; ?>
        <?php if (!empty($userContext['is_administrator'])): ?>
            <span class="badge bg-dark ms-auto"><i class="fas fa-user-cog me-1"></i>Admin View</span>
        <?php endif; ?>
    </div>
</div>
