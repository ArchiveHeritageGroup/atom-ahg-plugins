<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'index']); ?>" class="text-decoration-none text-muted">
                <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'terms']); ?>" class="text-decoration-none text-muted">
                <?php echo __('Terms'); ?>
            </a>
            <i class="fas fa-chevron-right mx-2 small text-muted"></i>
            <?php echo __('Add Term'); ?>
        </h1>
    </div>

    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'termAdd']); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="term"><?php echo __('Term'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="term" name="term" required
                                   placeholder="<?php echo __('e.g., archive, manuscript, photograph'); ?>">
                            <div class="form-text"><?php echo __('The main term to add to the thesaurus.'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="domain"><?php echo __('Domain'); ?></label>
                            <select class="form-select" id="domain" name="domain">
                                <option value="general"><?php echo __('General'); ?></option>
                                <option value="archival"><?php echo __('Archival'); ?></option>
                                <option value="museum"><?php echo __('Museum'); ?></option>
                                <option value="library"><?php echo __('Library'); ?></option>
                                <option value="south_african"><?php echo __('South African'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="relationship"><?php echo __('Relationship Type'); ?></label>
                            <select class="form-select" id="relationship" name="relationship">
                                <option value="exact"><?php echo __('Exact (synonym)'); ?></option>
                                <option value="related"><?php echo __('Related'); ?></option>
                                <option value="broader"><?php echo __('Broader'); ?></option>
                                <option value="narrower"><?php echo __('Narrower'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="weight"><?php echo __('Weight'); ?></label>
                            <input type="number" class="form-control" id="weight" name="weight"
                                   value="0.8" min="0" max="1" step="0.1">
                            <div class="form-text"><?php echo __('Relevance weight (0.0 - 1.0). Higher = more relevant.'); ?></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="synonyms"><?php echo __('Synonyms'); ?></label>
                            <textarea class="form-control" id="synonyms" name="synonyms" rows="10"
                                      placeholder="<?php echo __('Enter one synonym per line...'); ?>"></textarea>
                            <div class="form-text"><?php echo __('Enter each synonym on a new line.'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'terms']); ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i><?php echo __('Save Term'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
