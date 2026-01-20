<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-clipboard-check me-2"></i>PII Review Queue</h1>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiScan']); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Scanner
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Pending Review
                <span class="badge bg-warning text-dark ms-2"><?php echo count($entities); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($entities) || count($entities) === 0): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <p>No pending PII entities to review</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;">Status</th>
                            <th style="width: 120px;">Type</th>
                            <th>Value</th>
                            <th>Object</th>
                            <th class="text-center" style="width: 100px;">Confidence</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entities as $entity): ?>
                        <tr id="entity-row-<?php echo $entity->id; ?>">
                            <td>
                                <?php if ($entity->status === 'flagged'): ?>
                                    <span class="badge bg-danger"><i class="fas fa-flag me-1"></i>Flagged</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $typeBadges = [
                                    'PERSON' => 'bg-info',
                                    'SA_ID' => 'bg-danger',
                                    'NG_NIN' => 'bg-danger',
                                    'PASSPORT' => 'bg-danger',
                                    'EMAIL' => 'bg-warning text-dark',
                                    'PHONE_SA' => 'bg-warning text-dark',
                                    'BANK_ACCOUNT' => 'bg-danger',
                                    'ORG' => 'bg-secondary',
                                    'GPE' => 'bg-secondary',
                                ];
                                $badge = $typeBadges[$entity->entity_type] ?? 'bg-primary';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $entity->entity_type; ?></span>
                            </td>
                            <td>
                                <code><?php echo esc_entities($entity->entity_value); ?></code>
                            </td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'id' => $entity->object_id]); ?>" target="_blank">
                                    <?php echo esc_entities(substr($entity->object_title ?? 'Untitled', 0, 50)); ?>
                                    <?php if (strlen($entity->object_title ?? '') > 50): ?>...<?php endif; ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <?php
                                $conf = round($entity->confidence * 100);
                                $confClass = $conf >= 80 ? 'text-success' : ($conf >= 60 ? 'text-warning' : 'text-danger');
                                ?>
                                <span class="<?php echo $confClass; ?>"><?php echo $conf; ?>%</span>
                            </td>
                            <td>
                                <form action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiEntityAction']); ?>" method="post" class="d-inline">
                                    <input type="hidden" name="entity_id" value="<?php echo $entity->id; ?>">
                                    <div class="btn-group btn-group-sm">
                                        <button type="submit" name="entity_action" value="approved" class="btn btn-outline-success" title="Approve - Not PII">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="submit" name="entity_action" value="redacted" class="btn btn-outline-warning" title="Redact - Is PII">
                                            <i class="fas fa-eraser"></i>
                                        </button>
                                        <button type="submit" name="entity_action" value="rejected" class="btn btn-outline-danger" title="Reject - False Positive">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-4">
        <div class="card-body">
            <h6><i class="fas fa-info-circle me-2"></i>Review Actions</h6>
            <div class="row">
                <div class="col-md-4">
                    <span class="badge bg-success me-2"><i class="fas fa-check"></i></span>
                    <strong>Approve</strong> - Not sensitive PII, can remain visible
                </div>
                <div class="col-md-4">
                    <span class="badge bg-warning text-dark me-2"><i class="fas fa-eraser"></i></span>
                    <strong>Redact</strong> - Is PII, should be masked/restricted
                </div>
                <div class="col-md-4">
                    <span class="badge bg-danger me-2"><i class="fas fa-times"></i></span>
                    <strong>Reject</strong> - False positive, not actually PII
                </div>
            </div>
        </div>
    </div>
</div>
