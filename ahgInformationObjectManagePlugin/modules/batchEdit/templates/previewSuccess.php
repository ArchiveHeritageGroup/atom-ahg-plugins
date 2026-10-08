<?php decorate_with('layout_1col'); ?>
<?php
$rows = $sf_data->getRaw('rows');
$fieldLabels = $sf_data->getRaw('fieldLabels');
$slugs = $sf_data->getRaw('slugs');
$values = $sf_data->getRaw('values');
$changing = $unchanged = $skipped = 0;
foreach ($rows as $row) {
    if ($row['skip']) {
        ++$skipped;
    } elseif ($row['changes']) {
        ++$changing;
    } else {
        ++$unchanged;
    }
}
?>

<?php slot('title'); ?>
  <h1><?php echo __('Batch edit - preview'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <p><?php echo __('%1% description(s) will change, %2% already have these values, %3% will be skipped. Nothing has been saved yet.', ['%1%' => $changing, '%2%' => $unchanged, '%3%' => $skipped]); ?></p>

  <table class="table table-bordered table-sm">
    <thead><tr><th><?php echo __('Description'); ?></th><th><?php echo __('Field'); ?></th><th><?php echo __('Before'); ?></th><th><?php echo __('After'); ?></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row) { ?>
        <?php $n = max(1, count($row['changes'])); ?>
        <?php $link = '<a href="'.url_for(['module' => 'informationobject', 'slug' => $row['slug']]).'">'.esc_entities($row['title'] ?: $row['slug']).'</a>'; ?>
        <?php if (!$row['changes']) { ?>
          <tr class="text-muted"><td><?php echo $link; ?></td><td colspan="3"><?php echo __('No change'); ?></td></tr>
        <?php } else { ?>
          <?php foreach ($row['changes'] as $i => $c) { ?>
            <tr<?php echo $row['skip'] ? ' class="table-danger"' : ''; ?>>
              <?php if (0 === $i) { ?>
                <td rowspan="<?php echo $n; ?>"><?php echo $link; ?><?php if ($row['skip']) { ?><br><strong><?php echo __('Skipped:'); ?></strong> <?php echo esc_entities(__($row['skip'])); ?><?php } ?></td>
              <?php } ?>
              <td><?php echo esc_entities($fieldLabels[$c['field']] ?? $c['field']); ?></td>
              <td><?php echo nl2br(esc_entities($c['before'])); ?></td>
              <td><?php echo nl2br(esc_entities($c['after'])); ?></td>
            </tr>
          <?php } ?>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>

  <form method="post" action="<?php echo url_for(['module' => 'batchEdit', 'action' => 'batch']); ?>" class="d-inline">
    <?php echo get_partial('batchEdit/carry', ['slugs' => $slugs, 'values' => $values]); ?>
    <ul class="actions mb-3 nav gap-2">
      <li><button class="btn atom-btn-outline-light" type="submit" name="step" value="form"><?php echo __('Back'); ?></button></li>
      <?php if ($changing) { ?>
        <li><button class="btn atom-btn-outline-success" type="submit" name="step" value="apply"><?php echo __('Apply to %1% description(s)', ['%1%' => $changing]); ?></button></li>
      <?php } ?>
    </ul>
  </form>
<?php end_slot(); ?>
