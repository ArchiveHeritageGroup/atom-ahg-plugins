<?php
/*
 * heratio#145 — Strongroom show (AtoM Heratio).
 */
$capacityUnits = $sf_data->getRaw('capacityUnits');
$capacity = (null !== $room->capacity_value) ? (float) $room->capacity_value : null;
$used = (float) $usedUnits;
$pct = (null !== $capacity && $capacity > 0)
    ? min(100, (int) round(($used / $capacity) * 100)) : null;
$unitLabel = $capacityUnits[$room->capacity_unit] ?? $room->capacity_unit;
$barClass = null === $pct ? 'bg-secondary'
    : ($pct >= 100 ? 'bg-danger' : ($pct >= 90 ? 'bg-warning' : 'bg-success'));
?>
<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_specialchars($room->name); ?>
    <?php if ($sf_user->hasCredential('administrator')) { ?>
      <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'edit', 'slug' => $room->slug]); ?>"
         class="btn btn-outline-primary btn-sm ms-2">
        <i class="fas fa-pencil-alt me-1"></i><?php echo __('Edit'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'delete', 'slug' => $room->slug]); ?>"
         class="btn btn-outline-danger btn-sm">
        <i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?>
      </a>
    <?php } ?>
    <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'browse']); ?>"
       class="btn btn-link btn-sm">&laquo; <?php echo __('Back to strongrooms'); ?></a>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if ($sf_user->hasFlash('notice')) { ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php } ?>
  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
  <?php } ?>

  <div class="row g-3 mb-4">
    <div class="col-md-7">
      <div class="card h-100">
        <div class="card-header"><?php echo __('Details'); ?></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4"><?php echo __('Slug'); ?></dt>
            <dd class="col-sm-8"><code><?php echo esc_specialchars($room->slug); ?></code></dd>

            <dt class="col-sm-4"><?php echo __('Location'); ?></dt>
            <dd class="col-sm-8"><?php echo esc_specialchars($room->location_description ?: '—'); ?></dd>

            <dt class="col-sm-4"><?php echo __('Notes'); ?></dt>
            <dd class="col-sm-8" style="white-space: pre-wrap;"><?php echo esc_specialchars($room->notes ?: '—'); ?></dd>
          </dl>
        </div>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card h-100">
        <div class="card-header"><?php echo __('Capacity'); ?></div>
        <div class="card-body">
          <?php if (null !== $capacity) { ?>
            <div class="progress mb-2" role="progressbar"
                 aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"
                 style="height: 1.5rem;">
              <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
            </div>
            <dl class="row mb-0 small">
              <dt class="col-6"><?php echo __('Total'); ?></dt>
              <dd class="col-6 text-end"><?php echo rtrim(rtrim(number_format($capacity, 2), '0'), '.'); ?>&nbsp;<?php echo esc_specialchars($unitLabel); ?></dd>
              <dt class="col-6"><?php echo __('Used'); ?></dt>
              <dd class="col-6 text-end"><?php echo rtrim(rtrim(number_format($used, 2), '0'), '.'); ?>&nbsp;<?php echo esc_specialchars($unitLabel); ?></dd>
              <dt class="col-6"><?php echo __('Remaining'); ?></dt>
              <dd class="col-6 text-end"><?php echo rtrim(rtrim(number_format(max(0, $capacity - $used), 2), '0'), '.'); ?>&nbsp;<?php echo esc_specialchars($unitLabel); ?></dd>
            </dl>
          <?php } else { ?>
            <p class="text-muted mb-0">
              <?php echo __('No capacity set for this strongroom.'); ?>
              <?php if ($sf_user->hasCredential('administrator')) { ?>
                <a href="<?php echo url_for(['module' => 'strongroom', 'action' => 'edit', 'slug' => $room->slug]); ?>"><?php echo __('Set one'); ?></a>.
              <?php } ?>
            </p>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?php echo __('Occupants'); ?>
        <span class="badge bg-secondary"><?php echo $occupants->count(); ?></span></span>
    </div>
    <?php if ($occupants->count() > 0) { ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr>
            <th><?php echo __('Physical object'); ?></th>
            <th><?php echo __('Location'); ?></th>
            <th class="text-end"><?php echo __('Size used'); ?></th>
            <?php if ($sf_user->hasCredential('administrator')) { ?>
              <th class="text-end" style="width: 6rem;"></th>
            <?php } ?>
          </tr></thead>
          <tbody>
          <?php foreach ($occupants as $occ) { ?>
            <tr>
              <td>
                <?php if ($occ->slug) { ?>
                  <a href="<?php echo url_for(['module' => 'physicalobject', 'slug' => $occ->slug]); ?>">
                    <?php echo esc_specialchars($occ->name ?: $occ->slug); ?>
                  </a>
                <?php } else { ?>
                  <?php echo esc_specialchars($occ->name ?: __('(unnamed)')); ?>
                <?php } ?>
              </td>
              <td class="small text-muted"><?php echo esc_specialchars((string) $occ->location); ?></td>
              <td class="text-end small">
                <?php echo rtrim(rtrim(number_format((float) $occ->size_units_used, 2), '0'), '.'); ?>&nbsp;<?php echo esc_specialchars($unitLabel); ?>
              </td>
              <?php if ($sf_user->hasCredential('administrator')) { ?>
                <td class="text-end">
                  <form method="post"
                        action="<?php echo url_for(['module' => 'strongroom', 'action' => 'unassign']); ?>"
                        class="d-inline">
                    <input type="hidden" name="physical_object_id" value="<?php echo (int) $occ->id; ?>">
                    <input type="hidden" name="return_slug" value="<?php echo esc_specialchars($room->slug); ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                            title="<?php echo __('Unassign from this strongroom'); ?>">
                      <i class="fas fa-times"></i>
                    </button>
                  </form>
                </td>
              <?php } ?>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } else { ?>
      <div class="card-body">
        <p class="text-muted mb-0"><?php echo __('No physical objects are assigned to this strongroom yet.'); ?></p>
      </div>
    <?php } ?>
  </div>

  <?php if ($sf_user->hasCredential('administrator')) { ?>
    <div class="card">
      <div class="card-header"><?php echo __('Assign a physical object'); ?></div>
      <div class="card-body">
        <p class="text-muted small mb-3">
          <?php echo __('Paste the physical object\'s slug (find it on the physical object\'s page URL — the segment after /physicalobject/).'); ?>
        </p>
        <form method="post" action="<?php echo url_for(['module' => 'strongroom', 'action' => 'assign', 'slug' => $room->slug]); ?>">
          <div class="row g-3 align-items-end">
            <div class="col-md-6">
              <label for="sr_po_slug" class="form-label fw-semibold"><?php echo __('Physical-object slug'); ?></label>
              <input type="text" id="sr_po_slug" name="physical_object_slug" class="form-control" required
                     placeholder="<?php echo __('e.g. box-12-shelf-3'); ?>">
            </div>
            <div class="col-md-4">
              <label for="sr_po_size" class="form-label fw-semibold">
                <?php echo __('Size used'); ?>
                <?php if (null !== $capacity) { ?>
                  <span class="text-muted">(<?php echo esc_specialchars($unitLabel); ?>)</span>
                <?php } ?>
              </label>
              <input type="number" id="sr_po_size" name="size_units_used" class="form-control"
                     min="0" step="0.01" value="0">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-plus me-1"></i><?php echo __('Assign'); ?>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  <?php } ?>
<?php end_slot(); ?>
