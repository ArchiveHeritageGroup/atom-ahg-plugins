<?php use_helper('Text'); ?>
<?php $rawBases = $sf_data->getRaw('lawfulBases'); ?>
<?php $service = new \ahgPrivacyPlugin\Service\PrivacyService(); ?>
<?php $isOfficer = $service->isPrivacyOfficer($sf_user->getUserId()) || $sf_user->isAdministrator(); ?>
<?php $approvalHistory = $service->getApprovalHistory($activity->id, 'ropa'); ?>
<?php $officers = $service->getPrivacyOfficers(); ?>

<?php
$statusClasses = [
    'draft' => 'secondary',
    'pending_review' => 'warning',
    'approved' => 'success',
    'archived' => 'dark'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h2 mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    <?php echo esc_entities($activity->name); ?>
                </h1>
                <small class="text-muted"><?php echo __('Processing Activity Record'); ?></small>
            </div>
        </div>
        <div>
            <span class="badge bg-<?php echo $statusClasses[$activity->status] ?? 'secondary'; ?> fs-6">
                <?php echo ucfirst(str_replace('_', ' ', $activity->status)); ?>
            </span>
            <?php if ($activity->status === 'draft'): ?>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaEdit', 'id' => $activity->id]); ?>" class="btn btn-primary ms-2">
                <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <?php if ($activity->rejection_reason): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong><?php echo __('Changes Requested'); ?>:</strong> <?php echo esc_entities($activity->rejection_reason); ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Processing Activity Details'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Jurisdiction'); ?></label>
                            <p class="mb-0"><strong><?php echo strtoupper($activity->jurisdiction ?? 'POPIA'); ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Lawful Basis'); ?></label>
                            <p class="mb-0"><?php echo isset($rawBases[$activity->lawful_basis]) ? $rawBases[$activity->lawful_basis]['label'] : ucfirst($activity->lawful_basis ?? 'Not specified'); ?></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted"><?php echo __('Purpose of Processing'); ?></label>
                        <p class="mb-0"><?php echo nl2br(esc_entities($activity->purpose)); ?></p>
                    </div>

                    <?php if ($activity->description): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted"><?php echo __('Description'); ?></label>
                        <p class="mb-0"><?php echo nl2br(esc_entities($activity->description)); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($activity->data_categories || $activity->data_subjects || $activity->recipients): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Data Information'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($activity->data_categories): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted"><?php echo __('Categories of Personal Data'); ?></label>
                        <p class="mb-0"><?php echo nl2br(esc_entities($activity->data_categories)); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($activity->data_subjects): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted"><?php echo __('Categories of Data Subjects'); ?></label>
                        <p class="mb-0"><?php echo nl2br(esc_entities($activity->data_subjects)); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($activity->recipients): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted"><?php echo __('Recipients'); ?></label>
                        <p class="mb-0"><?php echo nl2br(esc_entities($activity->recipients)); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($activity->retention_period): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted"><?php echo __('Retention Period'); ?></label>
                        <p class="mb-0"><?php echo esc_entities($activity->retention_period); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Approval History -->
            <?php if (count($approvalHistory) > 0): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Approval History'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($approvalHistory as $log): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <?php
                                    $actionIcons = ['submitted' => 'paper-plane text-primary', 'approved' => 'check-circle text-success', 'rejected' => 'times-circle text-danger'];
                                    $actionIcon = $actionIcons[$log->action] ?? 'circle text-secondary';
                                    ?>
                                    <i class="fas fa-<?php echo $actionIcon; ?> me-2"></i>
                                    <strong><?php echo ucfirst($log->action); ?></strong>
                                    <?php echo __('by'); ?> <?php echo esc_entities($log->username ?? 'Unknown'); ?>
                                    <?php if ($log->comment): ?>
                                    <br><small class="text-muted ms-4"><?php echo esc_entities($log->comment); ?></small>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo $log->created_at; ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Workflow Actions -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i><?php echo __('Workflow'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($activity->status === 'draft'): ?>
                        <p class="text-muted small"><?php echo __('Submit this record for review by a Privacy Officer.'); ?></p>
                        <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaSubmit', 'id' => $activity->id]); ?>">
                            <?php if (count($officers) > 1): ?>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('Assign to Officer'); ?></label>
                                <select name="officer_id" class="form-select">
                                    <option value=""><?php echo __('Auto-assign (Primary Officer)'); ?></option>
                                    <?php foreach ($officers as $officer): ?>
                                    <option value="<?php echo $officer->user_id; ?>"><?php echo esc_entities($officer->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i><?php echo __('Submit for Review'); ?>
                            </button>
                        </form>

                    <?php elseif ($activity->status === 'pending_review'): ?>
                        <p class="text-muted small"><?php echo __('This record is pending review.'); ?></p>
                        
                        <?php if ($isOfficer): ?>
                        <!-- Approve -->
                        <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaApprove', 'id' => $activity->id]); ?>" class="mb-3">
                            <div class="mb-2">
                                <textarea name="comment" class="form-control" rows="2" placeholder="<?php echo __('Optional comment...'); ?>"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-1"></i><?php echo __('Approve'); ?>
                            </button>
                        </form>
                        
                        <!-- Reject -->
                        <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaReject', 'id' => $activity->id]); ?>">
                            <div class="mb-2">
                                <textarea name="reason" class="form-control" rows="2" placeholder="<?php echo __('Reason for rejection (required)...'); ?>" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-times me-1"></i><?php echo __('Request Changes'); ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-clock me-2"></i><?php echo __('Awaiting review by Privacy Officer'); ?>
                        </div>
                        <?php endif; ?>

                    <?php elseif ($activity->status === 'approved'): ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                            <p class="text-success mb-0"><strong><?php echo __('Approved'); ?></strong></p>
                            <?php if ($activity->approved_at): ?>
                            <small class="text-muted"><?php echo $activity->approved_at; ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DPIA Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('DPIA Status'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($activity->dpia_required): ?>
                    <p><i class="fas fa-exclamation-triangle text-warning me-2"></i><strong><?php echo __('DPIA Required'); ?></strong></p>
                    <?php if ($activity->dpia_completed): ?>
                    <p class="text-success mb-0"><i class="fas fa-check me-1"></i><?php echo __('Completed'); ?>
                    <?php if ($activity->dpia_date): ?> - <?php echo $activity->dpia_date; ?><?php endif; ?></p>
                    <?php else: ?>
                    <p class="text-danger mb-0"><i class="fas fa-times me-1"></i><?php echo __('Not Completed'); ?></p>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-success mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('DPIA Not Required'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Record Info -->
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Record Info'); ?></h5>
                </div>
                <div class="card-body small">
                    <?php if ($activity->owner): ?>
                    <p class="mb-2"><strong><?php echo __('Owner'); ?>:</strong> <?php echo esc_entities($activity->owner); ?></p>
                    <?php endif; ?>
                    <?php if ($activity->department): ?>
                    <p class="mb-2"><strong><?php echo __('Department'); ?>:</strong> <?php echo esc_entities($activity->department); ?></p>
                    <?php endif; ?>
                    <?php if ($activity->assigned_officer_id): ?>
                    <?php 
                    $assignedOfficer = \Illuminate\Database\Capsule\Manager::table('privacy_officer')->where('id', $activity->assigned_officer_id)->first();
                    if ($assignedOfficer): ?>
                    <p class="mb-2"><strong><?php echo __('Assigned Officer'); ?>:</strong> <?php echo esc_entities($assignedOfficer->name); ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                    <p class="mb-2"><strong><?php echo __('Created'); ?>:</strong> <?php echo $activity->created_at; ?></p>
                    <?php if ($activity->next_review_date): ?>
                    <p class="mb-0"><strong><?php echo __('Next Review'); ?>:</strong> <?php echo $activity->next_review_date; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
