<?php use_helper('Date') ?>

<div class="container-xxl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-shield-check me-2"></i>
            ICIP Management
        </h1>
        <div>
            <a href="<?php echo url_for('@icip_communities') ?>" class="btn btn-outline-primary">
                <i class="bi bi-people"></i> Communities
            </a>
            <a href="<?php echo url_for('@icip_reports') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up"></i> Reports
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">ICIP Records</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($stats['total_icip_objects'] ?? 0) ?></h2>
                        </div>
                        <i class="bi bi-archive fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Communities</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($stats['total_communities'] ?? 0) ?></h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-subtitle mb-2">Pending Consultation</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($stats['pending_consultations'] ?? 0) ?></h2>
                        </div>
                        <i class="bi bi-clock-history fs-1 opacity-50"></i>
                    </div>
                </div>
                <?php if (($stats['pending_consultations'] ?? 0) > 0): ?>
                    <div class="card-footer bg-transparent border-0">
                        <a href="<?php echo url_for('@icip_report_pending') ?>" class="text-dark small">View list &rarr;</a>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Expiring Consents</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($stats['expiring_consents'] ?? 0) ?></h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                    </div>
                </div>
                <?php if (($stats['expiring_consents'] ?? 0) > 0): ?>
                    <div class="card-footer bg-transparent border-0">
                        <a href="<?php echo url_for('@icip_report_expiry') ?>" class="text-white small">View list &rarr;</a>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Secondary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">TK Labels Applied</h6>
                            <h4 class="mb-0"><?php echo number_format($stats['tk_labels_applied'] ?? 0) ?></h4>
                        </div>
                        <span class="badge bg-info fs-5 p-2">
                            <i class="bi bi-tag"></i>
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for('@icip_tk_labels') ?>">Manage TK Labels &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active Restrictions</h6>
                            <h4 class="mb-0"><?php echo number_format($stats['active_restrictions'] ?? 0) ?></h4>
                        </div>
                        <span class="badge bg-secondary fs-5 p-2">
                            <i class="bi bi-lock"></i>
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for('@icip_restrictions') ?>">View Restrictions &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Follow-ups Due</h6>
                            <h4 class="mb-0"><?php echo number_format($stats['follow_ups_due'] ?? 0) ?></h4>
                        </div>
                        <span class="badge bg-warning text-dark fs-5 p-2">
                            <i class="bi bi-calendar-check"></i>
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?php echo url_for('@icip_consultations') ?>?status=follow_up_required">View Follow-ups &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pending Consultations -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock text-warning me-2"></i>
                        Pending Consultation
                    </h5>
                    <a href="<?php echo url_for('@icip_report_pending') ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pendingConsultations)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mb-0 mt-2">No pending consultations</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Record</th>
                                        <th>Community</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingConsultations as $record): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url_for('@icip_object?slug=' . ($record->slug ?? 'unknown')) ?>">
                                                    <?php echo $record->object_title ?? $record->identifier ?? 'Untitled' ?>
                                                </a>
                                            </td>
                                            <td><?php echo $record->community_name ?? '<span class="text-muted">Not specified</span>' ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">
                                                    <?php echo ucwords(str_replace('_', ' ', $record->consent_status)) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- Expiring Consents -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Expiring Consents (90 days)
                    </h5>
                    <a href="<?php echo url_for('@icip_report_expiry') ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($expiringConsents)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mb-0 mt-2">No consents expiring soon</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Record</th>
                                        <th>Expires</th>
                                        <th>Days Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expiringConsents as $record): ?>
                                        <?php
                                        $expiryDate = new DateTime($record->consent_expiry_date);
                                        $today = new DateTime();
                                        $daysLeft = $today->diff($expiryDate)->days;
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url_for('@icip_object?slug=' . ($record->slug ?? 'unknown')) ?>">
                                                    <?php echo $record->object_title ?? 'Untitled' ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('j M Y', strtotime($record->consent_expiry_date)) ?></td>
                                            <td>
                                                <span class="badge <?php echo $daysLeft < 30 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                    <?php echo $daysLeft ?> days
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Consultations -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-chat-dots me-2"></i>
                Recent Consultations
            </h5>
            <a href="<?php echo url_for('@icip_consultations') ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentConsultations)): ?>
                <div class="p-4 text-center text-muted">
                    <p class="mb-0">No consultations recorded yet</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Community</th>
                                <th>Type</th>
                                <th>Summary</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentConsultations as $consultation): ?>
                                <tr>
                                    <td><?php echo date('j M Y', strtotime($consultation->consultation_date)) ?></td>
                                    <td><?php echo $consultation->community_name ?></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?></td>
                                    <td><?php echo mb_substr($consultation->summary, 0, 60) . (strlen($consultation->summary) > 60 ? '...' : '') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match ($consultation->status) {
                                            'completed' => 'bg-success',
                                            'scheduled' => 'bg-info',
                                            'cancelled' => 'bg-secondary',
                                            'follow_up_required' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $consultation->status)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-lightning me-2"></i>
                Quick Actions
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?php echo url_for('@icip_community_add') ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Community
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo url_for('@icip_consent_add') ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-check me-2"></i>
                        Record Consent
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo url_for('@icip_consultation_add') ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-chat-dots me-2"></i>
                        Log Consultation
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?php echo url_for('@icip_notices') ?>" class="btn btn-outline-primary w-100">
                        <i class="bi bi-bell me-2"></i>
                        Cultural Notices
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
