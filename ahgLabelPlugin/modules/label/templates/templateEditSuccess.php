<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1 class="h3"><i class="fas fa-tags me-2"></i><?php echo $sf_data->getRaw('template') ? __('Edit label template') : __('New label template') ?></h1>
<?php end_slot() ?>
<?php
$t = $sf_data->getRaw('template');
$v = function ($f, $d = '') use ($t) { return htmlspecialchars((string) ($t->$f ?? $d)); };
$ck = function ($f, $default = false) use ($t) { $val = $t ? $t->$f : ($default ? 1 : 0); return $val ? ' checked' : ''; };
$sel = function ($f, $opt) use ($t) { return ((string) ($t->$f ?? '') === (string) $opt) ? ' selected' : ''; };
$postUrl = url_for(['module' => 'label', 'action' => 'templateEdit']);
?>
<form method="post" action="<?php echo $postUrl; ?>">
  <?php if ($t): ?><input type="hidden" name="id" value="<?php echo (int) $t->id; ?>"><?php endif; ?>
  <div class="card mb-3"><div class="card-body">
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Name'); ?> *</label><input class="form-control" name="name" required value="<?php echo $v('name'); ?>"></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Page size'); ?></label>
        <select class="form-select" name="page_size"><option value="A4"<?php echo $sel('page_size', 'A4'); ?>>A4</option><option value="Letter"<?php echo $sel('page_size', 'Letter'); ?>>Letter</option></select></div>
      <div class="col-md-3 mb-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="def"<?php echo $ck('is_default'); ?>><label class="form-check-label" for="def"><?php echo __('Default'); ?></label></div></div>
    </div>
    <div class="row">
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Columns'); ?></label><input type="number" min="1" max="10" class="form-control" name="columns" value="<?php echo $v('columns', '3'); ?>"></div>
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Rows'); ?></label><input type="number" min="1" max="20" class="form-control" name="rows" value="<?php echo $v('rows', '8'); ?>"></div>
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Label W (mm)'); ?></label><input type="number" step="0.1" class="form-control" name="label_width_mm" value="<?php echo $v('label_width_mm', '63.5'); ?>"></div>
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Label H (mm)'); ?></label><input type="number" step="0.1" class="form-control" name="label_height_mm" value="<?php echo $v('label_height_mm', '33.9'); ?>"></div>
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Margin (mm)'); ?></label><input type="number" step="0.1" class="form-control" name="margin_mm" value="<?php echo $v('margin_mm', '10'); ?>"></div>
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Font (pt)'); ?></label><input type="number" min="5" max="24" class="form-control" name="font_size_pt" value="<?php echo $v('font_size_pt', '9'); ?>"></div>
    </div>
  </div></div>

  <div class="card mb-3"><div class="card-body">
    <h6><?php echo __('Content'); ?></h6>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_identifier" value="1" id="si"<?php echo $ck('show_identifier', true); ?>><label class="form-check-label" for="si"><?php echo __('Show identifier'); ?></label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_title" value="1" id="st"<?php echo $ck('show_title', true); ?>><label class="form-check-label" for="st"><?php echo __('Show title'); ?></label></div>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="show_repository" value="1" id="sr"<?php echo $ck('show_repository'); ?>><label class="form-check-label" for="sr"><?php echo __('Show repository'); ?></label></div>
    <div class="row mt-2">
      <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="show_barcode" value="1" id="sb"<?php echo $ck('show_barcode', true); ?>><label class="form-check-label" for="sb"><?php echo __('Show barcode'); ?></label></div></div>
      <div class="col-md-4"><label class="form-label"><?php echo __('Barcode source'); ?></label>
        <select class="form-select form-select-sm" name="barcode_source">
          <?php foreach (['identifier', 'accession', 'call_number', 'isbn'] as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('barcode_source', $o); ?>><?php echo __(ucwords(str_replace('_', ' ', $o))); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="show_qr" value="1" id="sq"<?php echo $ck('show_qr'); ?>><label class="form-check-label" for="sq"><?php echo __('Show QR code'); ?></label></div></div>
      <div class="col-md-4"><label class="form-label"><?php echo __('QR target'); ?></label>
        <select class="form-select form-select-sm" name="qr_target"><option value="url"<?php echo $sel('qr_target', 'url'); ?>><?php echo __('Record URL'); ?></option><option value="identifier"<?php echo $sel('qr_target', 'identifier'); ?>><?php echo __('Identifier'); ?></option></select></div>
    </div>
  </div></div>

  <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
  <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'label', 'action' => 'templates']); ?>"><?php echo __('Cancel'); ?></a>
</form>
