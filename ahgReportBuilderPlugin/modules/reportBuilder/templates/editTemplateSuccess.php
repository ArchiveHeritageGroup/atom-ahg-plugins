<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-pencil text-primary me-2"></i><?php echo __('Edit Template'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$rawTemplate = $sf_data->getRaw('template');
$categories = ['NARSSA', 'GRAP 103', 'Accession', 'Condition', 'Custom'];
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'templates']); ?>"><?php echo __('Templates'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Edit'); ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4"><?php echo __('Edit Template'); ?>: <?php echo htmlspecialchars($rawTemplate->name); ?></h5>

                <form method="post" action="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'editTemplate', 'id' => $rawTemplate->id]); ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label"><?php echo __('Template Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($rawTemplate->name); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label"><?php echo __('Description'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($rawTemplate->description ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label"><?php echo __('Category'); ?></label>
                        <select class="form-select" id="category" name="category">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo ($rawTemplate->category === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'templates']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i><?php echo __('Cancel'); ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?php echo __('Save Changes'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>
