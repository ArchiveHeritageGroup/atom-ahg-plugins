<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-clipboard-check me-2"></i><?php echo __('ODI Metadata Quality Scorecard'); ?></h1>
<?php end_slot(); ?>

<?php $sc = $sf_data->getRaw('scorecard'); ?>

<p class="text-muted"><?php echo __('NISO Open Discovery Initiative metadata completeness across %1% published library items.', ['%1%' => $sc['total']]); ?></p>

<?php if ($sc['total'] === 0): ?>
  <div class="alert alert-info"><?php echo __('No published library items to score.'); ?></div>
<?php else: ?>
  <div class="card mb-4">
    <div class="card-body text-center">
      <div class="display-4 mb-0 <?php echo $sc['score'] >= 80 ? 'text-success' : ($sc['score'] >= 50 ? 'text-warning' : 'text-danger'); ?>"><?php echo $sc['score']; ?>%</div>
      <div class="text-muted"><?php echo __('Overall completeness score'); ?></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-light"><h5 class="mb-0"><?php echo __('Field fill rates'); ?></h5></div>
    <div class="table-responsive">
      <table class="table table-striped mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Field'); ?></th><th class="text-end"><?php echo __('Filled'); ?></th><th style="width:40%"><?php echo __('Coverage'); ?></th></tr></thead>
        <tbody>
          <?php foreach ($sc['fields'] as $f): ?>
            <tr>
              <td><?php echo esc_entities($f['label']); ?></td>
              <td class="text-end"><?php echo (int) $f['filled']; ?> / <?php echo (int) $sc['total']; ?></td>
              <td>
                <div class="progress" role="progressbar" aria-valuenow="<?php echo $f['pct']; ?>" aria-valuemin="0" aria-valuemax="100">
                  <div class="progress-bar <?php echo $f['pct'] >= 80 ? 'bg-success' : ($f['pct'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>" style="width: <?php echo $f['pct']; ?>%"><?php echo $f['pct']; ?>%</div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
