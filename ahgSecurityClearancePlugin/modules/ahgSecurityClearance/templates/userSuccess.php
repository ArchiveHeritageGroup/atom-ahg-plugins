<?php use_helper('Date'); ?>

<div class="row">
  <div class="col-md-12">

    <?php echo link_to_if($sf_user->isAdministrator(), '<h1 class="multiline">'.__('User security clearance').'<span class="sub">'.$user->username.'</span></h1>', url_for(['module' => 'user', 'action' => 'edit', 'slug' => $user->slug]), ['title' => __('Edit user')]); ?>

    <?php if ($sf_request->getParameter('success')): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?php if ('updated' === $sf_request->getParameter('success')): ?>
          <?php echo __('Security clearance has been updated successfully.'); ?>
        <?php elseif ('revoked' === $sf_request->getParameter('success')): ?>
          <?php echo __('Security clearance has been revoked.'); ?>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <section id="content">

      <div class="row mb-4">
        <!-- Current Clearance -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="fas fa-shield-alt me-2"></i><?php echo __('Security Clearance Settings'); ?>
              </h5>
            </div>
            <div class="card-body">

              <?php if ($clearance): ?>
                <div class="alert alert-info mb-4">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                      <strong><?php echo __('Current Clearance:'); ?></strong> 
                      <span class="badge fs-6" style="background-color: <?php echo $clearance->classificationColor; ?>;">
                        <?php echo $clearance->classificationName; ?>
                      </span>
                      <br>
                      <small class="text-muted">
                        <?php echo __('Granted by %1% on %2%', ['%1%' => $clearance->grantedByUsername ?? __('System'), '%2%' => $clearance->grantedAt ? format_date($clearance->grantedAt, 'f') : __('Unknown')]); ?>
                        <?php if ($clearance->expiresAt): ?>
                          | <?php echo __('Expires: %1%', ['%1%' => format_date($clearance->expiresAt, 'f')]); ?>
                        <?php endif; ?>
                      </small>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <div class="alert alert-warning mb-4">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  <?php echo __('This user does not have a security clearance. They can only access public documents.'); ?>
                </div>
              <?php endif; ?>

              <form method="post" action="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'user', 'slug' => $user->slug]); ?>">

                <div class="mb-4">
                  <label for="classification_id" class="form-label fw-bold">
                    <i class="fas fa-lock me-1"></i><?php echo __('Clearance Level'); ?>
                  </label>
                  <select name="classification_id" id="classification_id" class="form-select form-select-lg">
                    <option value=""><?php echo __('-- Select Classification --'); ?></option>
                    <?php foreach ($classifications as $c): ?>
                      <option value="<?php echo $c->id; ?>" 
                              <?php echo ($clearance && $clearance->classificationId == $c->id) ? 'selected' : ''; ?>
                              style="background-color: <?php echo $c->color; ?>20;">
                        <?php echo $c->name; ?> (<?php echo __('Level %1%', ['%1%' => $c->level]); ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="form-text">
                    <?php echo __('Select the maximum classification level this user should be able to access.'); ?>
                  </div>
                </div>

                <div class="mb-4">
                  <label for="expires_at" class="form-label fw-bold">
                    <i class="fas fa-calendar-times me-1"></i><?php echo __('Expiry Date'); ?>
                  </label>
                  <input type="date" name="expires_at" id="expires_at" class="form-control"
                         value="<?php echo ($clearance && $clearance->expiresAt) ? date('Y-m-d', strtotime($clearance->expiresAt)) : ''; ?>"
                         min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                  <div class="form-text">
                    <?php echo __('Leave empty for no automatic expiry.'); ?>
                  </div>
                </div>

                <div class="mb-4">
                  <label for="notes" class="form-label fw-bold">
                    <i class="fas fa-sticky-note me-1"></i><?php echo __('Notes'); ?>
                  </label>
                  <textarea name="notes" id="notes" class="form-control" rows="3"
                            placeholder="<?php echo __('Enter any notes about this clearance...'); ?>"><?php echo $clearance ? $clearance->notes : ''; ?></textarea>
                </div>

                <div class="d-flex justify-content-between border-top pt-3">
                  <div>
                    <?php if ($clearance): ?>
                      <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#revokeModal">
                        <i class="fas fa-times me-1"></i><?php echo __('Revoke Clearance'); ?>
                      </button>
                    <?php endif; ?>
                  </div>
                  <button type="submit" name="action_type" value="update" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    <?php echo $clearance ? __('Update Clearance') : __('Grant Clearance'); ?>
                  </button>
                </div>

              </form>

            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
          <!-- Classification Guide -->
          <div class="card mb-4">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Classification Levels'); ?></h6>
            </div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between"><span class="badge bg-success">Public</span><small class="text-muted">Level 0</small></li>
              <li class="list-group-item d-flex justify-content-between"><span class="badge bg-info">Internal</span><small class="text-muted">Level 1</small></li>
              <li class="list-group-item d-flex justify-content-between"><span class="badge bg-warning text-dark">Restricted</span><small class="text-muted">Level 2</small></li>
              <li class="list-group-item d-flex justify-content-between"><span class="badge" style="background-color:#fd7e14;color:white;">Confidential</span><small class="text-muted">Level 3</small></li>
              <li class="list-group-item d-flex justify-content-between"><span class="badge bg-danger">Secret</span><small class="text-muted">Level 4</small></li>
              <li class="list-group-item d-flex justify-content-between"><span class="badge" style="background-color:#6f42c1;color:white;">Top Secret</span><small class="text-muted">Level 5</small></li>
            </ul>
          </div>

          <!-- History -->
          <?php if (!empty($history)): ?>
            <div class="card">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Change History'); ?></h6>
              </div>
              <ul class="list-group list-group-flush">
                <?php foreach (array_slice($history, 0, 5) as $record): ?>
                  <li class="list-group-item small">
                    <div class="d-flex justify-content-between">
                      <span class="badge <?php echo 'revoked' === $record['action'] ? 'bg-danger' : ('granted' === $record['action'] ? 'bg-success' : 'bg-info'); ?>">
                        <?php echo ucfirst($record['action']); ?>
                      </span>
                      <small class="text-muted"><?php echo date('Y-m-d', strtotime($record['created_at'])); ?></small>
                    </div>
                    <?php if ($record['new_name']): ?>
                      <small><?php echo ($record['previous_name'] ?? __('None')).' → '.$record['new_name']; ?></small>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </section>

  </div>
</div>

<!-- Revoke Modal -->
<?php if ($clearance): ?>
<div class="modal fade" id="revokeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'user', 'slug' => $user->slug]); ?>">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Revoke Clearance'); ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><?php echo __('Are you sure you want to revoke the security clearance for %1%?', ['%1%' => '<strong>'.$user->username.'</strong>']); ?></p>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Reason'); ?> <span class="text-danger">*</span></label>
            <textarea name="revoke_reason" class="form-control" rows="2" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" name="action_type" value="revoke" class="btn btn-danger"><?php echo __('Revoke Clearance'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
