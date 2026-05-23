<?php
/*
 * heratio#145 — Strongroom browse (AtoM Heratio).
 * Pure HTML + Bootstrap utility classes — no inline <script> or <style>, so it
 * needs no CSP nonce.
 */
$capacityUnits = $sf_data->getRaw('capacityUnits');
?>
<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Strongrooms'); ?>
    <?php if ($sf_user->hasCredential('administrator')) { ?>
      <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'create']); ?>"
         class="btn btn-primary btn-sm ms-2">
        <i class="fas fa-plus me-1"></i><?php echo __('Add strongroom'); ?>
      </a>
    <?php } ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if ($sf_user->hasFlash('notice')) { ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php } ?>
  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
  <?php } ?>

  <form method="get" action="<?php echo url_for(['module' => 'strongroom', 'action' => 'browse']); ?>"
        class="mb-3" role="search">
    <div class="input-group" style="max-width: 32rem;">
      <input type="search" name="q" value="<?php echo esc_specialchars($search); ?>"
             class="form-control" placeholder="<?php echo __('Search strongrooms'); ?>">
      <button type="submit" class="btn btn-outline-secondary"><?php echo __('Search'); ?></button>
      <?php if ('' !== $search) { ?>
        <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'browse']); ?>"
           class="btn btn-outline-secondary"><?php echo __('Clear'); ?></a>
      <?php } ?>
    </div>
  </form>

  <?php if ($rooms->total() > 0) { ?>
    <div class="table-responsive mb-3">
      <table class="table table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Location'); ?></th>
            <th><?php echo __('Capacity'); ?></th>
            <th style="min-width: 12rem;"><?php echo __('Utilisation'); ?></th>
            <th class="text-end"><?php echo __('Occupants'); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rooms as $room) {
            $capacity = (null !== $room->capacity_value) ? (float) $room->capacity_value : null;
            $used = (float) $room->used_units;
            $pct = (null !== $capacity && $capacity > 0)
                ? min(100, (int) round(($used / $capacity) * 100)) : null;
            $unitLabel = $capacityUnits[$room->capacity_unit] ?? $room->capacity_unit;
            $barClass = null === $pct ? 'bg-secondary'
                : ($pct >= 100 ? 'bg-danger' : ($pct >= 90 ? 'bg-warning' : 'bg-success'));
        ?>
          <tr>
            <td>
              <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]); ?>">
                <?php echo esc_specialchars($room->name); ?>
              </a>
            </td>
            <td class="small text-muted">
              <?php echo esc_specialchars(mb_strimwidth((string) $room->location_description, 0, 80, '…')); ?>
            </td>
            <td class="small">
              <?php if (null !== $capacity) { ?>
                <?php echo rtrim(rtrim(number_format($capacity, 2), '0'), '.'); ?>&nbsp;<?php echo esc_specialchars($unitLabel); ?>
              <?php } else { ?>
                <span class="text-muted"><?php echo __('not set'); ?></span>
              <?php } ?>
            </td>
            <td>
              <?php if (null !== $pct) { ?>
                <div class="progress" role="progressbar"
                     aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"
                     style="height: 1rem;">
                  <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
                </div>
                <div class="small text-muted mt-1">
                  <?php echo rtrim(rtrim(number_format($used, 2), '0'), '.'); ?> / <?php echo rtrim(rtrim(number_format($capacity, 2), '0'), '.'); ?>&nbsp;<?php echo esc_specialchars($unitLabel); ?>
                </div>
              <?php } else { ?>
                <span class="text-muted small"><?php echo __('no capacity set'); ?></span>
              <?php } ?>
            </td>
            <td class="text-end"><?php echo (int) $room->occupant_count; ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>

    <?php if (method_exists($rooms, 'links')) { echo $rooms->links(); } ?>
  <?php } else { ?>
    <p class="text-muted">
      <?php if ('' !== $search) {
          echo __('No strongrooms match your search.');
      } else {
          echo __('No strongrooms yet.');
          if ($sf_user->hasCredential('administrator')) { ?>
            <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'create']); ?>"><?php echo __('Add the first one.'); ?></a>
          <?php }
      } ?>
    </p>
  <?php } ?>
<?php end_slot(); ?>
