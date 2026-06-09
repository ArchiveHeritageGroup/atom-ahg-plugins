<?php
/* #136 — Exhibition Space builder (drag-and-drop layout). */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
$data = [];
foreach ($placements as $p) {
    $data[] = [
        'id' => (int) $p->id,
        'io' => (int) $p->information_object_id,
        'title' => (string) ($p->title ?? ('Object #'.$p->information_object_id)),
        'thumb' => $p->thumb ?: null,
        'x' => $p->pos_x !== null ? (float) $p->pos_x : null,
        'y' => $p->pos_y !== null ? (float) $p->pos_y : null,
        'w' => (float) $p->item_w,
        'h' => (float) $p->item_h,
        'wall' => (string) $p->wall,
        'z' => (int) $p->z_order,
        'tour' => $p->tour_order !== null ? (int) $p->tour_order : null,
    ];
}
?>
<div class="container-fluid px-4 py-3 exhibition-builder">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-vector-square me-2"></i><?php echo esc_entities($space->name) ?>
      <small class="text-muted"><?php echo __('Builder') ?></small>
    </h1>
    <button type="button" id="exh-save" class="btn btn-success"><i class="fas fa-save me-1"></i><?php echo __('Save layout') ?></button>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'walkthrough', 'slug' => $space->slug]) ?>" class="btn btn-outline-info" target="_blank"><i class="fas fa-walking me-1"></i><?php echo __('Walkthrough') ?></a>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back to space') ?></a>
  </div>

  <?php if ($sf_user->hasFlash('notice')): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('notice') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>

  <div class="row g-3">
    <div class="col-lg-3">
      <div class="card mb-3">
        <div class="card-header py-2"><i class="fas fa-ruler-combined me-1"></i><?php echo __('Room') ?></div>
        <div class="card-body">
          <form method="post" action="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'saveRoom', 'slug' => $space->slug]) ?>">
            <div class="row g-2">
              <div class="col-6"><label class="form-label small"><?php echo __('Width') ?></label><input type="number" name="room_width" class="form-control form-control-sm" value="<?php echo (int) $space->room_width ?>" min="200" max="6000"></div>
              <div class="col-6"><label class="form-label small"><?php echo __('Height') ?></label><input type="number" name="room_height" class="form-control form-control-sm" value="<?php echo (int) $space->room_height ?>" min="200" max="6000"></div>
              <div class="col-6"><label class="form-label small"><?php echo __('Wall') ?></label><input type="color" name="wall_color" class="form-control form-control-sm form-control-color w-100" value="<?php echo esc_entities($space->wall_color) ?>"></div>
              <div class="col-6"><label class="form-label small"><?php echo __('Floor') ?></label><input type="color" name="floor_color" class="form-control form-control-sm form-control-color w-100" value="<?php echo esc_entities($space->floor_color) ?>"></div>
            </div>
            <button class="btn btn-sm btn-outline-primary mt-2 w-100"><?php echo __('Apply room') ?></button>
          </form>
        </div>
      </div>
      <div class="card">
        <div class="card-header py-2"><i class="fas fa-images me-1"></i><?php echo __('Unplaced objects') ?> <span id="exh-tray-count" class="badge bg-secondary"></span></div>
        <div class="card-body p-2" id="exh-tray" style="min-height:80px"><div class="text-muted small p-2"><?php echo __('Drag objects onto the floor plan. Add placements from the space page.') ?></div></div>
      </div>
      <div class="card mt-3" id="exh-inspector" style="display:none">
        <div class="card-header py-2"><i class="fas fa-sliders-h me-1"></i><span id="exh-insp-title"><?php echo __('Selected') ?></span></div>
        <div class="card-body">
          <label class="form-label small"><?php echo __('Wall') ?></label>
          <select id="exh-wall" class="form-select form-select-sm mb-2">
            <option value="north"><?php echo __('North wall') ?></option>
            <option value="east"><?php echo __('East wall') ?></option>
            <option value="south"><?php echo __('South wall') ?></option>
            <option value="west"><?php echo __('West wall') ?></option>
            <option value="floor"><?php echo __('Floor (plinth)') ?></option>
          </select>
          <label class="form-label small"><?php echo __('Size') ?></label>
          <input type="range" id="exh-size" class="form-range" min="40" max="400" step="5">
          <label class="form-label small"><?php echo __('Guided-tour stop #') ?></label>
          <input type="number" id="exh-tour" class="form-control form-control-sm mb-2" min="1" placeholder="—">
          <button type="button" id="exh-unplace" class="btn btn-sm btn-outline-danger w-100"><?php echo __('Remove from layout') ?></button>
        </div>
      </div>
    </div>

    <div class="col-lg-9">
      <div class="exh-stage border rounded bg-light p-2" style="overflow:auto;max-height:78vh">
        <div id="exh-canvas" class="exh-canvas position-relative mx-auto"
             style="width:<?php echo (int) $space->room_width ?>px;height:<?php echo (int) $space->room_height ?>px;background:<?php echo esc_entities($space->floor_color) ?>;border:14px solid <?php echo esc_entities($space->wall_color) ?>;box-shadow:inset 0 0 60px rgba(0,0,0,.15)">
        </div>
      </div>
      <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i><?php echo __('Drag to move. Drag the corner to resize. Click to select & set wall / tour order. Remember to Save.') ?></p>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr ?> type="application/json" id="exh-data"><?php echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script <?php echo $nonceAttr ?>>
  window.EXH_BUILDER = {
    slug: <?php echo json_encode($space->slug) ?>,
    saveUrl: <?php echo json_encode(url_for(['module' => 'exhibitionSpace', 'action' => 'saveLayout', 'slug' => $space->slug])) ?>
  };
</script>
<script <?php echo $nonceAttr ?> src="/plugins/ahgExhibitionPlugin/web/js/exhibition-builder.js"></script>
