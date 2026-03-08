<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('ILL Request'); ?></h1>
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

<?php $req = $sf_data->getRaw('illRequest'); ?>
<?php if (!$req): ?>
  <div class="alert alert-warning"><?php echo __('ILL request not found.'); ?></div>
<?php else: ?>

  <!-- Action buttons -->
  <div class="mb-3">
    <a href="<?php echo url_for(['module' => 'ill', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to list'); ?>
    </a>
  </div>

  <div class="row">
    <!-- Request detail card -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-book me-2"></i><?php echo __('Request Details'); ?>
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:35%"><?php echo __('Title'); ?></th>
              <td class="fw-bold"><?php echo esc_entities($req->title ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Author'); ?></th>
              <td><?php echo esc_entities($req->author ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('ISBN'); ?></th>
              <td><?php echo esc_entities($req->isbn ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('ISSN'); ?></th>
              <td><?php echo esc_entities($req->issn ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Volume / Issue'); ?></th>
              <td><?php echo esc_entities($req->volume_issue ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Pages needed'); ?></th>
              <td><?php echo esc_entities($req->pages_needed ?? '-'); ?></td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <!-- Libraries card -->
    <div class="col-lg-6 mb-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-secondary text-white">
          <i class="fas fa-university me-2"></i><?php echo __('Libraries'); ?>
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:40%"><?php echo __('Direction'); ?></th>
              <td>
                <?php
                  $dir = $req->direction ?? '';
                  $dirBadge = $dir === 'borrow' ? 'bg-info' : ($dir === 'lend' ? 'bg-primary' : 'bg-secondary');
                ?>
                <span class="badge <?php echo $dirBadge; ?>"><?php echo esc_entities(ucfirst($dir ?: '-')); ?></span>
              </td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Requesting library'); ?></th>
              <td><?php echo esc_entities($req->requesting_library ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Lending library'); ?></th>
              <td><?php echo esc_entities($req->lending_library ?? '-'); ?></td>
            </tr>
          </table>

          <!-- Patron info if linked -->
          <?php if (!empty($req->patron_id)): ?>
            <hr>
            <h6 class="fw-bold"><?php echo __('Patron'); ?></h6>
            <table class="table table-sm mb-0">
              <tr>
                <th class="text-muted" style="width:40%"><?php echo __('Name'); ?></th>
                <td><?php echo esc_entities(trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? '')) ?: '-'); ?></td>
              </tr>
              <tr>
                <th class="text-muted"><?php echo __('Barcode'); ?></th>
                <td><code><?php echo esc_entities($req->patron_barcode ?? '-'); ?></code></td>
              </tr>
              <tr>
                <th class="text-muted"><?php echo __('Email'); ?></th>
                <td><?php echo esc_entities($req->email ?? '-'); ?></td>
              </tr>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Status card -->
  <div class="card shadow-sm mb-4">
    <div class="card-header">
      <i class="fas fa-info-circle me-2"></i><?php echo __('Status & Dates'); ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:40%"><?php echo __('Status'); ?></th>
              <td>
                <?php
                  $st = $req->ill_status ?? '';
                  $stBadge = 'bg-secondary';
                  if ($st === 'submitted') { $stBadge = 'bg-warning text-dark'; }
                  elseif ($st === 'approved') { $stBadge = 'bg-info'; }
                  elseif ($st === 'sent') { $stBadge = 'bg-primary'; }
                  elseif ($st === 'received') { $stBadge = 'bg-success'; }
                  elseif ($st === 'returned') { $stBadge = 'bg-dark'; }
                  elseif ($st === 'cancelled') { $stBadge = 'bg-danger'; }
                ?>
                <span class="badge <?php echo $stBadge; ?> fs-6">
                  <?php echo esc_entities(ucfirst($st ?: 'unknown')); ?>
                </span>
              </td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Request date'); ?></th>
              <td><?php echo esc_entities($req->request_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Needed by'); ?></th>
              <td><?php echo esc_entities($req->needed_by_date ?? '-'); ?></td>
            </tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-sm mb-0">
            <tr>
              <th class="text-muted" style="width:40%"><?php echo __('Sent date'); ?></th>
              <td><?php echo esc_entities($req->sent_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Received date'); ?></th>
              <td><?php echo esc_entities($req->received_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Due date'); ?></th>
              <td><?php echo esc_entities($req->due_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Returned date'); ?></th>
              <td><?php echo esc_entities($req->returned_date ?? '-'); ?></td>
            </tr>
            <tr>
              <th class="text-muted"><?php echo __('Shipping cost'); ?></th>
              <td>
                <?php if (!empty($req->shipping_cost)): ?>
                  <?php echo esc_entities($req->currency ?? 'USD'); ?> <?php echo number_format((float) $req->shipping_cost, 2); ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          </table>
        </div>
      </div>
      <?php if (!empty($req->notes)): ?>
        <div class="mt-3">
          <strong><?php echo __('Notes'); ?>:</strong>
          <p class="mb-0"><?php echo nl2br(esc_entities($req->notes)); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Status update form -->
  <?php if (!in_array($req->ill_status ?? '', ['returned', 'cancelled'])): ?>
    <div class="card shadow-sm">
      <div class="card-header">
        <i class="fas fa-edit me-2"></i><?php echo __('Update Status'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'ill', 'action' => 'status']); ?>">
          <input type="hidden" name="id" value="<?php echo (int) $req->id; ?>">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="ill_new_status" class="form-label"><?php echo __('New status'); ?></label>
              <select class="form-select" id="ill_new_status" name="new_status" required>
                <option value=""><?php echo __('Select status...'); ?></option>
                <?php
                  $availableStatuses = ['submitted', 'approved', 'sent', 'received', 'returned', 'cancelled'];
                  foreach ($availableStatuses as $s):
                    if ($s === ($req->ill_status ?? '')) { continue; }
                ?>
                  <option value="<?php echo $s; ?>"><?php echo esc_entities(ucfirst($s)); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label for="ill_status_notes" class="form-label"><?php echo __('Notes'); ?></label>
              <textarea class="form-control" id="ill_status_notes" name="notes" rows="1"
                        placeholder="<?php echo __('Optional notes for this status change'); ?>"></textarea>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-1"></i><?php echo __('Update'); ?>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>
