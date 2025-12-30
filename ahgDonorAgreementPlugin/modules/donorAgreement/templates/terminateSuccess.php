<?php slot('title') ?><?php echo __('Terminate Agreement') ?><?php end_slot() ?>
<form method="post">
<section class="card mb-4"><div class="card-header"><h2 class="h5 mb-0"><?php echo __('Terminate') ?> <?php echo esc_entities($agreement['agreement_number']) ?></h2></div><div class="card-body"><div class="row g-3">
<div class="col-md-4"><label class="form-label"><?php echo __('Termination Date') ?></label><input type="date" name="termination_date" class="form-control" value="<?php echo date('Y-m-d') ?>"></div>
<div class="col-12"><label class="form-label"><?php echo __('Reason') ?> *</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
</div></div></section>
<div class="d-flex justify-content-between"><a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement['id']]) ?>" class="btn btn-secondary"><?php echo __('Cancel') ?></a><button type="submit" class="btn btn-danger"><?php echo __('Terminate Agreement') ?></button></div>
</form>
