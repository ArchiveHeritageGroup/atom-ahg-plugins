<?php
$session = $sf_data->getRaw('session');
$stats = $sf_data->getRaw('stats') ?? [];
$errors = $sf_data->getRaw('errors') ?? [];
$rowCount = $sf_data->getRaw('rowCount') ?? 0;
?>

<h1><?php echo __('Validation Report') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => esc_entities($session->title ?: __('Session #' . $session->id))],
        ['title' => __('Validate')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-success rounded-pill">1</span><br><small class="text-muted"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">2</span><br><small class="text-muted"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">3</span><br><small class="text-muted"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">4</span><br><small class="fw-bold"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">5</span><br><small class="text-muted"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-secondary rounded-pill">6</span><br><small class="text-muted"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 58%"></div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-0"><?php echo $stats['total'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Total Rows') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <h3 class="mb-0 text-success"><?php echo $stats['valid'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Valid') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h3 class="mb-0 text-warning"><?php echo $stats['warnings'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Warnings') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h3 class="mb-0 text-danger"><?php echo $stats['errors'] ?? 0 ?></h3>
                <small class="text-muted"><?php echo __('Errors') ?></small>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Issues') ?></h5>
        <span class="badge bg-secondary"><?php echo count($errors) ?> <?php echo __('issues') ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 8%"><?php echo __('Row') ?></th>
                        <th style="width: 10%"><?php echo __('Severity') ?></th>
                        <th style="width: 15%"><?php echo __('Field') ?></th>
                        <th><?php echo __('Message') ?></th>
                        <th style="width: 20%"><?php echo __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($errors as $err): ?>
                        <tr>
                            <td><strong>#<?php echo $err->row_number ?></strong></td>
                            <td>
                                <?php if ($err->severity === 'error'): ?>
                                    <span class="badge bg-danger"><?php echo __('Error') ?></span>
                                <?php elseif ($err->severity === 'warning'): ?>
                                    <span class="badge bg-warning text-dark"><?php echo __('Warning') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info"><?php echo __('Info') ?></span>
                                <?php endif ?>
                            </td>
                            <td><code><?php echo esc_entities($err->field_name ?? '') ?></code></td>
                            <td><?php echo esc_entities($err->message) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($err->field_name): ?>
                                        <button type="button" class="btn btn-outline-primary btn-fix"
                                                data-row="<?php echo $err->row_number ?>"
                                                data-field="<?php echo esc_entities($err->field_name) ?>">
                                            <i class="fas fa-edit"></i> <?php echo __('Fix') ?>
                                        </button>
                                    <?php endif ?>
                                    <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'validate', 'id' => $session->id]) ?>" class="d-inline">
                                        <input type="hidden" name="form_action" value="exclude">
                                        <input type="hidden" name="row_number" value="<?php echo $err->row_number ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-times"></i> <?php echo __('Exclude') ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?php echo __('All rows passed validation.') ?>
    </div>
<?php endif ?>

<!-- Inline Fix Modal -->
<div class="modal fade" id="fixModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'validate', 'id' => $session->id]) ?>">
                <input type="hidden" name="form_action" value="fix">
                <input type="hidden" name="row_number" id="fix_row_number">
                <input type="hidden" name="field_name" id="fix_field_name">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('Fix Field Value') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Row') ?>: <strong id="fix_row_label"></strong></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Field') ?>: <code id="fix_field_label"></code></label>
                    </div>
                    <div class="mb-3">
                        <label for="fix_value" class="form-label"><?php echo __('New value') ?></label>
                        <input type="text" class="form-control" id="fix_value" name="field_value" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Apply Fix') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'map', 'id' => $session->id]) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Mapping') ?>
    </a>
    <div>
        <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'validate', 'id' => $session->id]) ?>" class="d-inline">
            <input type="hidden" name="form_action" value="validate">
            <button type="submit" class="btn btn-outline-secondary me-2">
                <i class="fas fa-sync me-1"></i><?php echo __('Re-validate') ?>
            </button>
        </form>
        <?php $validCount = $stats['valid'] ?? 0; ?>
        <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'validate', 'id' => $session->id]) ?>" class="d-inline">
            <input type="hidden" name="form_action" value="proceed">
            <button type="submit" class="btn btn-primary" <?php echo $validCount === 0 ? 'disabled' : '' ?>>
                <i class="fas fa-eye me-1"></i><?php echo __('Preview') ?> (<?php echo $validCount ?> <?php echo __('valid rows') ?>)
                <i class="fas fa-arrow-right ms-1"></i>
            </button>
        </form>
        <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'validate', 'id' => $session->id]) ?>" class="d-inline ms-2">
            <input type="hidden" name="form_action" value="commit">
            <button type="submit" class="btn btn-success" <?php echo $validCount === 0 ? 'disabled' : '' ?>>
                <i class="fas fa-check me-1"></i><?php echo __('Commit') ?> (<?php echo $validCount ?> <?php echo __('rows') ?>)
            </button>
        </form>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-fix').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('fix_row_number').value = this.dataset.row;
            document.getElementById('fix_field_name').value = this.dataset.field;
            document.getElementById('fix_row_label').textContent = '#' + this.dataset.row;
            document.getElementById('fix_field_label').textContent = this.dataset.field;
            document.getElementById('fix_value').value = '';
            new bootstrap.Modal(document.getElementById('fixModal')).show();
        });
    });
});
</script>
