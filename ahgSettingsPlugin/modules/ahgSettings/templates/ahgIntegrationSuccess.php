<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
    <?php include_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
    <h1><?php echo __('AHG Central Integration'); ?></h1>
<?php end_slot(); ?>

<?php if (isset($testResult)): ?>
    <div class="alert alert-<?php echo $testResult['success'] ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <strong><?php echo $testResult['success'] ? __('Success!') : __('Error:'); ?></strong>
        <?php echo $testResult['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-cloud me-2"></i><?php echo __('About AHG Central'); ?></h5>
    </div>
    <div class="card-body">
        <p class="mb-2">
            <?php echo __('AHG Central is a cloud service provided by The Archive and Heritage Group that enhances your AtoM instance with:'); ?>
        </p>
        <ul class="mb-3">
            <li><strong><?php echo __('Shared NER Training'); ?></strong> - <?php echo __('Contribute and benefit from a community-trained Named Entity Recognition model'); ?></li>
            <li><strong><?php echo __('Future AI Services'); ?></strong> - <?php echo __('Access to upcoming cloud-based AI features'); ?></li>
            <li><strong><?php echo __('Usage Analytics'); ?></strong> - <?php echo __('Optional aggregate statistics to improve the platform'); ?></li>
        </ul>
        <p class="text-muted small mb-0">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('Note: This is separate from local AI services configured in the AI Services settings. Local AI services run on your own infrastructure while AHG Central is a cloud service.'); ?>
        </p>
    </div>
</div>

<form method="post" action="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'ahgIntegration']); ?>">
    <?php echo $form->renderGlobalErrors(); ?>
    <?php echo $form->renderHiddenFields(); ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Connection Settings'); ?></h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="ahg_central_enabled" class="form-label"><?php echo __('Enable AHG Central Integration'); ?></label>
                <div>
                    <?php echo $form['ahg_central_enabled']->render(['class' => 'form-check-input']); ?>
                </div>
                <div class="form-text"><?php echo $settings['ahg_central_enabled']['help']; ?></div>
            </div>

            <div class="mb-3">
                <label for="ahg_central_api_url" class="form-label"><?php echo __('AHG Central API URL'); ?></label>
                <?php echo $form['ahg_central_api_url']->render(['class' => 'form-control']); ?>
                <div class="form-text"><?php echo $settings['ahg_central_api_url']['help']; ?></div>
            </div>

            <div class="mb-3">
                <label for="ahg_central_api_key" class="form-label"><?php echo __('API Key'); ?></label>
                <div class="input-group">
                    <?php echo $form['ahg_central_api_key']->render(['class' => 'form-control', 'id' => 'ahg_central_api_key']); ?>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <div class="form-text"><?php echo $settings['ahg_central_api_key']['help']; ?></div>
            </div>

            <div class="mb-3">
                <label for="ahg_central_site_id" class="form-label"><?php echo __('Site ID'); ?></label>
                <?php echo $form['ahg_central_site_id']->render(['class' => 'form-control']); ?>
                <div class="form-text"><?php echo $settings['ahg_central_site_id']['help']; ?></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plug me-2"></i><?php echo __('Test Connection'); ?></h5>
        </div>
        <div class="card-body">
            <p class="mb-3"><?php echo __('Test the connection to AHG Central before saving your settings.'); ?></p>
            <button type="submit" name="test_connection" value="1" class="btn btn-info">
                <i class="fas fa-plug me-1"></i> <?php echo __('Test Connection'); ?>
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-terminal me-2"></i><?php echo __('Environment Variables (Legacy)'); ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                <?php echo __('Previously, AHG Central was configured via environment variables. Database settings (above) take precedence over environment variables.'); ?>
            </p>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th><?php echo __('Variable'); ?></th>
                        <th><?php echo __('Current Value'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $envVars = [
                        'NER_TRAINING_API_URL' => getenv('NER_TRAINING_API_URL'),
                        'NER_API_KEY' => getenv('NER_API_KEY') ? '********' : '',
                        'NER_SITE_ID' => getenv('NER_SITE_ID'),
                    ];
                    foreach ($envVars as $name => $value):
                    ?>
                    <tr>
                        <td><code><?php echo $name; ?></code></td>
                        <td><?php echo $value ?: '<em class="text-muted">' . __('Not set') . '</em>'; ?></td>
                        <td>
                            <?php if ($value): ?>
                                <span class="badge bg-warning"><?php echo __('Will be overridden by database settings'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo __('Not set'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> <?php echo __('Save Settings'); ?>
        </button>
        <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']); ?>" class="btn btn-secondary">
            <?php echo __('Cancel'); ?>
        </a>
    </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function togglePasswordVisibility() {
    const input = document.getElementById('ahg_central_api_key');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
