<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for([$object, 'module' => 'informationobject']) ?>"><?php echo htmlspecialchars($object->title ?? 'Record') ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('@icip_object?slug=' . $object->slug) ?>">ICIP</a></li>
            <li class="breadcrumb-item active">Consultations</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-chat-dots me-2"></i>
            Consultation History
        </h1>
        <a href="<?php echo url_for('@icip_consultation_add') ?>?object_id=<?php echo $object->id ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Log Consultation
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <strong><?php echo count($consultations) ?></strong> consultations recorded
        </div>
        <div class="card-body">
            <?php if (empty($consultations)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mb-0 mt-2">No consultations recorded for this record.</p>
                    <a href="<?php echo url_for('@icip_consultation_add') ?>?object_id=<?php echo $object->id ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i> Log First Consultation
                    </a>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($consultations as $consultation): ?>
                        <div class="timeline-item mb-4 pb-4 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($consultation->community_name) ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('j F Y', strtotime($consultation->consultation_date)) ?>
                                        &bull;
                                        <?php echo ucwords(str_replace('_', ' ', $consultation->consultation_type)) ?>
                                        &bull;
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
                                        <i class="bi <?php echo $methodIcon ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $consultation->consultation_method)) ?>
                                    </p>
                                </div>
                                <div>
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
                                </div>
                            </div>

                            <div class="mt-2">
                                <h6>Summary</h6>
                                <p><?php echo nl2br(htmlspecialchars($consultation->summary)) ?></p>
                            </div>

                            <?php if ($consultation->outcomes): ?>
                                <div class="mt-2">
                                    <h6>Outcomes</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($consultation->outcomes)) ?></p>
                                </div>
                            <?php endif ?>

                            <?php if ($consultation->follow_up_date): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-arrow-return-right me-1"></i>
                                        Follow-up: <?php echo date('j M Y', strtotime($consultation->follow_up_date)) ?>
                                    </small>
                                </div>
                            <?php endif ?>

                            <div class="mt-2">
                                <a href="<?php echo url_for('@icip_consultation_view?id=' . $consultation->id) ?>" class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                                <a href="<?php echo url_for('@icip_consultation_edit?id=' . $consultation->id) ?>" class="btn btn-sm btn-outline-secondary">
                                    Edit
                                </a>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
