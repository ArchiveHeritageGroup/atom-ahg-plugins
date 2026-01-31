<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_reports') ?>">Reports</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($community->name) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-people me-2"></i>
                <?php echo htmlspecialchars($community->name) ?>
            </h1>
            <p class="text-muted mb-0">
                <?php echo $states[$community->state_territory] ?? $community->state_territory ?>
                <?php if ($community->language_group): ?>
                    &bull; <?php echo htmlspecialchars($community->language_group) ?>
                <?php endif ?>
            </p>
        </div>
        <div>
            <a href="<?php echo url_for('@icip_community_view?id=' . $id) ?>" class="btn btn-outline-primary">
                <i class="bi bi-eye me-1"></i> View Community
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-primary"><?php echo count($consents) ?></div>
                    <div class="text-muted">Consent Records</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-success"><?php echo count($consultations) ?></div>
                    <div class="text-muted">Consultations</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-warning"><?php echo count($notices) ?></div>
                    <div class="text-muted">Cultural Notices</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6 text-info"><?php echo count($labels) ?></div>
                    <div class="text-muted">TK Labels</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consent Records -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Consent Records</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($consents)): ?>
                <div class="p-4 text-center text-muted">No consent records linked to this community</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Record</th>
                                <th>Status</th>
                                <th>Consent Date</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consents as $consent): ?>
                                <tr>
                                    <td>
                                        <?php if ($consent->slug): ?>
                                            <a href="<?php echo url_for('@icip_object?slug=' . $consent->slug) ?>">
                                                <?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($consent->consent_status) {
                                            'full_consent' => 'bg-success',
                                            'conditional_consent', 'restricted_consent' => 'bg-info',
                                            'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                            'denied' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass ?>">
                                            <?php echo $statusOptions[$consent->consent_status] ?? ucwords(str_replace('_', ' ', $consent->consent_status)) ?>
                                        </span>
                                    </td>
                                    <td><?php echo $consent->consent_date ? date('j M Y', strtotime($consent->consent_date)) : '-' ?></td>
                                    <td><?php echo $consent->consent_expiry_date ? date('j M Y', strtotime($consent->consent_expiry_date)) : '-' ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Consultations -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Consultation History</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($consultations)): ?>
                <div class="p-4 text-center text-muted">No consultations recorded</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Summary</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $consultation): ?>
                                <tr>
                                    <td><?php echo date('j M Y', strtotime($consultation->consultation_date)) ?></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?></td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $consultation->consultation_method)) ?></td>
                                    <td><?php echo mb_substr($consultation->summary, 0, 80) . (strlen($consultation->summary) > 80 ? '...' : '') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match ($consultation->status) {
                                            'completed' => 'bg-success',
                                            'scheduled' => 'bg-info',
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

    <div class="row">
        <!-- Cultural Notices -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Cultural Notices</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notices)): ?>
                        <div class="p-4 text-center text-muted">No cultural notices</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($notices as $notice): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($notice->notice_name) ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($notice->object_title ?? 'Untitled') ?></small>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- TK Labels -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">TK Labels</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($labels)): ?>
                        <div class="p-4 text-center text-muted">No TK labels</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($labels as $label): ?>
                                <li class="list-group-item">
                                    <span class="badge bg-secondary me-2"><?php echo strtoupper($label->code) ?></span>
                                    <?php echo htmlspecialchars($label->label_name) ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($label->object_title ?? 'Untitled') ?></small>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
