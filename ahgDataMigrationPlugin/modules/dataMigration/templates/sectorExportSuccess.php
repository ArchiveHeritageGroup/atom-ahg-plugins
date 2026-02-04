<?php use_helper('Javascript') ?>

<h1><?php echo __('Export Records by Sector') ?></h1>

<?php if ($error): ?>
<div class="alert alert-danger">
    <strong><?php echo __('Error:') ?></strong> <?php echo $error ?>
</div>
<?php endif ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?php echo __('Sector Export') ?> - <?php echo ucfirst($sector) ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo url_for('dataMigration/sectorExport?sector='.$sector) ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="repository" class="form-label"><?php echo __('Repository (optional)') ?></label>
                    <select class="form-select" id="repository" name="repository">
                        <option value=""><?php echo __('-- All Repositories --') ?></option>
                        <?php foreach ($repositories as $repo): ?>
                        <option value="<?php echo $repo->id ?>"><?php echo esc_entities($repo->name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="limit" class="form-label"><?php echo __('Maximum Records') ?></label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="100">100</option>
                        <option value="500">500</option>
                        <option value="1000" selected>1,000</option>
                        <option value="5000">5,000</option>
                        <option value="10000">10,000</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label"><?php echo __('Or enter specific Record IDs (comma-separated)') ?></label>
                    <input type="text" class="form-control" id="record_ids" name="record_ids"
                           placeholder="e.g., 123, 456, 789">
                    <div class="form-text"><?php echo __('Leave empty to use repository/limit filters above') ?></div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-download"></i> <?php echo __('Export CSV') ?>
                </button>
                <a href="<?php echo url_for('dataMigration/index') ?>" class="btn btn-secondary">
                    <?php echo __('Cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?php echo __('Sector Export Templates') ?></h5>
    </div>
    <div class="card-body">
        <p><?php echo __('Quick links to export records using different sector templates:') ?></p>
        <div class="list-group">
            <a href="<?php echo url_for('dataMigration/sectorExport?sector=archive') ?>"
               class="list-group-item list-group-item-action <?php echo 'archive' === $sector ? 'active' : '' ?>">
                <i class="bi bi-archive me-2"></i> <?php echo __('Archives (ISAD-G)') ?>
                <small class="text-muted d-block"><?php echo __('Export with ISAD-G compliant columns') ?></small>
            </a>
            <a href="<?php echo url_for('dataMigration/sectorExport?sector=museum') ?>"
               class="list-group-item list-group-item-action <?php echo 'museum' === $sector ? 'active' : '' ?>">
                <i class="bi bi-bank me-2"></i> <?php echo __('Museum (Spectrum)') ?>
                <small class="text-muted d-block"><?php echo __('Export with Spectrum 5.0 compliant columns') ?></small>
            </a>
            <a href="<?php echo url_for('dataMigration/sectorExport?sector=library') ?>"
               class="list-group-item list-group-item-action <?php echo 'library' === $sector ? 'active' : '' ?>">
                <i class="bi bi-book me-2"></i> <?php echo __('Library (MARC/RDA)') ?>
                <small class="text-muted d-block"><?php echo __('Export with MARC/RDA compliant columns') ?></small>
            </a>
            <a href="<?php echo url_for('dataMigration/sectorExport?sector=gallery') ?>"
               class="list-group-item list-group-item-action <?php echo 'gallery' === $sector ? 'active' : '' ?>">
                <i class="bi bi-easel me-2"></i> <?php echo __('Gallery (CCO)') ?>
                <small class="text-muted d-block"><?php echo __('Export with CCO compliant columns') ?></small>
            </a>
            <a href="<?php echo url_for('dataMigration/sectorExport?sector=dam') ?>"
               class="list-group-item list-group-item-action <?php echo 'dam' === $sector ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-image me-2"></i> <?php echo __('Digital Assets (DC)') ?>
                <small class="text-muted d-block"><?php echo __('Export with Dublin Core compliant columns') ?></small>
            </a>
        </div>
    </div>
</div>
