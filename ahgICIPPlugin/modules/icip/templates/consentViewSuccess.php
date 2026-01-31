<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_consent_list') ?>">Consent Records</a></li>
            <li class="breadcrumb-item active">View Consent</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-file-earmark-check me-2"></i>
                Consent Record
            </h1>
            <p class="text-muted mb-0">
                <?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?>
            </p>
        </div>
        <a href="<?php echo url_for('@icip_consent_edit?id=' . $id) ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-1">Consent Status</h5>
                            <?php
                            $statusClass = match ($consent->consent_status) {
                                'full_consent' => 'bg-success',
                                'conditional_consent', 'restricted_consent' => 'bg-info',
                                'pending_consultation', 'consultation_in_progress' => 'bg-warning text-dark',
                                'denied' => 'bg-danger',
                                'not_required' => 'bg-light text-dark',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $statusClass ?> fs-6">
                                <?php echo $statusOptions[$consent->consent_status] ?? ucwords(str_replace('_', ' ', $consent->consent_status)) ?>
                            </span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php if ($consent->consent_date): ?>
                                <small class="text-muted">Granted:</small>
                                <strong><?php echo date('j M Y', strtotime($consent->consent_date)) ?></strong>
                            <?php endif ?>
                            <?php if ($consent->consent_expiry_date): ?>
                                <br>
                                <?php
                                $expiryDate = new DateTime($consent->consent_expiry_date);
                                $today = new DateTime();
                                $isExpired = $expiryDate < $today;
                                ?>
                                <small class="text-muted">Expires:</small>
                                <strong class="<?php echo $isExpired ? 'text-danger' : '' ?>">
                                    <?php echo date('j M Y', strtotime($consent->consent_expiry_date)) ?>
                                    <?php if ($isExpired): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php endif ?>
                                </strong>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scope -->
            <?php
            $scopeArray = [];
            if (!empty($consent->consent_scope)) {
                $scopeArray = json_decode($consent->consent_scope, true) ?? [];
            }
            ?>
            <?php if (!empty($scopeArray)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Consent Scope</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($scopeOptions as $value => $label): ?>
                                <div class="col-md-4 mb-2">
                                    <?php if (in_array($value, $scopeArray)): ?>
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle text-muted me-2"></i>
                                    <?php endif ?>
                                    <?php echo $label ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <!-- Conditions -->
            <?php if ($consent->conditions): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Conditions</h5>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($consent->conditions)) ?>
                    </div>
                </div>
            <?php endif ?>

            <!-- Restrictions -->
            <?php if ($consent->restrictions): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Restrictions</h5>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($consent->restrictions)) ?>
                    </div>
                </div>
            <?php endif ?>

            <!-- Notes -->
            <?php if ($consent->notes): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Notes</h5>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($consent->notes)) ?>
                    </div>
                </div>
            <?php endif ?>
        </div>

        <div class="col-lg-4">
            <!-- Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Details</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Information Object</dt>
                        <dd>
                            <?php if ($consent->slug): ?>
                                <a href="<?php echo url_for('@icip_object?slug=' . $consent->slug) ?>">
                                    <?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?>
                                </a>
                            <?php else: ?>
                                ID: <?php echo $consent->information_object_id ?>
                            <?php endif ?>
                        </dd>

                        <dt>Community</dt>
                        <dd>
                            <?php if ($consent->community_id): ?>
                                <a href="<?php echo url_for('@icip_community_view?id=' . $consent->community_id) ?>">
                                    <?php echo htmlspecialchars($consent->community_name) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Not specified</span>
                            <?php endif ?>
                        </dd>

                        <?php if ($consent->consent_granted_by): ?>
                            <dt>Granted By</dt>
                            <dd><?php echo htmlspecialchars($consent->consent_granted_by) ?></dd>
                        <?php endif ?>

                        <?php if ($consent->consent_document_path): ?>
                            <dt>Document</dt>
                            <dd>
                                <a href="<?php echo htmlspecialchars($consent->consent_document_path) ?>" target="_blank">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>
                                    View Document
                                </a>
                            </dd>
                        <?php endif ?>
                    </dl>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <?php if ($consent->slug): ?>
                        <a href="<?php echo url_for('@icip_object?slug=' . $consent->slug) ?>" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-archive me-1"></i> View ICIP Summary
                        </a>
                    <?php endif ?>
                    <?php if ($consent->community_id): ?>
                        <a href="<?php echo url_for('@icip_consultation_add') ?>?community_id=<?php echo $consent->community_id ?>&object_id=<?php echo $consent->information_object_id ?>" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-chat-dots me-1"></i> Log Consultation
                        </a>
                    <?php endif ?>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-body small text-muted">
                    <p class="mb-1">
                        <strong>Created:</strong>
                        <?php echo $consent->created_at ? date('j M Y H:i', strtotime($consent->created_at)) : '-' ?>
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong>
                        <?php echo $consent->updated_at ? date('j M Y H:i', strtotime($consent->updated_at)) : '-' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
