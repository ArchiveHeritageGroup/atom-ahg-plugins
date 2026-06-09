<?php
/* #136 — Exhibition Space 2.5D pannable walkthrough + guided tour. */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
$data = [];
foreach ($placements as $p) {
    if ($p->pos_x === null) { continue; }
    $data[] = [
        'io' => (int) $p->information_object_id,
        'title' => (string) ($p->title ?? ('Object #'.$p->information_object_id)),
        'thumb' => $p->thumb ?: null,
        'x' => (float) $p->pos_x, 'y' => (float) $p->pos_y,
        'w' => (float) $p->item_w, 'h' => (float) $p->item_h,
        'wall' => (string) $p->wall,
        'tour' => $p->tour_order !== null ? (int) $p->tour_order : null,
        'slug' => null,
    ];
}
?>
<div class="exhibition-walkthrough" style="position:relative">
  <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 bg-dark text-white">
    <span class="h5 mb-0 flex-grow-1"><i class="fas fa-walking me-2"></i><?php echo esc_entities($space->name) ?></span>
    <button id="exh-tour-start" class="btn btn-info btn-sm"><i class="fas fa-route me-1"></i><?php echo __('Start guided tour') ?></button>
    <button id="exh-tour-prev" class="btn btn-outline-light btn-sm" style="display:none"><i class="fas fa-chevron-left"></i></button>
    <button id="exh-tour-next" class="btn btn-outline-light btn-sm" style="display:none"><i class="fas fa-chevron-right"></i></button>
    <button id="exh-tour-stop" class="btn btn-outline-light btn-sm" style="display:none"><?php echo __('Exit tour') ?></button>
    <?php if ($sf_user->isAuthenticated()): ?>
      <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'builder', 'slug' => $space->slug]) ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-vector-square me-1"></i><?php echo __('Builder') ?></a>
    <?php endif ?>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>" class="btn btn-light btn-sm"><?php echo __('Close') ?></a>
  </div>

  <div id="exh-viewport" style="position:relative;width:100%;height:80vh;overflow:hidden;background:radial-gradient(circle at 50% 30%, #3a3a42, #15151a);cursor:grab">
    <div id="exh-scene" style="position:absolute;transform-origin:0 0;width:<?php echo (int) $space->room_width ?>px;height:<?php echo (int) $space->room_height ?>px;background:<?php echo esc_entities($space->floor_color) ?>;border:18px solid <?php echo esc_entities($space->wall_color) ?>;box-shadow:0 30px 80px rgba(0,0,0,.6), inset 0 0 90px rgba(0,0,0,.25)">
      <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,.18), rgba(255,255,255,.05) 40%, rgba(0,0,0,.12));pointer-events:none"></div>
    </div>
    <div id="exh-caption" style="position:absolute;left:0;right:0;bottom:0;padding:14px 18px;background:linear-gradient(0deg, rgba(0,0,0,.8), transparent);color:#fff;display:none">
      <div class="small text-info" id="exh-cap-step"></div>
      <div class="h5 mb-0" id="exh-cap-title"></div>
    </div>
    <div style="position:absolute;top:8px;right:12px;color:#fff;opacity:.7;font-size:12px"><i class="fas fa-arrows-alt me-1"></i><?php echo __('Drag to pan · scroll to zoom') ?></div>
  </div>
</div>

<script <?php echo $nonceAttr ?> type="application/json" id="exh-wt-data"><?php echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script <?php echo $nonceAttr ?> src="/plugins/ahgExhibitionPlugin/web/js/exhibition-walkthrough.js"></script>
