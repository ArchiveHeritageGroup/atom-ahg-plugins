<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $isEdit ? __('Edit Log Entry') : __('New Log Entry'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Call & Issue Log'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorCallLog'])],
  ['label' => $isEdit ? __('Edit') : __('New Entry')],
]]); ?>

<?php $rawErrors = sfOutputEscaper::unescape($errors ?? []); ?>
<?php if (!empty($rawErrors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($rawErrors as $e): ?>
        <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php $e = $entry ? sfOutputEscaper::unescape($entry) : null; ?>
<?php $rawInstitutions = sfOutputEscaper::unescape($institutions ?? []); ?>

<div class="card">
  <div class="card-header fw-semibold">
    <i class="fas fa-phone-alt me-2"></i><?php echo $isEdit ? __('Edit Log Entry') : __('New Log Entry'); ?>
  </div>
  <div class="card-body">
    <form method="post" action="">

      <div class="row g-3 mb-3">
        <!-- Type & Direction -->
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Interaction Type'); ?> <span class="text-danger">*</span></label>
          <select name="interaction_type" class="form-select">
            <?php foreach (['call' => 'Phone Call', 'email' => 'Email', 'meeting' => 'Meeting', 'support_ticket' => 'Support Ticket', 'site_visit' => 'Site Visit', 'video_call' => 'Video Call', 'other' => 'Other'] as $val => $label): ?>
              <option value="<?php echo $val; ?>"<?php echo ($e->interaction_type ?? 'call') === $val ? ' selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Direction'); ?></label>
          <select name="direction" class="form-select">
            <option value="outbound"<?php echo ($e->direction ?? 'outbound') === 'outbound' ? ' selected' : ''; ?>><?php echo __('Outbound'); ?></option>
            <option value="inbound"<?php echo ($e->direction ?? '') === 'inbound' ? ' selected' : ''; ?>><?php echo __('Inbound'); ?></option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Institution'); ?></label>
          <select name="institution_id" class="form-select">
            <option value=""><?php echo __('— None —'); ?></option>
            <?php foreach ($rawInstitutions as $inst): ?>
              <option value="<?php echo (int) $inst->id; ?>"<?php echo ((int) ($e->institution_id ?? 0)) === (int) $inst->id ? ' selected' : ''; ?>><?php echo htmlspecialchars($inst->name, ENT_QUOTES, 'UTF-8'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Subject -->
      <div class="mb-3">
        <label class="form-label"><?php echo __('Subject'); ?> <span class="text-danger">*</span></label>
        <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($e->subject ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
      </div>

      <!-- Description -->
      <div class="mb-3">
        <label class="form-label"><?php echo __('Description'); ?></label>
        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($e->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="row g-3 mb-3">
        <!-- Status & Priority -->
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Status'); ?></label>
          <select name="status" class="form-select">
            <?php foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'escalated' => 'Escalated'] as $val => $label): ?>
              <option value="<?php echo $val; ?>"<?php echo ($e->status ?? 'open') === $val ? ' selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Priority'); ?></label>
          <select name="priority" class="form-select">
            <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $val => $label): ?>
              <option value="<?php echo $val; ?>"<?php echo ($e->priority ?? 'medium') === $val ? ' selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Duration (minutes)'); ?></label>
          <input type="number" name="duration_minutes" class="form-control" min="0" value="<?php echo (int) ($e->duration_minutes ?? 0); ?>">
        </div>
      </div>

      <hr>
      <h6 class="text-muted mb-3"><i class="fas fa-user me-1"></i><?php echo __('Contact Person'); ?></h6>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Name'); ?></label>
          <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($e->contact_name ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Email'); ?></label>
          <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($e->contact_email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Phone'); ?></label>
          <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($e->contact_phone ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <hr>
      <h6 class="text-muted mb-3"><i class="fas fa-clipboard-check me-1"></i><?php echo __('Resolution & Follow-up'); ?></h6>

      <!-- Resolution -->
      <div class="mb-3">
        <label class="form-label"><?php echo __('Resolution Notes'); ?></label>
        <textarea name="resolution" class="form-control" rows="3"><?php echo htmlspecialchars($e->resolution ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Follow-up Date'); ?></label>
          <input type="date" name="follow_up_date" class="form-control" value="<?php echo htmlspecialchars($e->follow_up_date ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-8">
          <label class="form-label"><?php echo __('Follow-up Notes'); ?></label>
          <input type="text" name="follow_up_notes" class="form-control" value="<?php echo htmlspecialchars($e->follow_up_notes ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLog']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $isEdit ? __('Update') : __('Create'); ?></button>
      </div>
    </form>
  </div>
</div>

<?php end_slot(); ?>
