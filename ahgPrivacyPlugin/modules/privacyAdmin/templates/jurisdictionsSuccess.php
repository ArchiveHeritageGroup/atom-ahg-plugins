<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-0"><i class="fas fa-globe me-2"></i><?php echo __('Privacy Jurisdictions'); ?></h1>
            <p class="text-muted mb-0"><?php echo __('Manage privacy compliance frameworks by region'); ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i><?php echo $sf_user->getFlash('notice'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Active Jurisdiction Banner -->
    <?php if ($activeJurisdiction): ?>
    <div class="alert alert-primary mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x me-3"></i>
            <div>
                <h5 class="mb-1"><?php echo __('Active Jurisdiction'); ?>: <?php echo htmlspecialchars($activeJurisdiction->name); ?></h5>
                <p class="mb-0"><?php echo htmlspecialchars($activeJurisdiction->full_name); ?> (<?php echo htmlspecialchars($activeJurisdiction->country); ?>)</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo __('No active jurisdiction configured. Install and activate a jurisdiction to enable compliance tracking.'); ?>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-light">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo count($jurisdictions); ?></h3>
                    <small class="text-muted"><?php echo __('Available'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo count(array_filter($sf_data->getRaw('jurisdictions'), function($j) { return $j->is_installed; })); ?></h3>
                    <small><?php echo __('Installed'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo count($byRegion); ?></h3>
                    <small><?php echo __('Regions'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h3 class="mb-0"><?php echo $activeJurisdiction ? 1 : 0; ?></h3>
                    <small><?php echo __('Active'); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Jurisdictions by Region -->
    <?php foreach ($byRegion as $region => $regionJurisdictions): ?>
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">
                <?php
                $regionIcons = [
                    'Africa' => 'fas fa-globe-africa',
                    'Europe' => 'fas fa-globe-europe',
                    'North America' => 'fas fa-globe-americas',
                    'South America' => 'fas fa-globe-americas',
                    'Asia' => 'fas fa-globe-asia',
                    'Oceania' => 'fas fa-globe',
                    'International' => 'fas fa-globe',
                ];
                ?>
                <i class="<?php echo $regionIcons[$region] ?? 'fas fa-globe'; ?> me-2"></i>
                <?php echo htmlspecialchars($region); ?>
                <span class="badge bg-secondary ms-2"><?php echo count($regionJurisdictions); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;"></th>
                            <th><?php echo __('Code'); ?></th>
                            <th><?php echo __('Name'); ?></th>
                            <th><?php echo __('Country'); ?></th>
                            <th class="text-center"><?php echo __('DSAR Days'); ?></th>
                            <th class="text-center"><?php echo __('Breach Hours'); ?></th>
                            <th class="text-center"><?php echo __('Status'); ?></th>
                            <th class="text-end"><?php echo __('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regionJurisdictions as $j): ?>
                        <tr<?php echo ($activeJurisdiction && $activeJurisdiction->code === $j->code) ? ' class="table-primary"' : ''; ?>>
                            <td class="text-center">
                                <?php if ($j->icon): ?>
                                <span style="font-size: 1.5rem;"><?php echo $j->icon; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($j->code); ?></code>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($j->name); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($j->full_name); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($j->country); ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?php echo $j->dsar_days; ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger"><?php echo $j->breach_hours; ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($activeJurisdiction && $activeJurisdiction->code === $j->code): ?>
                                <span class="badge bg-success"><i class="fas fa-star me-1"></i><?php echo __('ACTIVE'); ?></span>
                                <?php elseif ($j->is_installed): ?>
                                <span class="badge bg-info"><?php echo __('Installed'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo __('Not Installed'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionInfo', 'code' => $j->code]); ?>"
                                       class="btn btn-outline-secondary" title="<?php echo __('Details'); ?>">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <?php if (!$j->is_installed): ?>
                                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionInstall', 'code' => $j->code]); ?>"
                                       class="btn btn-outline-success" title="<?php echo __('Install'); ?>"
                                       onclick="return confirm('<?php echo __('Install jurisdiction: '); ?><?php echo addslashes($j->name); ?>?');">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php else: ?>
                                    <?php if (!$activeJurisdiction || $activeJurisdiction->code !== $j->code): ?>
                                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionSetActive', 'code' => $j->code]); ?>"
                                       class="btn btn-outline-primary" title="<?php echo __('Set as Active'); ?>"
                                       onclick="return confirm('<?php echo __('Set as active jurisdiction: '); ?><?php echo addslashes($j->name); ?>?');">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionUninstall', 'code' => $j->code]); ?>"
                                       class="btn btn-outline-danger" title="<?php echo __('Uninstall'); ?>"
                                       onclick="return confirm('<?php echo __('Uninstall jurisdiction: '); ?><?php echo addslashes($j->name); ?>? This will remove all jurisdiction-specific rules.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Help Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i><?php echo __('About Regional Jurisdictions'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6><i class="fas fa-download me-2 text-success"></i><?php echo __('Installing'); ?></h6>
                    <p class="small text-muted"><?php echo __('Installing a jurisdiction loads its lawful bases, special categories, request types, compliance rules, and retention schedules.'); ?></p>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-check me-2 text-primary"></i><?php echo __('Activating'); ?></h6>
                    <p class="small text-muted"><?php echo __('The active jurisdiction determines which compliance rules apply globally. Only one jurisdiction can be active at a time.'); ?></p>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-terminal me-2 text-dark"></i><?php echo __('CLI Management'); ?></h6>
                    <p class="small text-muted"><?php echo __('Use the command line for advanced management:'); ?></p>
                    <code class="small">php symfony privacy:jurisdiction --help</code>
                </div>
            </div>
        </div>
    </div>
</div>
