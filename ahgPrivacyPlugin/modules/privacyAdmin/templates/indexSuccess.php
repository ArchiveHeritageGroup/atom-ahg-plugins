<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h1 class="h2 mb-0"><i class="fas fa-user-shield me-2"></i><?php echo __('Privacy Compliance'); ?></h1>
        <div class="d-flex flex-wrap gap-2">
            <!-- Jurisdiction Selector -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-globe me-1"></i>
                    <?php
                    $currentJurisdiction = $sf_request->getParameter('jurisdiction', 'all');
                    $jurisdictions = \ahgPrivacyPlugin\Service\PrivacyService::getJurisdictions();
                    echo $currentJurisdiction === 'all' ? __('All Jurisdictions') : ($jurisdictions[$currentJurisdiction]['name'] ?? $currentJurisdiction);
                    ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>"><?php echo __('All Jurisdictions'); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header"><i class="fas fa-globe-africa me-1"></i><?php echo __('Africa'); ?></h6></li>
                    <li><a class="dropdown-item" href="?jurisdiction=popia"><span class="fi fi-za me-2"></span>POPIA (South Africa)</a></li>
                    <li><a class="dropdown-item" href="?jurisdiction=ndpa"><span class="fi fi-ng me-2"></span>NDPA (Nigeria)</a></li>
                    <li><a class="dropdown-item" href="?jurisdiction=kenya_dpa"><span class="fi fi-ke me-2"></span>Kenya DPA</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header"><i class="fas fa-globe-europe me-1"></i><?php echo __('International'); ?></h6></li>
                    <li><a class="dropdown-item" href="?jurisdiction=gdpr"><span class="fi fi-eu me-2"></span>GDPR (EU)</a></li>
                    <li><a class="dropdown-item" href="?jurisdiction=pipeda"><span class="fi fi-ca me-2"></span>PIPEDA (Canada)</a></li>
                    <li><a class="dropdown-item" href="?jurisdiction=ccpa"><span class="fi fi-us me-2"></span>CCPA (California)</a></li>
                </ul>
            </div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'report']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-chart-bar me-1"></i><span class="d-none d-sm-inline"><?php echo __('Reports'); ?></span>
            </a>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'notifications']); ?>" class="btn btn-outline-secondary position-relative">
                <i class="fas fa-bell"></i>
                <?php $notifCount = $sf_data->getRaw('notificationCount') ?? 0; if ($notifCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $notifCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'config']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog"></i><span class="d-none d-sm-inline ms-1"><?php echo __('Settings'); ?></span>
            </a>
        </div>
    </div>

    <!-- Active Jurisdiction Banner -->
    <?php if (isset($activeJurisdiction) && $activeJurisdiction): ?>
    <div class="alert alert-primary d-flex align-items-center mb-4">
        <i class="fas fa-globe-africa fa-2x me-3"></i>
        <div class="flex-grow-1">
            <strong><?php echo __('Active Jurisdiction'); ?>:</strong>
            <?php echo htmlspecialchars($activeJurisdiction->name); ?> -
            <?php echo htmlspecialchars($activeJurisdiction->full_name); ?>
            (<?php echo htmlspecialchars($activeJurisdiction->country); ?>)
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictions']); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-cog me-1"></i><?php echo __('Manage'); ?>
        </a>
    </div>
    <?php else: ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div class="flex-grow-1">
            <strong><?php echo __('No Active Jurisdiction'); ?></strong> -
            <?php echo __('Install and activate a jurisdiction to enable compliance tracking.'); ?>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictions']); ?>" class="btn btn-warning btn-sm">
            <i class="fas fa-globe me-1"></i><?php echo __('Configure'); ?>
        </a>
    </div>
    <?php endif; ?>

    <!-- Compliance Score -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white text-center py-4">
                    <h5 class="mb-3">
                        <?php if ($currentJurisdiction !== 'all' && isset($jurisdictions[$currentJurisdiction])): ?>
                        <?php echo $jurisdictions[$currentJurisdiction]['name']; ?> 
                        <?php endif; ?>
                        <?php echo __('Compliance Score'); ?>
                    </h5>
                    <div class="display-1 fw-bold"><?php echo $stats['compliance_score'] ?? 0; ?>%</div>
                    <div class="progress mt-3 mx-auto" style="max-width: 400px; height: 10px;">
                        <div class="progress-bar bg-light" style="width: <?php echo $stats['compliance_score'] ?? 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('DSARs'); ?></h6>
                            <h2 class="mb-0"><?php echo $stats['dsar']['pending'] ?? 0; ?></h2>
                            <small class="text-muted"><?php echo __('pending'); ?></small>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                    <?php if (($stats['dsar']['overdue'] ?? 0) > 0): ?>
                    <div class="mt-2 text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i><?php echo $stats['dsar']['overdue']; ?> <?php echo __('overdue'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarList', 'jurisdiction' => $currentJurisdiction]); ?>" class="text-primary">
                        <?php echo __('View all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('Breaches'); ?></h6>
                            <h2 class="mb-0"><?php echo $stats['breach']['open'] ?? 0; ?></h2>
                            <small class="text-muted"><?php echo __('open'); ?></small>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-exclamation-circle fa-2x"></i>
                        </div>
                    </div>
                    <?php if (($stats['breach']['critical'] ?? 0) > 0): ?>
                    <div class="mt-2 text-danger">
                        <i class="fas fa-radiation me-1"></i><?php echo $stats['breach']['critical']; ?> <?php echo __('critical'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachList', 'jurisdiction' => $currentJurisdiction]); ?>" class="text-danger">
                        <?php echo __('View all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('ROPA'); ?></h6>
                            <h2 class="mb-0"><?php echo $stats['ropa']['approved'] ?? 0; ?></h2>
                            <small class="text-muted"><?php echo __('of '); ?><?php echo $stats['ropa']['total'] ?? 0; ?> <?php echo __('approved'); ?></small>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                    <?php if (($stats['ropa']['requiring_dpia'] ?? 0) > 0): ?>
                    <div class="mt-2 text-warning">
                        <i class="fas fa-clipboard-check me-1"></i><?php echo $stats['ropa']['requiring_dpia']; ?> <?php echo __('need DPIA'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaList', 'jurisdiction' => $currentJurisdiction]); ?>" class="text-success">
                        <?php echo __('View all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted"><?php echo __('Consents'); ?></h6>
                            <h2 class="mb-0"><?php echo $stats['consent']['active'] ?? 0; ?></h2>
                            <small class="text-muted"><?php echo __('active'); ?></small>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-handshake fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentList']); ?>" class="text-info">
                        <?php echo __('View all'); ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Quick Actions'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarAdd']); ?>" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-plus-circle d-block mb-2 fa-2x"></i>
                                <?php echo __('New DSAR'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachAdd']); ?>" class="btn btn-outline-danger btn-lg w-100">
                                <i class="fas fa-exclamation-triangle d-block mb-2 fa-2x"></i>
                                <?php echo __('Report Breach'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaAdd']); ?>" class="btn btn-outline-success btn-lg w-100">
                                <i class="fas fa-clipboard-list d-block mb-2 fa-2x"></i>
                                <?php echo __('Add Activity'); ?>
                            </a>
                        </div>
                        <?php if ($currentJurisdiction === 'popia' || $currentJurisdiction === 'all'): ?>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'paiaList']); ?>" class="btn btn-outline-warning btn-lg w-100">
                                <i class="fas fa-file-contract d-block mb-2 fa-2x"></i>
                                <?php echo __('PAIA Requests'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'officerList']); ?>" class="btn btn-outline-secondary btn-lg w-100">
                                <i class="fas fa-user-tie d-block mb-2 fa-2x"></i>
                                <?php echo __('Officers'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintList']); ?>" class="btn btn-outline-warning btn-lg w-100">
                                <i class="fas fa-exclamation-circle d-block mb-2 fa-2x"></i>
                                <?php echo __('Complaints'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictions']); ?>" class="btn btn-outline-info btn-lg w-100">
                                <i class="fas fa-globe d-block mb-2 fa-2x"></i>
                                <?php echo __('Jurisdictions'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-6 text-center mb-3">
                            <a href="<?php echo url_for(['module' => 'privacy', 'action' => 'index']); ?>" class="btn btn-outline-dark btn-lg w-100" target="_blank">
                                <i class="fas fa-external-link-alt d-block mb-2 fa-2x"></i>
                                <?php echo __('Public Page'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Jurisdiction Info Cards -->
    <div class="row">
        <?php 
        $displayJurisdictions = $currentJurisdiction === 'all' 
            ? \ahgPrivacyPlugin\Service\PrivacyService::getAfricanJurisdictions() 
            : [$currentJurisdiction => $jurisdictions[$currentJurisdiction] ?? null];
        ?>
        <?php foreach ($displayJurisdictions as $code => $info): ?>
        <?php if (!$info) continue; ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <span class="fi fi-<?php echo $info['icon']; ?> me-2"></span>
                        <?php echo $info['name']; ?> (<?php echo $info['country']; ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small"><?php echo $info['full_name']; ?></p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo __('DSAR Response Time'); ?>
                            <span class="badge bg-primary rounded-pill"><?php echo $info['dsar_days']; ?> <?php echo __('days'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo __('Breach Notification'); ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $info['breach_hours'] ?: 'ASAP'; ?> <?php echo $info['breach_hours'] ? __('hours') : ''; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo __('Effective'); ?>
                            <span class="badge bg-secondary rounded-pill"><?php echo $info['effective_date']; ?></span>
                        </li>
                    </ul>
                    <div class="mt-3">
                        <small class="text-muted"><?php echo __('Regulator:'); ?></small>
                        <br>
                        <a href="<?php echo $info['regulator_url']; ?>" target="_blank" class="small">
                            <?php echo $info['regulator']; ?> <i class="fas fa-external-link-alt ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
