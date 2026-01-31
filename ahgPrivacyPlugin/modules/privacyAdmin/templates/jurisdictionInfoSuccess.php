<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-0">
                <?php if ($jurisdiction->icon): ?>
                <span class="me-2"><?php echo $jurisdiction->icon; ?></span>
                <?php endif; ?>
                <?php echo htmlspecialchars($jurisdiction->name); ?>
            </h1>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($jurisdiction->full_name); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictions']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Jurisdictions'); ?>
            </a>
            <?php if (!$jurisdiction->is_installed): ?>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionInstall', 'code' => $jurisdiction->code]); ?>"
               class="btn btn-success"
               onclick="return confirm('<?php echo __('Install this jurisdiction?'); ?>');">
                <i class="fas fa-download me-1"></i><?php echo __('Install'); ?>
            </a>
            <?php elseif (!$activeJurisdiction || $activeJurisdiction->code !== $jurisdiction->code): ?>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionSetActive', 'code' => $jurisdiction->code]); ?>"
               class="btn btn-primary"
               onclick="return confirm('<?php echo __('Set as active jurisdiction?'); ?>');">
                <i class="fas fa-check me-1"></i><?php echo __('Set as Active'); ?>
            </a>
            <?php else: ?>
            <span class="btn btn-success disabled">
                <i class="fas fa-star me-1"></i><?php echo __('Currently Active'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Main Info Column -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Jurisdiction Details'); ?></h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted"><?php echo __('Code'); ?></td>
                            <td><code><?php echo htmlspecialchars($jurisdiction->code); ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><?php echo __('Country'); ?></td>
                            <td><?php echo htmlspecialchars($jurisdiction->country); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><?php echo __('Region'); ?></td>
                            <td><?php echo htmlspecialchars($jurisdiction->region); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><?php echo __('Effective Date'); ?></td>
                            <td><?php echo $jurisdiction->effective_date ? date('d M Y', strtotime($jurisdiction->effective_date)) : '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><?php echo __('Currency'); ?></td>
                            <td><?php echo htmlspecialchars($jurisdiction->default_currency); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Response Deadlines'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="display-4 text-primary"><?php echo $jurisdiction->dsar_days; ?></div>
                            <small class="text-muted"><?php echo __('DSAR Response Days'); ?></small>
                        </div>
                        <div class="col-6">
                            <div class="display-4 text-danger"><?php echo $jurisdiction->breach_hours; ?></div>
                            <small class="text-muted"><?php echo __('Breach Notification Hours'); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-landmark me-2"></i><?php echo __('Regulator'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong><?php echo htmlspecialchars($jurisdiction->regulator); ?></strong></p>
                    <?php if ($jurisdiction->regulator_url): ?>
                    <a href="<?php echo htmlspecialchars($jurisdiction->regulator_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i><?php echo __('Visit Website'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($jurisdiction->is_installed): ?>
            <div class="card mt-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Installation Status'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <i class="fas fa-calendar me-2 text-muted"></i>
                        <?php echo __('Installed'); ?>: <?php echo date('d M Y H:i', strtotime($jurisdiction->installed_at)); ?>
                    </p>
                    <?php if (isset($stats)): ?>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <strong class="text-primary"><?php echo $stats['lawful_bases'] ?? 0; ?></strong><br>
                            <small class="text-muted"><?php echo __('Lawful Bases'); ?></small>
                        </div>
                        <div class="col-6 mb-2">
                            <strong class="text-info"><?php echo $stats['special_categories'] ?? 0; ?></strong><br>
                            <small class="text-muted"><?php echo __('Special Categories'); ?></small>
                        </div>
                        <div class="col-6 mb-2">
                            <strong class="text-success"><?php echo $stats['request_types'] ?? 0; ?></strong><br>
                            <small class="text-muted"><?php echo __('Request Types'); ?></small>
                        </div>
                        <div class="col-6 mb-2">
                            <strong class="text-warning"><?php echo $stats['compliance_rules'] ?? 0; ?></strong><br>
                            <small class="text-muted"><?php echo __('Compliance Rules'); ?></small>
                        </div>
                    </div>
                    <?php if (($stats['dsars'] ?? 0) > 0 || ($stats['breaches'] ?? 0) > 0): ?>
                    <hr>
                    <p class="mb-1 small text-muted"><?php echo __('Usage'); ?>:</p>
                    <span class="badge bg-primary me-1"><?php echo $stats['dsars'] ?? 0; ?> DSARs</span>
                    <span class="badge bg-danger"><?php echo $stats['breaches'] ?? 0; ?> Breaches</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Components Column -->
        <div class="col-lg-8">
            <?php if (!$jurisdiction->is_installed): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo __('This jurisdiction is not installed. Install it to see lawful bases, special categories, request types, and compliance rules.'); ?>
            </div>
            <?php else: ?>

            <!-- Lawful Bases -->
            <?php if (isset($lawfulBases) && count($lawfulBases) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Lawful Bases'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Code'); ?></th>
                                    <th><?php echo __('Name'); ?></th>
                                    <th><?php echo __('Legal Reference'); ?></th>
                                    <th class="text-center"><?php echo __('Consent'); ?></th>
                                    <th class="text-center"><?php echo __('LIA'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lawfulBases as $lb): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($lb->code); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($lb->name); ?></strong>
                                        <?php if ($lb->description): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(truncate_text($lb->description, 100)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($lb->legal_reference ?? '-'); ?></small></td>
                                    <td class="text-center">
                                        <?php echo $lb->requires_consent ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-minus text-muted"></i>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $lb->requires_lia ? '<i class="fas fa-check text-warning"></i>' : '<i class="fas fa-minus text-muted"></i>'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Special Categories -->
            <?php if (isset($specialCategories) && count($specialCategories) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Special Categories of Data'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Code'); ?></th>
                                    <th><?php echo __('Category'); ?></th>
                                    <th><?php echo __('Legal Reference'); ?></th>
                                    <th class="text-center"><?php echo __('Explicit Consent'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($specialCategories as $sc): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($sc->code); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sc->name); ?></strong>
                                        <?php if ($sc->description): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($sc->description); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($sc->legal_reference ?? '-'); ?></small></td>
                                    <td class="text-center">
                                        <?php echo $sc->requires_explicit_consent ? '<i class="fas fa-exclamation-triangle text-danger"></i>' : '<i class="fas fa-minus text-muted"></i>'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Request Types -->
            <?php if (isset($requestTypes) && count($requestTypes) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Data Subject Request Types'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Code'); ?></th>
                                    <th><?php echo __('Request Type'); ?></th>
                                    <th><?php echo __('Legal Reference'); ?></th>
                                    <th class="text-center"><?php echo __('Response Days'); ?></th>
                                    <th class="text-center"><?php echo __('Fee Allowed'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requestTypes as $rt): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($rt->code); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rt->name); ?></strong>
                                        <?php if ($rt->description): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($rt->description); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($rt->legal_reference ?? '-'); ?></small></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $rt->response_days ?? $jurisdiction->dsar_days; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $rt->fee_allowed ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Compliance Rules -->
            <?php if (isset($complianceRules) && count($complianceRules) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Compliance Rules'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Code'); ?></th>
                                    <th><?php echo __('Category'); ?></th>
                                    <th><?php echo __('Rule'); ?></th>
                                    <th><?php echo __('Legal Ref'); ?></th>
                                    <th class="text-center"><?php echo __('Severity'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $severityClasses = [
                                    'error' => 'danger',
                                    'warning' => 'warning',
                                    'info' => 'info',
                                ];
                                ?>
                                <?php foreach ($complianceRules as $rule): ?>
                                <tr>
                                    <td><code class="small"><?php echo htmlspecialchars($rule->code); ?></code></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($rule->category); ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rule->name); ?></strong>
                                        <?php if ($rule->description): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(truncate_text($rule->description, 80)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($rule->legal_reference ?? '-'); ?></small></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $severityClasses[$rule->severity] ?? 'secondary'; ?>">
                                            <?php echo htmlspecialchars($rule->severity); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
