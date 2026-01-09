<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-edit me-2"></i><?php echo __('Edit DSAR'); ?>
            <small class="text-muted"><?php echo esc_specialchars($dsar->reference_number); ?></small>
        </h1>
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $dsar->id]); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to View'); ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarEdit', 'id' => $dsar->id]); ?>">
        <input type="hidden" name="id" value="<?php echo $dsar->id; ?>">
        
        <div class="row">
            <!-- Left Column - Main Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Requestor Information'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select name="jurisdiction" class="form-select" disabled>
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($dsar->jurisdiction ?? '') === $code ? 'selected' : ''; ?>><?php echo $info['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted"><?php echo __('Cannot change jurisdiction after creation'); ?></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Request Type'); ?></label>
                                <select name="request_type" class="form-select">
                                    <?php foreach ($requestTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($dsar->request_type ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Requestor Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="requestor_name" class="form-control" value="<?php echo esc_specialchars($dsar->requestor_name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Email'); ?></label>
                                <input type="email" name="requestor_email" class="form-control" value="<?php echo esc_specialchars($dsar->requestor_email ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Phone'); ?></label>
                                <input type="tel" name="requestor_phone" class="form-control" value="<?php echo esc_specialchars($dsar->requestor_phone ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('ID Type'); ?></label>
                                <select name="requestor_id_type" class="form-select">
                                    <option value=""><?php echo __('-- Select --'); ?></option>
                                    <?php foreach ($idTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($dsar->requestor_id_type ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('ID Number'); ?></label>
                                <input type="text" name="requestor_id_number" class="form-control" value="<?php echo esc_specialchars($dsar->requestor_id_number ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Address'); ?></label>
                            <textarea name="requestor_address" class="form-control" rows="2"><?php echo esc_specialchars($dsar->requestor_address ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Request Details'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="4"><?php echo esc_specialchars($dsarI18n->description ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Internal Notes'); ?></label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo esc_specialchars($dsarI18n->notes ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Response Summary'); ?></label>
                            <textarea name="response_summary" class="form-control" rows="3" placeholder="<?php echo __('Summary of how the request was handled...'); ?>"><?php echo esc_specialchars($dsarI18n->response_summary ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Outcome Section (when completed/rejected) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-gavel me-2"></i><?php echo __('Outcome'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Outcome'); ?></label>
                                <select name="outcome" class="form-select">
                                    <?php foreach ($outcomeOptions as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($dsar->outcome ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Refusal Reason'); ?></label>
                            <textarea name="refusal_reason" class="form-control" rows="2" placeholder="<?php echo __('If refused, explain the legal grounds...'); ?>"><?php echo esc_specialchars($dsar->refusal_reason ?? ''); ?></textarea>
                            <small class="text-muted"><?php echo __('Required if outcome is Refused'); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Status & Admin -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Status & Assignment'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($dsar->status ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Priority'); ?></label>
                            <select name="priority" class="form-select">
                                <option value="low" <?php echo ($dsar->priority ?? '') === 'low' ? 'selected' : ''; ?>><?php echo __('Low'); ?></option>
                                <option value="normal" <?php echo ($dsar->priority ?? 'normal') === 'normal' ? 'selected' : ''; ?>><?php echo __('Normal'); ?></option>
                                <option value="high" <?php echo ($dsar->priority ?? '') === 'high' ? 'selected' : ''; ?>><?php echo __('High'); ?></option>
                                <option value="urgent" <?php echo ($dsar->priority ?? '') === 'urgent' ? 'selected' : ''; ?>><?php echo __('Urgent'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Assigned To'); ?></label>
                            <select name="assigned_to" class="form-select">
                                <option value=""><?php echo __('-- Unassigned --'); ?></option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->id; ?>" <?php echo ($dsar->assigned_to ?? '') == $user->id ? 'selected' : ''; ?>><?php echo esc_specialchars($user->username); ?> (<?php echo esc_specialchars($user->email); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_verified" value="1" class="form-check-input" id="isVerified" <?php echo ($dsar->is_verified ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="isVerified"><?php echo __('Identity Verified'); ?></label>
                            </div>
                            <?php if ($dsar->verified_at): ?>
                            <small class="text-muted"><?php echo __('Verified at'); ?>: <?php echo $dsar->verified_at; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i><?php echo __('Dates'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Received Date'); ?></label>
                            <input type="date" class="form-control" value="<?php echo $dsar->received_date ?? ''; ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Due Date'); ?></label>
                            <input type="date" class="form-control" value="<?php echo $dsar->due_date ?? ''; ?>" disabled>
                            <?php 
                            $dueDate = strtotime($dsar->due_date ?? 'now');
                            $today = strtotime('today');
                            $daysLeft = floor(($dueDate - $today) / 86400);
                            if ($daysLeft < 0): ?>
                            <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Overdue by'); ?> <?php echo abs($daysLeft); ?> <?php echo __('days'); ?></small>
                            <?php elseif ($daysLeft <= 5): ?>
                            <small class="text-warning"><i class="fas fa-clock"></i> <?php echo $daysLeft; ?> <?php echo __('days remaining'); ?></small>
                            <?php else: ?>
                            <small class="text-muted"><?php echo $daysLeft; ?> <?php echo __('days remaining'); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php if ($dsar->completed_date): ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Completed Date'); ?></label>
                            <input type="date" class="form-control" value="<?php echo $dsar->completed_date; ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-money-bill me-2"></i><?php echo __('Fees'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Fee Required'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="fee_required" class="form-control" step="0.01" min="0" value="<?php echo $dsar->fee_required ?? ''; ?>" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="fee_paid" value="1" class="form-check-input" id="feePaid" <?php echo ($dsar->fee_paid ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="feePaid"><?php echo __('Fee Paid'); ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo __('Save Changes'); ?>
                            </button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $dsar->id]); ?>" class="btn btn-outline-secondary">
                                <?php echo __('Cancel'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
