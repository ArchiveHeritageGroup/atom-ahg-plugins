<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><i class="fa fa-check-square-o"></i> <?php echo __('GRAP Compliance Check') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="grap-report">

  <div class="btn-group" style="margin-bottom: 20px;">
    <a href="<?php echo url_for('grapReport/index') ?>" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> <?php echo __('Back to Dashboard') ?>
    </a>
  </div>

  <!-- Summary Stats -->
  <div class="row" style="margin-bottom: 20px;">
    <div class="span3">
      <div class="well text-center">
        <h2><?php echo $stats['total'] ?></h2>
        <p><?php echo __('Total Assets') ?></p>
      </div>
    </div>
    <div class="span3">
      <div class="well text-center">
        <h2 class="text-success"><?php echo $stats['compliant'] ?></h2>
        <p><?php echo __('Compliant (≥80%)') ?></p>
      </div>
    </div>
    <div class="span3">
      <div class="well text-center">
        <h2 class="text-warning"><?php echo $stats['non_compliant'] ?></h2>
        <p><?php echo __('Needs Work (<80%)') ?></p>
      </div>
    </div>
    <div class="span3">
      <div class="well text-center">
        <h2><?php echo $stats['average'] ?>%</h2>
        <p><?php echo __('Average Compliance') ?></p>
      </div>
    </div>
  </div>

  <div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <?php echo __('Compliance is calculated based on required GRAP 103 fields (70%) and recommended fields (30%). Required fields include recognition status, measurement basis, asset class, acquisition details, carrying amount, heritage significance, and condition rating.') ?>
  </div>

  <?php if (!empty($items)): ?>
    <table class="table table-bordered table-striped sticky-enabled">
      <thead>
        <tr>
          <th><?php echo __('Reference') ?></th>
          <th><?php echo __('Title') ?></th>
          <th><?php echo __('Asset Class') ?></th>
          <th class="text-center" style="width: 150px;"><?php echo __('Compliance') ?></th>
          <th><?php echo __('Missing Fields') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <?php
            $pct = $item['compliance_percentage'] ?? 0;
            $rowClass = '';
            $barClass = 'progress-bar-danger';
            if ($pct >= 80) {
                $barClass = 'progress-bar-success';
            } elseif ($pct >= 50) {
                $barClass = 'progress-bar-warning';
                $rowClass = 'warning';
            } else {
                $rowClass = 'error';
            }
          ?>
          <tr class="<?php echo $rowClass ?>">
            <td>
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'informationobject']) ?>">
                <?php echo esc_entities($item['reference_code'] ?: '-') ?>
              </a>
            </td>
            <td><?php echo esc_entities($item['title'] ?: __('Untitled')) ?></td>
            <td>
              <?php 
                $classes = GrapHeritageAssetForm::getAssetClassChoices();
                echo esc_entities($classes[$item['asset_class'] ?? ''] ?? '-');
              ?>
            </td>
            <td>
              <div class="progress" style="margin-bottom: 0; height: 20px;">
                <div class="progress-bar <?php echo $barClass ?>" 
                     role="progressbar" 
                     style="width: <?php echo $pct ?>%;"
                     aria-valuenow="<?php echo $pct ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                  <?php echo $pct ?>%
                </div>
              </div>
            </td>
            <td>
              <?php if (!empty($item['missing_fields'])): ?>
                <small class="text-muted">
                  <?php echo esc_entities($item['missing_fields']) ?>
                  <?php if (($item['missing_count'] ?? 0) > 3): ?>
                    <em>(+<?php echo $item['missing_count'] - 3 ?> more)</em>
                  <?php endif ?>
                </small>
              <?php else: ?>
                <span class="text-success"><i class="fa fa-check"></i> <?php echo __('Complete') ?></span>
              <?php endif ?>
            </td>
            <td>
              <a href="<?php echo url_for(['slug' => $item['slug'], 'module' => 'grap', 'action' => 'edit']) ?>" 
                 class="btn btn-xs btn-primary" title="<?php echo __('Edit GRAP') ?>">
                <i class="fa fa-pencil"></i> <?php echo __('Edit') ?>
              </a>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">
      <i class="fa fa-info-circle"></i>
      <?php echo __('No GRAP data has been entered yet.') ?>
    </div>
  <?php endif ?>

</div>

<?php end_slot() ?>
