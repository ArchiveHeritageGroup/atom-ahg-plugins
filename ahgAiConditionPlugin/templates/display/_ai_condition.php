<?php if (isset($resource) && $resource->id): ?>
<a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'assess']) ?>?object_id=<?php echo $resource->id ?>" class="btn btn-sm btn-outline-info" title="<?php echo __('AI Condition Scan') ?>">
    <i class="fas fa-robot me-1"></i><?php echo __('AI Scan') ?>
</a>
<?php endif ?>
