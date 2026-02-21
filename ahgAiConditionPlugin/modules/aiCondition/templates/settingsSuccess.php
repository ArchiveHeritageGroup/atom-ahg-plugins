<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('AI Condition Settings') ?></h6></div>
        <div class="card-body py-2 small">
            <p class="text-muted"><?php echo __('Configure the AI condition assessment service connection and defaults.') ?></p>
            <a href="<?php echo url_for(['module' => 'aiCondition', 'action' => 'index']) ?>" class="btn btn-sm btn-outline-secondary w-100">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Assessments') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i><?php echo __('AI Condition Settings') ?></h1>
<p class="text-muted small mb-3"><?php echo __('Configure service connection and assessment defaults') ?></p>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<form method="post" action="<?php echo url_for(['module' => 'aiCondition', 'action' => 'settings']) ?>">
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Service Connection') ?></h6></div>
        <div class="card-body">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Service URL') ?></label>
                <div class="col-sm-7">
                    <input type="url" class="form-control form-control-sm" name="ai_condition_service_url" value="<?php echo esc_entities($settings['ai_condition_service_url']) ?>">
                </div>
                <div class="col-sm-2">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="testBtn">
                        <i class="fas fa-plug me-1"></i><?php echo __('Test') ?>
                    </button>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('API Key') ?></label>
                <div class="col-sm-9">
                    <input type="text" class="form-control form-control-sm" name="ai_condition_api_key" value="<?php echo esc_entities($settings['ai_condition_api_key']) ?>">
                </div>
            </div>
            <div id="testResult" style="display:none"></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><?php echo __('Assessment Defaults') ?></h6></div>
        <div class="card-body">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Min Confidence') ?></label>
                <div class="col-sm-9">
                    <input type="number" class="form-control form-control-sm" name="ai_condition_min_confidence" value="<?php echo esc_entities($settings['ai_condition_min_confidence']) ?>" min="0.1" max="0.9" step="0.05">
                    <div class="form-text"><?php echo __('Minimum confidence threshold for damage detection (0.1 - 0.9)') ?></div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Overlay Enabled') ?></label>
                <div class="col-sm-9">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ai_condition_overlay_enabled" value="1" <?php echo $settings['ai_condition_overlay_enabled'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small"><?php echo __('Generate annotated overlay images with bounding boxes') ?></label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Auto-Scan on Upload') ?></label>
                <div class="col-sm-9">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ai_condition_auto_scan" value="1" <?php echo $settings['ai_condition_auto_scan'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small"><?php echo __('Automatically scan digital objects when uploaded') ?></label>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label col-form-label-sm"><?php echo __('Alert Grade') ?></label>
                <div class="col-sm-9">
                    <select class="form-select form-select-sm" name="ai_condition_notify_grade">
                        <?php foreach (['excellent','good','fair','poor','critical'] as $g): ?>
                        <option value="<?php echo $g ?>" <?php echo $settings['ai_condition_notify_grade'] === $g ? 'selected' : '' ?>><?php echo ucfirst($g) ?></option>
                        <?php endforeach ?>
                    </select>
                    <div class="form-text"><?php echo __('Notify when condition grade is at or below this level') ?></div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i><?php echo __('Save Settings') ?>
    </button>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('testBtn').addEventListener('click', function() {
    var el = document.getElementById('testResult');
    el.style.display = '';
    el.innerHTML = '<div class="alert alert-info py-1 small"><i class="fas fa-spinner fa-spin me-1"></i>Testing...</div>';

    fetch('<?php echo url_for(['module' => 'aiCondition', 'action' => 'apiTest']) ?>')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var d = data.data || {};
            el.innerHTML = '<div class="alert alert-success py-1 small"><i class="fas fa-check me-1"></i>Connected! Version: ' + (d.version || 'unknown') + ', GPU: ' + (d.gpu_available ? 'Yes' : 'No') + '</div>';
        } else {
            el.innerHTML = '<div class="alert alert-danger py-1 small"><i class="fas fa-times me-1"></i>' + (data.error || 'Connection failed') + '</div>';
        }
    })
    .catch(function() {
        el.innerHTML = '<div class="alert alert-danger py-1 small"><i class="fas fa-times me-1"></i>Network error</div>';
    });
});
</script>
<?php end_slot() ?>
