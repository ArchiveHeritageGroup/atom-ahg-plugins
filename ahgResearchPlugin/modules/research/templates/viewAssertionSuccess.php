<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Assertion</li>
    </ol>
</nav>

<h1 class="h2 mb-4">Assertion #<?php echo (int) $assertion->id; ?></h1>

<div class="card mb-4">
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4"><div class="p-3 bg-light rounded"><strong>Subject</strong><br><?php echo htmlspecialchars($assertion->subject_label ?? $assertion->subject_type . ':' . $assertion->subject_id); ?></div></div>
            <div class="col-md-4"><div class="p-3"><i class="fas fa-arrow-right fa-2x text-muted mb-2"></i><br><strong class="text-primary"><?php echo htmlspecialchars($assertion->predicate); ?></strong></div></div>
            <div class="col-md-4"><div class="p-3 bg-light rounded"><strong>Object</strong><br><?php echo htmlspecialchars($assertion->object_label ?? $assertion->object_value ?? ''); ?></div></div>
        </div>
        <div class="mt-3 text-center">
            <span class="badge bg-<?php echo match($assertion->status) { 'proposed' => 'info', 'verified' => 'success', 'disputed' => 'warning', 'retracted' => 'danger', default => 'secondary' }; ?> me-2"><?php echo ucfirst($assertion->status); ?></span>
            <span class="badge bg-light text-dark me-2"><?php echo ucfirst($assertion->assertion_type); ?></span>
            <?php if ($assertion->confidence !== null): ?><span class="badge bg-light text-dark">Confidence: <?php echo number_format((float)$assertion->confidence, 1); ?>%</span><?php endif; ?>
            <span class="badge bg-secondary">v<?php echo (int) $assertion->version; ?></span>
        </div>
    </div>
</div>

<?php if (!empty($assertion->evidence)): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Evidence</h5></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Source</th><th>Relationship</th><th>Note</th><th>Added</th></tr></thead>
            <tbody>
            <?php foreach ($assertion->evidence as $ev): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ev->source_type . ':' . $ev->source_id); ?></td>
                    <td><span class="badge bg-<?php echo $ev->relationship === 'supports' ? 'success' : 'danger'; ?>"><?php echo ucfirst($ev->relationship); ?></span></td>
                    <td><?php echo htmlspecialchars($ev->note ?? ''); ?></td>
                    <td><?php echo $ev->created_at; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($conflicts)): ?>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning"><h5 class="mb-0">Conflicts Detected</h5></div>
    <div class="card-body">
        <?php foreach ($conflicts as $c): ?>
        <div class="border-bottom pb-2 mb-2">
            <strong><?php echo htmlspecialchars($c->predicate); ?></strong> &rarr; <?php echo htmlspecialchars($c->object_label ?? $c->object_value ?? ''); ?>
            <span class="badge bg-<?php echo match($c->status) { 'proposed' => 'info', 'verified' => 'success', 'disputed' => 'warning', default => 'secondary' }; ?>"><?php echo ucfirst($c->status); ?></span>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewAssertion', 'id' => $c->id]); ?>" class="btn btn-sm btn-outline-secondary ms-2">View</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
