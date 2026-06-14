<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1 class="h3"><i class="fas fa-tags me-2"></i><?php echo __('Label templates') ?></h1>
<?php end_slot() ?>
<?php $templates = $sf_data->getRaw('templates') ?: []; $listUrl = url_for(['module' => 'label', 'action' => 'templates']); ?>

<div class="d-flex justify-content-end mb-3">
  <a class="btn btn-outline-secondary btn-sm me-2" href="<?php echo url_for(['module' => 'label', 'action' => 'batch']); ?>"><i class="fas fa-print me-1"></i><?php echo __('Batch print'); ?></a>
  <a class="btn btn-primary btn-sm" href="<?php echo url_for(['module' => 'label', 'action' => 'templateEdit']); ?>"><i class="fas fa-plus me-1"></i><?php echo __('New template'); ?></a>
</div>

<div class="card"><div class="card-body p-0">
  <table class="table table-hover mb-0 align-middle">
    <thead><tr><th><?php echo __('Name'); ?></th><th><?php echo __('Page'); ?></th><th><?php echo __('Grid'); ?></th><th><?php echo __('Label (mm)'); ?></th><th><?php echo __('Shows'); ?></th><th></th></tr></thead>
    <tbody>
    <?php if (empty($templates)): ?>
      <tr><td colspan="6" class="text-muted p-3"><?php echo __('No templates yet.'); ?></td></tr>
    <?php else: foreach ($templates as $t): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars((string) $t->name); ?></strong><?php if ($t->is_default): ?> <span class="badge bg-success"><?php echo __('default'); ?></span><?php endif; ?></td>
        <td><?php echo htmlspecialchars((string) $t->page_size); ?></td>
        <td><?php echo (int) $t->columns; ?>&times;<?php echo (int) $t->rows; ?></td>
        <td><?php echo rtrim(rtrim((string) $t->label_width_mm, '0'), '.'); ?>&times;<?php echo rtrim(rtrim((string) $t->label_height_mm, '0'), '.'); ?></td>
        <td class="small">
          <?php $bits = []; if ($t->show_identifier) $bits[] = __('ID'); if ($t->show_title) $bits[] = __('title'); if ($t->show_barcode) $bits[] = __('barcode') . ' (' . $t->barcode_source . ')'; if ($t->show_qr) $bits[] = 'QR'; echo htmlspecialchars(implode(', ', $bits)); ?>
        </td>
        <td class="text-end">
          <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'label', 'action' => 'templateEdit', 'id' => $t->id]); ?>"><i class="fas fa-pen"></i></a>
          <form method="post" action="<?php echo $listUrl; ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this template?'); ?>');">
            <input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $t->id; ?>">
            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div></div>
