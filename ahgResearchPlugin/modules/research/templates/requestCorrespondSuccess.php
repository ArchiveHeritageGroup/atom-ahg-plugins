<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php include_partial('research/accessibilityHelpers') ?>

<nav aria-label="<?php echo __('Breadcrumb') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']) ?>"><?php echo __('Research') ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'requestsDashboard']) ?>"><?php echo __('Requests') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo __('Correspondence') ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-envelope text-primary me-2" aria-hidden="true"></i><?php echo __('Request Correspondence') ?></h1>

<?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success" role="status"><i class="fas fa-check-circle me-2" aria-hidden="true"></i><?php echo $sf_user->getFlash('notice') ?></div>
<?php endif ?>

<?php if ($requestData): ?>
<div class="row">
    <!-- Correspondence Thread -->
    <div class="col-lg-8">
        <!-- Request Summary -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php if ($requestType === 'reproduction'): ?>
                            <strong><?php echo __('Reproduction') ?></strong> <code><?php echo htmlspecialchars($requestData->reference_number ?? 'N/A') ?></code>
                        <?php else: ?>
                            <strong><?php echo __('Material Request') ?></strong> <code>#<?php echo $requestData->id ?></code>
                            — <?php echo htmlspecialchars($requestData->item_title ?? 'Untitled') ?>
                        <?php endif ?>
                    </div>
                    <span class="badge bg-info" role="status"><?php echo ucfirst(str_replace('_', ' ', $requestData->status ?? '')) ?></span>
                </div>
                <small class="text-muted"><?php echo __('Researcher') ?>: <?php echo htmlspecialchars(trim(($requestData->first_name ?? '') . ' ' . ($requestData->last_name ?? ''))) ?></small>
            </div>
        </div>

        <!-- Thread -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo __('Messages') ?> (<?php echo count($correspondence) ?>)</h5>
            </div>
            <div class="card-body" role="log" aria-label="<?php echo __('Correspondence thread') ?>" aria-live="polite">
                <?php if (empty($correspondence)): ?>
                    <p class="text-muted text-center py-3"><i class="fas fa-inbox fa-2x d-block mb-2" aria-hidden="true"></i><?php echo __('No messages yet. Start the conversation below.') ?></p>
                <?php else: ?>
                    <?php foreach ($correspondence as $msg): ?>
                    <div class="d-flex mb-3 <?php echo $msg->sender_type === 'staff' ? '' : 'flex-row-reverse' ?>">
                        <div class="flex-shrink-0 me-3 <?php echo $msg->sender_type !== 'staff' ? 'ms-3 me-0' : '' ?>">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 40px; height: 40px; background-color: <?php echo $msg->sender_type === 'staff' ? '#0d6efd' : '#198754' ?>; color: white;"
                                 aria-hidden="true">
                                <i class="fas fa-<?php echo $msg->sender_type === 'staff' ? 'user-tie' : 'user' ?>"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1" style="max-width: 80%;">
                            <div class="card <?php echo $msg->is_internal ? 'border-warning' : '' ?>">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong class="small"><?php echo htmlspecialchars($msg->sender_name ?? 'Unknown') ?></strong>
                                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($msg->created_at)) ?></small>
                                    </div>
                                    <?php if ($msg->is_internal): ?>
                                        <span class="badge bg-warning text-dark mb-1" role="note"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('Internal Note') ?></span>
                                    <?php endif ?>
                                    <?php if ($msg->subject): ?>
                                        <div class="fw-bold small mb-1"><?php echo htmlspecialchars($msg->subject) ?></div>
                                    <?php endif ?>
                                    <div class="small"><?php echo nl2br(htmlspecialchars($msg->body)) ?></div>
                                    <?php if ($msg->attachment_name): ?>
                                        <div class="mt-1"><i class="fas fa-paperclip me-1" aria-hidden="true"></i><small><?php echo htmlspecialchars($msg->attachment_name) ?></small></div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>

            <!-- Reply Form -->
            <div class="card-footer">
                <form method="post" action="<?php echo url_for("research/request/{$requestData->id}/correspond/{$requestType}") ?>">
                    <div class="mb-2">
                        <label for="msgSubject" class="form-label visually-hidden"><?php echo __('Subject') ?></label>
                        <input type="text" name="subject" id="msgSubject" class="form-control form-control-sm" placeholder="<?php echo __('Subject (optional)') ?>">
                    </div>
                    <div class="mb-2">
                        <label for="msgBody" class="form-label visually-hidden"><?php echo __('Message') ?></label>
                        <textarea name="body" id="msgBody" class="form-control" rows="3" placeholder="<?php echo __('Type your message...') ?>" aria-required="true" required></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" name="is_internal" id="isInternal" value="1" class="form-check-input">
                            <label for="isInternal" class="form-check-label small"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('Internal note (hidden from researcher)') ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1" aria-hidden="true"></i><?php echo __('Send') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Timeline + Actions -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0"><?php echo __('Actions') ?></h5></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo url_for("research/request/{$requestData->id}/triage/{$requestType}") ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-clipboard-check me-1" aria-hidden="true"></i><?php echo __('Triage') ?>
                    </a>
                    <form method="post" action="<?php echo url_for("research/request/{$requestData->id}/close/{$requestType}") ?>" class="d-inline">
                        <input type="hidden" name="closure_reason" value="fulfilled">
                        <button type="submit" class="btn btn-outline-success btn-sm w-100" onclick="return confirm('<?php echo __('Close this request as fulfilled?') ?>')">
                            <i class="fas fa-check-circle me-1" aria-hidden="true"></i><?php echo __('Close as Fulfilled') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-stream me-2" aria-hidden="true"></i><?php echo __('Timeline') ?></h5></div>
            <div class="card-body p-0">
                <?php if (empty($timeline)): ?>
                    <p class="text-muted p-3"><?php echo __('No events yet.') ?></p>
                <?php else: ?>
                    <ul class="list-group list-group-flush" aria-label="<?php echo __('Request timeline') ?>">
                        <?php foreach (array_slice($timeline, 0, 25) as $event): ?>
                        <li class="list-group-item py-2">
                            <div class="d-flex justify-content-between">
                                <small class="fw-bold"><?php echo htmlspecialchars($event->action) ?></small>
                                <small class="text-muted"><?php echo date('M j H:i', strtotime($event->performed_at)) ?></small>
                            </div>
                            <?php if ($event->comment): ?>
                                <small class="text-muted d-block"><?php echo htmlspecialchars(substr($event->comment, 0, 80)) ?></small>
                            <?php endif ?>
                        </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning" role="alert"><?php echo __('Request not found.') ?></div>
<?php endif ?>
