<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h2 mb-0"><?php echo esc_entities($dsar->reference_number); ?></h1>
                <small class="text-muted"><?php echo $jurisdictionInfo['name'] ?? $dsar->jurisdiction; ?> - <?php echo $requestTypes[$dsar->request_type] ?? $dsar->request_type; ?></small>
            </div>
        </div>
        <div>
            <?php
            $statusClasses = [
                'received' => 'secondary', 'verified' => 'info', 'in_progress' => 'primary',
                'pending_info' => 'warning', 'completed' => 'success', 'rejected' => 'danger', 'withdrawn' => 'dark'
            ];
            $isOverdue = strtotime($dsar->due_date) < time() && !in_array($dsar->status, ['completed', 'rejected', 'withdrawn']);
            ?>
            <span class="badge bg-<?php echo $statusClasses[$dsar->status] ?? 'secondary'; ?> fs-6">
                <?php echo ucfirst(str_replace('_', ' ', $dsar->status)); ?>
            </span>
            <?php if ($isOverdue): ?>
            <span class="badge bg-danger fs-6 ms-1"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Overdue'); ?></span>
            <?php endif; ?>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarEdit', 'id' => $dsar->id]); ?>" class="btn btn-primary ms-3">
                <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Request Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0"><?php echo __('Request Details'); ?></h5>
                    <span class="badge bg-<?php echo $dsar->priority === 'urgent' ? 'danger' : ($dsar->priority === 'high' ? 'warning' : 'secondary'); ?>">
                        <?php echo ucfirst($dsar->priority); ?> <?php echo __('Priority'); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><?php echo __('Request Type:'); ?></strong><br><?php echo $requestTypes[$dsar->request_type] ?? $dsar->request_type; ?></p>
                            <p><strong><?php echo __('Received:'); ?></strong><br><?php echo $dsar->received_date; ?></p>
                            <p><strong><?php echo __('Due Date:'); ?></strong><br>
                                <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>"><?php echo $dsar->due_date; ?></span>
                                <?php if (!$isOverdue && strtotime($dsar->due_date) > time()): ?>
                                <br><small class="text-muted"><?php echo ceil((strtotime($dsar->due_date) - time()) / 86400); ?> <?php echo __('days remaining'); ?></small>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><?php echo __('Assigned To:'); ?></strong><br><?php echo esc_entities($dsar->assigned_username ?? '-'); ?></p>
                            <?php if ($dsar->completed_date): ?>
                            <p><strong><?php echo __('Completed:'); ?></strong><br><?php echo $dsar->completed_date; ?></p>
                            <?php endif; ?>
                            <?php if ($dsar->outcome): ?>
                            <p><strong><?php echo __('Outcome:'); ?></strong><br><?php echo ucfirst(str_replace('_', ' ', $dsar->outcome)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($dsar->description): ?>
                    <hr>
                    <p><strong><?php echo __('Description:'); ?></strong></p>
                    <p><?php echo nl2br(esc_entities($dsar->description)); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Requestor Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Requestor'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><?php echo __('Name:'); ?></strong><br><?php echo esc_entities($dsar->requestor_name); ?></p>
                            <?php if ($dsar->requestor_email): ?>
                            <p><strong><?php echo __('Email:'); ?></strong><br><a href="mailto:<?php echo $dsar->requestor_email; ?>"><?php echo $dsar->requestor_email; ?></a></p>
                            <?php endif; ?>
                            <?php if ($dsar->requestor_phone): ?>
                            <p><strong><?php echo __('Phone:'); ?></strong><br><?php echo esc_entities($dsar->requestor_phone); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($dsar->requestor_id_type): ?>
                            <p><strong><?php echo __('ID Type:'); ?></strong><br><?php echo esc_entities($dsar->requestor_id_type); ?></p>
                            <?php endif; ?>
                            <?php if ($dsar->requestor_id_number): ?>
                            <p><strong><?php echo __('ID Number:'); ?></strong><br><?php echo esc_entities($dsar->requestor_id_number); ?></p>
                            <?php endif; ?>
                            <p><strong><?php echo __('Verified:'); ?></strong><br>
                                <?php if ($dsar->is_verified): ?>
                                <span class="text-success"><i class="fas fa-check-circle"></i> <?php echo __('Yes'); ?> (<?php echo $dsar->verified_at; ?>)</span>
                                <?php else: ?>
                                <span class="text-warning"><i class="fas fa-clock"></i> <?php echo __('Pending'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Activity Log'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($logs->isEmpty()): ?>
                    <p class="text-muted text-center py-4"><?php echo __('No activity logged yet'); ?></p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($logs as $log): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo ucfirst(str_replace('_', ' ', $log->action)); ?></strong>
                                    <?php if ($log->username): ?>
                                    <span class="text-muted">by <?php echo esc_entities($log->username); ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo $log->created_at; ?></small>
                            </div>
                            <?php if ($log->details): ?>
                            <small class="text-muted"><?php echo esc_entities($log->details); ?></small>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Actions'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarUpdate', 'id' => $dsar->id]); ?>">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Update Status'); ?></label>
                            <select name="status" class="form-select">
                                <option value="received" <?php echo $dsar->status === 'received' ? 'selected' : ''; ?>><?php echo __('Received'); ?></option>
                                <option value="verified" <?php echo $dsar->status === 'verified' ? 'selected' : ''; ?>><?php echo __('Verified'); ?></option>
                                <option value="in_progress" <?php echo $dsar->status === 'in_progress' ? 'selected' : ''; ?>><?php echo __('In Progress'); ?></option>
                                <option value="pending_info" <?php echo $dsar->status === 'pending_info' ? 'selected' : ''; ?>><?php echo __('Pending Info'); ?></option>
                                <option value="completed" <?php echo $dsar->status === 'completed' ? 'selected' : ''; ?>><?php echo __('Completed'); ?></option>
                                <option value="rejected" <?php echo $dsar->status === 'rejected' ? 'selected' : ''; ?>><?php echo __('Rejected'); ?></option>
                                <option value="withdrawn" <?php echo $dsar->status === 'withdrawn' ? 'selected' : ''; ?>><?php echo __('Withdrawn'); ?></option>
                            </select>
                        </div>

                        <?php if (!$dsar->is_verified): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_verified" value="1" class="form-check-input" id="verify">
                            <label class="form-check-label" for="verify"><?php echo __('Mark identity as verified'); ?></label>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Response Summary'); ?></label>
                            <textarea name="response_summary" class="form-control" rows="3"><?php echo esc_entities($dsar->response_summary ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?php echo __('Update'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Jurisdiction Info -->
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $jurisdictionInfo['name'] ?? $dsar->jurisdiction; ?></h5>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><?php echo $jurisdictionInfo['full_name'] ?? ''; ?></p>
                    <ul class="list-unstyled small mb-0">
                        <li><i class="fas fa-clock me-2"></i><?php echo __('Response deadline:'); ?> <?php echo $jurisdictionInfo['dsar_days'] ?? 30; ?> <?php echo __('days'); ?></li>
                        <li><i class="fas fa-university me-2"></i><a href="<?php echo $jurisdictionInfo['regulator_url'] ?? '#'; ?>" target="_blank"><?php echo $jurisdictionInfo['regulator'] ?? ''; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
