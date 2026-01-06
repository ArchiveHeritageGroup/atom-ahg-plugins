<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="d-flex justify-content-between align-items-center flex-grow-1">
            <h1 class="h2 mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Privacy Settings'); ?></h1>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']); ?>" class="btn btn-outline-info">
                <i class="fas fa-globe me-1"></i><?php echo __('Manage Jurisdictions'); ?>
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <!-- Jurisdiction Tabs -->
    <ul class="nav nav-tabs mb-4">
        <?php foreach ($jurisdictions as $code => $info): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $currentJurisdiction === $code ? 'active' : ''; ?>" 
               href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'config', 'jurisdiction' => $code]); ?>">
                <?php echo $info['name']; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'config', 'jurisdiction' => $currentJurisdiction]); ?>">
        <input type="hidden" name="jurisdiction" value="<?php echo $currentJurisdiction; ?>">
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $jurisdictionInfo['name'] ?? $currentJurisdiction; ?> <?php echo __('Configuration'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Organization Name'); ?></label>
                                <input type="text" name="organization_name" class="form-control" 
                                       value="<?php echo esc_entities($config->organization_name ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Registration Number'); ?></label>
                                <input type="text" name="registration_number" class="form-control" 
                                       value="<?php echo esc_entities($config->registration_number ?? ''); ?>"
                                       placeholder="<?php echo __('e.g., Company registration'); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Data Protection Email'); ?></label>
                                <input type="email" name="data_protection_email" class="form-control" 
                                       value="<?php echo esc_entities($config->data_protection_email ?? ''); ?>"
                                       placeholder="privacy@example.org">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('DSAR Response Days'); ?></label>
                                <input type="number" name="dsar_response_days" class="form-control" 
                                       value="<?php echo $config->dsar_response_days ?? $jurisdictionInfo['dsar_days'] ?? 30; ?>"
                                       min="1" max="90">
                                <small class="text-muted"><?php echo __('Default:'); ?> <?php echo $jurisdictionInfo['dsar_days'] ?? 30; ?> <?php echo __('days'); ?></small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Breach Notification Hours'); ?></label>
                                <input type="number" name="breach_notification_hours" class="form-control" 
                                       value="<?php echo $config->breach_notification_hours ?? $jurisdictionInfo['breach_hours'] ?? 72; ?>"
                                       min="0" max="168">
                                <small class="text-muted"><?php echo __('Default:'); ?> <?php echo $jurisdictionInfo['breach_hours'] ?: 'ASAP'; ?></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Default Retention Years'); ?></label>
                                <input type="number" name="retention_default_years" class="form-control" 
                                       value="<?php echo $config->retention_default_years ?? 5; ?>"
                                       min="1" max="100">
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   <?php echo ($config->is_active ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active"><?php echo __('Enable this jurisdiction'); ?></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Jurisdiction Info -->
                <div class="card mb-4 bg-light">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $jurisdictionInfo['name'] ?? ''; ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="small"><?php echo $jurisdictionInfo['full_name'] ?? ''; ?></p>
                        <ul class="list-unstyled small">
                            <li><strong><?php echo __('Country:'); ?></strong> <?php echo $jurisdictionInfo['country'] ?? ''; ?></li>
                            <li><strong><?php echo __('Effective:'); ?></strong> <?php echo $jurisdictionInfo['effective_date'] ?? ''; ?></li>
                            <li><strong><?php echo __('Regulator:'); ?></strong><br>
                                <a href="<?php echo $jurisdictionInfo['regulator_url'] ?? '#'; ?>" target="_blank">
                                    <?php echo $jurisdictionInfo['regulator'] ?? ''; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Assigned Officers -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo __('Privacy Officers'); ?></h5>
                        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'officerAdd']); ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if ($officers->isEmpty()): ?>
                        <li class="list-group-item text-muted"><?php echo __('No officers assigned'); ?></li>
                        <?php else: ?>
                        <?php foreach ($officers as $officer): ?>
                        <li class="list-group-item">
                            <strong><?php echo esc_entities($officer->name); ?></strong>
                            <?php if ($officer->title): ?>
                            <br><small class="text-muted"><?php echo esc_entities($officer->title); ?></small>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?php echo __('Save Configuration'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
