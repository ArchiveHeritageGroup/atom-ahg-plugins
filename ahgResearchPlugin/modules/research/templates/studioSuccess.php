<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$project    = sfOutputEscaper::unescape($project);
$artefacts  = sfOutputEscaper::unescape($artefacts ?? []);
$sourcePool = sfOutputEscaper::unescape($sourcePool ?? []);
$outputTypes = sfOutputEscaper::unescape($outputTypes ?? []);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Studio</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><i class="fas fa-flask text-primary me-2"></i>Studio</h1>
    <span class="badge bg-secondary"><?php echo count($artefacts); ?> artefact<?php echo count($artefacts) === 1 ? '' : 's'; ?></span>
</div>

<?php if ($msg = $sf_user->getFlash('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = $sf_user->getFlash('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-magic me-2"></i>Generate new artefact</h5>
            </div>
            <div class="card-body">
                <?php if (empty($sourcePool)): ?>
                    <div class="alert alert-warning mb-0">
                        Your project has no evidence-set items yet. Add sources to a collection first.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'studioGenerate', 'projectId' => $project->id]); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Output type</label>
                            <select name="output_type" class="form-select" required>
                                <?php foreach ($outputTypes as $key => $info): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($info['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sources (Ctrl/Cmd-click to multi-select)</label>
                            <select name="source_ids[]" class="form-select" multiple size="10" required>
                                <?php foreach ($sourcePool as $s): ?>
                                    <option value="<?php echo (int) $s['object_id']; ?>">
                                        <?php echo htmlspecialchars(($s['title'] ?: 'Untitled') . ($s['identifier'] ? ' (' . $s['identifier'] . ')' : '') . ' - ' . $s['collection_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select 1-20 sources. The LLM will cite each as [N] in the output.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-bolt me-1"></i> Generate
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent artefacts</h5>
            </div>
            <?php if (empty($artefacts)): ?>
                <div class="card-body text-muted">No artefacts yet. Generate one to get started.</div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($artefacts as $a): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="<?php echo url_for(['module' => 'research', 'action' => 'studioShow', 'projectId' => $project->id, 'artefactId' => $a['id']]); ?>">
                                    <strong><?php echo htmlspecialchars($a['title'] ?: ($outputTypes[$a['output_type']]['label'] ?? $a['output_type'])); ?></strong>
                                </a>
                                <div class="small text-muted">
                                    <?php echo htmlspecialchars($outputTypes[$a['output_type']]['label'] ?? $a['output_type']); ?>
                                    &middot; <?php echo htmlspecialchars(date('j M Y H:i', strtotime($a['created_at']))); ?>
                                </div>
                            </div>
                            <?php
                                $statusBadge = ['ready' => 'success', 'generating' => 'info', 'pending' => 'secondary', 'error' => 'danger'][$a['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusBadge; ?>"><?php echo htmlspecialchars($a['status']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
