<?php
$form = $sf_data->getRaw('form') ?: [];
$peers = $sf_data->getRaw('peers') ?: [];
$v = fn ($k) => esc_entities((string) ($form[$k] ?? ''));
?>
<div class="container py-3" style="max-width: 860px">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-arrow-left-right me-2"></i><?php echo __('New loan request') ?></h1>
    <a href="<?php echo url_for(['module' => 'federation', 'action' => 'loans']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to loans') ?></a>
  </div>

  <?php if (empty($peers)): ?>
    <div class="alert alert-warning"><?php echo __('Add the other institution as a federation peer first.') ?> <a href="<?php echo url_for(['module' => 'federation', 'action' => 'addPeer']) ?>"><?php echo __('Add peer') ?></a></div>
  <?php endif ?>

  <form method="post" action="<?php echo url_for(['module' => 'federation', 'action' => 'loanNew']) ?>" class="card">
    <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label" for="direction"><?php echo __('Who borrows') ?></label>
          <select class="form-select" id="direction" name="direction">
            <option value="outgoing" <?php echo 'incoming' !== ($form['direction'] ?? '') ? 'selected' : '' ?>><?php echo __('We borrow from them (outgoing)') ?></option>
            <option value="incoming" <?php echo 'incoming' === ($form['direction'] ?? '') ? 'selected' : '' ?>><?php echo __('They borrow from us (incoming)') ?></option>
          </select>
        </div>
        <div class="col-md-7">
          <label class="form-label" for="peer_id"><?php echo __('Other institution') ?> <span class="text-danger">*</span></label>
          <select class="form-select" id="peer_id" name="peer_id" required>
            <option value=""><?php echo __('Choose a federation peer') ?></option>
            <?php foreach ($peers as $id => $name): ?>
              <option value="<?php echo (int) $id ?>" <?php echo (int) ($form['peer_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?php echo esc_entities($name) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label" for="item_title"><?php echo __('Item') ?></label>
          <input type="text" class="form-control" id="item_title" name="item_title" maxlength="1024" value="<?php echo $v('item_title') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="item_ref"><?php echo __('Record id here') ?></label>
          <input type="text" class="form-control" id="item_ref" name="item_ref" maxlength="255" value="<?php echo $v('item_ref') ?>">
          <div class="form-text"><?php echo __('Our record, or the harvested copy of theirs.') ?></div>
        </div>
        <div class="col-12">
          <label class="form-label" for="purpose"><?php echo __('Purpose') ?></label>
          <input type="text" class="form-control" id="purpose" name="purpose" maxlength="2048" value="<?php echo $v('purpose') ?>" placeholder="<?php echo __('Exhibition, research, conservation...') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="needed_from"><?php echo __('Needed from') ?></label>
          <input type="date" class="form-control" id="needed_from" name="needed_from" value="<?php echo $v('needed_from') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="needed_to"><?php echo __('Needed until') ?></label>
          <input type="date" class="form-control" id="needed_to" name="needed_to" value="<?php echo $v('needed_to') ?>">
        </div>
        <div class="col-12">
          <label class="form-label" for="notes"><?php echo __('Notes') ?></label>
          <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo $v('notes') ?></textarea>
        </div>
      </div>
    </div>
    <div class="card-footer text-end"><button type="submit" class="btn btn-primary"><?php echo __('Create loan request') ?></button></div>
  </form>
</div>
