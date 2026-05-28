<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Overdue Items'); ?></h1>
<?php end_slot(); ?>

<?php
  $rawPatrons = $sf_data->getRaw('overduePatrons');
  $totalItems = (int) $sf_data->getRaw('totalItems');
  $totalPatrons = (int) $sf_data->getRaw('totalPatrons');
  $sendResult = $sf_data->getRaw('sendResult');
  $sendSuccess = (bool) $sf_data->getRaw('sendSuccess');
  $page = (int) $sf_data->getRaw('page');
  $totalPages = (int) $sf_data->getRaw('totalPages');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <span class="text-muted">
      <?php echo __('%1% overdue item(s) across %2% patron(s)', ['%1%' => $totalItems, '%2%' => $totalPatrons]); ?>
    </span>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'index']); ?>"
       class="btn btn-outline-primary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Checkout station'); ?>
    </a>
  </div>
</div>

<?php /* ===== Send Notices Card ===== */ ?>
<div class="card mb-4 border-primary">
  <div class="card-header bg-primary text-white">
    <i class="fas fa-envelope me-2"></i><?php echo __('Send Overdue Notices'); ?>
  </div>
  <div class="card-body">
    <?php if (!empty($sendResult)): ?>
      <?php if ($sendSuccess): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i>
          <?php if (strpos($sendResult, '{') === 0): ?>
            <strong><?php echo __('Dry run preview:'); ?></strong><br>
            <pre class="mb-0 mt-2" style="max-height:300px; overflow:auto; font-size:12px;"><?php echo $sendResult; ?></pre>
          <?php else: ?>
            <?php echo $sendResult; ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo esc_entities($sendResult); ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <p class="small text-muted mb-3">
      <?php echo __('Send overdue notice emails to patrons with items past their due date.'
        . ' You can preview the batch with a dry run before sending for real.'); ?>
    </p>

    <form method="post" class="row g-3 align-items-end">
      <input type="hidden" name="send_notices" value="1">

      <div class="col-md-3">
        <label for="min-days" class="form-label small fw-bold">
          <?php echo __('Minimum days overdue'); ?>
        </label>
        <select name="min_days" id="min-days" class="form-select">
          <option value="1"><?php echo __('1+ days'); ?></option>
          <option value="7"><?php echo __('7+ days'); ?></option>
          <option value="14"><?php echo __('14+ days'); ?></option>
          <option value="30"><?php echo __('30+ days'); ?></option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small fw-bold">&nbsp;</label>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="dry_run" id="dry-run" value="1" checked>
          <label class="form-check-label small" for="dry-run">
            <?php echo __('Dry run (preview only)'); ?>
          </label>
        </div>
      </div>

      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-paper-plane me-1"></i>
          <?php echo __('Send / Preview Notices'); ?>
        </button>
      </div>

      <div class="col-md-3">
        <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue']); ?>"
           class="btn btn-outline-secondary w-100">
          <i class="fas fa-redo me-1"></i>
          <?php echo __('Refresh List'); ?>
        </a>
      </div>
    </form>
  </div>
</div>

<?php /* ===== Overdue Items Table ===== */ ?>
<?php if (empty($rawPatrons)): ?>
  <div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo __('No overdue items. All loans are current.'); ?>
  </div>
<?php else: ?>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Patron'); ?></th>
              <th><?php echo __('Card'); ?></th>
              <th><?php echo __('Email'); ?></th>
              <th><?php echo __('Items overdue'); ?></th>
              <th><?php echo __('Max days overdue'); ?></th>
              <th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawPatrons as $patron): ?>
              <?php
                $items = $patron['items'] ?? [];
                $maxDays = 0;
                foreach ($items as $item) {
                  $maxDays = max($maxDays, $item['days_overdue'] ?? 0);
                }
                $severityClass = $maxDays > 30 ? 'table-danger' : ($maxDays > 14 ? 'table-warning' : '');
                $hasEmail = !empty($patron['patron_email'])
                  && filter_var($patron['patron_email'], FILTER_VALIDATE_EMAIL);
              ?>
              <tr class="<?php echo $severityClass; ?>">
                <td>
                  <strong><?php echo esc_entities($patron['patron_name'] ?: '—'); ?></strong>
                </td>
                <td>
                  <code><?php echo esc_entities($patron['patron_barcode'] ?: '—'); ?></code>
                </td>
                <td>
                  <?php if ($hasEmail): ?>
                    <a href="mailto:<?php echo esc_entities($patron['patron_email']); ?>">
                      <?php echo esc_entities($patron['patron_email']); ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted"><?php echo __('No email'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-secondary"><?php echo count($items); ?></span>
                </td>
                <td>
                  <span class="badge <?php echo $maxDays > 30 ? 'bg-danger' : ($maxDays > 14 ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                    <?php echo $maxDays; ?>d
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'patron_id' => $patron['patron_id']]); ?>"
                       class="btn btn-outline-primary"
                       title="<?php echo __('View items'); ?>">
                      <i class="fas fa-list"></i>
                    </a>
                    <?php if ($hasEmail): ?>
                      <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue']); ?>"
                            class="d-inline" title="<?php echo __('Send notice to this patron'); ?>">
                        <input type="hidden" name="send_notices" value="1">
                        <input type="hidden" name="patron_id" value="<?php echo (int) $patron['patron_id']; ?>">
                        <input type="hidden" name="dry_run" value="1">
                        <button type="submit" class="btn btn-outline-info">
                          <i class="fas fa-envelope"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                    <a href="<?php echo url_for(['module' => 'patron', 'action' => 'view', 'id' => $patron['patron_id']]); ?>"
                       class="btn btn-outline-secondary"
                       title="<?php echo __('Patron profile'); ?>">
                      <i class="fas fa-user"></i>
                    </a>
                  </div>
                </td>
              </tr>

              <?php /* Item sub-rows */ ?>
              <?php foreach ($items as $item): ?>
                <?php
                  $days = (int) ($item['days_overdue'] ?? 0);
                  $badgeClass = $days > 30 ? 'bg-danger' : ($days > 14 ? 'bg-warning text-dark' : 'bg-secondary');
                ?>
                <tr class="table-row-nested text-muted small">
                  <td colspan="2" class="ps-4 text-secondary">
                    <i class="fas fa-caret-right me-1"></i>
                    <?php echo esc_entities($item['item_title'] ?: '—'); ?>
                  </td>
                  <td><code><?php echo esc_entities($item['item_barcode'] ?: '—'); ?></code></td>
                  <td><?php echo esc_entities($item['call_number'] ?: '—'); ?></td>
                  <td><?php echo substr($item['due_date'] ?? '', 0, 10); ?></td>
                  <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $days; ?>d</span></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'renew']); ?>"
                            class="d-inline">
                        <input type="hidden" name="item_barcode" value="<?php echo esc_entities($item['item_barcode'] ?? ''); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning" title="<?php echo __('Renew'); ?>">
                          <i class="fas fa-redo"></i>
                        </button>
                      </form>
                      <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'checkin']); ?>"
                            class="d-inline">
                        <input type="hidden" name="item_barcode" value="<?php echo esc_entities($item['item_barcode'] ?? ''); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Return'); ?>">
                          <i class="fas fa-sign-in-alt"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php /* Pagination */ ?>
  <?php if ($totalPages > 1): ?>
    <nav aria-label="Overdue pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $page - 1]); ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>

        <?php
          $start = max(1, $page - 2);
          $end = min($totalPages, $page + 2);
        ?>
        <?php if ($start > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => 1]); ?>">1</a>
          </li>
          <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $i]); ?>">
              <?php echo $i; ?>
            </a>
          </li>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $totalPages]); ?>">
              <?php echo $totalPages; ?>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $page + 1]); ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

<?php endif; ?>
