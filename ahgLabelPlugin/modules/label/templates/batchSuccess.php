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
$batchUrl = url_for(['module' => 'label', 'action' => 'batch']);
$base = rtrim((string) sfConfig::get('app_siteBaseUrl', ''), '/');
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
      <label class="form-label"><?php echo __('Object IDs'); ?></label>
      <input class="form-control" name="ids" value="<?php echo htmlspecialchars($idsRaw); ?>" placeholder="<?php echo __('e.g. 631, 642, 700'); ?>">
      <div class="form-text"><?php echo __('Comma/space separated. Overrides repository.'); ?></div>
    </div>
    <div class="col-md-4">
      <label class="form-label"><?php echo __('…or whole repository'); ?></label>
      <select class="form-select" name="repository_id"><option value=""><?php echo __('— none —'); ?></option>
        <?php foreach ($repositoryOptions as $rid => $rname): ?><option value="<?php echo (int) $rid; ?>"<?php echo $repositoryId === (int) $rid ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $rname); ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary"><i class="fas fa-eye me-1"></i><?php echo __('Generate'); ?></button>
    <?php if ($hasSelection && !empty($records)): ?>
      <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print me-1"></i><?php echo __('Print'); ?></button>
      <span class="ms-2 text-muted"><?php echo count($records); ?> <?php echo __('labels'); ?></span>
    <?php endif; ?>
    <a class="btn btn-link" href="<?php echo url_for(['module' => 'label', 'action' => 'templates']); ?>"><?php echo __('Templates'); ?></a>
  </div>
</div></form>

<?php if ($hasSelection && empty($records)): ?>
  <div class="alert alert-warning no-print"><?php echo __('No matching records found for that selection.'); ?></div>
<?php endif; ?>

<?php if (!empty($records) && $t): ?>
<style <?php echo $nonceAttr; ?>>
  @page { size: <?php echo $t->page_size; ?>; margin: <?php echo $margin; ?>mm; }
  .label-sheet { display: grid; grid-template-columns: repeat(<?php echo $cols; ?>, <?php echo $lw; ?>mm); gap: <?php echo $gutter; ?>mm; max-width: <?php echo $pageW; ?>mm; }
  .label-cell { width: <?php echo $lw; ?>mm; height: <?php echo $lh; ?>mm; border: 1px dashed #bbb; padding: 1.5mm; font-size: <?php echo $font; ?>pt; overflow: hidden; box-sizing: border-box; display: flex; flex-direction: column; }
  .label-cell .l-id { font-weight: bold; }
  .label-cell .l-title { flex: 1; overflow: hidden; }
  .label-cell .l-codes { display: flex; gap: 2mm; align-items: flex-end; }
  .label-cell .l-codes img { max-height: <?php echo max(8, (int) ($lh / 3)); ?>mm; }
  @media print { .no-print { display: none !important; } .label-cell { border: none; } body { background: #fff; } }
</style>
<div class="label-sheet">
  <?php foreach ($records as $r): ?>
    <?php
        $bcVal = lbl_barcode_value($r, $t);
        $qrData = ($t->qr_target === 'identifier') ? ($r['identifier'] ?? '') : ($base && !empty($r['slug']) ? $base . '/index.php/' . $r['slug'] : ($r['slug'] ?? ''));
    ?>
    <div class="label-cell">
      <?php if ($t->show_identifier && !empty($r['identifier'])): ?><div class="l-id"><?php echo htmlspecialchars((string) $r['identifier']); ?></div><?php endif; ?>
      <?php if ($t->show_title): ?><div class="l-title"><?php echo htmlspecialchars((string) ($r['title'] ?? $r['slug'] ?? '')); ?></div><?php endif; ?>
      <?php if ($t->show_repository && !empty($r['repository'])): ?><div class="l-repo text-muted"><?php echo htmlspecialchars((string) $r['repository']); ?></div><?php endif; ?>
      <div class="l-codes">
        <?php if ($t->show_barcode && $bcVal !== ''): ?><img alt="barcode" src="https://barcodeapi.org/api/128/<?php echo rawurlencode($bcVal); ?>"><?php endif; ?>
        <?php if ($t->show_qr && $qrData !== ''): ?><img alt="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo rawurlencode((string) $qrData); ?>"><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
