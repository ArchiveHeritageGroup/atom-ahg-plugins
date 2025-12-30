<?php slot('title') ?><?php echo __('Upload Document') ?><?php end_slot() ?>
<form method="post" enctype="multipart/form-data">
<section class="card mb-4"><div class="card-header"><h2 class="h5 mb-0"><?php echo __('Upload Document to') ?> <?php echo esc_entities($agreement['agreement_number']) ?></h2></div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label"><?php echo __('Document Type') ?> *</label><select name="document[document_type]" class="form-select" required><option value="">Select...</option><option value="signed_agreement">Signed Agreement</option><option value="draft">Draft</option><option value="amendment">Amendment</option><option value="correspondence">Correspondence</option><option value="inventory">Inventory</option><option value="deed_of_gift">Deed of Gift</option><option value="valuation">Valuation</option><option value="other">Other</option></select></div>
<div class="col-md-6"><label class="form-label"><?php echo __('Title') ?></label><input type="text" name="document[title]" class="form-control"></div>
<div class="col-12"><label class="form-label"><?php echo __('Description') ?></label><textarea name="document[description]" class="form-control" rows="2"></textarea></div>
<div class="col-md-6"><label class="form-label"><?php echo __('Document Date') ?></label><input type="date" name="document[document_date]" class="form-control"></div>
<div class="col-md-6"><label class="form-label"><?php echo __('File') ?> *</label><input type="file" name="document[file]" class="form-control" required></div>
<div class="col-md-3"><div class="form-check"><input type="checkbox" name="document[is_signed]" value="1" class="form-check-input" id="is_signed"><label class="form-check-label" for="is_signed"><?php echo __('Signed') ?></label></div></div>
<div class="col-md-3"><label class="form-label"><?php echo __('Signature Date') ?></label><input type="date" name="document[signature_date]" class="form-control"></div>
<div class="col-md-3"><div class="form-check"><input type="checkbox" name="document[is_confidential]" value="1" class="form-check-input" id="is_conf"><label class="form-check-label" for="is_conf"><?php echo __('Confidential') ?></label></div></div>
</div></div></section>
<div class="d-flex justify-content-between"><a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement['id']]) ?>" class="btn btn-secondary"><?php echo __('Cancel') ?></a><button type="submit" class="btn btn-primary"><?php echo __('Upload') ?></button></div>
</form>
