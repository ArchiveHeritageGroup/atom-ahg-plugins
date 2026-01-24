<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links') ?></h5>
        </div>
        <div class="card-body">
            <a href="<?php echo url_for(['module' => 'iiifCollection', 'action' => 'view', 'id' => $collection->id]) ?>" class="btn btn-outline-primary w-100 mb-2">
                <i class="fas fa-eye me-2"></i><?php echo __('View Collection') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'iiifCollection', 'action' => 'addItems', 'id' => $collection->id]) ?>" class="btn btn-outline-success w-100">
                <i class="fas fa-plus me-2"></i><?php echo __('Add Items') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1><i class="fas fa-edit me-2"></i><?php echo __('Edit Collection') ?></h1>
<h2><?php echo esc_entities($collection->display_name) ?></h2>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="iiif-collection-form">
    <form method="post" action="<?php echo url_for(['module' => 'iiifCollection', 'action' => 'update', 'id' => $collection->id]) ?>">
        <input type="hidden" name="id" value="<?php echo $collection->id ?>">
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('Collection Details') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label" for="name"><?php echo __('Name') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo esc_entities($collection->name) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label" for="parent_id"><?php echo __('Parent Collection') ?></label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value=""><?php echo __('— None (Top Level) —') ?></option>
                                <?php foreach ($allCollections as $col): ?>
                                    <?php if ($col->id != $collection->id): ?>
                                    <option value="<?php echo $col->id ?>" <?php echo ($collection->parent_id == $col->id) ? 'selected' : '' ?>>
                                        <?php echo esc_entities($col->display_name) ?>
                                    </option>
                                    <?php endif ?>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label" for="description"><?php echo __('Description') ?></label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo esc_entities($collection->description) ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="attribution"><?php echo __('Attribution') ?></label>
                            <input type="text" class="form-control" id="attribution" name="attribution" value="<?php echo esc_entities($collection->attribution) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="viewing_hint"><?php echo __('Viewing Hint') ?></label>
                            <select class="form-select" id="viewing_hint" name="viewing_hint">
                                <option value="individuals" <?php echo ($collection->viewing_hint == 'individuals') ? 'selected' : '' ?>><?php echo __('Individuals') ?></option>
                                <option value="paged" <?php echo ($collection->viewing_hint == 'paged') ? 'selected' : '' ?>><?php echo __('Paged') ?></option>
                                <option value="continuous" <?php echo ($collection->viewing_hint == 'continuous') ? 'selected' : '' ?>><?php echo __('Continuous') ?></option>
                                <option value="multi-part" <?php echo ($collection->viewing_hint == 'multi-part') ? 'selected' : '' ?>><?php echo __('Multi-part') ?></option>
                                <option value="top" <?php echo ($collection->viewing_hint == 'top') ? 'selected' : '' ?>><?php echo __('Top') ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" <?php echo $collection->is_public ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">
                            <?php echo __('Public') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save me-2"></i><?php echo __('Save Changes') ?>
            </button>
            <a href="<?php echo url_for(['module' => 'iiifCollection', 'action' => 'view', 'id' => $collection->id]) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i><?php echo __('Cancel') ?>
            </a>
        </div>
    </form>
</div>
<?php end_slot() ?>
