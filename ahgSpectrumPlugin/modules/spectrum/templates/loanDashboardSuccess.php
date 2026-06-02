<h1 class="h3 mb-4"><i class="fas fa-handshake me-2"></i><?php echo __('Loans Out Dashboard'); ?></h1>

<div class="mb-3">
  <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Spectrum'); ?>
  </a>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><?php echo __('Loans Out'); ?></h5></div>
  <div class="card-body p-0">
    <?php $rawLoans = $sf_data->getRaw('loans'); ?>
    <?php if (!empty($rawLoans)): ?>
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Loan #'); ?></th>
              <th><?php echo __('Borrower'); ?></th>
              <th><?php echo __('Venue'); ?></th>
              <th><?php echo __('Loan date'); ?></th>
              <th><?php echo __('Return due'); ?></th>
              <th><?php echo __('Status'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawLoans as $loan): ?>
              <tr>
                <td><?php echo esc_entities($loan->loan_out_number ?? '—'); ?></td>
                <td><?php echo esc_entities($loan->borrower_name ?? ''); ?></td>
                <td><?php echo esc_entities($loan->venue_name ?? ''); ?></td>
                <td><?php echo esc_entities($loan->loan_out_date ?? ''); ?></td>
                <td><?php echo esc_entities($loan->loan_return_date ?? ''); ?></td>
                <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst((string) ($loan->loan_status ?? '—'))); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="p-4 text-center text-muted">
        <i class="fas fa-handshake fa-2x mb-2"></i>
        <p class="mb-0"><?php echo __('No outgoing loans recorded.'); ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>
