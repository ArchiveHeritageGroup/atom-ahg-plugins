<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Hypothesis</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2">Hypothesis</h1>
        <span class="badge bg-<?php echo match($hypothesis->status) { 'proposed' => 'info', 'testing' => 'warning', 'supported' => 'success', 'refuted' => 'danger', default => 'secondary' }; ?> me-2"><?php echo ucfirst($hypothesis->status); ?></span>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Statement</h5></div>
    <div class="card-body">
        <p class="lead"><?php echo nl2br(htmlspecialchars($hypothesis->statement)); ?></p>
        <?php if ($hypothesis->tags): ?><div class="mt-2"><?php foreach (explode(',', $hypothesis->tags) as $tag): ?><span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars(trim($tag)); ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="mt-3 text-muted small">Evidence count: <?php echo (int) $hypothesis->evidence_count; ?> | Created: <?php echo $hypothesis->created_at; ?></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Evidence</h5></div>
    <div class="card-body">
        <?php if (!empty($hypothesis->evidence)): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Source</th><th>Relationship</th><th>Confidence</th><th>Note</th><th>Added</th></tr></thead>
                <tbody>
                <?php foreach ($hypothesis->evidence as $ev): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ev->source_type . ':' . $ev->source_id); ?></td>
                        <td><span class="badge bg-<?php echo $ev->relationship === 'supports' ? 'success' : ($ev->relationship === 'refutes' ? 'danger' : 'secondary'); ?>"><?php echo ucfirst($ev->relationship); ?></span></td>
                        <td><?php echo $ev->confidence !== null ? number_format((float)$ev->confidence, 1) . '%' : '-'; ?></td>
                        <td><?php echo htmlspecialchars($ev->note ?? ''); ?></td>
                        <td><?php echo $ev->created_at; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No evidence linked yet.</p>
        <?php endif; ?>
    </div>
</div>
