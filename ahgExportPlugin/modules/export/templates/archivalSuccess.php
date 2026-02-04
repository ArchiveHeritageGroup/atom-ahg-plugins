<?php $format = $format ?? 'csv'; ?>
<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
<h1><i class="bi bi-archive me-2"></i><?php echo __('Export Archival Descriptions'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>"><?php echo __('Export'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Archival Descriptions'); ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-download me-2"></i><?php echo __('Export Settings'); ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'export', 'action' => 'archival']); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Export Format'); ?></label>
                            <select name="format" class="form-select">
                                <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV (Bulk Export)</option>
                                <option value="ead" <?php echo $format === 'ead' ? 'selected' : ''; ?>>EAD 2002 (Single Record)</option>
                                <option value="dc" <?php echo $format === 'dc' ? 'selected' : ''; ?>>Dublin Core (Single Record)</option>
                            </select>
                            <div class="form-text"><?php echo __('CSV supports bulk export. EAD/DC require selecting a specific record.'); ?></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Repository'); ?></label>
                            <select name="repository_id" class="form-select">
                                <option value=""><?php echo __('All repositories'); ?></option>
                                <?php foreach ($repositories as $repo): ?>
                                <option value="<?php echo $repo->id; ?>"><?php echo esc_entities($repo->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Level of Description'); ?></label>
                            <select name="level_ids[]" class="form-select" multiple size="5">
                                <?php foreach ($levels as $level): ?>
                                <option value="<?php echo $level->id; ?>"><?php echo esc_entities($level->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?php echo __('Hold Ctrl/Cmd to select multiple. Leave empty for all levels.'); ?></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Limit'); ?></label>
                            <select name="limit" class="form-select">
                                <option value="0"><?php echo __('No limit (all records)'); ?></option>
                                <option value="100">100 <?php echo __('records'); ?></option>
                                <option value="500">500 <?php echo __('records'); ?></option>
                                <option value="1000">1,000 <?php echo __('records'); ?></option>
                                <option value="5000">5,000 <?php echo __('records'); ?></option>
                                <option value="10000">10,000 <?php echo __('records'); ?></option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back'); ?>
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-download me-1"></i><?php echo __('Export'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i><?php echo __('Statistics'); ?></h6>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt><?php echo __('Total Descriptions'); ?></dt>
                    <dd class="h3 text-primary"><?php echo number_format($recordCount); ?></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-question-circle me-2"></i><?php echo __('Export Tips'); ?></h6>
            </div>
            <div class="card-body small">
                <p><strong>CSV Export:</strong> Best for bulk data extraction and spreadsheet analysis.</p>
                <p><strong>EAD/DC Export:</strong> Navigate to a specific fonds or collection, then use Export from the "More" menu.</p>
                <p class="mb-0"><strong>Large exports:</strong> Consider using filters to reduce the dataset size.</p>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>
