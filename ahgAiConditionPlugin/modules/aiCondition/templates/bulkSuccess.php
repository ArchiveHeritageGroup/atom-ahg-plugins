<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Bulk Scan') ?></h6></div>
        <div class="card-body py-2 small">
            <p class="text-muted"><?php echo __('Scan all digital objects in a repository or collection through AI assessment.') ?></p>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary w-100">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Assessments') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-layer-group me-2"></i><?php echo __('Bulk Condition Scan') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Scan multiple objects for condition assessment') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card mb-3">
    <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Configure Scan') ?></h6></div>
    <div class="card-body">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Repository') ?></label>
            <div class="col-sm-9">
                <select class="form-select form-select-sm" id="bulkRepository">
                    <option value=""><?php echo __('-- All repositories --') ?></option>
                    <?php foreach ($repositories as $r): ?>
                    <option value="<?php echo $r->id ?>"><?php echo esc_entities($r->name) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Max Objects') ?></label>
            <div class="col-sm-9">
                <input type="number" class="form-control form-control-sm" id="bulkLimit" value="50" min="1" max="1000">
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Min Confidence') ?></label>
            <div class="col-sm-9">
                <input type="number" class="form-control form-control-sm" id="bulkConfidence" value="0.25" min="0.1" max="0.9" step="0.05">
            </div>
        </div>
        <div class="alert alert-warning small py-1">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('Bulk scans run as a background CLI task. Use the command below to start:') ?>
        </div>
        <div class="bg-dark text-light p-2 rounded small font-monospace" id="bulkCommand">
            php symfony ai-condition:bulk-scan --limit=50 --confidence=0.25
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function updateCommand() {
    var repo = document.getElementById('bulkRepository').value;
    var limit = document.getElementById('bulkLimit').value;
    var conf = document.getElementById('bulkConfidence').value;
    var cmd = 'php symfony ai-condition:bulk-scan --limit=' + limit + ' --confidence=' + conf;
    if (repo) cmd += ' --repository=' + repo;
    document.getElementById('bulkCommand').textContent = cmd;
}
document.getElementById('bulkRepository').addEventListener('change', updateCommand);
document.getElementById('bulkLimit').addEventListener('input', updateCommand);
document.getElementById('bulkConfidence').addEventListener('input', updateCommand);
</script>
<?php end_slot() ?>
