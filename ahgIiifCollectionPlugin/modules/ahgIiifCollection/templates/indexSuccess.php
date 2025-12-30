<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('IIIF Collections') ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php echo __('Organize and group related IIIF manifests into collections for easy browsing and discovery.') ?></p>
            <?php if ($sf_user->isAuthenticated()): ?>
            <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'new', 'parent_id' => $parentId]) ?>" class="btn btn-success w-100">
                <i class="fas fa-plus me-2"></i><?php echo __('Create Collection') ?>
            </a>
            <?php endif ?>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1>
    <i class="fas fa-layer-group me-2"></i>
    <?php if (isset($parentCollection)): ?>
        <?php echo esc_entities($parentCollection->display_name) ?>
    <?php else: ?>
        <?php echo __('IIIF Collections') ?>
    <?php endif ?>
</h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="iiif-collections">
    <?php if (isset($parentCollection)): ?>
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'index']) ?>"><?php echo __('Collections') ?></a></li>
            <li class="breadcrumb-item active"><?php echo esc_entities($parentCollection->display_name) ?></li>
        </ol>
    </nav>
    <?php endif ?>

    <?php if (empty($collections)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No collections found.') ?>
        <?php if ($sf_user->isAuthenticated()): ?>
        <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'new']) ?>"><?php echo __('Create your first collection') ?></a>
        <?php endif ?>
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($collections as $collection): ?>
        <div class="col">
            <div class="card h-100 collection-card">
                <?php if ($collection->thumbnail_url): ?>
                <img src="<?php echo esc_entities($collection->thumbnail_url) ?>" class="card-img-top" alt="<?php echo esc_entities($collection->display_name) ?>" style="height: 150px; object-fit: cover;">
                <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                    <i class="fas fa-layer-group fa-4x text-muted"></i>
                </div>
                <?php endif ?>
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'view', 'id' => $collection->id]) ?>">
                            <?php echo esc_entities($collection->display_name) ?>
                        </a>
                    </h5>
                    <?php if ($collection->display_description): ?>
                    <p class="card-text text-muted small"><?php echo esc_entities(mb_substr($collection->display_description, 0, 100)) ?>...</p>
                    <?php endif ?>
                </div>
                <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                    <span class="badge bg-secondary">
                        <i class="fas fa-images me-1"></i><?php echo $collection->item_count ?> <?php echo __('items') ?>
                    </span>
                    <div class="btn-group btn-group-sm">
                        <a href="<?php echo '/index.php/ahgIiifCollection/manifest?slug=' . $collection->slug ?>" class="btn btn-outline-info" title="<?php echo __('IIIF Manifest') ?>" target="_blank">
                            <i class="fas fa-code"></i>
                        </a>
                        <?php if ($sf_user->isAuthenticated()): ?>
                        <a href="<?php echo url_for(['module' => 'ahgIiifCollection', 'action' => 'edit', 'id' => $collection->id]) ?>" class="btn btn-outline-primary" title="<?php echo __('Edit') ?>">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>
<?php end_slot() ?>
