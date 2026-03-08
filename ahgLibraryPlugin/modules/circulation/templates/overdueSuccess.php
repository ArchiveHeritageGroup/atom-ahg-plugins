<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Overdue Items'); ?></h1>
<?php end_slot(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <span class="text-muted"><?php echo __('%1% overdue item(s)', ['%1%' => (int) $total]); ?></span>
  </div>
  <div>
    <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'index']); ?>" class="btn btn-outline-primary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to checkout station'); ?>
    </a>
  </div>
</div>

<?php $rawItems = $sf_data->getRaw('overdueItems'); ?>
<?php if (empty($rawItems)): ?>
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
              <th><?php echo __('Item'); ?></th>
              <th><?php echo __('Item barcode'); ?></th>
              <th><?php echo __('Call number'); ?></th>
              <th><?php echo __('Patron'); ?></th>
              <th><?php echo __('Patron barcode'); ?></th>
              <th><?php echo __('Checkout date'); ?></th>
              <th><?php echo __('Due date'); ?></th>
              <th><?php echo __('Days overdue'); ?></th>
              <th><?php echo __('Renewals'); ?></th>
              <th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawItems as $item): ?>
              <?php
                $daysOverdue = (int) ($item->days_overdue ?? 0);
                $severityClass = '';
                if ($daysOverdue > 30) {
                    $severityClass = 'table-danger';
                } elseif ($daysOverdue > 14) {
                    $severityClass = 'table-warning';
                }
              ?>
              <tr class="<?php echo $severityClass; ?>">
                <td><?php echo esc_entities($item->item_title ?? '-'); ?></td>
                <td><code><?php echo esc_entities($item->item_barcode ?? '-'); ?></code></td>
                <td><?php echo esc_entities($item->call_number ?? '-'); ?></td>
                <td><?php echo esc_entities($item->patron_name ?? '-'); ?></td>
                <td><code><?php echo esc_entities($item->patron_barcode ?? '-'); ?></code></td>
                <td><?php echo esc_entities($item->checkout_date ?? '-'); ?></td>
                <td><?php echo esc_entities($item->due_date ?? '-'); ?></td>
                <td>
                  <span class="badge <?php echo $daysOverdue > 30 ? 'bg-danger' : ($daysOverdue > 14 ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                    <?php echo $daysOverdue; ?> <?php echo __('days'); ?>
                  </span>
                </td>
                <td><?php echo (int) ($item->renewals ?? 0); ?></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <!-- Renew -->
                    <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'renew']); ?>" class="d-inline">
                      <input type="hidden" name="item_barcode" value="<?php echo esc_entities($item->item_barcode ?? ''); ?>">
                      <input type="hidden" name="patron_barcode" value="<?php echo esc_entities($item->patron_barcode ?? ''); ?>">
                      <button type="submit" class="btn btn-sm btn-outline-warning" title="<?php echo __('Renew'); ?>">
                        <i class="fas fa-redo"></i>
                      </button>
                    </form>
                    <!-- Checkin -->
                    <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'checkin']); ?>" class="d-inline">
                      <input type="hidden" name="item_barcode" value="<?php echo esc_entities($item->item_barcode ?? ''); ?>">
                      <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Return'); ?>">
                        <i class="fas fa-sign-in-alt"></i>
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
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $page - 1]); ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>

        <?php
          $startPage = max(1, $page - 2);
          $endPage = min($totalPages, $page + 2);
        ?>

        <?php if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => 1]); ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
          <?php if ($endPage < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'circulation', 'action' => 'overdue', 'page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
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
