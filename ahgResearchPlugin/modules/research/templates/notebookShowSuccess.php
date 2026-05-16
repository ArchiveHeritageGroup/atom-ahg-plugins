<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$notebook = sfOutputEscaper::unescape($notebook);
$items    = sfOutputEscaper::unescape($items ?? []);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'notebooks']); ?>">Notebooks</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($notebook->title); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="fas fa-book text-primary me-2"></i><?php echo htmlspecialchars($notebook->title); ?></h1>
    <div>
        <?php if (!$notebook->promoted_to_project_id): ?>
            <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'notebookPromote', 'id' => $notebook->id]); ?>" class="d-inline">
                <button type="submit" class="btn btn-success" onclick="return confirm('Promote this notebook to a public research project?');">
                    <i class="fas fa-rocket me-1"></i> Promote to project
                </button>
            </form>
        <?php else: ?>
            <a class="btn btn-outline-success" href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $notebook->promoted_to_project_id]); ?>">
                <i class="fas fa-external-link-alt me-1"></i> View project #<?php echo (int) $notebook->promoted_to_project_id; ?>
            </a>
        <?php endif; ?>
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'notebookDelete', 'id' => $notebook->id]); ?>" class="d-inline">
            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this notebook?');">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

<?php if ($msg = $sf_user->getFlash('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($msg = $sf_user->getFlash('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

<?php if ($notebook->summary): ?>
    <p class="text-muted"><?php echo nl2br(htmlspecialchars($notebook->summary)); ?></p>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add item</h5></div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'notebookShow', 'id' => $notebook->id]); ?>">
                    <input type="hidden" name="form_action" value="add_item">
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select name="item_type" class="form-select">
                            <option value="note">Note</option>
                            <option value="saved_query">Saved query</option>
                            <option value="ai_output">AI output</option>
                            <option value="source_pin">Pinned source</option>
                        </select>
                    </div>
                    <div class="mb-2"><input type="text" name="item_title" maxlength="500" placeholder="Title" class="form-control"></div>
                    <div class="mb-2"><textarea name="item_body" rows="4" placeholder="Body / details" class="form-control"></textarea></div>
                    <div class="mb-2"><input type="number" name="source_object_id" placeholder="Source object ID (for pinned source)" class="form-control"></div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="pinned" value="1" class="form-check-input" id="pinned-chk">
                        <label class="form-check-label" for="pinned-chk">Pin to top</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <h5 class="mb-3">Items <span class="badge bg-secondary"><?php echo count($items); ?></span></h5>
        <?php if (empty($items)): ?>
            <div class="card"><div class="card-body text-muted">No items yet.</div></div>
        <?php else: ?>
            <?php foreach ($items as $i): ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <?php if ($i['pinned']): ?><span class="badge bg-warning text-dark me-1"><i class="fas fa-thumbtack"></i></span><?php endif; ?>
                                <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($i['item_type']); ?></span>
                                <?php if ($i['title']): ?><strong><?php echo htmlspecialchars($i['title']); ?></strong><?php endif; ?>
                                <?php if ($i['body']): ?><div class="mt-2"><?php echo nl2br(htmlspecialchars(mb_substr($i['body'], 0, 800))); ?></div><?php endif; ?>
                                <?php if ($i['source_object_id']): ?>
                                    <div class="small text-muted mt-1">Source object: #<?php echo (int) $i['source_object_id']; ?></div>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'notebookShow', 'id' => $notebook->id]); ?>" class="d-inline ms-2">
                                <input type="hidden" name="form_action" value="remove_item">
                                <input type="hidden" name="item_id" value="<?php echo (int) $i['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?');"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
