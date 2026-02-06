<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for([$object, 'module' => 'informationobject']) ?>"><?php echo htmlspecialchars($object->title ?? $object->identifier ?? 'Record') ?></a></li>
            <li class="breadcrumb-item active">ICIP</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-shield-check me-2"></i>
                ICIP Information
            </h1>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($object->title ?? 'Untitled') ?></p>
        </div>
        <a href="<?php echo url_for([$object, 'module' => 'informationobject']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Record
        </a>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <!-- Summary Card -->
    <?php if ($summary && $summary->has_icip_content): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>This record has ICIP content</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="fs-4"><?php echo $summary->consent_status ? ucwords(str_replace('_', ' ', $summary->consent_status)) : 'Unknown' ?></div>
                        <div class="text-muted small">Consent Status</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="fs-4"><?php echo $summary->cultural_notice_count ?? 0 ?></div>
                        <div class="text-muted small">Cultural Notices</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="fs-4"><?php echo $summary->tk_label_count ?? 0 ?></div>
                        <div class="text-muted small">TK Labels</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="fs-4"><?php echo $summary->restriction_count ?? 0 ?></div>
                        <div class="text-muted small">Restrictions</div>
                    </div>
                </div>
                <?php if ($summary->requires_acknowledgement): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        This record requires user acknowledgement before viewing.
                    </div>
                <?php endif ?>
                <?php if ($summary->blocks_access): ?>
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="bi bi-lock me-2"></i>
                        Access to this record is blocked by cultural notices.
                    </div>
                <?php endif ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary">
            <i class="bi bi-info-circle me-2"></i>
            No ICIP content has been recorded for this item yet.
        </div>
    <?php endif ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo url_for('@icip_object?slug=' . $object->slug) ?>">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for('@icip_object_consent?slug=' . $object->slug) ?>">
                Consent <span class="badge bg-secondary"><?php echo count($consents) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for('@icip_object_notices?slug=' . $object->slug) ?>">
                Notices <span class="badge bg-secondary"><?php echo count($notices) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for('@icip_object_labels?slug=' . $object->slug) ?>">
                TK Labels <span class="badge bg-secondary"><?php echo count($labels) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for('@icip_object_restrictions?slug=' . $object->slug) ?>">
                Restrictions <span class="badge bg-secondary"><?php echo count($restrictions) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for('@icip_object_consultations?slug=' . $object->slug) ?>">
                Consultations <span class="badge bg-secondary"><?php echo count($consultations) ?></span>
            </a>
        </li>
    </ul>

    <div class="row">
        <div class="col-lg-8">
            <!-- Consent Summary -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Consent Status</h5>
                    <a href="<?php echo url_for('@icip_object_consent?slug=' . $object->slug) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Consent
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($consents)): ?>
                        <p class="text-muted mb-0">No consent records. <a href="<?php echo url_for('@icip_object_consent?slug=' . $object->slug) ?>">Add consent record</a></p>
                    <?php else: ?>
                        <?php $latest = $consents[0]; ?>
                        <div class="d-flex align-items-center mb-3">
                            <?php
                            $statusClass = match ($latest->consent_status) {
                                'full_consent' => 'bg-success',
                                'conditional_consent', 'restricted_consent' => 'bg-info',
                                'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                'denied' => 'bg-danger',
                                'not_required' => 'bg-light text-dark',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $statusClass ?> fs-6 me-3">
                                <?php echo $statusOptions[$latest->consent_status] ?? ucwords(str_replace('_', ' ', $latest->consent_status)) ?>
                            </span>
                            <?php if ($latest->community_name): ?>
                                <span class="text-muted">Community: <?php echo htmlspecialchars($latest->community_name) ?></span>
                            <?php endif ?>
                        </div>
                        <?php if ($latest->consent_date): ?>
                            <p class="mb-1"><strong>Consent Date:</strong> <?php echo date('j M Y', strtotime($latest->consent_date)) ?></p>
                        <?php endif ?>
                        <?php if ($latest->consent_expiry_date): ?>
                            <?php
                            $expiryDate = new DateTime($latest->consent_expiry_date);
                            $today = new DateTime();
                            $isExpired = $expiryDate < $today;
                            ?>
                            <p class="mb-0">
                                <strong>Expiry:</strong>
                                <span class="<?php echo $isExpired ? 'text-danger' : '' ?>">
                                    <?php echo date('j M Y', strtotime($latest->consent_expiry_date)) ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php endif ?>
                                </span>
                            </p>
                        <?php endif ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- Cultural Notices -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cultural Notices</h5>
                    <a href="<?php echo url_for('@icip_object_notices?slug=' . $object->slug) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Notice
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($notices)): ?>
                        <p class="text-muted mb-0">No cultural notices applied.</p>
                    <?php else: ?>
                        <?php foreach ($notices as $notice): ?>
                            <div class="icip-notice icip-notice-<?php echo $notice->severity ?> mb-3 p-3 rounded">
                                <?php
                                $severityIcon = match ($notice->severity) {
                                    'critical' => 'bi-exclamation-triangle-fill text-danger',
                                    'warning' => 'bi-exclamation-circle text-warning',
                                    default => 'bi-info-circle text-info'
                                };
                                ?>
                                <div class="d-flex">
                                    <i class="bi <?php echo $severityIcon ?> fs-4 me-3"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($notice->notice_name) ?></strong>
                                        <?php if ($notice->requires_acknowledgement): ?>
                                            <span class="badge bg-warning text-dark ms-2">Requires Acknowledgement</span>
                                        <?php endif ?>
                                        <p class="mb-0 mt-1">
                                            <?php echo htmlspecialchars($notice->custom_text ?? $notice->default_text ?? '') ?>
                                        </p>
                                        <?php if ($notice->community_name): ?>
                                            <small class="text-muted">Requested by: <?php echo htmlspecialchars($notice->community_name) ?></small>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

            <!-- TK Labels -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">TK Labels</h5>
                    <a href="<?php echo url_for('@icip_object_labels?slug=' . $object->slug) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Label
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($labels)): ?>
                        <p class="text-muted mb-0">No TK labels applied.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($labels as $label): ?>
                                <div class="icip-tk-label-card p-2 border rounded" style="min-width: 200px;">
                                    <div class="d-flex align-items-center">
                                        <span class="badge <?php echo $label->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' ?> me-2">
                                            <?php echo strtoupper($label->label_code) ?>
                                        </span>
                                        <div>
                                            <strong><?php echo htmlspecialchars($label->label_name) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Applied by: <?php echo ucfirst($label->applied_by) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Access Restrictions -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Restrictions</h5>
                    <a href="<?php echo url_for('@icip_object_restrictions?slug=' . $object->slug) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($restrictions)): ?>
                        <p class="text-muted mb-0">No access restrictions.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($restrictions as $restriction): ?>
                                <li class="mb-2">
                                    <i class="bi bi-lock-fill text-danger me-2"></i>
                                    <?php echo $restrictionTypes[$restriction->restriction_type] ?? ucwords(str_replace('_', ' ', $restriction->restriction_type)) ?>
                                    <?php if ($restriction->override_security_clearance): ?>
                                        <span class="badge bg-danger ms-1" title="Overrides security clearance">Override</span>
                                    <?php endif ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>

            <!-- Recent Consultations -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Consultations</h5>
                    <a href="<?php echo url_for('@icip_object_consultations?slug=' . $object->slug) ?>" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($consultations)): ?>
                        <p class="text-muted mb-0">No consultations recorded.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach (array_slice($consultations, 0, 3) as $consultation): ?>
                                <li class="mb-2 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($consultation->community_name) ?></strong>
                                        <small class="text-muted"><?php echo date('j M Y', strtotime($consultation->consultation_date)) ?></small>
                                    </div>
                                    <small><?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?></small>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo url_for('@icip_consultation_add') ?>?object_id=<?php echo $object->id ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-chat-dots me-1"></i> Log Consultation
                    </a>
                    <a href="<?php echo url_for('@icip_consent_add') ?>?object_id=<?php echo $object->id ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-check me-1"></i> Add Consent Record
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.icip-notice-critical {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}
.icip-notice-warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}
.icip-notice-info {
    background-color: #cff4fc;
    border-left: 4px solid #0dcaf0;
}
.icip-tk-label {
    background-color: #8B4513;
    color: white;
}
.icip-bc-label {
    background-color: #228B22;
    color: white;
}
</style>
