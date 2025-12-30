<?php slot('title') ?><?php echo __('Add Reminder') ?><?php end_slot() ?>
<form method="post">
<section class="card mb-4"><div class="card-header"><h2 class="h5 mb-0"><?php echo __('Add Reminder to') ?> <?php echo esc_entities($agreement['agreement_number']) ?></h2></div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label"><?php echo __('Type') ?> *</label><select name="reminder[reminder_type]" class="form-select" required><option value="expiry_warning">Expiry Warning</option><option value="review_due">Review Due</option><option value="donor_contact">Donor Contact</option><option value="anniversary">Anniversary</option><option value="custom">Custom</option></select></div>
<div class="col-md-6"><label class="form-label"><?php echo __('Priority') ?></label><select name="reminder[priority]" class="form-select"><option value="low">Low</option><option value="normal" selected>Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
<div class="col-12"><label class="form-label"><?php echo __('Title') ?> *</label><input type="text" name="reminder[title]" class="form-control" required></div>
<div class="col-12"><label class="form-label"><?php echo __('Description') ?></label><textarea name="reminder[description]" class="form-control" rows="2"></textarea></div>
<div class="col-md-4"><label class="form-label"><?php echo __('Reminder Date') ?> *</label><input type="date" name="reminder[reminder_date]" class="form-control" required></div>
<div class="col-md-4"><div class="form-check mt-4"><input type="checkbox" name="reminder[is_recurring]" value="1" class="form-check-input" id="recurring"><label class="form-check-label" for="recurring"><?php echo __('Recurring') ?></label></div></div>
<div class="col-md-4"><label class="form-label"><?php echo __('Pattern') ?></label><select name="reminder[recurrence_pattern]" class="form-select"><option value="">None</option><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="yearly">Yearly</option></select></div>
</div></div></section>
<div class="d-flex justify-content-between"><a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement['id']]) ?>" class="btn btn-secondary"><?php echo __('Cancel') ?></a><button type="submit" class="btn btn-primary"><?php echo __('Save') ?></button></div>
</form>
