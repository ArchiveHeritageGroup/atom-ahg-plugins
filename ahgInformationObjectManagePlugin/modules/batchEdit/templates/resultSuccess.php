<?php decorate_with('layout_1col'); ?>
<?php
$results = $sf_data->getRaw('results');
$fieldLabels = $sf_data->getRaw('fieldLabels');
$count = ['updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'error' => 0];
foreach ($results as $r) {
    ++$count[$r['status']];
}
$statusLabel = ['updated' => __('Updated'), 'unchanged' => __('No change'), 'skipped' => __('Skipped'), 'error' => __('Error')];
?>

<?php slot('title'); ?>
  <h1><?php echo __('Batch edit - done'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <div class="alert <?php echo $count['error'] ? 'alert-warning' : 'alert-success'; ?>">
    <?php echo __('%1% updated, %2% unchanged, %3% skipped, %4% failed.', ['%1%' => $count['updated'], '%2%' => $count['unchanged'], '%3%' => $count['skipped'], '%4%' => $count['error']]); ?>
  </div>

  <table class="table table-bordered table-sm">
    <thead><tr><th><?php echo __('Description'); ?></th><th><?php echo __('Result'); ?></th><th><?php echo __('Fields'); ?></th></tr></thead>
    <tbody>
      <?php foreach ($results as $r) { ?>
        <tr class="<?php echo ['updated' => 'table-success', 'skipped' => 'table-warning', 'error' => 'table-danger', 'unchanged' => 'text-muted'][$r['status']]; ?>">
          <td><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $r['slug']]); ?>"><?php echo esc_entities($r['title'] ?: $r['slug']); ?></a></td>
          <td><?php echo $statusLabel[$r['status']]; ?><?php echo $r['message'] ? ': '.esc_entities(__($r['message'])) : ''; ?></td>
          <td><?php echo esc_entities(implode(', ', array_map(function ($c) use ($fieldLabels) { return $fieldLabels[$c['field']] ?? $c['field']; }, $r['changes']))); ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

  <ul class="actions mb-3 nav gap-2">
    <li><a class="btn atom-btn-outline-light" href="<?php echo url_for(['module' => 'clipboard', 'action' => 'view']); ?>"><?php echo __('Back to clipboard'); ?></a></li>
  </ul>
<?php end_slot(); ?>
