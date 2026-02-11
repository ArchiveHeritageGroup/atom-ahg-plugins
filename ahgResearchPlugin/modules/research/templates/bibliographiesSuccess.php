<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Bibliographies</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-book text-primary me-2"></i>My Bibliographies</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBibModal">
        <i class="fas fa-plus me-1"></i> New Bibliography
    </button>
</div>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<?php if (!empty($bibliographies)): ?>
    <div class="row">
        <?php foreach ($bibliographies as $bib): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewBibliography', 'id' => $bib->id]); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($bib->name); ?>
                            </a>
                        </h5>
                        <?php if ($bib->description): ?>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($bib->description, 0, 100)); ?></p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary"><?php echo $bib->entry_count ?? 0; ?> entries</span>
                            <small class="text-muted"><?php echo ucfirst($bib->citation_style ?? 'chicago'); ?> style</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bib->id, 'format' => 'ris']); ?>" class="btn btn-outline-secondary" title="Export RIS">RIS</a>
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bib->id, 'format' => 'bibtex']); ?>" class="btn btn-outline-secondary" title="Export BibTeX">BibTeX</a>
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportBibliography', 'id' => $bib->id, 'format' => 'zotero']); ?>" class="btn btn-outline-secondary" title="Export Zotero">Zotero</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
            <h5>No Bibliographies Yet</h5>
            <p class="text-muted">Create a bibliography to organize your research citations.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBibModal">
                <i class="fas fa-plus me-1"></i> Create Bibliography
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Create Bibliography Modal -->
<div class="modal fade" id="createBibModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Create Bibliography</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Thesis Bibliography">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Citation Style</label>
                        <select name="citation_style" class="form-select">
                            <option value="chicago">Chicago</option>
                            <option value="mla">MLA</option>
                            <option value="apa">APA</option>
                            <option value="harvard">Harvard</option>
                            <option value="turabian">Turabian</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
