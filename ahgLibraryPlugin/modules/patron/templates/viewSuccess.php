<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Patron: %1%', ['%1%' => esc_entities($patron->first_name . ' ' . $patron->last_name)]); ?></h1>
<?php end_slot(); ?>

<?php $rawPatron = $sf_data->getRaw('patron'); ?>
<?php $rawCheckouts = $sf_data->getRaw('checkouts'); ?>
<?php $rawHolds = $sf_data->getRaw('holds'); ?>
<?php $rawFines = $sf_data->getRaw('fines'); ?>
<?php $rawHistory = $sf_data->getRaw('history'); ?>

<!-- Patron Header -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h3 class="mb-1"><?php echo esc_entities($rawPatron->first_name . ' ' . $rawPatron->last_name); ?></h3>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <span class="text-muted"><i class="fas fa-barcode me-1"></i><code><?php echo esc_entities($rawPatron->patron_barcode); ?></code></span>
          <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($rawPatron->patron_type)); ?></span>
          <?php if ($rawPatron->borrowing_status === 'active'): ?>
            <span class="badge bg-success"><?php echo __('Active'); ?></span>
          <?php elseif ($rawPatron->borrowing_status === 'suspended'): ?>
            <span class="badge bg-danger"><?php echo __('Suspended'); ?></span>
          <?php else: ?>
            <span class="badge bg-warning text-dark"><?php echo esc_entities(ucfirst($rawPatron->borrowing_status)); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($rawPatron->email)): ?>
          <div class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo esc_entities($rawPatron->email); ?></div>
        <?php endif; ?>
        <?php if (!empty($rawPatron->phone)): ?>
          <div class="text-muted"><i class="fas fa-phone me-1"></i><?php echo esc_entities($rawPatron->phone); ?></div>
        <?php endif; ?>
        <?php if (!empty($rawPatron->expiry_date)): ?>
          <div class="text-muted mt-1"><i class="fas fa-calendar me-1"></i><?php echo __('Expires: %1%', ['%1%' => esc_entities($rawPatron->expiry_date)]); ?></div>
        <?php endif; ?>
        <div class="text-muted mt-1">
          <small><?php echo __('Max checkouts: %1% | Max holds: %2%', ['%1%' => (int) $rawPatron->max_checkouts, '%2%' => (int) $rawPatron->max_holds]); ?></small>
        </div>
        <?php if (!empty($rawPatron->notes)): ?>
          <div class="mt-2"><small class="text-muted"><?php echo nl2br(esc_entities($rawPatron->notes)); ?></small></div>
        <?php endif; ?>
      </div>
      <div class="btn-group">
        <a href="<?php echo url_for(['module' => 'patron', 'action' => 'edit', 'id' => $rawPatron->id]); ?>" class="btn btn-outline-primary">
          <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
        </a>
        <?php if ($rawPatron->borrowing_status === 'active'): ?>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#suspendModal">
            <i class="fas fa-ban me-1"></i><?php echo __('Suspend'); ?>
          </button>
        <?php elseif ($rawPatron->borrowing_status === 'suspended'): ?>
          <form method="post" action="<?php echo url_for(['module' => 'patron', 'action' => 'reactivate']); ?>" class="d-inline">
            <input type="hidden" name="id" value="<?php echo $rawPatron->id; ?>">
            <button type="submit" class="btn btn-outline-success">
              <i class="fas fa-check me-1"></i><?php echo __('Reactivate'); ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="patronTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="checkouts-tab" data-bs-toggle="tab" data-bs-target="#checkouts" type="button" role="tab">
      <i class="fas fa-book-reader me-1"></i><?php echo __('Checkouts'); ?>
      <span class="badge bg-primary ms-1"><?php echo count($rawCheckouts); ?></span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="holds-tab" data-bs-toggle="tab" data-bs-target="#holds" type="button" role="tab">
      <i class="fas fa-clock me-1"></i><?php echo __('Holds'); ?>
      <span class="badge bg-info ms-1"><?php echo count($rawHolds); ?></span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="fines-tab" data-bs-toggle="tab" data-bs-target="#fines" type="button" role="tab">
      <i class="fas fa-dollar-sign me-1"></i><?php echo __('Fines'); ?>
      <?php if (count($rawFines) > 0): ?>
        <span class="badge bg-danger ms-1"><?php echo count($rawFines); ?></span>
      <?php endif; ?>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
      <i class="fas fa-history me-1"></i><?php echo __('History'); ?>
    </button>
  </li>
</ul>

<div class="tab-content" id="patronTabContent">

  <!-- Checkouts Tab -->
  <div class="tab-pane fade show active" id="checkouts" role="tabpanel">
    <?php if (empty($rawCheckouts)): ?>
      <div class="alert alert-info"><?php echo __('No current checkouts.'); ?></div>
    <?php else: ?>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Barcode'); ?></th>
                <th><?php echo __('Checkout Date'); ?></th>
                <th><?php echo __('Due Date'); ?></th>
                <th class="text-center"><?php echo __('Renewals'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th class="text-end"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawCheckouts as $checkout): ?>
                <?php $isOverdue = (!empty($checkout->due_date) && $checkout->due_date < date('Y-m-d')); ?>
                <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                  <td><?php echo esc_entities($checkout->title ?? __('Untitled')); ?></td>
                  <td><code><?php echo esc_entities($checkout->copy_barcode ?? ''); ?></code></td>
                  <td><?php echo esc_entities($checkout->checkout_date ?? ''); ?></td>
                  <td>
                    <?php echo esc_entities($checkout->due_date ?? ''); ?>
                    <?php if ($isOverdue): ?>
                      <span class="badge bg-danger ms-1"><?php echo __('Overdue'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center"><?php echo (int) ($checkout->renewal_count ?? 0); ?></td>
                  <td><?php echo esc_entities(ucfirst(str_replace('_', ' ', $checkout->checkout_status ?? ''))); ?></td>
                  <td class="text-end">
                    <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'renew']); ?>" class="d-inline">
                      <input type="hidden" name="checkout_id" value="<?php echo (int) $checkout->id; ?>">
                      <input type="hidden" name="patron_id" value="<?php echo $rawPatron->id; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-primary" title="<?php echo __('Renew'); ?>">
                        <i class="fas fa-redo"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Holds Tab -->
  <div class="tab-pane fade" id="holds" role="tabpanel">
    <?php if (empty($rawHolds)): ?>
      <div class="alert alert-info"><?php echo __('No active holds.'); ?></div>
    <?php else: ?>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Hold Date'); ?></th>
                <th class="text-center"><?php echo __('Position'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th class="text-end"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawHolds as $hold): ?>
                <tr>
                  <td><?php echo esc_entities($hold->title ?? __('Untitled')); ?></td>
                  <td><?php echo esc_entities($hold->hold_date ?? ''); ?></td>
                  <td class="text-center"><?php echo (int) ($hold->queue_position ?? 0); ?></td>
                  <td>
                    <?php if ($hold->hold_status === 'ready'): ?>
                      <span class="badge bg-success"><?php echo __('Ready'); ?></span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark"><?php echo esc_entities(ucfirst($hold->hold_status ?? '')); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'cancelHold']); ?>" class="d-inline">
                      <input type="hidden" name="hold_id" value="<?php echo (int) $hold->id; ?>">
                      <input type="hidden" name="patron_id" value="<?php echo $rawPatron->id; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Cancel'); ?>"
                              onclick="return confirm('<?php echo __('Cancel this hold?'); ?>');">
                        <i class="fas fa-times"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Fines Tab -->
  <div class="tab-pane fade" id="fines" role="tabpanel">
    <?php if (empty($rawFines)): ?>
      <div class="alert alert-info"><?php echo __('No outstanding fines.'); ?></div>
    <?php else: ?>
      <?php
        $totalBalance = 0;
        foreach ($rawFines as $fine) {
            $remaining = ($fine->amount ?? 0) - ($fine->amount_paid ?? 0);
            $totalBalance += $remaining;
        }
      ?>
      <div class="alert alert-warning mb-3">
        <strong><?php echo __('Total Balance: %1%', ['%1%' => number_format($totalBalance, 2)]); ?></strong>
      </div>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Type'); ?></th>
                <th class="text-end"><?php echo __('Amount'); ?></th>
                <th class="text-end"><?php echo __('Paid'); ?></th>
                <th class="text-end"><?php echo __('Remaining'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Description'); ?></th>
                <th class="text-end"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawFines as $fine): ?>
                <?php $remaining = ($fine->amount ?? 0) - ($fine->amount_paid ?? 0); ?>
                <tr>
                  <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $fine->fine_type ?? ''))); ?></span></td>
                  <td class="text-end"><?php echo number_format($fine->amount ?? 0, 2); ?></td>
                  <td class="text-end"><?php echo number_format($fine->amount_paid ?? 0, 2); ?></td>
                  <td class="text-end"><strong><?php echo number_format($remaining, 2); ?></strong></td>
                  <td><?php echo esc_entities($fine->created_at ?? ''); ?></td>
                  <td><?php echo esc_entities($fine->description ?? ''); ?></td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'payFine']); ?>" class="d-inline">
                        <input type="hidden" name="fine_id" value="<?php echo (int) $fine->id; ?>">
                        <input type="hidden" name="patron_id" value="<?php echo $rawPatron->id; ?>">
                        <button type="submit" class="btn btn-outline-success" title="<?php echo __('Pay'); ?>">
                          <i class="fas fa-money-bill"></i>
                        </button>
                      </form>
                      <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'waiveFine']); ?>" class="d-inline">
                        <input type="hidden" name="fine_id" value="<?php echo (int) $fine->id; ?>">
                        <input type="hidden" name="patron_id" value="<?php echo $rawPatron->id; ?>">
                        <button type="submit" class="btn btn-outline-warning" title="<?php echo __('Waive'); ?>"
                                onclick="return confirm('<?php echo __('Waive this fine?'); ?>');">
                          <i class="fas fa-hand-holding-usd"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- History Tab -->
  <div class="tab-pane fade" id="history" role="tabpanel">
    <?php if (empty($rawHistory)): ?>
      <div class="alert alert-info"><?php echo __('No checkout history.'); ?></div>
    <?php else: ?>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Checkout Date'); ?></th>
                <th><?php echo __('Return Date'); ?></th>
                <th><?php echo __('Status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rawHistory as $record): ?>
                <tr>
                  <td><?php echo esc_entities($record->title ?? __('Untitled')); ?></td>
                  <td><?php echo esc_entities($record->checkout_date ?? ''); ?></td>
                  <td><?php echo esc_entities($record->return_date ?? '-'); ?></td>
                  <td>
                    <?php if ($record->checkout_status === 'returned'): ?>
                      <span class="badge bg-success"><?php echo __('Returned'); ?></span>
                    <?php elseif ($record->checkout_status === 'checked_out'): ?>
                      <span class="badge bg-primary"><?php echo __('Checked Out'); ?></span>
                    <?php elseif ($record->checkout_status === 'lost'): ?>
                      <span class="badge bg-danger"><?php echo __('Lost'); ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $record->checkout_status ?? ''))); ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Suspend Modal -->
<?php if ($rawPatron->borrowing_status === 'active'): ?>
<div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for(['module' => 'patron', 'action' => 'suspend']); ?>">
        <input type="hidden" name="id" value="<?php echo $rawPatron->id; ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="suspendModalLabel"><?php echo __('Suspend Patron'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Reason for suspension'); ?></label>
            <textarea name="reason" class="form-control" rows="3" placeholder="<?php echo __('Enter reason...'); ?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-danger"><?php echo __('Suspend Patron'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="mt-3">
  <a href="<?php echo url_for(['module' => 'patron', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Patrons'); ?>
  </a>
</div>
