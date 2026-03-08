<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Serial Subscription'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($notice)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $notice; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php $rawSub = $sf_data->getRaw('subscription'); ?>
<?php if (!$rawSub): ?>
  <div class="alert alert-warning"><?php echo __('Subscription not found.'); ?></div>
<?php else: ?>

  <!-- Action buttons -->
  <div class="mb-3">
    <a href="<?php echo url_for(['module' => 'serial', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to list'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'serial', 'action' => 'edit', 'id' => $rawSub->id]); ?>" class="btn btn-outline-primary">
      <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
    </a>
  </div>

  <!-- Subscription header card -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <i class="fas fa-newspaper me-2"></i><?php echo esc_entities($rawSub->title ?? __('Untitled')); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:40%"><?php echo __('ISSN'); ?></th>
              <td><?php echo esc_entities($rawSub->issn ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Call number'); ?></th>
              <td><?php echo esc_entities($rawSub->call_number ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Vendor'); ?></th>
              <td><?php echo esc_entities($rawSub->vendor_name ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Subscription #'); ?></th>
              <td><?php echo esc_entities($rawSub->subscription_number ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Status'); ?></th>
              <td>
                <?php
                  $statusBadge = 'bg-secondary';
                  $st = $rawSub->subscription_status ?? '';
                  if ($st === 'active') { $statusBadge = 'bg-success'; }
                  elseif ($st === 'suspended') { $statusBadge = 'bg-warning text-dark'; }
                  elseif ($st === 'cancelled') { $statusBadge = 'bg-danger'; }
                  elseif ($st === 'expired') { $statusBadge = 'bg-dark'; }
                ?>
                <span class="badge <?php echo $statusBadge; ?>">
                  <?php echo esc_entities(ucfirst($st ?: 'unknown')); ?>
                </span>
              </td>
            </tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:40%"><?php echo __('Frequency'); ?></th>
              <td><?php echo esc_entities(ucfirst($rawSub->frequency ?? '-')); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Start date'); ?></th>
              <td><?php echo esc_entities($rawSub->start_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('End date'); ?></th>
              <td><?php echo esc_entities($rawSub->end_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Renewal date'); ?></th>
              <td><?php echo esc_entities($rawSub->renewal_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Cost/year'); ?></th>
              <td>
                <?php if (!empty($rawSub->cost_per_year)): ?>
                  <?php echo esc_entities($rawSub->currency ?? 'USD'); ?> <?php echo number_format((float) $rawSub->cost_per_year, 2); ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          </table>
        </div>
      </div>
      <?php if (!empty($rawSub->notes)): ?>
        <div class="mt-3">
          <strong><?php echo __('Notes'); ?>:</strong>
          <p class="mb-0"><?php echo nl2br(esc_entities($rawSub->notes)); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Gaps section -->
  <?php $rawGaps = $sf_data->getRaw('gaps'); ?>
  <?php if (!empty($rawGaps)): ?>
    <div class="card shadow-sm mb-4 border-warning">
      <div class="card-header bg-warning text-dark">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Missing / Claimed Issues'); ?>
        <span class="badge bg-dark ms-2"><?php echo count($rawGaps); ?></span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-warning mb-0">
            <thead>
              <tr>
                <th><?php echo __('Issue #'); ?></th>
                <th><?php echo __('Volume'); ?></th>
                <th><?php echo __('Expected date'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawGaps as $gap): ?>
                <tr>
                  <td><?php echo esc_entities($gap->issue_number ?? '-'); ?></td>
                  <td><?php echo esc_entities($gap->volume ?? '-'); ?></td>
                  <td><?php echo esc_entities($gap->expected_date ?? '-'); ?></td>
                  <td>
                    <span class="badge <?php echo ($gap->issue_status ?? '') === 'claimed' ? 'bg-info' : 'bg-warning text-dark'; ?>">
                      <?php echo esc_entities(ucfirst($gap->issue_status ?? 'expected')); ?>
                    </span>
                  </td>
                  <td>
                    <?php if (($gap->issue_status ?? '') === 'expected'): ?>
                      <form method="post" action="<?php echo url_for(['module' => 'serial', 'action' => 'claim']); ?>" class="d-inline">
                        <input type="hidden" name="issue_id" value="<?php echo (int) $gap->id; ?>">
                        <input type="hidden" name="subscription_id" value="<?php echo (int) $rawSub->id; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-info" title="<?php echo __('Claim'); ?>">
                          <i class="fas fa-paper-plane me-1"></i><?php echo __('Claim'); ?>
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Check In Issue form (collapsible) -->
  <div class="card shadow-sm mb-4">
    <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#checkinForm" aria-expanded="false">
      <i class="fas fa-plus-circle me-2"></i><?php echo __('Check In Issue'); ?>
      <i class="fas fa-chevron-down float-end mt-1"></i>
    </div>
    <div class="collapse" id="checkinForm">
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'serial', 'action' => 'checkin']); ?>">
          <input type="hidden" name="subscription_id" value="<?php echo (int) $rawSub->id; ?>">
          <div class="row g-3">
            <div class="col-md-3">
              <label for="ci_volume" class="form-label"><?php echo __('Volume'); ?></label>
              <input type="text" class="form-control" id="ci_volume" name="volume">
            </div>
            <div class="col-md-3">
              <label for="ci_issue_number" class="form-label"><?php echo __('Issue number'); ?></label>
              <input type="text" class="form-control" id="ci_issue_number" name="issue_number">
            </div>
            <div class="col-md-3">
              <label for="ci_issue_date" class="form-label"><?php echo __('Issue date'); ?></label>
              <input type="date" class="form-control" id="ci_issue_date" name="issue_date">
            </div>
            <div class="col-md-3">
              <label for="ci_received_date" class="form-label"><?php echo __('Received date'); ?></label>
              <input type="date" class="form-control" id="ci_received_date" name="received_date"
                     value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-6">
              <label for="ci_supplement" class="form-label"><?php echo __('Supplement'); ?></label>
              <input type="text" class="form-control" id="ci_supplement" name="supplement"
                     placeholder="<?php echo __('e.g., Index, Special issue'); ?>">
            </div>
            <div class="col-md-6">
              <label for="ci_notes" class="form-label"><?php echo __('Notes'); ?></label>
              <input type="text" class="form-control" id="ci_notes" name="notes">
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-check me-1"></i><?php echo __('Check In'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Issues table -->
  <div class="card shadow-sm">
    <div class="card-header">
      <i class="fas fa-list me-2"></i><?php echo __('Issues'); ?>
    </div>
    <div class="card-body p-0">
      <?php $rawIssues = $sf_data->getRaw('issues'); ?>
      <?php if (empty($rawIssues)): ?>
        <div class="p-3 text-muted">
          <i class="fas fa-info-circle me-2"></i><?php echo __('No issues recorded yet.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Volume'); ?></th>
                <th><?php echo __('Issue #'); ?></th>
                <th><?php echo __('Expected date'); ?></th>
                <th><?php echo __('Received date'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Supplement'); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawIssues as $issue): ?>
                <?php
                  $issueBadge = 'bg-secondary';
                  $ist = $issue->issue_status ?? '';
                  if ($ist === 'received') { $issueBadge = 'bg-success'; }
                  elseif ($ist === 'expected') { $issueBadge = 'bg-primary'; }
                  elseif ($ist === 'claimed') { $issueBadge = 'bg-info'; }
                  elseif ($ist === 'missing') { $issueBadge = 'bg-danger'; }
                ?>
                <tr>
                  <td><?php echo esc_entities($issue->volume ?? '-'); ?></td>
                  <td><?php echo esc_entities($issue->issue_number ?? '-'); ?></td>
                  <td><?php echo esc_entities($issue->expected_date ?? '-'); ?></td>
                  <td><?php echo esc_entities($issue->received_date ?? '-'); ?></td>
                  <td>
                    <span class="badge <?php echo $issueBadge; ?>">
                      <?php echo esc_entities(ucfirst($ist ?: 'unknown')); ?>
                    </span>
                  </td>
                  <td><?php echo esc_entities($issue->supplement ?? '-'); ?></td>
                  <td>
                    <?php if ($ist === 'expected'): ?>
                      <form method="post" action="<?php echo url_for(['module' => 'serial', 'action' => 'claim']); ?>" class="d-inline">
                        <input type="hidden" name="issue_id" value="<?php echo (int) $issue->id; ?>">
                        <input type="hidden" name="subscription_id" value="<?php echo (int) $rawSub->id; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-info" title="<?php echo __('Claim'); ?>">
                          <i class="fas fa-paper-plane"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>
