<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?>
  <h1 class="h3"><i class="fas fa-file-import me-2"></i><?php echo __('ONIX ingestion'); ?></h1>
<?php end_slot(); ?>
<?php
$error = $sf_data->getRaw('error');
$ingest = $sf_data->getRaw('ingest');
$lines = $sf_data->getRaw('lines') ?: [];
$ingests = $sf_data->getRaw('ingests') ?: [];
$statusTone = ['valid' => 'success', 'duplicate' => 'warning', 'invalid' => 'danger', 'imported' => 'primary', 'parsed' => 'secondary', 'skipped' => 'secondary'];
?>

<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div class="card mb-4"><div class="card-body">
  <form method="post" enctype="multipart/form-data" action="<?php echo url_for(['module' => 'library', 'action' => 'onix']); ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><?php echo __('ONIX file (.xml)'); ?></label>
        <input type="file" class="form-control" name="onix_file" accept=".xml,application/xml,text/xml">
      </div>
      <div class="col-md-6">
        <label class="form-label"><?php echo __('…or paste ONIX XML'); ?></label>
        <textarea class="form-control font-monospace" name="onix_xml" rows="3" placeholder="&lt;ONIXMessage …&gt;"></textarea>
      </div>
    </div>
    <button class="btn btn-primary mt-3"><i class="fas fa-upload me-1"></i><?php echo __('Parse & validate'); ?></button>
  </form>
</div></div>

<?php if ($ingest): ?>
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><?php echo __('Review'); ?>: <?php echo esc_entities($ingest->filename ?? ('#' . $ingest->id)); ?>
        <span class="text-muted small">ONIX <?php echo esc_entities($ingest->onix_version ?? '?'); ?></span></h5>
      <span class="d-flex align-items-center gap-2">
        <span class="badge bg-success"><?php echo (int) $ingest->valid_count; ?> <?php echo __('valid'); ?></span>
        <span class="badge bg-danger"><?php echo (int) $ingest->error_count; ?> <?php echo __('issues'); ?></span>
        <span class="badge bg-secondary"><?php echo (int) $ingest->record_count; ?> <?php echo __('total'); ?></span>
        <?php if ($ingest->status === 'committed'): ?>
          <span class="badge bg-primary"><?php echo __('committed'); ?> → <?php echo __('order'); ?> #<?php echo (int) $ingest->order_id; ?></span>
        <?php elseif ((int) $ingest->valid_count > 0): ?>
          <form method="post" action="<?php echo url_for(['module' => 'library', 'action' => 'onix']); ?>" class="d-inline"
                onsubmit="return confirm('<?php echo __('Create an acquisitions order from the valid lines?'); ?>');">
            <input type="hidden" name="form_action" value="commit">
            <input type="hidden" name="id" value="<?php echo (int) $ingest->id; ?>">
            <button class="btn btn-sm btn-primary"><i class="fas fa-check me-1"></i><?php echo __('Commit to acquisitions'); ?></button>
          </form>
        <?php endif; ?>
      </span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 align-middle">
        <thead><tr><th><?php echo __('Status'); ?></th><th><?php echo __('ISBN/ISSN'); ?></th><th><?php echo __('Title'); ?></th><th><?php echo __('Author'); ?></th><th><?php echo __('Publisher'); ?></th><th><?php echo __('Issue'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($lines)): ?>
          <tr><td colspan="6" class="text-muted p-3"><?php echo __('No lines.'); ?></td></tr>
        <?php else: foreach ($lines as $l): ?>
          <tr>
            <td><span class="badge bg-<?php echo $statusTone[$l->status] ?? 'secondary'; ?>"><?php echo esc_entities($l->status); ?></span></td>
            <td><?php echo esc_entities($l->isbn ?: $l->issn ?: '-'); ?></td>
            <td><?php echo esc_entities($l->title ?? '-'); ?><?php if ($l->subtitle): ?> <span class="text-muted">: <?php echo esc_entities($l->subtitle); ?></span><?php endif; ?></td>
            <td><?php echo esc_entities($l->author ?? '-'); ?></td>
            <td><?php echo esc_entities($l->publisher ?? '-'); ?> <?php echo esc_entities($l->pub_year ?? ''); ?></td>
            <td class="small text-danger"><?php echo esc_entities($l->error ?? ''); ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><?php echo __('Recent ingests'); ?></h5></div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <thead><tr><th><?php echo __('File'); ?></th><th><?php echo __('Version'); ?></th><th><?php echo __('Records'); ?></th><th><?php echo __('Valid'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('When'); ?></th></tr></thead>
      <tbody>
      <?php if (empty($ingests)): ?>
        <tr><td colspan="6" class="text-muted p-3"><?php echo __('No ingests yet.'); ?></td></tr>
      <?php else: foreach ($ingests as $ig): ?>
        <tr>
          <td><a href="<?php echo url_for(['module' => 'library', 'action' => 'onix', 'id' => $ig->id]); ?>"><?php echo esc_entities($ig->filename ?? ('#' . $ig->id)); ?></a></td>
          <td><?php echo esc_entities($ig->onix_version ?? '-'); ?></td>
          <td><?php echo (int) $ig->record_count; ?></td>
          <td><?php echo (int) $ig->valid_count; ?></td>
          <td><?php echo esc_entities($ig->status); ?></td>
          <td class="small text-muted"><?php echo esc_entities($ig->created_at ?? ''); ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
