<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-book me-2"></i><?php echo __('Serials Bindery'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header bg-light"><h5 class="mb-0"><?php echo __('Send issues to bindery'); ?></h5></div>
  <div class="card-body">
    <?php if (empty($sf_data->getRaw('bindable'))): ?>
      <div class="alert alert-info mb-0"><?php echo __('No received issues awaiting binding.'); ?></div>
    <?php else: ?>
      <form method="post" action="<?php echo url_for(['module' => 'serial', 'action' => 'bindery']); ?>">
        <input type="hidden" name="op" value="send">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead class="table-light"><tr><th></th><th><?php echo __('Vol'); ?></th><th><?php echo __('Issue'); ?></th><th><?php echo __('Date'); ?></th></tr></thead>
            <tbody>
              <?php foreach ($sf_data->getRaw('bindable') as $i): ?>
                <tr>
                  <td><input type="checkbox" name="issue_ids[]" value="<?php echo (int) $i->id; ?>"></td>
                  <td><?php echo esc_entities($i->volume ?? ''); ?></td>
                  <td><?php echo esc_entities($i->issue_number ?? ''); ?></td>
                  <td><?php echo esc_entities($i->expected_date ?? $i->issue_date ?? ''); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-md-4"><label class="form-label"><?php echo __('Bindery vendor id (optional)'); ?></label><input type="number" name="vendor_id" class="form-control" min="1"></div>
          <div class="col-md-5"><label class="form-label"><?php echo __('Notes'); ?></label><input type="text" name="notes" class="form-control"></div>
          <div class="col-md-3"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send to bindery'); ?></button></div>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header bg-light"><h5 class="mb-0"><?php echo __('Bindery batches'); ?></h5></div>
  <?php if (empty($sf_data->getRaw('batches'))): ?>
    <div class="card-body"><div class="alert alert-info mb-0"><?php echo __('No bindery batches yet.'); ?></div></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Batch'); ?></th><th><?php echo __('Items'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Sent'); ?></th><th><?php echo __('Returned'); ?></th><th class="text-end"><?php echo __('Action'); ?></th></tr></thead>
        <tbody>
          <?php foreach ($sf_data->getRaw('batches') as $b): ?>
            <tr>
              <td><code><?php echo esc_entities($b->batch_number); ?></code></td>
              <td><?php echo (int) $b->item_count; ?></td>
              <td><span class="badge bg-<?php echo $b->status === 'returned' ? 'success' : ($b->status === 'cancelled' ? 'secondary' : 'warning text-dark'); ?>"><?php echo esc_entities($b->status); ?></span></td>
              <td><?php echo esc_entities($b->sent_date ?? ''); ?></td>
              <td><?php echo esc_entities($b->returned_date ?? ''); ?></td>
              <td class="text-end">
                <?php if ($b->status === 'sent'): ?>
                  <form method="post" action="<?php echo url_for(['module' => 'serial', 'action' => 'bindery']); ?>" class="d-inline">
                    <input type="hidden" name="op" value="receive"><input type="hidden" name="batch_id" value="<?php echo (int) $b->id; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-success"><?php echo __('Receive'); ?></button>
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
