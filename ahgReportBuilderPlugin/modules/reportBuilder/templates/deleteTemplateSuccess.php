<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-trash text-danger me-2"></i><?php echo __('Delete Template'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$rawTemplate = $sf_data->getRaw('template');
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'templates']); ?>"><?php echo __('Templates'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Delete'); ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-danger fs-1 d-block mb-3"></i>
                <h5><?php echo __('Delete Template'); ?></h5>
                <p class="text-muted mb-3">
                    <?php echo __('Are you sure you want to delete the template'); ?>
                    <strong>"<?php echo htmlspecialchars($rawTemplate->name); ?>"</strong>?
                </p>
                <p class="text-danger small mb-4"><?php echo __('This action cannot be undone.'); ?></p>

                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'templates']); ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg me-1"></i><?php echo __('Cancel'); ?>
                    </a>
                    <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'deleteTemplate', 'id' => $rawTemplate->id, 'confirm' => 1]); ?>" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i><?php echo __('Delete'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>
