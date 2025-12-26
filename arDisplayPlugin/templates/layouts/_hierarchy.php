<?php
/**
 * Hierarchy layout - tree view for archives
 */
$ancestors = $data['ancestors'] ?? [];
$children = $data['children'] ?? [];
?>

<?php // Breadcrumb/Ancestors ?>
<?php if (!empty($ancestors)): ?>
<nav class="hierarchy-breadcrumb mb-3" aria-label="Hierarchy">
    <ol class="breadcrumb mb-0">
        <?php foreach ($ancestors as $a): ?>
        <li class="breadcrumb-item">
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $a->slug]); ?>">
                <?php echo $a->title ?? $a->identifier; ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li class="breadcrumb-item active"><?php echo $object->title ?? $object->identifier; ?></li>
    </ol>
</nav>
<?php endif; ?>

<?php // Current Object Summary ?>
<div class="hierarchy-current card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start">
            <?php if ($digitalObject): ?>
            <img src="<?php echo $digitalObject->path; ?>" class="me-3 rounded" style="max-width: 80px;" alt="">
            <?php endif; ?>
            <div class="flex-grow-1">
                <h4 class="mb-1">
                    <span class="badge bg-secondary me-2"><?php echo $object->level_name; ?></span>
                    <?php echo $object->title ?? 'Untitled'; ?>
                </h4>
                <?php if ($object->identifier): ?>
                <p class="text-muted mb-2"><?php echo $object->identifier; ?></p>
                <?php endif; ?>
                <?php if (!empty($fields['description']['scope_content'])): ?>
                <p class="mb-0"><?php echo substr(strip_tags($fields['description']['scope_content']['value']), 0, 300); ?>...</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php // Children ?>
<?php if (!empty($children)): ?>
<div class="hierarchy-children">
    <h5 class="mb-3">
        <i class="fas fa-folder-open me-2 text-muted"></i>
        Contents (<?php echo count($children); ?>)
    </h5>
    <div class="list-group">
        <?php foreach ($children as $child): ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $child->slug]); ?>" 
           class="list-group-item list-group-item-action d-flex align-items-center">
            <?php if ($child->thumbnail_path): ?>
            <img src="<?php echo $child->thumbnail_path; ?>" class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="">
            <?php else: ?>
            <div class="me-3 text-muted" style="width: 50px; text-align: center;">
                <i class="fas <?php echo get_level_icon(strtolower($child->level_name ?? 'file')); ?> fa-2x"></i>
            </div>
            <?php endif; ?>
            <div class="flex-grow-1">
                <strong><?php echo $child->title ?? 'Untitled'; ?></strong>
                <?php if ($child->identifier): ?>
                <br><small class="text-muted"><?php echo $child->identifier; ?></small>
                <?php endif; ?>
            </div>
            <span class="badge bg-light text-dark"><?php echo $child->level_name; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-light">
    <i class="fas fa-info-circle me-2"></i>No child items
</div>
<?php endif; ?>
