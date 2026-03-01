<?php decorate_with('layout_1col'); ?>

<?php
  $rawPairs = $sf_data->getRaw('pairs');
  $pairs = is_array($rawPairs) ? $rawPairs : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-clone me-2"></i><?php echo __('Dedup Scan Results'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dedup'); ?>"><?php echo __('Deduplication'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Scan Results'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card">
    <div class="card-header d-flex justify-content-between">
      <span><?php echo __('%1% potential duplicate pair(s)', ['%1%' => count($pairs)]); ?></span>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Actor A'); ?></th>
            <th><?php echo __('Actor B'); ?></th>
            <th class="text-center"><?php echo __('Score'); ?></th>
            <th><?php echo __('Match'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pairs)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4"><?php echo __('No duplicates found above the threshold.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($pairs as $pair): ?>
              <?php
                $matchColors = ['exact' => 'danger', 'strong' => 'warning', 'possible' => 'info', 'weak' => 'secondary'];
                $color = $matchColors[$pair['match_type']] ?? 'secondary';
              ?>
              <tr>
                <td><?php echo htmlspecialchars($pair['actor_a_name']); ?></td>
                <td><?php echo htmlspecialchars($pair['actor_b_name']); ?></td>
                <td class="text-center">
                  <strong><?php echo number_format($pair['score'] * 100, 1); ?>%</strong>
                </td>
                <td>
                  <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($pair['match_type']); ?></span>
                </td>
                <td>
                  <a href="<?php echo url_for('@ahg_authority_dedup_compare?id=' . $pair['actor_a_id'] . '&secondary_id=' . $pair['actor_b_id']); ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-columns me-1"></i><?php echo __('Compare'); ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php end_slot(); ?>
