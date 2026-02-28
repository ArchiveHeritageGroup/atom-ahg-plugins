<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'requestsDashboard']) ?>"><?php echo __('Requests') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Triage Request') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-clipboard-check text-primary me-2" aria-hidden="true"></i><?php echo __('Triage Request') ?></h1>

<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i><?php echo $sf_user->getFlash('error') ?></div>
<?php endif ?>

<?php if ($requestData): ?>
<div class="row">
    <!-- Request Details -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Request Details') ?></h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <?php if ($requestType === 'reproduction'): ?>
                        <dt class="col-sm-4"><?php echo __('Reference') ?></dt>
                        <dd class="col-sm-8"><code><?php echo htmlspecialchars($requestData->reference_number ?? 'N/A') ?></code></dd>
                        <dt class="col-sm-4"><?php echo __('Purpose') ?></dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($requestData->purpose ?? '—') ?></dd>
                        <dt class="col-sm-4"><?php echo __('Intended Use') ?></dt>
                        <dd class="col-sm-8"><?php echo ucfirst($requestData->intended_use ?? '—') ?></dd>
                    <?php else: ?>
                        <dt class="col-sm-4"><?php echo __('Item') ?></dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($requestData->item_title ?? 'Untitled') ?></dd>
                        <dt class="col-sm-4"><?php echo __('Location') ?></dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($requestData->shelf_location ?? $requestData->location_code ?? '—') ?></dd>
                        <dt class="col-sm-4"><?php echo __('Booking Date') ?></dt>
                        <dd class="col-sm-8"><?php echo $requestData->booking_date ?? '—' ?></dd>
                    <?php endif ?>
                    <dt class="col-sm-4"><?php echo __('Researcher') ?></dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars(trim(($requestData->first_name ?? '') . ' ' . ($requestData->last_name ?? ''))) ?> (<?php echo htmlspecialchars($requestData->researcher_email ?? '') ?>)</dd>
                    <dt class="col-sm-4"><?php echo __('Status') ?></dt>
                    <dd class="col-sm-8"><span class="badge bg-info" role="status"><?php echo ucfirst(str_replace('_', ' ', $requestData->status ?? 'unknown')) ?></span></dd>
                    <dt class="col-sm-4"><?php echo __('Current Triage') ?></dt>
                    <dd class="col-sm-8">
                        <?php if ($requestData->triage_status): ?>
                            <span class="badge bg-<?php echo match($requestData->triage_status) { 'triage_approved' => 'success', 'triage_denied' => 'danger', default => 'warning' } ?>" role="status">
                                <?php echo ucfirst(str_replace(['triage_', '_'], ['', ' '], $requestData->triage_status)) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary" role="status"><i class="fas fa-clock me-1" aria-hidden="true"></i><?php echo __('Not yet triaged') ?></span>
                        <?php endif ?>
                    </dd>
                    <?php if (!empty($requestData->sla)): ?>
                    <dt class="col-sm-4"><?php echo __('SLA Due') ?></dt>
                    <dd class="col-sm-8">
                        <?php echo $requestData->sla_due_date ?? ($requestData->sla['due_at'] ?? '—') ?>
                        <?php if (isset($requestData->sla['status'])): ?>
                            <span class="badge ms-1" style="background-color: <?php echo \WorkflowSlaService::STATUS_COLORS[$requestData->sla['status']] ?? '#6c757d' ?>" role="status">
                                <?php echo ucfirst(str_replace('_', ' ', $requestData->sla['status'])) ?>
                            </span>
                        <?php endif ?>
                    </dd>
                    <?php endif ?>
                    <dt class="col-sm-4"><?php echo __('Correspondence') ?></dt>
                    <dd class="col-sm-8">
                        <a href="<?php echo url_for("research/request/{$requestData->id}/correspond/{$requestType}") ?>">
                            <?php echo $requestData->correspondence_count ?? 0 ?> <?php echo __('messages') ?>
                        </a>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Triage Form -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Triage Decision') ?></h5></div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for("research/request/{$requestData->id}/triage/{$requestType}") ?>">
                    <fieldset>
                        <legend class="visually-hidden"><?php echo __('Triage decision options') ?></legend>
                        <div class="mb-3">
                            <label class="form-label fw-bold" id="decisionLabel"><?php echo __('Decision') ?></label>
                            <div role="radiogroup" aria-labelledby="decisionLabel">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="decision" id="decisionApprove" value="approved" aria-required="true" checked>
                                    <label class="form-check-label text-success" for="decisionApprove"><i class="fas fa-check me-1" aria-hidden="true"></i><?php echo __('Approve') ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="decision" id="decisionDeny" value="denied">
                                    <label class="form-check-label text-danger" for="decisionDeny"><i class="fas fa-times me-1" aria-hidden="true"></i><?php echo __('Deny') ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="decision" id="decisionInfo" value="needs_info">
                                    <label class="form-check-label text-warning" for="decisionInfo"><i class="fas fa-question me-1" aria-hidden="true"></i><?php echo __('Needs Information') ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="triageNotes" class="form-label"><?php echo __('Notes') ?></label>
                            <textarea name="triage_notes" id="triageNotes" class="form-control" rows="3" placeholder="<?php echo __('Add notes about the triage decision...') ?>"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-gavel me-1" aria-hidden="true"></i><?php echo __('Submit Decision') ?></button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'requestsDashboard']) ?>" class="btn btn-outline-secondary ms-2"><?php echo __('Cancel') ?></a>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>

    <!-- Timeline Sidebar -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-stream me-2" aria-hidden="true"></i><?php echo __('Timeline') ?></h5></div>
            <div class="card-body p-0">
                <?php if (empty($timeline)): ?>
                    <p class="text-muted p-3"><?php echo __('No events yet.') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush" aria-label="<?php echo __('Request timeline') ?>">
                        <?php foreach (array_slice($timeline, 0, 20) as $event): ?>
                        <li class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <small class="fw-bold"><?php echo htmlspecialchars($event->action) ?></small>
                                <small class="text-muted"><?php echo date('M j H:i', strtotime($event->performed_at)) ?></small>
                            </div>
                            <?php if ($event->comment): ?>
                                <small class="text-muted d-block"><?php echo htmlspecialchars(substr($event->comment, 0, 100)) ?></small>
                            <?php endif ?>
                            <small class="text-muted"><?php echo htmlspecialchars($event->performer_name ?? '') ?></small>
                        </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i><?php echo __('Request not found.') ?></div>
<?php endif ?>
