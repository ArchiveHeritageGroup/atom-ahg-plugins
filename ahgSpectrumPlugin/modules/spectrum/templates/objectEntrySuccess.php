<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-sign-in-alt me-2"></i><?php echo __('Object Entry'); ?>
  <small class="text-muted ms-2"><?php echo esc_entities($resource->title ?? $resource->slug); ?></small>
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <div class="p-2 bg-white border mb-3">
    <h2 class="h6 mb-2"><?php echo __('Collections Procedures'); ?></h2>
    <div class="list-group rounded-0">
      <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>">
        <i class="fas fa-tasks me-2" aria-hidden="true"></i><?php echo __('Procedures'); ?>
      </a>
      <a class="list-group-item list-group-item-action" href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>">
        <i class="fas fa-arrow-left me-2" aria-hidden="true"></i><?php echo __('Back to record'); ?>
      </a>
    </div>
  </div>
<?php end_slot(); ?>

<?php
// The Spectrum procedure definition lists object_number, entry_date, entry_reason and
// depositor as the required fields for Object Entry. spectrum_object_entry has held
// columns for all of them since it was created, but nothing ever wrote the table, so
// the Object Entry report was permanently empty. This is that capture form.
$value = static function ($entry, string $field) {
    return null === $entry ? '' : (string) ($entry->{$field} ?? '');
};
?>

<form method="post" class="mb-4">
  <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">

  <div class="card mb-3">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Entry'); ?></h5></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="entry_number"><?php echo __('Entry number'); ?></label>
          <input type="text" class="form-control" id="entry_number" name="entry_number"
                 value="<?php echo esc_entities($value($entry, 'entry_number')); ?>"
                 placeholder="<?php echo __('Generated if left blank'); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="entry_date"><?php echo __('Entry date'); ?></label>
          <input type="date" class="form-control" id="entry_date" name="entry_date"
                 value="<?php echo esc_entities($value($entry, 'entry_date')); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="entry_method"><?php echo __('Entry method'); ?></label>
          <input type="text" class="form-control" id="entry_method" name="entry_method"
                 value="<?php echo esc_entities($value($entry, 'entry_method')); ?>">
        </div>
        <div class="col-12">
          <label class="form-label" for="entry_reason"><?php echo __('Reason for entry'); ?></label>
          <textarea class="form-control" id="entry_reason" name="entry_reason" rows="2"><?php echo esc_entities($value($entry, 'entry_reason')); ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Depositor'); ?></h5></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="depositor_name"><?php echo __('Depositor name'); ?></label>
          <input type="text" class="form-control" id="depositor_name" name="depositor_name"
                 value="<?php echo esc_entities($value($entry, 'depositor_name')); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="depositor_contact"><?php echo __('Depositor contact'); ?></label>
          <input type="text" class="form-control" id="depositor_contact" name="depositor_contact"
                 value="<?php echo esc_entities($value($entry, 'depositor_contact')); ?>">
        </div>
        <div class="col-12">
          <label class="form-label" for="depositor_address"><?php echo __('Depositor address'); ?></label>
          <textarea class="form-control" id="depositor_address" name="depositor_address" rows="2"><?php echo esc_entities($value($entry, 'depositor_address')); ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="current_owner"><?php echo __('Current owner'); ?></label>
          <input type="text" class="form-control" id="current_owner" name="current_owner"
                 value="<?php echo esc_entities($value($entry, 'current_owner')); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="owner_contact"><?php echo __('Owner contact'); ?></label>
          <input type="text" class="form-control" id="owner_contact" name="owner_contact"
                 value="<?php echo esc_entities($value($entry, 'owner_contact')); ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Receipt'); ?></h5></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="received_by"><?php echo __('Received by'); ?></label>
          <input type="text" class="form-control" id="received_by" name="received_by"
                 value="<?php echo esc_entities($value($entry, 'received_by')); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="return_date"><?php echo __('Return date'); ?></label>
          <input type="date" class="form-control" id="return_date" name="return_date"
                 value="<?php echo esc_entities($value($entry, 'return_date')); ?>">
        </div>
        <div class="col-12">
          <label class="form-label" for="packing_note"><?php echo __('Packing note'); ?></label>
          <textarea class="form-control" id="packing_note" name="packing_note" rows="2"><?php echo esc_entities($value($entry, 'packing_note')); ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label" for="entry_note"><?php echo __('Entry note'); ?></label>
          <textarea class="form-control" id="entry_note" name="entry_note" rows="2"><?php echo esc_entities($value($entry, 'entry_note')); ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-success">
    <i class="fas fa-check me-1"></i><?php echo $entry ? __('Update object entry') : __('Record object entry'); ?>
  </button>
</form>
