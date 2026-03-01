<?php decorate_with('layout_1col'); ?>

<?php
  $rawComparison = $sf_data->getRaw('comparison');
  $comp = is_array($rawComparison) ? $rawComparison : (array) $rawComparison;

  $primary   = isset($comp['primary']) ? (object) $comp['primary'] : null;
  $secondary = isset($comp['secondary']) ? (object) $comp['secondary'] : null;
  $fields    = $comp['comparison'] ?? [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-columns me-2"></i><?php echo __('Compare Authority Records'); ?></h1>
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
      <li class="breadcrumb-item active"><?php echo __('Compare'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!$primary || !$secondary): ?>
    <div class="alert alert-warning"><?php echo __('Could not load both records for comparison.'); ?></div>
  <?php else: ?>

    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-columns me-1"></i><?php echo __('Field Comparison'); ?>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th style="width:20%"><?php echo __('Field'); ?></th>
              <th style="width:38%"><?php echo __('Primary: %1%', ['%1%' => htmlspecialchars($primary->authorized_form_of_name ?? '')]); ?></th>
              <th style="width:38%"><?php echo __('Secondary: %1%', ['%1%' => htmlspecialchars($secondary->authorized_form_of_name ?? '')]); ?></th>
              <th style="width:4%"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fields as $fieldName => $info): ?>
              <?php $info = (array) $info; ?>
              <tr>
                <td><strong><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($fieldName))); ?></strong></td>
                <td>
                  <small><?php echo nl2br(htmlspecialchars(mb_substr($info['primary'] ?? '', 0, 300))); ?></small>
                </td>
                <td>
                  <small><?php echo nl2br(htmlspecialchars(mb_substr($info['secondary'] ?? '', 0, 300))); ?></small>
                </td>
                <td>
                  <?php if ($info['match'] ?? false): ?>
                    <i class="fas fa-check text-success"></i>
                  <?php else: ?>
                    <i class="fas fa-times text-danger"></i>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Counts -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body py-2">
            <small class="text-muted"><?php echo __('Primary Relations'); ?></small>
            <h5 class="mb-0"><?php echo $comp['primary_relations'] ?? 0; ?></h5>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body py-2">
            <small class="text-muted"><?php echo __('Secondary Relations'); ?></small>
            <h5 class="mb-0"><?php echo $comp['secondary_relations'] ?? 0; ?></h5>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body py-2">
            <small class="text-muted"><?php echo __('Primary Resources'); ?></small>
            <h5 class="mb-0"><?php echo $comp['primary_resources'] ?? 0; ?></h5>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center">
          <div class="card-body py-2">
            <small class="text-muted"><?php echo __('Secondary Resources'); ?></small>
            <h5 class="mb-0"><?php echo $comp['secondary_resources'] ?? 0; ?></h5>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2">
      <a href="<?php echo url_for('@ahg_authority_merge?id=' . ($primary->id ?? 0)); ?>" class="btn btn-warning">
        <i class="fas fa-compress-arrows-alt me-1"></i><?php echo __('Merge into Primary'); ?>
      </a>
      <a href="<?php echo url_for('@ahg_authority_dedup'); ?>" class="btn btn-outline-secondary">
        <?php echo __('Back to Dedup'); ?>
      </a>
    </div>

  <?php endif; ?>

<?php end_slot(); ?>
