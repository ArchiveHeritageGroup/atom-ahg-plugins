<?php use_helper('Javascript') ?>

<h1><?php echo __('Validate Import File') ?></h1>

<?php if ($error): ?>
<div class="alert alert-danger">
    <strong><?php echo __('Error:') ?></strong> <?php echo $error ?>
</div>
<?php endif ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?php echo __('Validation Settings') ?></h5>
    </div>
    <div class="card-body">
        <form id="validateForm" method="post" action="<?php echo url_for('dataMigration/validate') ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="filename" class="form-label"><?php echo __('File') ?></label>
                    <input type="text" class="form-control" id="filename" name="filename"
                           value="<?php echo esc_entities($filename) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="sector" class="form-label"><?php echo __('Sector') ?></label>
                    <select class="form-select" id="sector" name="sector">
                        <option value="archive" <?php echo 'archive' === $sector ? 'selected' : '' ?>><?php echo __('Archives (ISAD-G)') ?></option>
                        <option value="museum" <?php echo 'museum' === $sector ? 'selected' : '' ?>><?php echo __('Museum (Spectrum)') ?></option>
                        <option value="library" <?php echo 'library' === $sector ? 'selected' : '' ?>><?php echo __('Library (MARC/RDA)') ?></option>
                        <option value="gallery" <?php echo 'gallery' === $sector ? 'selected' : '' ?>><?php echo __('Gallery (CCO)') ?></option>
                        <option value="dam" <?php echo 'dam' === $sector ? 'selected' : '' ?>><?php echo __('Digital Assets (DC)') ?></option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="mapping_id" class="form-label"><?php echo __('Mapping Profile (optional)') ?></label>
                    <select class="form-select" id="mapping_id" name="mapping_id">
                        <option value=""><?php echo __('-- No mapping --') ?></option>
                        <!-- Populated via JavaScript -->
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="duplicate_strategy" class="form-label"><?php echo __('Duplicate Detection') ?></label>
                    <select class="form-select" id="duplicate_strategy" name="duplicate_strategy">
                        <option value="identifier"><?php echo __('Match by Identifier') ?></option>
                        <option value="legacyId"><?php echo __('Match by Legacy ID') ?></option>
                        <option value="title_date"><?php echo __('Match by Title + Date') ?></option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <strong><?php echo __('Validation Options') ?></strong>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="validate_schema" name="validate_schema" value="1" checked>
                        <label class="form-check-label" for="validate_schema"><?php echo __('Schema Validation') ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="validate_referential" name="validate_referential" value="1" checked>
                        <label class="form-check-label" for="validate_referential"><?php echo __('Referential Integrity') ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="validate_duplicates" name="validate_duplicates" value="1" checked>
                        <label class="form-check-label" for="validate_duplicates"><?php echo __('Duplicate Detection') ?></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="check_database" name="check_database" value="1" checked>
                        <label class="form-check-label" for="check_database"><?php echo __('Check Database') ?></label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?php echo __('Validate File') ?>
                </button>
                <a href="<?php echo url_for('dataMigration/index') ?>" class="btn btn-secondary">
                    <?php echo __('Cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php if ($report): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <?php echo __('Validation Results') ?>
            <?php if ($report->isValid()): ?>
                <span class="badge bg-success ms-2"><?php echo __('Valid') ?></span>
            <?php else: ?>
                <span class="badge bg-danger ms-2"><?php echo __('Invalid') ?></span>
            <?php endif ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h3 class="mb-0"><?php echo $report->getTotalRows() ?></h3>
                        <small class="text-muted"><?php echo __('Total Rows') ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body py-2">
                        <h3 class="mb-0"><?php echo $report->getValidRows() ?></h3>
                        <small><?php echo __('Valid Rows') ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center <?php echo $report->getErrorCount() > 0 ? 'bg-danger text-white' : '' ?>">
                    <div class="card-body py-2">
                        <h3 class="mb-0"><?php echo $report->getErrorCount() ?></h3>
                        <small><?php echo __('Errors') ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center <?php echo $report->getWarningCount() > 0 ? 'bg-warning' : '' ?>">
                    <div class="card-body py-2">
                        <h3 class="mb-0"><?php echo $report->getWarningCount() ?></h3>
                        <small><?php echo __('Warnings') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($report->getErrorCount() > 0 || $report->getWarningCount() > 0): ?>
        <h6><?php echo __('Issues Found') ?></h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th style="width: 80px"><?php echo __('Row') ?></th>
                        <th style="width: 150px"><?php echo __('Column') ?></th>
                        <th style="width: 100px"><?php echo __('Severity') ?></th>
                        <th><?php echo __('Message') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 0;
                    foreach ($report->getRowErrors() as $row => $columns):
                        foreach ($columns as $column => $issues):
                            foreach ($issues as $issue):
                                if ($count >= 100) {
                                    break 3;
                                }
                                ++$count;
                    ?>
                    <tr>
                        <td><?php echo $row ?></td>
                        <td><code><?php echo esc_entities($column) ?></code></td>
                        <td>
                            <?php if ('error' === $issue['severity']): ?>
                                <span class="badge bg-danger"><?php echo __('Error') ?></span>
                            <?php elseif ('warning' === $issue['severity']): ?>
                                <span class="badge bg-warning text-dark"><?php echo __('Warning') ?></span>
                            <?php else: ?>
                                <span class="badge bg-info"><?php echo __('Info') ?></span>
                            <?php endif ?>
                        </td>
                        <td><?php echo esc_entities($issue['message']) ?></td>
                    </tr>
                    <?php
                            endforeach;
                        endforeach;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($report->getErrorCount() + $report->getWarningCount() > 100): ?>
        <p class="text-muted">
            <em><?php echo __('Showing first 100 issues. Download full report for complete list.') ?></em>
        </p>
        <?php endif ?>

        <?php endif ?>

        <?php
        $violations = $report->getRuleViolations();
        if (!empty($violations)):
        ?>
        <h6 class="mt-4"><?php echo __('Issues by Rule') ?></h6>
        <table class="table table-sm" style="max-width: 400px">
            <thead>
                <tr>
                    <th><?php echo __('Rule') ?></th>
                    <th class="text-end"><?php echo __('Count') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $rule => $ruleCount): ?>
                <tr>
                    <td><code><?php echo esc_entities($rule) ?></code></td>
                    <td class="text-end"><?php echo $ruleCount ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>

        <p class="text-muted mt-3 mb-0">
            <small>
                <?php echo __('Validation completed in %1% seconds', ['%1%' => number_format($report->getElapsedTime(), 2)]) ?>
            </small>
        </p>
    </div>
</div>
<?php endif ?>
