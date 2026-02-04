<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-plus-circle text-primary me-2"></i><?php echo __('Create New Report'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Create New Report'); ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear me-2"></i><?php echo __('Report Settings'); ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'create']); ?>">
                    <div class="mb-4">
                        <label for="name" class="form-label"><?php echo __('Report Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="name" name="name" required
                               placeholder="<?php echo __('e.g., Monthly Accessions Report'); ?>">
                        <div class="form-text"><?php echo __('Choose a descriptive name for your report.'); ?></div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label"><?php echo __('Description'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="<?php echo __('Briefly describe what this report shows...'); ?>"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><?php echo __('Data Source'); ?> <span class="text-danger">*</span></label>
                        <div class="form-text mb-3"><?php echo __('Select the type of data you want to report on.'); ?></div>

                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                            <?php foreach ($dataSources as $key => $source): ?>
                            <div class="col">
                                <input type="radio" class="btn-check" name="data_source" id="source_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $key === 'information_object' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3 data-source-btn" for="source_<?php echo $key; ?>" style="min-height: 140px;">
                                    <i class="bi <?php echo $source['icon']; ?> fs-2 mb-2"></i>
                                    <span class="fw-bold text-center" style="word-break: break-word;"><?php echo $source['label']; ?></span>
                                    <small class="text-muted mt-1 text-center px-1 d-block" style="font-size: 0.65rem; line-height: 1.2; word-break: break-word; overflow-wrap: break-word;"><?php echo $source['description']; ?></small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i><?php echo __('Cancel'); ?>
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-right me-1"></i><?php echo __('Continue to Designer'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.btn-check:checked + .btn-outline-primary {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}
.btn-check:checked + .btn-outline-primary .text-muted {
    color: rgba(255,255,255,0.8) !important;
}
/* Fix text overflow in data source cards - override Bootstrap button white-space */
.data-source-btn {
    white-space: normal !important;
    overflow: hidden !important;
    max-width: 100% !important;
}
.data-source-btn span,
.data-source-btn small {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    word-break: break-word !important;
    hyphens: auto !important;
    max-width: 100% !important;
    width: 100% !important;
    display: block !important;
}
.data-source-btn small {
    line-height: 1.2 !important;
    font-size: 0.65rem !important;
}
</style>
<?php end_slot() ?>
