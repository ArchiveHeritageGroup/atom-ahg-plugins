<?php
/**
 * Declassification Management Template.
 */
?>

<h1><i class="fas fa-unlock"></i> <?php echo __('Declassification Management') ?></h1>

<!-- Due Now -->
<?php if (!empty($dueDeclassifications)): ?>
<div class="card mb-4 border-warning">
  <div class="card-header bg-warning">
    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Due for Declassification') ?></h5>
  </div>
  <div class="card-body">
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('Object') ?></th>
          <th><?php echo __('Current') ?></th>
          <th><?php echo __('Downgrade To') ?></th>
          <th><?php echo __('Scheduled') ?></th>
          <th><?php echo __('Actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dueDeclassifications as $dec): ?>
        <tr>
          <td>
            <a href="/<?php echo $dec->object_id ?>"><?php echo esc_entities($dec->title ?? $dec->identifier ?? 'ID: '.$dec->object_id) ?></a>
          </td>
          <td><span class="badge bg-danger"><?php echo esc_entities($dec->from_classification) ?></span></td>
          <td><span class="badge bg-success"><?php echo esc_entities($dec->to_classification ?? 'Public') ?></span></td>
          <td><?php echo $dec->scheduled_date ?></td>
          <td>
            <form action="/security/declassify/<?php echo $dec->object_id ?>" method="post" style="display:inline">
              <input type="hidden" name="new_classification_id" value="<?php echo $dec->to_classification_id ?>">
              <input type="hidden" name="reason" value="Scheduled declassification">
              <button type="submit" class="btn btn-sm btn-success">
                <i class="fas fa-check"></i> <?php echo __('Process') ?>
              </button>
            </form>
            <a href="/<?php echo $dec->object_id ?>/security" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="alert alert-success mb-4">
  <i class="fas fa-check-circle"></i> <?php echo __('No declassifications currently due.') ?>
</div>
<?php endif ?>

<!-- Scheduled -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-calendar"></i> <?php echo __('Scheduled Declassifications') ?></h5>
  </div>
  <div class="card-body">
    <?php if (empty($scheduled)): ?>
    <p class="text-muted text-center"><?php echo __('No future declassifications scheduled.') ?></p>
    <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th><?php echo __('Object') ?></th>
          <th><?php echo __('Current') ?></th>
          <th><?php echo __('Downgrade To') ?></th>
          <th><?php echo __('Scheduled Date') ?></th>
          <th><?php echo __('Days Until') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($scheduled as $item): ?>
        <?php $daysUntil = (strtotime($item->scheduled_date) - time()) / 86400; ?>
        <tr class="<?php echo $daysUntil <= 30 ? 'table-warning' : '' ?>">
          <td>
            <a href="/<?php echo $item->object_id ?>"><?php echo esc_entities($item->title ?? 'ID: '.$item->object_id) ?></a>
          </td>
          <td><?php echo esc_entities($item->from_name) ?></td>
          <td><?php echo esc_entities($item->to_name ?? 'Public') ?></td>
          <td><?php echo $item->scheduled_date ?></td>
          <td><?php echo round($daysUntil) ?> days</td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <?php endif ?>
  </div>
</div>
