<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-id-card me-2"></i><?php echo __('My Library Account'); ?></h1>
<?php end_slot(); ?>

<?php $account = $sf_data->getRaw('account'); ?>

<?php if (!$account): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('You do not have a library patron account. Please contact library staff to register.'); ?>
  </div>
<?php else: ?>
  <?php
    $patron    = $account['patron'];
    $checkouts = $account['checkouts'] ?? [];
    $holds     = $account['holds'] ?? [];
    $fines     = $account['fines'] ?? [];
    $balance   = $account['balance'] ?? 0;
  ?>

  <!-- Patron Info -->
  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h5 class="mb-1"><?php echo esc_entities(($patron->first_name ?? '') . ' ' . ($patron->last_name ?? '')); ?></h5>
          <p class="text-muted mb-0">
            <?php echo __('Barcode:'); ?> <strong><?php echo esc_entities($patron->patron_barcode ?? ''); ?></strong>
            &middot;
            <?php echo __('Type:'); ?> <?php echo esc_entities(ucfirst($patron->patron_type ?? 'general')); ?>
            &middot;
            <?php echo __('Status:'); ?>
            <?php
              $bStatus = $patron->borrowing_status ?? 'unknown';
              $bClass = $bStatus === 'active' ? 'bg-success' : ($bStatus === 'suspended' ? 'bg-danger' : 'bg-secondary');
            ?>
            <span class="badge <?php echo $bClass; ?>"><?php echo esc_entities(ucfirst($bStatus)); ?></span>
          </p>
        </div>
        <div class="col-md-6 text-md-end">
          <?php if ($balance > 0): ?>
            <span class="badge bg-danger fs-6"><?php echo __('Balance Due:'); ?> $<?php echo number_format((float) $balance, 2); ?></span>
          <?php else: ?>
            <span class="badge bg-success fs-6"><?php echo __('No outstanding fines'); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="loans-tab" data-bs-toggle="tab" data-bs-target="#loans-pane" type="button" role="tab" aria-controls="loans-pane" aria-selected="true">
        <i class="fas fa-book me-1"></i><?php echo __('Current Loans'); ?>
        <span class="badge bg-primary ms-1"><?php echo count($checkouts); ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="holds-tab" data-bs-toggle="tab" data-bs-target="#holds-pane" type="button" role="tab" aria-controls="holds-pane" aria-selected="false">
        <i class="fas fa-hand-paper me-1"></i><?php echo __('Holds'); ?>
        <span class="badge bg-primary ms-1"><?php echo count($holds); ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="fines-tab" data-bs-toggle="tab" data-bs-target="#fines-pane" type="button" role="tab" aria-controls="fines-pane" aria-selected="false">
        <i class="fas fa-dollar-sign me-1"></i><?php echo __('Fines'); ?>
        <?php if ($balance > 0): ?>
          <span class="badge bg-danger ms-1"><?php echo count($fines); ?></span>
        <?php endif; ?>
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- Current Loans -->
    <div class="tab-pane fade show active" id="loans-pane" role="tabpanel" aria-labelledby="loans-tab">
      <?php if (empty($checkouts)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i><?php echo __('You have no items currently on loan.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Barcode'); ?></th>
                <th><?php echo __('Checkout Date'); ?></th>
                <th><?php echo __('Due Date'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($checkouts as $co): ?>
                <?php
                  $isOverdue = false;
                  if (!empty($co->due_date)) {
                      $isOverdue = strtotime($co->due_date) < time() && empty($co->return_date);
                  }
                ?>
                <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                  <td><?php echo esc_entities($co->title ?? __('Unknown item')); ?></td>
                  <td><small><?php echo esc_entities($co->item_barcode ?? ''); ?></small></td>
                  <td><?php echo esc_entities($co->checkout_date ?? ''); ?></td>
                  <td>
                    <?php echo esc_entities($co->due_date ?? ''); ?>
                    <?php if ($isOverdue): ?>
                      <span class="badge bg-danger ms-1"><?php echo __('OVERDUE'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                      $coStatus = $co->checkout_status ?? $co->status ?? 'active';
                      echo esc_entities(ucfirst(str_replace('_', ' ', $coStatus)));
                    ?>
                  </td>
                  <td>
                    <?php if (empty($co->return_date)): ?>
                      <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'renew']); ?>" class="d-inline">
                        <input type="hidden" name="item_barcode" value="<?php echo esc_entities($co->item_barcode ?? ''); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                          <i class="fas fa-redo me-1"></i><?php echo __('Renew'); ?>
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

    <!-- Holds -->
    <div class="tab-pane fade" id="holds-pane" role="tabpanel" aria-labelledby="holds-tab">
      <?php if (empty($holds)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i><?php echo __('You have no active holds.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Hold Date'); ?></th>
                <th><?php echo __('Position'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($holds as $hold): ?>
                <tr>
                  <td><?php echo esc_entities($hold->title ?? __('Unknown item')); ?></td>
                  <td><?php echo esc_entities($hold->hold_date ?? $hold->created_at ?? ''); ?></td>
                  <td><?php echo (int) ($hold->queue_position ?? 0); ?></td>
                  <td>
                    <?php
                      $hStatus = $hold->hold_status ?? 'pending';
                      $hClass = 'bg-secondary';
                      if ($hStatus === 'ready') {
                          $hClass = 'bg-success';
                      } elseif ($hStatus === 'pending') {
                          $hClass = 'bg-warning text-dark';
                      }
                    ?>
                    <span class="badge <?php echo $hClass; ?>"><?php echo esc_entities(ucfirst($hStatus)); ?></span>
                  </td>
                  <td>
                    <?php if (in_array($hStatus, ['pending', 'ready'])): ?>
                      <form method="post" action="<?php echo url_for(['module' => 'opac', 'action' => 'hold']); ?>" class="d-inline">
                        <input type="hidden" name="library_item_id" value="<?php echo (int) ($hold->library_item_id ?? 0); ?>">
                        <input type="hidden" name="cancel_hold" value="<?php echo (int) ($hold->id ?? 0); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo __('Cancel this hold?'); ?>');">
                          <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
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

    <!-- Fines -->
    <div class="tab-pane fade" id="fines-pane" role="tabpanel" aria-labelledby="fines-tab">
      <?php if (empty($fines)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i><?php echo __('You have no fines.'); ?>
        </div>
      <?php else: ?>
        <div class="alert alert-warning mb-3">
          <strong><?php echo __('Outstanding Balance:'); ?></strong> $<?php echo number_format((float) $balance, 2); ?>
        </div>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Description'); ?></th>
                <th><?php echo __('Amount'); ?></th>
                <th><?php echo __('Paid'); ?></th>
                <th><?php echo __('Status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fines as $fine): ?>
                <tr>
                  <td><?php echo esc_entities($fine->fine_date ?? $fine->created_at ?? ''); ?></td>
                  <td><?php echo esc_entities($fine->description ?? $fine->fine_type ?? ''); ?></td>
                  <td>$<?php echo number_format((float) ($fine->amount ?? 0), 2); ?></td>
                  <td>$<?php echo number_format((float) ($fine->paid_amount ?? 0), 2); ?></td>
                  <td>
                    <?php
                      $fStatus = $fine->fine_status ?? $fine->status ?? 'unpaid';
                      $fClass = $fStatus === 'paid' ? 'bg-success' : ($fStatus === 'partial' ? 'bg-warning text-dark' : 'bg-danger');
                    ?>
                    <span class="badge <?php echo $fClass; ?>"><?php echo esc_entities(ucfirst($fStatus)); ?></span>
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
