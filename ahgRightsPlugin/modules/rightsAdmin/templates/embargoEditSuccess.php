<?php echo get_partial('header', ['title' => isset($embargo) ? 'Edit Embargo' : 'New Embargo']); ?>

<div class="container-fluid">
  <div class="row">
    <?php include_partial('rightsAdmin/sidebar', ['active' => 'embargoes']); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
          <i class="fas fa-clock me-2"></i>
          <?php echo isset($embargo) ? __('Edit Embargo') : __('New Embargo'); ?>
        </h1>
      </div>

      <form method="post">
        <div class="row">
          <div class="col-lg-8">
            
            <!-- Object Selection -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('Target Object'); ?></h5>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label"><?php echo __('Information Object ID'); ?> <span class="text-danger">*</span></label>
                  <input type="number" name="object_id" class="form-control" required
                         value="<?php echo $embargo->object_id ?? $sf_request->getParameter('object_id', ''); ?>">
                  <small class="form-text text-muted">Enter the ID of the information object to embargo</small>
                </div>
              </div>
            </div>

            <!-- Embargo Details -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('Embargo Details'); ?></h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Embargo Type'); ?> <span class="text-danger">*</span></label>
                    <select name="embargo_type" class="form-select" required>
                      <?php foreach ($formOptions['embargo_type_options'] as $value => $label): ?>
                      <option value="<?php echo $value; ?>" <?php echo (($embargo->embargo_type ?? 'full') === $value) ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Reason'); ?> <span class="text-danger">*</span></label>
                    <select name="reason" class="form-select" required>
                      <?php foreach ($formOptions['embargo_reason_options'] as $value => $label): ?>
                      <option value="<?php echo $value; ?>" <?php echo (($embargo->reason ?? '') === $value) ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Start Date'); ?> <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" required
                           value="<?php echo $embargo->start_date ?? date('Y-m-d'); ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('End Date'); ?></label>
                    <input type="date" name="end_date" class="form-control"
                           value="<?php echo $embargo->end_date ?? ''; ?>">
                    <small class="form-text text-muted">Leave empty for indefinite embargo</small>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input type="checkbox" name="auto_release" class="form-check-input" id="auto_release" value="1"
                           <?php echo ($embargo->auto_release ?? 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="auto_release">
                      <?php echo __('Automatically lift embargo when end date is reached'); ?>
                    </label>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Reason Note'); ?></label>
                  <textarea name="reason_note" class="form-control" rows="3"><?php echo $embargo->reason_note ?? ''; ?></textarea>
                </div>
              </div>
            </div>

            <!-- Review Settings -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('Review Settings'); ?></h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Review Date'); ?></label>
                    <input type="date" name="review_date" class="form-control"
                           value="<?php echo $embargo->review_date ?? ''; ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo __('Review Interval (months)'); ?></label>
                    <input type="number" name="review_interval_months" class="form-control" min="1" max="120"
                           value="<?php echo $embargo->review_interval_months ?? 12; ?>">
                  </div>
                </div>
              </div>
            </div>

            <!-- Notification Settings -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('Notifications'); ?></h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label"><?php echo __('Notify Before (days)'); ?></label>
                    <input type="number" name="notify_before_days" class="form-control" min="1" max="365"
                           value="<?php echo $embargo->notify_before_days ?? 30; ?>">
                  </div>
                  <div class="col-md-8 mb-3">
                    <label class="form-label"><?php echo __('Notification Emails'); ?></label>
                    <input type="text" name="notify_emails" class="form-control" placeholder="email1@example.com, email2@example.com"
                           value="<?php echo is_array($embargo->notify_emails ?? null) ? implode(', ', json_decode($embargo->notify_emails, true)) : ''; ?>">
                    <small class="form-text text-muted">Comma-separated list of emails</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Internal Note -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('Internal Note'); ?></h5>
              </div>
              <div class="card-body">
                <textarea name="internal_note" class="form-control" rows="3" 
                          placeholder="Internal notes not visible to users"><?php echo $embargo->internal_note ?? ''; ?></textarea>
              </div>
            </div>

          </div>

          <!-- Sidebar -->
          <div class="col-lg-4">
            <?php if (isset($embargo) && !empty($embargoLog)): ?>
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('History'); ?></h5>
              </div>
              <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                  <?php foreach ($embargoLog as $log): ?>
                  <li class="list-group-item">
                    <strong><?php echo ucfirst($log->action); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo date('d M Y H:i', strtotime($log->performed_at)); ?></small>
                    <?php if ($log->notes): ?>
                      <br><small><?php echo $log->notes; ?></small>
                    <?php endif; ?>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <?php endif; ?>

            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><?php echo __('Embargo Types'); ?></h5>
              </div>
              <div class="card-body">
                <dl class="mb-0">
                  <dt>Full</dt>
                  <dd class="text-muted small">Complete restriction - no access to metadata or digital objects</dd>
                  
                  <dt>Metadata Only</dt>
                  <dd class="text-muted small">Metadata visible, digital objects hidden</dd>
                  
                  <dt>Digital Only</dt>
                  <dd class="text-muted small">Digital objects hidden, metadata visible</dd>
                  
                  <dt>Partial</dt>
                  <dd class="text-muted small mb-0">Custom restrictions based on user roles</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save Embargo'); ?>
          </button>
          <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes']); ?>" class="btn btn-secondary">
            <?php echo __('Cancel'); ?>
          </a>
        </div>
      </form>

    </main>
  </div>
</div>
