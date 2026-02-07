<?php
require_once sfConfig::get('sf_plugins_dir') . '/ahgUiOverridesPlugin/lib/helper/AhgLaravelHelper.php';
?>
<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('Condition Reports'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo $resource->title ?? $resource->slug; ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if (empty($conditionReports)): ?>
  <div class="alert alert-info">
    <?php echo __('No condition reports have been recorded for this item.'); ?>
  </div>
<?php else: ?>

  <!-- Current Condition Summary -->
  <?php if ($currentCondition): ?>
  <div class="card mb-4">
    <div class="card-header">
      <h2 class="h5 mb-0"><?php echo __('Current Condition'); ?></h2>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <strong><?php echo __('Rating'); ?>:</strong>
          <span class="badge" style="background-color: <?php echo $ratings[$currentCondition->condition_rating]['color'] ?? '#999'; ?>">
            <?php echo $ratings[$currentCondition->condition_rating]['label'] ?? $currentCondition->condition_rating; ?>
          </span>
        </div>
        <div class="col-md-3">
          <strong><?php echo __('Date'); ?>:</strong>
          <?php echo $currentCondition->assessment_date; ?>
        </div>
        <div class="col-md-3">
          <strong><?php echo __('Assessor'); ?>:</strong>
          <?php echo $currentCondition->assessor_name; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Condition History -->
  <h2 class="h5"><?php echo __('Condition History'); ?></h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th><?php echo __('Date'); ?></th>
        <th><?php echo __('Rating'); ?></th>
        <th><?php echo __('Assessor'); ?></th>
        <th><?php echo __('Purpose'); ?></th>
        <th><?php echo __('Notes'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($conditionReports as $report): ?>
      <tr>
        <td><?php echo $report->assessment_date; ?></td>
        <td>
          <span class="badge" style="background-color: <?php echo $ratings[$report->condition_rating]['color'] ?? '#999'; ?>">
            <?php echo $ratings[$report->condition_rating]['label'] ?? $report->condition_rating; ?>
          </span>
        </td>
        <td><?php echo $report->assessor_name; ?></td>
        <td><?php echo $report->purpose; ?></td>
        <td><?php echo $report->notes; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

<?php endif; ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions">
    <ul class="nav gap-2">
      <?php if ($canEdit && ahg_is_plugin_enabled('ahgSpectrumPlugin')): ?>
        <li><?php echo link_to(__('Add Condition Report'), ['module' => 'spectrum', 'action' => 'conditionReportAdd', 'slug' => $resource->slug], ['class' => 'btn atom-btn-outline-success']); ?></li>
      <?php endif; ?>
      <li><?php echo link_to(__('Back to record'), ['module' => 'informationobject', 'slug' => $resource->slug], ['class' => 'btn atom-btn-outline-light']); ?></li>
    </ul>
  </section>
<?php end_slot(); ?>
