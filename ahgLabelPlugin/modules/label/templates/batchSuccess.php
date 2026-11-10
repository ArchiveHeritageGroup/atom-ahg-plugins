<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1 class="h3 no-print"><i class="fas fa-print me-2"></i><?php echo __('Batch label printing') ?></h1>
<?php end_slot() ?>
<?php
$templates = $sf_data->getRaw('templates') ?: [];
$repositoryOptions = $sf_data->getRaw('repositoryOptions') ?: [];
$t = $sf_data->getRaw('template');
$records = $sf_data->getRaw('records') ?: [];
$hasSelection = $sf_data->getRaw('hasSelection');
$idsRaw = (string) $sf_data->getRaw('idsRaw');
$repositoryId = (int) $sf_data->getRaw('repositoryId');
$withChildren = (bool) $sf_data->getRaw('withChildren');
$batchUrl = url_for(['module' => 'label', 'action' => 'batch']);
$base = rtrim((string) sfConfig::get('app_siteBaseUrl', ''), '/') ?: sfContext::getInstance()->getRequest()->getUriPrefix();
$perSheet = $t ? max(1, (int) $t->columns * (int) $t->rows) : 24;
$nonce = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';

// page dimensions in mm
$pageW = ($t && $t->page_size === 'Letter') ? 216 : 210;
$cols = $t ? max(1, (int) $t->columns) : 3;
$lw = $t ? (float) $t->label_width_mm : 63.5;
$lh = $t ? (float) $t->label_height_mm : 33.9;
$gutter = $t ? (float) $t->gutter_mm : 2.5;
$margin = $t ? (float) $t->margin_mm : 10;
$font = $t ? (int) $t->font_size_pt : 9;

function lbl_barcode_value(array $r, $t): string
{
    $src = $t ? $t->barcode_source : 'identifier';
    return (string) ($r[$src] ?? $r['identifier'] ?? '');
}
?>

<form method="get" action="<?php echo $batchUrl; ?>" class="card mb-4 no-print"><div class="card-body">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label"><?php echo __('Template'); ?></label>
      <select class="form-select" name="template_id">
        <?php foreach ($templates as $tp): ?><option value="<?php echo (int) $tp->id; ?>"<?php echo ($t && (int) $t->id === (int) $tp->id) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $tp->name); ?></option><?php endforeach; ?>
      </select>
      <?php if (empty($templates)): ?><div class="form-text text-danger"><?php echo __('Create a template first.'); ?> <a href="<?php echo url_for(['module' => 'label', 'action' => 'templateEdit']); ?>"><?php echo __('New template'); ?></a></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="lbl-ids"><?php echo __('Records'); ?></label>
      <input class="form-control" id="lbl-ids" name="ids" value="<?php echo htmlspecialchars($idsRaw); ?>" placeholder="<?php echo __('e.g. HRD/1955, farm-diary-volume-1-1934'); ?>">
      <div class="form-text"><?php echo __('Identifiers, slugs or ids, separated by commas or spaces. Overrides repository.'); ?></div>
      <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="children" value="1" id="lbl-children"<?php echo $withChildren ? ' checked' : ''; ?>><label class="form-check-label" for="lbl-children"><?php echo __('Include the records below each one'); ?></label></div>
    </div>
    <div class="col-md-4">
      <label class="form-label"><?php echo __('...or a whole repository'); ?></label>
      <select class="form-select" name="repository_id"><option value=""><?php echo __('- none -'); ?></option>
        <?php foreach ($repositoryOptions as $rid => $rname): ?><option value="<?php echo (int) $rid; ?>"<?php echo $repositoryId === (int) $rid ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $rname); ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary"><i class="fas fa-eye me-1"></i><?php echo __('Generate'); ?></button>
    <?php if ($hasSelection && !empty($records)): ?>
      <button type="button" class="btn btn-success" data-ahg-call="printSheets"><i class="fas fa-print me-1"></i><?php echo __('Print'); ?></button>
      <span class="ms-2 text-muted"><?php echo count($records); ?> <?php echo __('labels'); ?></span>
    <?php endif; ?>
    <a class="btn btn-link" href="<?php echo url_for(['module' => 'label', 'action' => 'templates']); ?>"><?php echo __('Templates'); ?></a>
  </div>
</div></form>

<?php if ($hasSelection && empty($records)): ?>
  <div class="alert alert-warning no-print"><?php echo __('No matching records found for that selection.'); ?></div>
<?php endif; ?>

<?php if (!empty($records) && $t): ?>
<style id="label-sheet-css" <?php echo $nonceAttr; ?>>
  @page { size: <?php echo $t->page_size; ?>; margin: <?php echo $margin; ?>mm; }
  .label-sheet { display: grid; grid-template-columns: repeat(<?php echo $cols; ?>, <?php echo $lw; ?>mm); gap: <?php echo $gutter; ?>mm; max-width: <?php echo $pageW; ?>mm; }
  .label-cell { width: <?php echo $lw; ?>mm; height: <?php echo $lh; ?>mm; border: 1px dashed #bbb; padding: 1.5mm; font-size: <?php echo $font; ?>pt; overflow: hidden; box-sizing: border-box; display: flex; flex-direction: column; }
  .label-cell .l-id { font-weight: bold; }
  .label-cell .l-title { flex: 1; overflow: hidden; }
  .label-cell .l-codes { display: flex; gap: 2mm; align-items: flex-end; }
  .label-cell .l-codes img { max-height: <?php echo max(8, (int) ($lh / 3)); ?>mm; }
  .label-sheet + .label-sheet { margin-top: 6mm; }
  /* Only the sheets print. Hiding .no-print left the site header, banner and
     floating buttons on the page, and labels ran on without a break, so a second
     sheet started part-way down the label stock. */
  @media print {
    body * { visibility: hidden !important; }
    .label-sheet, .label-sheet * { visibility: visible !important; }
    .label-print-area { position: absolute; left: 0; top: 0; }
    .label-sheet { break-after: page; margin: 0 !important; }
    .label-sheet:last-child { break-after: auto; }
    .label-cell { border: none; }
    body { background: #fff !important; }
  }
</style>
<div class="label-print-area">
<?php foreach (array_chunk($records, $perSheet) as $sheet): ?>
<div class="label-sheet">
  <?php foreach ($sheet as $r): ?>
    <?php
        $bcVal = lbl_barcode_value($r, $t);
        $qrData = ($t->qr_target === 'identifier') ? ($r['identifier'] ?? '') : (!empty($r['slug']) ? $base . '/' . $r['slug'] : '');
    ?>
    <div class="label-cell">
      <?php if ($t->show_identifier && !empty($r['identifier'])): ?><div class="l-id"><?php echo htmlspecialchars((string) $r['identifier']); ?></div><?php endif; ?>
      <?php if ($t->show_title): ?><div class="l-title"><?php echo htmlspecialchars((string) ($r['title'] ?? $r['slug'] ?? '')); ?></div><?php endif; ?>
      <?php if ($t->show_repository && !empty($r['repository'])): ?><div class="l-repo text-muted"><?php echo htmlspecialchars((string) $r['repository']); ?></div><?php endif; ?>
      <div class="l-codes">
<?php // Generated locally rather than fetched from barcodeapi.org / api.qrserver.com (#260). ?>
        <?php if ($t->show_barcode && $bcVal !== ''): ?><img alt="barcode" src="<?php echo \AtomExtensions\Services\BarcodeService::barcodeDataUri($bcVal); ?>"><?php endif; ?>
        <?php if ($t->show_qr && $qrData !== ''): ?><img alt="qr" src="<?php echo \AtomExtensions\Services\BarcodeService::qrDataUri((string) $qrData); ?>"><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
<script <?php echo $nonceAttr; ?>>
// Prints the sheets alone, from a hidden frame. Printing the page itself, even with
// everything else hidden, kept the page's height and fed out a blank sheet.
function printSheets() {
    var css = document.getElementById('label-sheet-css').textContent
        + 'body{margin:0;font-family:Arial,sans-serif;color:#000}.label-cell{border:none}.label-sheet{break-after:page;margin:0}.label-sheet:last-child{break-after:auto}.text-muted{color:#555}';
    var f = document.createElement('iframe');
    f.setAttribute('aria-hidden', 'true');
    f.style.position = 'fixed'; f.style.width = '0'; f.style.height = '0'; f.style.border = '0';
    document.body.appendChild(f);
    var d = f.contentWindow.document;
    d.open();
    d.write('<!doctype html><html><head><meta charset="utf-8"><title></title><style nonce="<?php echo preg_replace('/^nonce=/', '', (string) $nonce); ?>">' + css + '</style></head><body>' + document.querySelector('.label-print-area').innerHTML + '</body></html>');
    d.close();
    setTimeout(function () { f.contentWindow.focus(); f.contentWindow.print(); setTimeout(function () { f.remove(); }, 1000); }, 300);
}
</script>
<?php endif; ?>
