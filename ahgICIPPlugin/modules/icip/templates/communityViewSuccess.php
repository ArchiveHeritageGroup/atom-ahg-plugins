<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_communities') ?>">Communities</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($community->name) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-people me-2"></i>
                <?php echo htmlspecialchars($community->name) ?>
            </h1>
            <?php if (!$community->is_active): ?>
                <span class="badge bg-secondary">Inactive</span>
            <?php endif ?>
        </div>
        <div>
            <a href="<?php echo url_for('@icip_community_edit?id=' . $id) ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="<?php echo url_for('@icip_report_community?id=' . $id) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up me-1"></i> Full Report
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Community Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>Language Group</dt>
                                <dd><?php echo htmlspecialchars($community->language_group ?? '-') ?></dd>

                                <dt>Region</dt>
                                <dd><?php echo htmlspecialchars($community->region ?? '-') ?></dd>

                                <dt>State/Territory</dt>
                                <dd>
                                    <span class="badge bg-secondary"><?php echo $community->state_territory ?></span>
                                    <?php echo $states[$community->state_territory] ?? '' ?>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <?php if ($community->alternate_names): ?>
                                <dl>
                                    <dt>Alternate Names</dt>
                                    <dd>
                                        <?php
                                        $altNames = json_decode($community->alternate_names, true) ?? [];
                                        echo implode(', ', array_map('htmlspecialchars', $altNames)) ?: '-';
                                        ?>
                                    </dd>
                                </dl>
                            <?php endif ?>

                            <?php if ($community->native_title_reference): ?>
                                <dl>
                                    <dt>Native Title Reference</dt>
                                    <dd><?php echo htmlspecialchars($community->native_title_reference) ?></dd>
                                </dl>
                            <?php endif ?>
                        </div>
                    </div>

                    <?php if ($community->notes): ?>
                        <hr>
                        <h6>Notes</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($community->notes)) ?></p>
                    <?php endif ?>
                </div>
            </div>

            <!-- Recent Consents -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Consent Records</h5>
                    <span class="badge bg-primary"><?php echo count($consents) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($consents)): ?>
                        <div class="p-4 text-center text-muted">
                            No consent records linked to this community
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Record</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consents as $consent): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($consent->object_title ?? 'Untitled') ?></td>
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
                                                    <?php echo ucwords(str_replace('_', ' ', $consent->consent_status)) ?>
                                                </span>
                                            </td>
                                            <td><?php echo $consent->consent_date ? date('j M Y', strtotime($consent->consent_date)) : '-' ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <!-- Recent Consultations -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Consultations</h5>
                    <span class="badge bg-primary"><?php echo count($consultations) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($consultations)): ?>
                        <div class="p-4 text-center text-muted">
                            No consultations recorded
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Summary</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consultations as $consultation): ?>
                                        <tr>
                                            <td><?php echo date('j M Y', strtotime($consultation->consultation_date)) ?></td>
                                            <td><?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?></td>
                                            <td><?php echo mb_substr($consultation->summary, 0, 50) . (strlen($consultation->summary) > 50 ? '...' : '') ?></td>
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
        </div>

        <div class="col-lg-4">
            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($community->contact_name): ?>
                        <p class="mb-2">
                            <i class="bi bi-person me-2 text-muted"></i>
                            <strong><?php echo htmlspecialchars($community->contact_name) ?></strong>
                        </p>
                    <?php endif ?>

                    <?php if ($community->contact_email): ?>
                        <p class="mb-2">
                            <i class="bi bi-envelope me-2 text-muted"></i>
                            <a href="mailto:<?php echo htmlspecialchars($community->contact_email) ?>">
                                <?php echo htmlspecialchars($community->contact_email) ?>
                            </a>
                        </p>
                    <?php endif ?>

                    <?php if ($community->contact_phone): ?>
                        <p class="mb-2">
                            <i class="bi bi-telephone me-2 text-muted"></i>
                            <a href="tel:<?php echo htmlspecialchars($community->contact_phone) ?>">
                                <?php echo htmlspecialchars($community->contact_phone) ?>
                            </a>
                        </p>
                    <?php endif ?>

                    <?php if ($community->contact_address): ?>
                        <p class="mb-0">
                            <i class="bi bi-geo-alt me-2 text-muted"></i>
                            <?php echo nl2br(htmlspecialchars($community->contact_address)) ?>
                        </p>
                    <?php endif ?>

                    <?php if (!$community->contact_name && !$community->contact_email && !$community->contact_phone && !$community->contact_address): ?>
                        <p class="text-muted mb-0">No contact information recorded</p>
                    <?php endif ?>
                </div>
            </div>

            <!-- PBC Information -->
            <?php if ($community->prescribed_body_corporate): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Prescribed Body Corporate</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong><?php echo htmlspecialchars($community->prescribed_body_corporate) ?></strong>
                        </p>
                        <?php if ($community->pbc_contact_email): ?>
                            <p class="mb-0">
                                <i class="bi bi-envelope me-2 text-muted"></i>
                                <a href="mailto:<?php echo htmlspecialchars($community->pbc_contact_email) ?>">
                                    <?php echo htmlspecialchars($community->pbc_contact_email) ?>
                                </a>
                            </p>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo url_for('@icip_consent_add') ?>?community_id=<?php echo $id ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-file-earmark-check me-1"></i> Add Consent Record
                    </a>
                    <a href="<?php echo url_for('@icip_consultation_add') ?>?community_id=<?php echo $id ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-chat-dots me-1"></i> Log Consultation
                    </a>
                    <a href="<?php echo url_for('@icip_report_community?id=' . $id) ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-graph-up me-1"></i> View Full Report
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card mt-4">
                <div class="card-body small text-muted">
                    <p class="mb-1">
                        <strong>Created:</strong>
                        <?php echo $community->created_at ? date('j M Y H:i', strtotime($community->created_at)) : '-' ?>
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong>
                        <?php echo $community->updated_at ? date('j M Y H:i', strtotime($community->updated_at)) : '-' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
