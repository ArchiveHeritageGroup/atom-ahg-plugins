<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_dashboard') ?>">ICIP</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_consultations') ?>">Consultations</a></li>
            <li class="breadcrumb-item active">View Consultation</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1>
                <i class="bi bi-chat-dots me-2"></i>
                Consultation with <?php echo htmlspecialchars($consultation->community_name) ?>
            </h1>
            <p class="text-muted mb-0">
                <?php echo date('j F Y', strtotime($consultation->consultation_date)) ?>
                &bull;
                <?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?>
            </p>
        </div>
        <a href="<?php echo url_for('@icip_consultation_edit?id=' . $id) ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($consultation->summary)) ?>
                </div>
            </div>

            <?php if ($consultation->outcomes): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Outcomes</h5>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($consultation->outcomes)) ?>
                    </div>
                </div>
            <?php endif ?>

            <!-- Attendees -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Attendees</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($consultation->community_representatives): ?>
                            <div class="col-md-6 mb-3">
                                <h6>Community Representatives</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($consultation->community_representatives)) ?></p>
                            </div>
                        <?php endif ?>

                        <?php if ($consultation->institution_representatives): ?>
                            <div class="col-md-6 mb-3">
                                <h6>Institution Representatives</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($consultation->institution_representatives)) ?></p>
                            </div>
                        <?php endif ?>

                        <?php if ($consultation->attendees): ?>
                            <div class="col-md-6 mb-3">
                                <h6>Other Attendees</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($consultation->attendees)) ?></p>
                            </div>
                        <?php endif ?>
                    </div>

                    <?php if (!$consultation->community_representatives && !$consultation->institution_representatives && !$consultation->attendees): ?>
                        <p class="text-muted mb-0">No attendee information recorded</p>
                    <?php endif ?>
                </div>
            </div>

            <!-- Follow-up -->
            <?php if ($consultation->follow_up_date || $consultation->follow_up_notes): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>
                            Follow-up
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($consultation->follow_up_date): ?>
                            <?php
                            $followUpDate = new DateTime($consultation->follow_up_date);
                            $today = new DateTime();
                            $isOverdue = $followUpDate < $today && $consultation->status === 'follow_up_required';
                            ?>
                            <p class="mb-2">
                                <strong>Follow-up Date:</strong>
                                <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                    <?php echo date('j F Y', strtotime($consultation->follow_up_date)) ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger ms-2">Overdue</span>
                                    <?php endif ?>
                                </span>
                            </p>
                        <?php endif ?>

                        <?php if ($consultation->follow_up_notes): ?>
                            <h6>Notes</h6>
                            <?php echo nl2br(htmlspecialchars($consultation->follow_up_notes)) ?>
                        <?php endif ?>
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
                        <dt>Community</dt>
                        <dd>
                            <a href="<?php echo url_for('@icip_community_view?id=' . $consultation->community_id) ?>">
                                <?php echo htmlspecialchars($consultation->community_name) ?>
                            </a>
                        </dd>

                        <dt>Type</dt>
                        <dd><?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?></dd>

                        <dt>Method</dt>
                        <dd>
                            <?php
                            $methodIcon = match ($consultation->consultation_method) {
                                'in_person' => 'bi-person',
                                'phone' => 'bi-telephone',
                                'video' => 'bi-camera-video',
                                'email' => 'bi-envelope',
                                'letter' => 'bi-envelope-paper',
                                default => 'bi-chat'
                            };
                            ?>
                            <i class="bi <?php echo $methodIcon ?> me-1"></i>
                            <?php echo ucwords(str_replace('_', ' ', $consultation->consultation_method)) ?>
                        </dd>

                        <?php if ($consultation->location): ?>
                            <dt>Location</dt>
                            <dd><?php echo htmlspecialchars($consultation->location) ?></dd>
                        <?php endif ?>

                        <dt>Status</dt>
                        <dd>
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
                        </dd>

                        <?php if ($consultation->is_confidential): ?>
                            <dt>Confidentiality</dt>
                            <dd><span class="badge bg-danger">Confidential</span></dd>
                        <?php endif ?>
                    </dl>
                </div>
            </div>

            <!-- Linked Record -->
            <?php if ($consultation->object_title): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Linked Record</h5>
                    </div>
                    <div class="card-body">
                        <a href="<?php echo url_for('@icip_object?slug=' . $consultation->slug) ?>">
                            <i class="bi bi-archive me-2"></i>
                            <?php echo htmlspecialchars($consultation->object_title) ?>
                        </a>
                    </div>
                </div>
            <?php endif ?>

            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo url_for('@icip_consultation_add') ?>?community_id=<?php echo $consultation->community_id ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-plus-circle me-1"></i> Log Follow-up
                    </a>
                    <a href="<?php echo url_for('@icip_community_view?id=' . $consultation->community_id) ?>" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-people me-1"></i> View Community
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-body small text-muted">
                    <p class="mb-1">
                        <strong>Created:</strong>
                        <?php echo $consultation->created_at ? date('j M Y H:i', strtotime($consultation->created_at)) : '-' ?>
                    </p>
                    <p class="mb-0">
                        <strong>Last Updated:</strong>
                        <?php echo $consultation->updated_at ? date('j M Y H:i', strtotime($consultation->updated_at)) : '-' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
