<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$notebooks = sfOutputEscaper::unescape($notebooks ?? []);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Notebooks</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2 mb-0"><i class="fas fa-book text-primary me-2"></i>Notebooks</h1>
    <span class="badge bg-secondary"><?php echo count($notebooks); ?></span>
</div>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($msg = $sf_user->getFlash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>New notebook</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'notebooks']); ?>">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" required maxlength="255" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Summary (optional)</label>
                        <textarea name="summary" rows="3" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-book me-1"></i>Create</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if (empty($notebooks)): ?>
            <div class="card">
                <div class="card-body text-muted">No notebooks yet. Create one on the left.</div>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notebooks as $n): ?>
                    <a href="<?php echo url_for(['module' => 'research', 'action' => 'notebookShow', 'id' => $n['id']]); ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                <?php if (!empty($n['summary'])): ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars(mb_substr($n['summary'], 0, 160)); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($n['promoted_to_project_id'])): ?>
                                <span class="badge bg-success">Promoted &rarr; project #<?php echo (int) $n['promoted_to_project_id']; ?></span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars(date('j M Y', strtotime($n['created_at']))); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
