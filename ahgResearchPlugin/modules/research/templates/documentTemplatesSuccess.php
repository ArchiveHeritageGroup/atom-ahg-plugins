<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Document Templates</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Document Templates</h1>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'editDocumentTemplate']); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Template</a>
</div>

<?php if (empty($templates)): ?>
    <div class="alert alert-info">No document templates defined.</div>
<?php else: ?>
<div class="row">
    <?php foreach ($templates as $t): ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5><?php echo htmlspecialchars($t->name); ?></h5>
                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($t->document_type); ?></span>
                <p class="text-muted small mt-2"><?php echo htmlspecialchars($t->description ?? ''); ?></p>
            </div>
            <div class="card-footer"><a href="<?php echo url_for(['module' => 'research', 'action' => 'editDocumentTemplate', 'id' => $t->id]); ?>" class="btn btn-sm btn-outline-primary">Edit</a></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
