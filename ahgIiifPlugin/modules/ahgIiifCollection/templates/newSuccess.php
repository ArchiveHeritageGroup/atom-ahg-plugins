<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Help') ?></h5>
        </div>
        <div class="card-body">
            <p class="small text-muted"><?php echo __('IIIF Collections group related manifests together, making it easier to browse and share related materials.') ?></p>
            <p class="small text-muted mb-0"><?php echo __('Collections can be nested to create hierarchies.') ?></p>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1><i class="fas fa-plus-circle me-2"></i><?php echo __('Create Collection') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="iiif-collection-form">
    <form method="post" action="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'create']) ?>">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('Collection Details') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label" for="name"><?php echo __('Name') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text"><?php echo __('The display name for this collection.') ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label" for="parent_id"><?php echo __('Parent Collection') ?></label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value=""><?php echo __('— None (Top Level) —') ?></option>
                                <?php foreach ($allCollections as $col): ?>
                                <option value="<?php echo $col->id ?>" <?php echo ($parentId == $col->id) ? 'selected' : '' ?>>
                                    <?php echo esc_entities($col->display_name) ?>
                                </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="description"><?php echo __('Description') ?></label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="attribution"><?php echo __('Attribution') ?></label>
                            <input type="text" class="form-control" id="attribution" name="attribution">
                            <div class="form-text"><?php echo __('Copyright or attribution statement.') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="viewing_hint"><?php echo __('Viewing Hint') ?></label>
                            <select class="form-select" id="viewing_hint" name="viewing_hint">
                                <option value="individuals"><?php echo __('Individuals - Distinct items') ?></option>
                                <option value="paged"><?php echo __('Paged - Book-like sequence') ?></option>
                                <option value="continuous"><?php echo __('Continuous - Scrolling view') ?></option>
                                <option value="multi-part"><?php echo __('Multi-part - Multiple volumes') ?></option>
                                <option value="top"><?php echo __('Top - Top-level collection') ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" checked>
                        <label class="form-check-label" for="is_public">
                            <?php echo __('Public') ?> - <?php echo __('Visible to all users') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-2"></i><?php echo __('Create Collection') ?>
            </button>
            <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i><?php echo __('Cancel') ?>
            </a>
        </div>
    </form>
</div>
<?php end_slot() ?>
