<?php
/*
 * Exhibition Space — Digital Twin Builder (Konva.js 2D floor-plan builder).
 * Faithful port of Heratio packages/ahg-exhibition resources/views/exhibition-space/builder.blade.php
 * (heratio#1138+). Blade -> Symfony PHP template; route() -> url_for(); fetch URLs map to the
 * shared CONTRACT actions on module "exhibitionSpace".
 *
 * Backend body-field mapping vs Heratio (PSIS controller reads these keys):
 *   placement_id -> id ;  size_units_used -> size_units ;  roomDims room_w/d/h -> w/d/h.
 *
 * Object search uses the existing JSON endpoint /api/autocomplete/glam (ahgAPIPlugin),
 * which returns [{id,label,value,...}] — not part of the builder contract but already shipped.
 *
 * Phase-2 stubs (no CONTRACT endpoint): wall-view per-item u/v persistence + wall-place,
 * floorplan/ceiling/wall-paint/floor-image/scan-shell uploads, furniture library, AI recs,
 * live-data sim, guided-tour audio upload. Their UI is rendered but clearly marked TODO.
 */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

$isAuth = $sf_user->isAuthenticated();

// ---- normalized initial data for the canvas (mirrors the Blade @json(...) vars) ----
$placements = isset($placements) && is_array($placements) ? $placements : [];
$walls = isset($walls) && is_array($walls) ? $walls : [];
$doors = isset($doors) && is_array($doors) ? $doors : [];
$windows = isset($windows) && is_array($windows) ? $windows : [];
$shape = isset($shape) ? $shape : null;
$roomDims = isset($roomDims) && is_array($roomDims) ? $roomDims : ['w' => 18, 'd' => 14, 'h' => 4];
$furniture = isset($furniture) && is_array($furniture) ? $furniture : [];
$guidedTour = isset($guidedTour) && is_array($guidedTour) ? $guidedTour : [];
$tourObjects = isset($tourObjects) && is_array($tourObjects) ? $tourObjects : [];

$JSON = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT;

// URL map — every CONTRACT action via url_for().
$urls = [
    'autocomplete' => '/api/autocomplete/glam',
    'place' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderPlace', 'slug' => $space->slug]),
    'layout' => url_for(['module' => 'exhibitionSpace', 'action' => 'saveLayout', 'slug' => $space->slug]),
    'remove' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderRemove', 'slug' => $space->slug]),
    'size' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderSize', 'slug' => $space->slug]),
    'tilt' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderTilt', 'slug' => $space->slug]),
    'spotlight' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderSpotlight', 'slug' => $space->slug]),
    'displayCase' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderDisplayCase', 'slug' => $space->slug]),
    'onFloor' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderOnFloor', 'slug' => $space->slug]),
    'viewSpot' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderView', 'slug' => $space->slug]),
    'zorder' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderZOrder', 'slug' => $space->slug]),
    'wall' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderWall', 'slug' => $space->slug]),
    'placements' => url_for(['module' => 'exhibitionSpace', 'action' => 'builderPlacements', 'slug' => $space->slug]),
    'roomDims' => url_for(['module' => 'exhibitionSpace', 'action' => 'roomDims', 'slug' => $space->slug]),
    'planWalls' => url_for(['module' => 'exhibitionSpace', 'action' => 'planWalls', 'slug' => $space->slug]),
    'planDoors' => url_for(['module' => 'exhibitionSpace', 'action' => 'planDoors', 'slug' => $space->slug]),
    'planWindows' => url_for(['module' => 'exhibitionSpace', 'action' => 'planWindows', 'slug' => $space->slug]),
    'planShape' => url_for(['module' => 'exhibitionSpace', 'action' => 'planShape', 'slug' => $space->slug]),
    'walkthrough' => url_for(['module' => 'exhibitionSpace', 'action' => 'walkthrough', 'slug' => $space->slug]),
];

$bootData = [
    'urls' => $urls,
    'spaceId' => (int) $space->id,
    'floorplan' => isset($space->floorplan_image_path) ? $space->floorplan_image_path : null,
    'placements' => $placements,
    'walls' => $walls,
    'doors' => $doors,
    'windows' => $windows,
    'shape' => $shape,
    'guidedTour' => $guidedTour,
    'tourObjects' => $tourObjects,
    'roomW' => (float) ($roomDims['w'] ?? 18),
    'roomD' => (float) ($roomDims['d'] ?? 14),
    'roomH' => (float) ($roomDims['h'] ?? 4),
];
?>
<div class="container-fluid px-4 py-3 exhibition-space builder">
  <div class="d-flex flex-wrap align-items-baseline mb-2 gap-2">
    <h1 class="mb-0 flex-grow-1">
      <i class="fas fa-cubes me-2"></i><?php echo __('Digital Twin Builder') ?>
      <small class="text-muted"><?php echo esc_entities($space->name) ?></small>
    </h1>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'walkthrough', 'slug' => $space->slug]) ?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="fas fa-walking me-1"></i><?php echo __('Walkthrough') ?></a>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back to space') ?></a>
  </div>
  <p class="text-muted small mb-3">
    <?php echo __('The start of digital twins: arrange this collection visually. Search an object, drop it on the floorplan, then drag, rotate and scale it into place. Changes save automatically.') ?>
  </p>

  <?php if ($sf_user->hasFlash('notice')): ?><div class="alert alert-success py-2"><?php echo $sf_user->getFlash('notice') ?></div><?php endif ?>
  <?php if ($sf_user->hasFlash('error')): ?><div class="alert alert-danger py-2"><?php echo $sf_user->getFlash('error') ?></div><?php endif ?>

  <div class="row g-3">
    <!-- Left: tools -->
    <div class="col-lg-3">
      <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="fas fa-plus me-1"></i><?php echo __('Add object') ?></strong></div>
        <div class="card-body">
          <select id="objectSearch" class="form-control form-control-sm">
            <option value=""><?php echo __('Type to search...') ?></option>
          </select>
          <label for="initialSize" class="form-label small mt-2 mb-1"><?php echo __('Initial size (units)') ?></label>
          <input type="number" id="initialSize" class="form-control form-control-sm" min="0" step="0.01" value="1">
          <small class="text-muted d-block mt-1"><?php echo __('Selecting an object drops it on the canvas at this size.') ?></small>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="fas fa-list me-1"></i><?php echo __('Objects in this space') ?></strong> <span class="badge bg-secondary" id="objCount">0</span></div>
        <div class="card-body p-2" style="max-height:220px;overflow:auto;">
          <div id="objList" class="small text-muted"><?php echo __('None yet.') ?></div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="fas fa-sliders-h me-1"></i><?php echo __('Selected object') ?></strong></div>
        <div class="card-body">
          <div id="selPanel" class="text-muted small"><?php echo __('Click an object on the canvas to select it.') ?></div>
          <div id="selControls" class="d-none">
            <div class="fw-bold small mb-2" id="selTitle"></div>
            <div class="btn-group btn-group-sm w-100 mb-2" role="group">
              <button type="button" class="btn btn-outline-secondary" data-act="rotL" title="<?php echo __('Rotate left') ?>"><i class="fas fa-undo"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-act="rotR" title="<?php echo __('Rotate right') ?>"><i class="fas fa-redo"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-act="smaller" title="<?php echo __('Smaller') ?>"><i class="fas fa-search-minus"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-act="bigger" title="<?php echo __('Bigger') ?>"><i class="fas fa-search-plus"></i></button>
            </div>
            <div class="btn-group btn-group-sm w-100 mb-2" role="group">
              <button type="button" class="btn btn-outline-warning" data-act="spot" id="spotBtn" title="<?php echo __('Spotlight: click to cycle off / light on approach / always-on. All modes dim the surroundings as you walk closer.') ?>"><i class="fas fa-lightbulb me-1"></i><?php echo __('Spot off') ?></button>
              <button type="button" class="btn btn-outline-info" data-act="case" id="caseBtn" title="<?php echo __('Show this item inside a glass display case on a plinth') ?>"><i class="fas fa-box-open me-1"></i><?php echo __('Case') ?></button>
              <button type="button" class="btn btn-outline-success" data-act="floor" id="floorBtn" title="<?php echo __('Stand this 3D model directly on the floor (no pedestal)') ?>"><i class="fas fa-down-long me-1"></i><?php echo __('On floor') ?></button>
              <button type="button" class="btn btn-outline-secondary" data-act="front" title="<?php echo __('Bring to front') ?>"><i class="fas fa-arrow-up"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-act="back" title="<?php echo __('Send to back') ?>"><i class="fas fa-arrow-down"></i></button>
            </div>
            <label for="selSize" class="form-label small mb-1"><?php echo __('Size (units)') ?></label>
            <input type="number" id="selSize" class="form-control form-control-sm mb-2" min="0" step="0.01">
            <div id="tiltControls" class="d-none border-top pt-2 mb-2">
              <label class="form-label small mb-1"><?php echo __('3D orientation (degrees)') ?></label>
              <div class="row g-1 mb-1">
                <div class="col-6"><input type="number" id="tiltX" class="form-control form-control-sm" step="90" placeholder="<?php echo __('Tilt X') ?>"></div>
                <div class="col-6"><input type="number" id="tiltZ" class="form-control form-control-sm" step="90" placeholder="<?php echo __('Tilt Z') ?>"></div>
              </div>
              <button type="button" id="tiltAuto" class="btn btn-outline-secondary btn-sm w-100"><?php echo __('Auto (reset)') ?></button>
              <small class="text-muted d-block mt-1"><?php echo __('Empty = auto. Use 90 / -90 to stand a model upright.') ?></small>
            </div>
            <div class="border-top pt-2 mb-2">
              <label class="form-label small mb-1"><?php echo __('Tour viewing spot') ?></label>
              <div class="d-flex gap-1">
                <button type="button" id="viewSpotBtn" class="btn btn-sm btn-outline-primary flex-fill" title="<?php echo __('Then click a spot on the plan where the tour should stand to view this object') ?>"><i class="fas fa-eye me-1"></i><?php echo __('Set spot') ?></button>
                <button type="button" id="viewSpotClear" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Clear viewing spot (back to automatic)') ?>"><i class="fas fa-times"></i></button>
              </div>
              <small class="text-muted d-block mt-1"><?php echo __('Where the guided tour stands to view this object. Best for floor objects - wall art is framed head-on automatically.') ?></small>
            </div>
            <label for="selWall" class="form-label small mb-1"><?php echo __('Hang on wall') ?></label>
            <select id="selWall" class="form-select form-select-sm mb-2"></select>
            <button type="button" id="btnRemove" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-trash me-1"></i><?php echo __('Remove from twin') ?></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: canvas -->
    <div class="col-lg-9">
      <div class="card">
        <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" id="modeFloor" class="btn btn-primary"><?php echo __('Floor view') ?></button>
            <button type="button" id="modeWall" class="btn btn-outline-primary"><?php echo __('Wall view') ?></button>
          </div>
          <select id="wvWall" class="form-select form-select-sm d-none" style="max-width:160px;">
            <option value="north"><?php echo __('Back wall') ?></option>
            <option value="south"><?php echo __('Front wall') ?></option>
            <option value="west"><?php echo __('Left wall') ?></option>
            <option value="east"><?php echo __('Right wall') ?></option>
          </select>
          <button type="button" id="wvAddWin" class="btn btn-sm btn-outline-info d-none" title="<?php echo __('Add a window to this wall - then drag it to position, double-click to remove') ?>"><i class="fas fa-window-maximize me-1"></i><?php echo __('Add window') ?></button>
          <span class="small text-muted"><span id="saveState"><?php echo __('All changes saved') ?></span> <i id="saveIcon" class="fas fa-check text-success ms-1"></i></span>
        </div>
        <div class="card-body p-0" style="position:relative;">
          <div id="stageWrap" style="width:100%;background:#f4f4f4;border-radius:0 0 .375rem .375rem;overflow:hidden;"></div>
          <!-- Wall-view window editor: appears when a window is clicked -->
          <div id="winEdit" class="d-none" style="position:absolute;top:10px;right:10px;z-index:6;background:#fff;border:1px solid #adb5bd;border-radius:6px;padding:8px;box-shadow:0 2px 10px rgba(0,0,0,.2);width:172px;">
            <div class="small fw-bold mb-2"><i class="fas fa-window-maximize me-1 text-info"></i><?php echo __('Window') ?></div>
            <div class="input-group input-group-sm mb-1"><span class="input-group-text" style="width:52px"><?php echo __('Width') ?></span><input type="number" id="winEditW" class="form-control" min="0.4" max="6" step="0.1"><span class="input-group-text">m</span></div>
            <div class="input-group input-group-sm mb-1"><span class="input-group-text" style="width:52px"><?php echo __('Sill') ?></span><input type="number" id="winEditSill" class="form-control" min="0" max="3" step="0.1"><span class="input-group-text">m</span></div>
            <div class="input-group input-group-sm mb-2"><span class="input-group-text" style="width:52px"><?php echo __('Height') ?></span><input type="number" id="winEditH" class="form-control" min="0.3" max="3.5" step="0.1"><span class="input-group-text">m</span></div>
            <button type="button" id="winEditDel" class="btn btn-sm btn-danger w-100 mb-1"><i class="fas fa-trash me-1"></i><?php echo __('Remove window') ?></button>
            <button type="button" id="winEditClose" class="btn btn-sm btn-outline-secondary w-100"><?php echo __('Done') ?></button>
          </div>
        </div>
      </div>

      <?php if ($isAuth): ?>
      <!-- Room appearance & setup -->
      <div class="row g-3 mt-1">
        <div class="col-md-6 col-xl-3">
          <div class="card mb-0">
            <div class="card-header py-2"><strong><i class="fas fa-ruler-combined me-1"></i><?php echo __('Room size (m)') ?></strong></div>
            <div class="card-body">
              <div class="row g-1 mb-2">
                <div class="col-4"><input type="number" id="rdW" class="form-control form-control-sm" min="1" step="0.5" placeholder="W" value="<?php echo esc_entities($roomDims['w']) ?>"></div>
                <div class="col-4"><input type="number" id="rdD" class="form-control form-control-sm" min="1" step="0.5" placeholder="D" value="<?php echo esc_entities($roomDims['d']) ?>"></div>
                <div class="col-4"><input type="number" id="rdH" class="form-control form-control-sm" min="1" step="0.5" placeholder="H" value="<?php echo esc_entities($roomDims['h']) ?>"></div>
              </div>
              <button type="button" id="rdSave" class="btn btn-sm btn-outline-primary w-100"><?php echo __('Save room size') ?></button>
              <small class="text-muted d-block mt-1"><?php echo __('Width / Depth / wall Height. Raise H for taller walls.') ?></small>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="card mb-0">
            <div class="card-header py-2"><strong><i class="fas fa-grip-lines-vertical me-1"></i><?php echo __('Interior walls') ?></strong></div>
            <div class="card-body">
              <button type="button" id="wallAdd" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-plus me-1"></i><?php echo __('Add wall') ?></button>
              <div id="wallList" class="small"></div>
              <small id="wallHint" class="text-muted d-block mt-1"><?php echo __('Add a divider wall to hang objects in the middle of the room.') ?></small>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="card mb-0">
            <div class="card-header py-2"><strong><i class="fas fa-door-open me-1"></i><?php echo __('Doors') ?></strong></div>
            <div class="card-body">
              <button type="button" id="doorAdd" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-plus me-1"></i><?php echo __('Add door') ?></button>
              <div id="doorList" class="small"></div>
              <small class="text-muted d-block mt-1"><?php echo __('Doors mark openings on a wall so objects stay clear of them.') ?></small>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-xl-3">
          <div class="card mb-0">
            <div class="card-header py-2"><strong><i class="fas fa-share-nodes me-1"></i><?php echo __('Share') ?></strong></div>
            <div class="card-body small">
              <label class="form-label mb-1"><?php echo __('Embed this walkthrough') ?></label>
              <textarea id="embedSnippet" class="form-control form-control-sm" rows="3" readonly>&lt;iframe src="<?php echo esc_entities(url_for(['module' => 'exhibitionSpace', 'action' => 'walkthrough', 'slug' => $space->slug], true)) ?>" width="100%" height="600" style="border:0" allowfullscreen&gt;&lt;/iframe&gt;</textarea>
              <button type="button" id="embedCopy" class="btn btn-sm btn-outline-primary w-100 mt-1"><i class="fas fa-copy me-1"></i><?php echo __('Copy embed code') ?></button>
            </div>
          </div>
        </div>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/konva@9.3.6/konva.min.js"></script>
<script<?php echo $nonce ?> type="application/json" id="exhibBuilderData"><?php echo json_encode($bootData, $JSON) ?></script>
<script<?php echo $nonce ?>>
(function () {
  var BOOT = {};
  try { BOOT = JSON.parse(document.getElementById('exhibBuilderData').textContent) || {}; } catch (e) { BOOT = {}; }
  var URLS = BOOT.urls || {};
  var FLOORPLAN = BOOT.floorplan || null;
  var PLACEMENTS = BOOT.placements || [];
  var GUIDED_TOUR = BOOT.guidedTour || [];
  var TOUR_OBJECTS = BOOT.tourObjects || [];
  var WALLS = BOOT.walls || [];
  var DOORS = BOOT.doors || [];
  var WINDOWS = BOOT.windows || [];
  var SHAPE = BOOT.shape || null;
  var SPACE_ID = BOOT.spaceId || 0;
  var ROOM_W = BOOT.roomW || 18, ROOM_D = BOOT.roomD || 14, ROOM_H = BOOT.roomH || 4;

  function jhdr() { return { 'Content-Type': 'application/json', 'Accept': 'application/json' }; }
  function postJSON(url, body) {
    return fetch(url, { method: 'POST', headers: jhdr(), body: JSON.stringify(body || {}) }).then(function (r) { return r.json(); });
  }

  function allDoors() { return (DOORS || []); }   // manual-only doors (no auto-doorways on PSIS)
  function aspectH(w) { return Math.round(w * Math.max(0.35, Math.min(1.6, ROOM_D / ROOM_W))); }

  if (typeof Konva === 'undefined') {
    document.getElementById('stageWrap').innerHTML = '<div class="p-4 text-muted"><?php echo __('Canvas library failed to load.') ?></div>';
    return;
  }

  var wrap = document.getElementById('stageWrap');
  var W = Math.max(320, wrap.clientWidth || 800);
  var H = aspectH(W);
  var NODE = 90;

  var stage = new Konva.Stage({ container: 'stageWrap', width: W, height: H });
  var bgLayer = new Konva.Layer();
  var wallLayer = new Konva.Layer();
  var doorLayer = new Konva.Layer({ listening: false });
  var layer = new Konva.Layer();
  var routeLayer = new Konva.Layer({ listening: false });
  var viewLayer = new Konva.Layer({ listening: false });
  var wvLayer = new Konva.Layer({ visible: false });
  stage.add(bgLayer); stage.add(wallLayer); stage.add(doorLayer); stage.add(layer); stage.add(viewLayer); stage.add(routeLayer); stage.add(wvLayer);

  // Door indicators on the floor view.
  function drawDoorMarkers() {
    doorLayer.destroyChildren();
    allDoors().forEach(function (d) {
      var pts, lx, ly;
      if (typeof d.edge === 'number' && SHAPE && SHAPE.length >= 3) {
        var a = SHAPE[d.edge % SHAPE.length], b = SHAPE[(d.edge + 1) % SHAPE.length];
        if (!a || !b) return;
        var ax = a.x * W, ay = a.z * H, bx = b.x * W, by = b.z * H;
        var ex = bx - ax, ey = by - ay, ep = Math.hypot(ex, ey) || 1, ux = ex / ep, uy = ey / ep;
        var em = Math.hypot((b.x - a.x) * ROOM_W, (b.z - a.z) * ROOM_D) || 1;
        var dlen = Math.min((d.width || 1.6) / em, 1) * ep;
        var pos = (d.pos == null ? 0.5 : d.pos), cxp = ax + ex * pos, cyp = ay + ey * pos;
        pts = [cxp - ux * dlen / 2, cyp - uy * dlen / 2, cxp + ux * dlen / 2, cyp + uy * dlen / 2];
        lx = cxp - 16; ly = cyp - 6;
      } else {
        var horiz = (d.wall === 'north' || d.wall === 'south');
        var lenPx = (d.width / (horiz ? ROOM_W : ROOM_D)) * (horiz ? W : H);
        if (d.wall === 'north') { var x = d.pos * W; pts = [x - lenPx / 2, 0, x + lenPx / 2, 0]; lx = x - 16; ly = 2; }
        else if (d.wall === 'south') { var xs = d.pos * W; pts = [xs - lenPx / 2, H, xs + lenPx / 2, H]; lx = xs - 16; ly = H - 16; }
        else if (d.wall === 'west') { var y = d.pos * H; pts = [0, y - lenPx / 2, 0, y + lenPx / 2]; lx = 6; ly = y - 6; }
        else { var ye = d.pos * H; pts = [W, ye - lenPx / 2, W, ye + lenPx / 2]; lx = W - 38; ly = ye - 6; }
      }
      var dcol = '#198754';
      doorLayer.add(new Konva.Line({ points: pts, stroke: dcol, strokeWidth: 8, lineCap: 'round', opacity: 0.9 }));
      doorLayer.add(new Konva.Text({ x: lx, y: ly, width: 36, align: 'center', text: '<?php echo __('door') ?>', fontSize: 9, fill: dcol }));
    });
    doorLayer.draw();
  }

  // Background mirrors the room's plan footprint.
  function drawBackground() {
    bgLayer.destroyChildren();
    var shaped = (SHAPE && SHAPE.length >= 3);
    bgLayer.add(new Konva.Rect({ x: 0, y: 0, width: W, height: H, fill: '#dfe2e6', listening: false }));
    var clipFn = shaped ? function (ctx) {
      ctx.beginPath(); ctx.moveTo(SHAPE[0].x * W, SHAPE[0].z * H);
      for (var i = 1; i < SHAPE.length; i++) ctx.lineTo(SHAPE[i].x * W, SHAPE[i].z * H);
      ctx.closePath();
    } : undefined;
    var roomBg = new Konva.Group(clipFn ? { clipFunc: clipFn } : {});
    bgLayer.add(roomBg);
    roomBg.add(new Konva.Rect({ x: 0, y: 0, width: W, height: H, fill: '#ffffff', listening: false }));
    if (FLOORPLAN) {
      var bg = new Image();
      bg.onload = function () { roomBg.add(new Konva.Image({ image: bg, x: 0, y: 0, width: W, height: H, listening: false })); bgLayer.draw(); };
      bg.src = FLOORPLAN;
    } else {
      var grid = 40;
      for (var gx = 0; gx <= W; gx += grid) roomBg.add(new Konva.Line({ points: [gx, 0, gx, H], stroke: '#e3e3e3', strokeWidth: 1, listening: false }));
      for (var gy = 0; gy <= H; gy += grid) roomBg.add(new Konva.Line({ points: [0, gy, W, gy], stroke: '#e3e3e3', strokeWidth: 1, listening: false }));
    }
    function wallBadge(mx, my, num) {
      bgLayer.add(new Konva.Circle({ x: mx, y: my, radius: 9, fill: '#0d6efd', opacity: 0.85, listening: false }));
      bgLayer.add(new Konva.Text({ x: mx - 9, y: my - 6, width: 18, align: 'center', text: '' + num, fontSize: 11, fill: '#fff', listening: false }));
    }
    if (shaped) {
      var pts = []; SHAPE.forEach(function (p) { pts.push(p.x * W, p.z * H); });
      bgLayer.add(new Konva.Line({ points: pts, closed: true, stroke: '#0d6efd', strokeWidth: 2, listening: false }));
      var cxp = 0, cyp = 0; SHAPE.forEach(function (p) { cxp += p.x; cyp += p.z; }); cxp = cxp / SHAPE.length * W; cyp = cyp / SHAPE.length * H;
      for (var e = 0; e < SHAPE.length; e++) {
        var pa = SHAPE[e], pb = SHAPE[(e + 1) % SHAPE.length];
        var mx = (pa.x + pb.x) / 2 * W, my = (pa.z + pb.z) / 2 * H;
        var dx = cxp - mx, dy = cyp - my, dl = Math.hypot(dx, dy) || 1; mx += dx / dl * 14; my += dy / dl * 14;
        wallBadge(mx, my, e + 1);
      }
    } else {
      wallBadge(W / 2, 16, 1); wallBadge(W / 2, H - 16, 2); wallBadge(16, H / 2, 3); wallBadge(W - 16, H / 2, 4);
    }
    bgLayer.draw();
  }
  drawBackground();

  var tr = new Konva.Transformer({ rotateEnabled: true, enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'], keepRatio: true });
  layer.add(tr);
  var selected = null;

  // ---- save (debounced) ----
  var dirty = false, saveTimer = null;
  function setState(t, ok) {
    document.getElementById('saveState').textContent = t;
    var ic = document.getElementById('saveIcon');
    ic.className = ok ? 'fas fa-check text-success ms-1' : 'fas fa-circle-notch fa-spin text-warning ms-1';
  }
  function scheduleSave() {
    dirty = true; setState('<?php echo __('Saving...') ?>', false);
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(saveLayout, 700);
  }
  function saveLayout() {
    var positions = layer.find('.placement').map(function (g) {
      return { id: g.getAttr('placementId'), pos_x: g.x() / W, pos_y: g.y() / H, rotation_deg: g.rotation(), scale: g.scaleX(), z_order: g.zIndex() };
    });
    postJSON(URLS.layout, { positions: positions })
      .then(function () { dirty = false; setState('<?php echo __('All changes saved') ?>', true); })
      .catch(function () { setState('<?php echo __('Save failed - retrying') ?>', false); setTimeout(saveLayout, 2000); });
  }

  function selectNode(g) {
    if (typeof setViewSpotMode === 'function') setViewSpotMode(false);
    selected = g; tr.nodes([g]);
    document.getElementById('selPanel').classList.add('d-none');
    document.getElementById('selControls').classList.remove('d-none');
    document.getElementById('selTitle').textContent = g.getAttr('titleText') || '';
    document.getElementById('selSize').value = g.getAttr('sizeUnits') != null ? g.getAttr('sizeUnits') : 0;
    var is3d = g.getAttr('objKind') === '3d';
    document.getElementById('tiltControls').classList.toggle('d-none', !is3d);
    if (is3d) {
      var tx = g.getAttr('tiltX'); var tz = g.getAttr('tiltZ');
      document.getElementById('tiltX').value = (tx === null || tx === undefined) ? '' : tx;
      document.getElementById('tiltZ').value = (tz === null || tz === undefined) ? '' : tz;
    }
    refreshWallOptions();
    document.getElementById('selWall').value = g.getAttr('wallKey') || '';
    setSpotBtn((+g.getAttr('spotlight')) || 0);
    var cb = document.getElementById('caseBtn'); if (cb) { var con = !!g.getAttr('displayCase'); cb.classList.toggle('btn-info', con); cb.classList.toggle('btn-outline-info', !con); }
    var fb = document.getElementById('floorBtn'); if (fb) { var fon = !!g.getAttr('onFloor'); fb.classList.toggle('btn-success', fon); fb.classList.toggle('btn-outline-success', !fon); }
    layer.draw();
  }
  // Spotlight mode button: 0 off, 1 light on approach, 2 always-on.
  function setSpotBtn(m) {
    var sb = document.getElementById('spotBtn'); if (!sb) return;
    m = (+m) || 0;
    var txt = ['<?php echo __('Spot off') ?>', '<?php echo __('Spot: approach') ?>', '<?php echo __('Spot: always') ?>'][m];
    sb.classList.toggle('btn-warning', m > 0);
    sb.classList.toggle('btn-outline-warning', m === 0);
    sb.innerHTML = '<i class="fas fa-lightbulb me-1"></i>' + txt;
    sb.title = txt;
  }
  function clearSelect() {
    selected = null; tr.nodes([]);
    document.getElementById('selPanel').classList.remove('d-none');
    document.getElementById('selControls').classList.add('d-none');
    layer.draw();
  }

  // ---- build a placement node ----
  function addNode(p) {
    var px = (p.pos_x !== null && p.pos_x !== undefined) ? p.pos_x * W : W / 2;
    var py = (p.pos_y !== null && p.pos_y !== undefined) ? p.pos_y * H : H / 2;
    var g = new Konva.Group({ x: px, y: py, draggable: true, name: 'placement', rotation: p.rotation_deg || 0, scaleX: p.scale || 1, scaleY: p.scale || 1 });
    g.setAttr('placementId', p.id);
    g.setAttr('ioId', p.information_object_id);
    g.setAttr('titleText', p.title);
    g.setAttr('sizeUnits', p.size_units_used != null ? p.size_units_used : 0);
    g.setAttr('objKind', p.kind || null);
    g.setAttr('wallKey', p.wall_or_zone || '');
    g.setAttr('tiltX', (p.tilt_x === null || p.tilt_x === undefined) ? null : p.tilt_x);
    g.setAttr('tiltZ', (p.tilt_z === null || p.tilt_z === undefined) ? null : p.tilt_z);
    g.setAttr('spotlight', (+p.spotlight) || 0);
    g.setAttr('displayCase', (+p.display_case) || 0);
    g.setAttr('onFloor', (+p.on_floor) || 0);
    g.setAttr('zOrder', p.z_order || 0);
    g.setAttr('viewX', (p.view_x === null || p.view_x === undefined) ? null : +p.view_x);
    g.setAttr('viewY', (p.view_y === null || p.view_y === undefined) ? null : +p.view_y);

    var rect = new Konva.Rect({ x: -NODE / 2, y: -NODE / 2, width: NODE, height: NODE, fill: '#ffffff', stroke: '#6c757d', strokeWidth: 1, cornerRadius: 4, shadowColor: '#000', shadowBlur: 6, shadowOpacity: 0.15, shadowOffset: { x: 0, y: 2 } });
    g.add(rect);

    if (p.thumb_url) {
      var img = new Image();
      img.onload = function () {
        var ki = new Konva.Image({ image: img, x: -NODE / 2 + 3, y: -NODE / 2 + 3, width: NODE - 6, height: NODE - 6, cornerRadius: 3 });
        g.add(ki); ki.moveToBottom(); rect.moveToBottom(); layer.draw();
      };
      img.onerror = function () {
        g.add(new Konva.Text({ text: '#' + p.information_object_id, x: -NODE / 2, y: -8, width: NODE, align: 'center', fontSize: 11, fill: '#999' }));
        layer.draw();
      };
      img.src = p.thumb_url;
    } else {
      g.add(new Konva.Text({ text: '#' + p.information_object_id, x: -NODE / 2, y: -8, width: NODE, align: 'center', fontSize: 11, fill: '#999' }));
    }

    var label = new Konva.Label({ x: 0, y: NODE / 2 + 4 });
    label.add(new Konva.Tag({ fill: 'rgba(33,37,41,0.85)', cornerRadius: 3, pointerDirection: 'up', pointerWidth: 6, pointerHeight: 4 }));
    label.add(new Konva.Text({ text: (p.title || '').substring(0, 28), fontSize: 11, padding: 3, fill: '#fff' }));
    label.offsetX(label.width() / 2);
    g.add(label);

    g.on('click tap', function (e) {
      e.cancelBubble = true;
      if (window.__exhibRouteMode && typeof window.__exhibAddStop === 'function') {
        window.__exhibAddStop(g.getAttr('ioId'));
      } else {
        selectNode(g);
      }
    });
    g.on('dragmove', function () { if (window.__exhibRouteMode) drawTourRoute(); drawViewSpots(); });
    g.on('dragend', scheduleSave);
    g.on('transformend', scheduleSave);
    layer.add(g);
    return g;
  }

  // ---- Guided-tour visual route overlay ----
  function nodeByIo(io) {
    var hit = null;
    layer.find('.placement').forEach(function (n) { if (String(n.getAttr('ioId')) === String(io)) hit = n; });
    return hit;
  }
  function drawTourRoute() {
    routeLayer.destroyChildren();
    if (!window.__exhibRouteMode || !layer.visible()) { routeLayer.draw(); return; }
    var ios = (typeof window.__exhibStops === 'function') ? (window.__exhibStops() || []) : [];
    var pts = [], seq = [];
    ios.forEach(function (io) { var n = nodeByIo(io); if (n) { pts.push(n.x(), n.y()); seq.push({ x: n.x(), y: n.y() }); } });
    if (pts.length >= 4) {
      routeLayer.add(new Konva.Line({ points: pts, stroke: '#0d6efd', strokeWidth: 3, opacity: 0.85, lineJoin: 'round', lineCap: 'round', dash: [10, 6] }));
    }
    seq.forEach(function (p, i) {
      var grp = new Konva.Group({ x: p.x, y: p.y, listening: false });
      grp.add(new Konva.Circle({ radius: 11, fill: '#0d6efd', stroke: '#fff', strokeWidth: 2 }));
      grp.add(new Konva.Text({ text: '' + (i + 1), x: -11, y: -6, width: 22, align: 'center', fontSize: 12, fontStyle: 'bold', fill: '#fff' }));
      routeLayer.add(grp);
    });
    routeLayer.draw();
  }
  window.__exhibDrawRoute = drawTourRoute;

  // ---- Tour viewing-spot markers ----
  function drawViewSpots() {
    viewLayer.destroyChildren();
    layer.find('.placement').forEach(function (n) {
      var vx = n.getAttr('viewX'), vy = n.getAttr('viewY');
      if (vx === null || vx === undefined || vy === null || vy === undefined) return;
      var ex = vx * W, ey = vy * H;
      viewLayer.add(new Konva.Line({ points: [ex, ey, n.x(), n.y()], stroke: '#0d6efd', strokeWidth: 1.5, dash: [6, 4], opacity: 0.7 }));
      var grp = new Konva.Group({ x: ex, y: ey });
      grp.add(new Konva.Circle({ radius: 9, fill: '#0d6efd', stroke: '#fff', strokeWidth: 2 }));
      grp.add(new Konva.Circle({ radius: 3, fill: '#fff' }));
      viewLayer.add(grp);
    });
    viewLayer.draw();
  }
  function setViewSpotMode(on) {
    window.__viewSpotMode = on;
    var b = document.getElementById('viewSpotBtn');
    if (b) { b.classList.toggle('btn-primary', on); b.classList.toggle('btn-outline-primary', !on); b.innerHTML = on ? '<i class="fas fa-crosshairs me-1"></i><?php echo __('Click the plan...') ?>' : '<i class="fas fa-eye me-1"></i><?php echo __('Set spot') ?>'; }
    stage.container().style.cursor = on ? 'crosshair' : '';
  }

  PLACEMENTS.forEach(addNode);
  layer.draw();
  drawViewSpots();
  renderObjList();

  // ---- interior walls ----
  var wallAdding = false, wallStart = null;
  var wallBtn = document.getElementById('wallAdd');
  var wallHintEl = document.getElementById('wallHint');
  var HINT_IDLE = '<?php echo __('Add a divider wall to hang objects in the middle of the room.') ?>';
  function setWallMode(on) {
    wallAdding = on; wallStart = null;
    if (!wallBtn) return;
    wallBtn.classList.toggle('btn-primary', on); wallBtn.classList.toggle('btn-outline-primary', !on);
    wallHintEl.textContent = on ? '<?php echo __('Click the start point, then the end point.') ?>' : HINT_IDLE;
    stage.container().style.cursor = on ? 'crosshair' : 'default';
  }
  function redrawWalls() {
    wallLayer.destroyChildren();
    WALLS.forEach(function (w) {
      wallLayer.add(new Konva.Line({ points: [w.x1 * W, w.z1 * H, w.x2 * W, w.z2 * H], stroke: '#495057', strokeWidth: 7, lineCap: 'round', listening: false }));
    });
    wallLayer.draw();
    renderWallList();
  }
  function renderWallList() {
    var el = document.getElementById('wallList'); if (!el) return; el.innerHTML = '';
    WALLS.forEach(function (w, i) {
      var row = document.createElement('div');
      row.className = 'd-flex justify-content-between align-items-center mb-1';
      row.innerHTML = '<span><?php echo __('Wall') ?> ' + (i + 1) + '</span>';
      var del = document.createElement('button');
      del.type = 'button'; del.className = 'btn btn-sm btn-outline-danger py-0'; del.innerHTML = '<i class="fas fa-times"></i>';
      del.addEventListener('click', function () { WALLS.splice(i, 1); saveWalls(); });
      row.appendChild(del); el.appendChild(row);
    });
  }
  function saveWalls() {
    postJSON(URLS.planWalls, { walls: WALLS }).then(function (d) { if (d && d.walls) { WALLS = d.walls; } redrawWalls(); refreshWallOptions(); })
      .catch(function () { redrawWalls(); refreshWallOptions(); });
  }
  function refreshWallOptions() {
    var sel = document.getElementById('selWall'); if (!sel) return; var cur = sel.value;
    var opts = '<option value=""><?php echo __('Auto (nearest)') ?></option>';
    if (SHAPE && SHAPE.length >= 3) {
      for (var e = 0; e < SHAPE.length; e++) opts += '<option value="edge:' + e + '"><?php echo __('Wall') ?> ' + (e + 1) + '</option>';
    } else {
      opts += '<option value="north"><?php echo __('Wall') ?> 1</option><option value="south"><?php echo __('Wall') ?> 2</option>' +
        '<option value="west"><?php echo __('Wall') ?> 3</option><option value="east"><?php echo __('Wall') ?> 4</option>';
    }
    WALLS.forEach(function (w, i) {
      opts += '<option value="' + w.id + '"><?php echo __('Interior') ?> ' + (i + 1) + ' <?php echo __('(front)') ?></option>';
      opts += '<option value="' + w.id + '|b"><?php echo __('Interior') ?> ' + (i + 1) + ' <?php echo __('(back)') ?></option>';
    });
    sel.innerHTML = opts; sel.value = cur;
  }
  if (wallBtn) wallBtn.addEventListener('click', function () { setWallMode(!wallAdding); });
  (function () {
    var sw = document.getElementById('selWall');
    if (sw) sw.addEventListener('change', function () {
      if (!selected) return;
      selected.setAttr('wallKey', this.value);
      postJSON(URLS.wall, { id: selected.getAttr('placementId'), wall: this.value });
    });
  })();
  redrawWalls(); refreshWallOptions(); drawDoorMarkers();

  // ---- doors editor (CONTRACT planDoors) ----
  function renderDoorList() {
    var el = document.getElementById('doorList'); if (!el) return; el.innerHTML = '';
    DOORS.forEach(function (d, i) {
      var row = document.createElement('div');
      row.className = 'd-flex justify-content-between align-items-center mb-1';
      var lbl = d.wall ? d.wall : ('edge ' + ((d.edge || 0) + 1));
      row.innerHTML = '<span class="text-capitalize">' + lbl + '</span>';
      var del = document.createElement('button');
      del.type = 'button'; del.className = 'btn btn-sm btn-outline-danger py-0'; del.innerHTML = '<i class="fas fa-times"></i>';
      del.addEventListener('click', function () { DOORS.splice(i, 1); saveDoors(); });
      row.appendChild(del); el.appendChild(row);
    });
  }
  function saveDoors() {
    postJSON(URLS.planDoors, { doors: DOORS }).then(function () { drawDoorMarkers(); renderDoorList(); })
      .catch(function () { drawDoorMarkers(); renderDoorList(); });
  }
  (function () {
    var b = document.getElementById('doorAdd'); if (!b) return;
    b.addEventListener('click', function () {
      DOORS.push({ wall: 'north', pos: 0.5, width: 1.6 });
      saveDoors();
    });
    renderDoorList();
  })();

  // Save room size (width / depth / wall height) — CONTRACT roomDims body {w,d,h}.
  var rdBtn = document.getElementById('rdSave');
  if (rdBtn) rdBtn.addEventListener('click', function () {
    var body = {
      w: document.getElementById('rdW').value || null,
      d: document.getElementById('rdD').value || null,
      h: document.getElementById('rdH').value || null
    };
    rdBtn.disabled = true;
    postJSON(URLS.roomDims, body).then(function (d) {
      if (d && d.room) { ROOM_W = d.room.w || ROOM_W; ROOM_D = d.room.d || ROOM_D; ROOM_H = d.room.h || ROOM_H; }
      rdBtn.textContent = '<?php echo __('Saved') ?>';
      setTimeout(function () { rdBtn.textContent = '<?php echo __('Save room size') ?>'; rdBtn.disabled = false; }, 1200);
    }).catch(function () { rdBtn.disabled = false; });
  });

  stage.on('click tap', function (e) {
    if (window.__viewSpotMode && selected) {
      var vp = stage.getPointerPosition(); if (!vp) return;
      var vx = Math.max(0, Math.min(1, vp.x / W)), vy = Math.max(0, Math.min(1, vp.y / H));
      selected.setAttr('viewX', vx); selected.setAttr('viewY', vy);
      postJSON(URLS.viewSpot, { id: selected.getAttr('placementId'), view_x: vx, view_y: vy });
      setViewSpotMode(false); drawViewSpots();
      return;
    }
    if (wallAdding) {
      var p = stage.getPointerPosition(); if (!p) return;
      if (!wallStart) { wallStart = { x: p.x / W, z: p.y / H }; }
      else {
        WALLS.push({ id: 'wall-' + Date.now(), x1: wallStart.x, z1: wallStart.z, x2: p.x / W, z2: p.y / H });
        setWallMode(false); saveWalls();
      }
      return;
    }
    if (e.target === stage) clearSelect();
  });

  // ---- selected-object controls ----
  document.querySelectorAll('#selControls [data-act]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (!selected) return;
      var a = b.getAttribute('data-act');
      if (a === 'rotL') selected.rotation(selected.rotation() - 15);
      if (a === 'rotR') selected.rotation(selected.rotation() + 15);
      if (a === 'smaller') { var s = Math.max(0.3, selected.scaleX() - 0.1); selected.scale({ x: s, y: s }); }
      if (a === 'bigger') { var s2 = Math.min(4, selected.scaleX() + 0.1); selected.scale({ x: s2, y: s2 }); }
      if (a === 'spot') {
        var cur = (+selected.getAttr('spotlight')) || 0, m = (cur + 1) % 3;
        selected.setAttr('spotlight', m); setSpotBtn(m);
        postJSON(URLS.spotlight, { id: selected.getAttr('placementId'), mode: m });
        return;
      }
      if (a === 'case') {
        var con = !selected.getAttr('displayCase'); selected.setAttr('displayCase', con);
        var cb = document.getElementById('caseBtn'); if (cb) { cb.classList.toggle('btn-info', con); cb.classList.toggle('btn-outline-info', !con); }
        postJSON(URLS.displayCase, { id: selected.getAttr('placementId'), on: con });
        return;
      }
      if (a === 'floor') {
        var fon = !selected.getAttr('onFloor'); selected.setAttr('onFloor', fon);
        var fb = document.getElementById('floorBtn'); if (fb) { fb.classList.toggle('btn-success', fon); fb.classList.toggle('btn-outline-success', !fon); }
        postJSON(URLS.onFloor, { id: selected.getAttr('placementId'), on: fon });
        return;
      }
      if (a === 'front' || a === 'back') {
        var zmax = 0, zmin = 0; layer.find('.placement').forEach(function (n) { var z = n.getAttr('zOrder') || 0; if (z > zmax) zmax = z; if (z < zmin) zmin = z; });
        var nz = (a === 'front') ? zmax + 1 : zmin - 1; selected.setAttr('zOrder', nz);
        if (a === 'front') selected.moveToTop(); else selected.moveToBottom();
        layer.draw();
        postJSON(URLS.zorder, { id: selected.getAttr('placementId'), z: nz });
        return;
      }
      layer.draw(); scheduleSave();
    });
  });
  function nodeForPlacement(id) {
    var hit = null;
    layer.find('.placement').forEach(function (n) { if (String(n.getAttr('placementId')) === String(id)) hit = n; });
    return hit;
  }
  function removePlacementById(id, row) {
    if (id == null) return;
    postJSON(URLS.remove, { id: id }).then(function (d) {
      if (!d.ok) return;
      var node = nodeForPlacement(id);
      if (node) { if (selected === node) clearSelect(); node.destroy(); layer.draw(); }
      if (row && row.parentNode) row.parentNode.removeChild(row);
      renderObjList();
    });
  }
  function removePlacement(g) { if (g) removePlacementById(g.getAttr('placementId')); }
  document.getElementById('btnRemove').addEventListener('click', function () { removePlacement(selected); });

  // Viewing-spot arm / clear.
  (function () {
    var setBtn = document.getElementById('viewSpotBtn'), clrBtn = document.getElementById('viewSpotClear');
    if (setBtn) setBtn.addEventListener('click', function () { if (!selected) return; setViewSpotMode(!window.__viewSpotMode); });
    if (clrBtn) clrBtn.addEventListener('click', function () {
      if (!selected) return;
      selected.setAttr('viewX', null); selected.setAttr('viewY', null);
      postJSON(URLS.viewSpot, { id: selected.getAttr('placementId'), view_x: null, view_y: null });
      setViewSpotMode(false); drawViewSpots();
    });
  })();

  // Full list of EVERY placed object in this space (server-sourced).
  function renderObjList() {
    var el = document.getElementById('objList'), cnt = document.getElementById('objCount');
    if (!el) return;
    fetch(URLS.placements, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var items = (d && d.placements) || [];
        if (cnt) cnt.textContent = items.length;
        if (!items.length) { el.innerHTML = '<?php echo __('None yet.') ?>'; return; }
        el.innerHTML = '';
        items.forEach(function (p) {
          var row = document.createElement('div');
          row.className = 'd-flex justify-content-between align-items-center mb-1';
          var name = document.createElement('a');
          name.href = '#'; name.className = 'text-truncate me-2 text-decoration-none'; name.style.maxWidth = '170px';
          var onCanvas = !!nodeForPlacement(p.id);
          name.textContent = (p.title || ('#' + p.id)) + (onCanvas ? '' : ' ⚠');
          name.title = onCanvas ? '<?php echo __('Select on canvas') ?>' : '<?php echo __('Off-canvas / no position - use Remove') ?>';
          name.addEventListener('click', function (e) { e.preventDefault(); var nd = nodeForPlacement(p.id); if (nd) selectNode(nd); });
          var del = document.createElement('button');
          del.type = 'button'; del.className = 'btn btn-sm btn-outline-danger py-0'; del.title = '<?php echo __('Remove from twin') ?>';
          del.innerHTML = '<i class="fas fa-times"></i>';
          del.addEventListener('click', function () { removePlacementById(p.id, row); });
          row.appendChild(name); row.appendChild(del); el.appendChild(row);
        });
      }).catch(function () { el.innerHTML = '<span class="text-danger"><?php echo __('Could not load list.') ?></span>'; });
  }

  // ---- edit size of the selected object (CONTRACT body {id,size_units}) ----
  document.getElementById('selSize').addEventListener('change', function () {
    if (!selected) return;
    var v = Math.max(0, parseFloat(this.value) || 0);
    selected.setAttr('sizeUnits', v);
    postJSON(URLS.size, { id: selected.getAttr('placementId'), size_units: v });
  });

  // ---- edit 3D orientation (tilt) — CONTRACT body {id,tilt_x,tilt_z} ----
  function saveTilt() {
    if (!selected) return;
    var xs = document.getElementById('tiltX').value;
    var zs = document.getElementById('tiltZ').value;
    var tx = xs === '' ? null : (parseFloat(xs) || 0);
    var tz = zs === '' ? null : (parseFloat(zs) || 0);
    selected.setAttr('tiltX', tx); selected.setAttr('tiltZ', tz);
    postJSON(URLS.tilt, { id: selected.getAttr('placementId'), tilt_x: tx, tilt_z: tz });
  }
  document.getElementById('tiltX').addEventListener('change', saveTilt);
  document.getElementById('tiltZ').addEventListener('change', saveTilt);
  document.getElementById('tiltAuto').addEventListener('click', function () {
    document.getElementById('tiltX').value = ''; document.getElementById('tiltZ').value = '';
    saveTilt();
  });

  // ---- add object via search (JSON autocomplete: /api/autocomplete/glam) ----
  if (typeof TomSelect !== 'undefined') {
    new TomSelect('#objectSearch', {
      valueField: 'id', labelField: 'label', searchField: ['label'],
      placeholder: '<?php echo __('Type to search information objects...') ?>',
      maxItems: 1, maxOptions: 15,
      load: function (q, cb) {
        if (q.length < 2) return cb();
        fetch(URLS.autocomplete + '?q=' + encodeURIComponent(q) + '&limit=15', { headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (rows) { cb(Array.isArray(rows) ? rows : []); })
          .catch(function () { cb(); });
      },
      render: { option: function (d, e) { return '<div>' + e(d.label || d.value || ('#' + d.id)) + '</div>'; } },
      onChange: function (val) {
        if (!val) return;
        var self = this;
        var done = function () { self.clear(true); self.clearOptions(); };
        if (mode === 'wall') {
          // TODO(parity-phase-2): wall-view placement (wallPlace/wallPos) is not in the
          // builder CONTRACT. Fall back to a floor placement so the object is still added.
        }
        postJSON(URLS.place, { information_object_id: val, pos_x: 0.5, pos_y: 0.5, size_units: parseFloat(document.getElementById('initialSize').value) || 0 })
          .then(function (d) { if (d.ok) { var g = addNode(d.placement); layer.draw(); selectNode(g); renderObjList(); } done(); })
          .catch(done);
      }
    });
  }

  // ---- Wall view (elevation editor) ----
  // Faithful elevation editor. Object u/v persistence + wall-place are NOT in the builder
  // CONTRACT, so dragging items here is local-only (TODO parity-phase-2). Windows DO persist
  // via the CONTRACT planWindows action.
  var mode = 'floor', WV_NODE = 70;
  var wvWall = (SHAPE && SHAPE.length >= 3) ? 'edge:0' : 'north';
  (function () {
    var sel = document.getElementById('wvWall'), html = '';
    if (SHAPE && SHAPE.length >= 3) {
      for (var e = 0; e < SHAPE.length; e++) html += '<option value="edge:' + e + '"><?php echo __('Wall') ?> ' + (e + 1) + '</option>';
    } else {
      html = '<option value="north"><?php echo __('Wall') ?> 1</option><option value="south"><?php echo __('Wall') ?> 2</option>' +
        '<option value="west"><?php echo __('Wall') ?> 3</option><option value="east"><?php echo __('Wall') ?> 4</option>';
    }
    WALLS.forEach(function (w, i) {
      html += '<option value="' + w.id + '"><?php echo __('Interior') ?> ' + (i + 1) + ' <?php echo __('(front)') ?></option>';
      html += '<option value="' + w.id + '|b"><?php echo __('Interior') ?> ' + (i + 1) + ' <?php echo __('(back)') ?></option>';
    });
    sel.innerHTML = html; sel.value = wvWall;
  })();
  function setMode(m) {
    mode = m;
    var fb = document.getElementById('modeFloor'), wb = document.getElementById('modeWall');
    fb.classList.toggle('btn-primary', m === 'floor'); fb.classList.toggle('btn-outline-primary', m !== 'floor');
    wb.classList.toggle('btn-primary', m === 'wall'); wb.classList.toggle('btn-outline-primary', m !== 'wall');
    document.getElementById('wvWall').classList.toggle('d-none', m !== 'wall');
    if (_wvAddWin) { _wvAddWin.classList.toggle('d-none', m !== 'wall'); refreshAddWinBtn(); }
    if (typeof closeWinEdit === 'function') closeWinEdit();
    var floorOn = (m === 'floor');
    bgLayer.visible(floorOn); wallLayer.visible(floorOn); doorLayer.visible(floorOn); layer.visible(floorOn); viewLayer.visible(floorOn); routeLayer.visible(floorOn); wvLayer.visible(!floorOn);
    tr.nodes([]); clearSelect();
    if (floorOn) { stage.draw(); drawTourRoute(); } else { buildWallView(); }
  }
  document.getElementById('modeFloor').addEventListener('click', function () { setMode('floor'); });
  document.getElementById('modeWall').addEventListener('click', function () { setMode('wall'); });
  document.getElementById('wvWall').addEventListener('change', function () { wvWall = this.value; refreshAddWinBtn(); if (typeof closeWinEdit === 'function') closeWinEdit(); buildWallView(); });

  function wvWallLengthM() {
    if (wvWall === 'north' || wvWall === 'south') return ROOM_W;
    if (wvWall === 'west' || wvWall === 'east') return ROOM_D;
    if (wvWall.indexOf && wvWall.indexOf('edge:') === 0 && SHAPE) {
      var i = parseInt(wvWall.slice(5), 10), pa = SHAPE[i], pb = SHAPE[(i + 1) % SHAPE.length];
      if (pa && pb) return Math.hypot((pb.x - pa.x) * ROOM_W, (pb.z - pa.z) * ROOM_D);
    }
    var baseW = (wvWall.indexOf && wvWall.slice(-2) === '|b') ? wvWall.slice(0, -2) : wvWall;
    var w = WALLS.filter(function (ww) { return ww.id === baseW; })[0];
    if (w) return Math.hypot((w.x2 - w.x1) * ROOM_W, (w.z2 - w.z1) * ROOM_D);
    return ROOM_W;
  }
  function doorEdge(d) {
    if (typeof d.edge === 'number') return { edge: d.edge, t: (d.pos == null ? 0.5 : d.pos) };
    if (!(SHAPE && SHAPE.length >= 3)) return null;
    var p = (d.pos == null ? 0.5 : d.pos), P;
    if (d.wall === 'north') P = { x: p, z: 0 };
    else if (d.wall === 'south') P = { x: p, z: 1 };
    else if (d.wall === 'east') P = { x: 1, z: p };
    else if (d.wall === 'west') P = { x: 0, z: p };
    else return null;
    var best = -1, bd = 1e9, bt = 0.5;
    for (var i = 0; i < SHAPE.length; i++) {
      var a = SHAPE[i], b = SHAPE[(i + 1) % SHAPE.length];
      var dx = b.x - a.x, dz = b.z - a.z, L2 = dx * dx + dz * dz || 1;
      var t = Math.max(0, Math.min(1, ((P.x - a.x) * dx + (P.z - a.z) * dz) / L2));
      var dist = Math.hypot(P.x - (a.x + t * dx), P.z - (a.z + t * dz));
      if (dist < bd) { bd = dist; best = i; bt = t; }
    }
    return { edge: best, t: bt };
  }
  function wvDoorsForWall() {
    var edgeMode = (wvWall.indexOf && wvWall.indexOf('edge:') === 0);
    if (!edgeMode) return allDoors().filter(function (d) { return d.wall === wvWall; });
    return allDoors().map(function (d) {
      var m = doorEdge(d); if (!m || ('edge:' + m.edge) !== wvWall) return null;
      return { width: d.width, pos: m.t };
    }).filter(Boolean);
  }
  function wvCanWindow() {
    return wvWall === 'north' || wvWall === 'south' || wvWall === 'east' || wvWall === 'west'
      || (wvWall.indexOf && wvWall.indexOf('edge:') === 0);
  }
  function wvWindowsForWall() {
    return (WINDOWS || []).filter(function (w) {
      return (w.wall && w.wall === wvWall) || (typeof w.edge === 'number' && ('edge:' + w.edge) === wvWall);
    });
  }
  function persistWindows() {
    setState('<?php echo __('Saving...') ?>', false);
    postJSON(URLS.planWindows, { windows: WINDOWS })
      .then(function (d) { if (d && d.windows) WINDOWS = d.windows; setState('<?php echo __('All changes saved') ?>', true); })
      .catch(function () { setState('<?php echo __('Save failed - retrying') ?>', false); setTimeout(persistWindows, 2000); });
  }
  function addWindowToWall() {
    if (!wvCanWindow()) { alert('<?php echo __('Windows go on a perimeter wall - pick Back/Front/Left/Right or a numbered wall.') ?>'); return; }
    var w = { pos: 0.5, width: 1.2, sill: 0.9, height: 1.3 };
    if (wvWall.indexOf('edge:') === 0) w.edge = parseInt(wvWall.slice(5), 10); else w.wall = wvWall;
    WINDOWS.push(w); persistWindows(); buildWallView();
  }
  function refreshAddWinBtn() {
    if (!_wvAddWin) return;
    var ok = wvCanWindow();
    _wvAddWin.disabled = !ok; _wvAddWin.classList.toggle('disabled', !ok);
    _wvAddWin.title = ok ? '<?php echo __('Add a window to this wall - drag to position, double-click to remove') ?>' : '<?php echo __('Windows go on perimeter walls, not interior dividers') ?>';
  }
  var _wvAddWin = document.getElementById('wvAddWin');
  if (_wvAddWin) _wvAddWin.addEventListener('click', addWindowToWall);
  var selectedWin = null, _winEdit = document.getElementById('winEdit');
  function selectWindow(w) {
    selectedWin = w; if (!_winEdit) return;
    document.getElementById('winEditW').value = (w.width != null ? w.width : 1.2);
    document.getElementById('winEditSill').value = (w.sill != null ? w.sill : 0.9);
    document.getElementById('winEditH').value = (w.height != null ? w.height : 1.3);
    _winEdit.classList.remove('d-none');
  }
  function closeWinEdit() { selectedWin = null; if (_winEdit) _winEdit.classList.add('d-none'); }
  (function () {
    var wE = document.getElementById('winEditW'), wS = document.getElementById('winEditSill'), wH = document.getElementById('winEditH');
    function applyWin() {
      if (!selectedWin) return;
      selectedWin.width = Math.max(0.4, Math.min(6, parseFloat(wE.value) || 1.2));
      selectedWin.sill = Math.max(0, Math.min(3, parseFloat(wS.value) || 0.9));
      selectedWin.height = Math.max(0.3, Math.min(3.5, parseFloat(wH.value) || 1.3));
      persistWindows(); buildWallView();
    }
    [wE, wS, wH].forEach(function (el) { if (el) el.addEventListener('change', applyWin); });
    var del = document.getElementById('winEditDel');
    if (del) del.addEventListener('click', function () {
      if (!selectedWin) return;
      var i = WINDOWS.indexOf(selectedWin); if (i >= 0) WINDOWS.splice(i, 1);
      closeWinEdit(); persistWindows(); buildWallView();
    });
    var cl = document.getElementById('winEditClose');
    if (cl) cl.addEventListener('click', closeWinEdit);
  })();
  var wvOX = 0, wvOY = 0, wvEW = 0, wvEH = 0, wvStepX = 0, wvStepY = 0;
  function buildWallView() {
    wvLayer.destroyChildren();
    var L = wvWallLengthM() || ROOM_W, Hm = ROOM_H || 4;
    var availW = W - 20, availH = H - 44, aspect = L / Hm;
    var ew = Math.min(availW, availH * aspect), eh = ew / aspect;
    if (eh > availH) { eh = availH; ew = eh * aspect; }
    wvOX = (W - ew) / 2; wvOY = 34 + (availH - eh) / 2; wvEW = ew; wvEH = eh;
    wvLayer.add(new Konva.Rect({ x: 0, y: 0, width: W, height: H, fill: '#ced4da', listening: false }));
    wvLayer.add(new Konva.Rect({ x: wvOX, y: wvOY, width: ew, height: eh, fill: '#f1f3f5', stroke: '#adb5bd', strokeWidth: 1, listening: false }));
    var stepX = ew / L, stepY = eh / Hm; wvStepX = stepX; wvStepY = stepY;
    for (var mx = 1; mx < L; mx++) { var gx = wvOX + mx * stepX; wvLayer.add(new Konva.Line({ points: [gx, wvOY, gx, wvOY + eh], stroke: '#dde1e6', strokeWidth: mx % 5 === 0 ? 1.5 : 1, listening: false })); }
    for (var my = 1; my < Hm; my++) { var gy = wvOY + eh - my * stepY; wvLayer.add(new Konva.Line({ points: [wvOX, gy, wvOX + ew, gy], stroke: '#dde1e6', strokeWidth: my % 5 === 0 ? 1.5 : 1, listening: false })); wvLayer.add(new Konva.Text({ x: wvOX + 2, y: gy - 10, text: my + 'm', fontSize: 8, fill: '#aeb4bb', listening: false })); }
    wvLayer.add(new Konva.Line({ points: [wvOX, wvOY + eh, wvOX + ew, wvOY + eh], stroke: '#868e96', strokeWidth: 4, listening: false }));
    var doorH = Math.min(2.6, Hm - 0.3);
    wvDoorsForWall().forEach(function (dd) {
      var dcol = '#198754';
      var dwpx = (dd.width / L) * ew, dhpx = (doorH / Hm) * eh, dx = wvOX + (dd.pos == null ? 0.5 : dd.pos) * ew - dwpx / 2, dy = wvOY + eh - dhpx;
      wvLayer.add(new Konva.Rect({ x: dx, y: dy, width: dwpx, height: dhpx, fill: '#cdd2d8', stroke: dcol, strokeWidth: 2, cornerRadius: 1, listening: false }));
      wvLayer.add(new Konva.Circle({ x: dx + dwpx - Math.min(7, dwpx * 0.18), y: dy + dhpx / 2, radius: 2.5, fill: dcol, listening: false }));
      wvLayer.add(new Konva.Text({ x: dx, y: dy - 13, width: dwpx, align: 'center', text: '<?php echo __('door') ?>', fontSize: 9, fill: dcol, listening: false }));
    });
    wvWindowsForWall().forEach(function (w) {
      var wpx = (w.width / L) * ew, hY = (w.height || 1.3), sY = (w.sill || 0.9);
      var wy = wvOY + eh - ((sY + hY) / Hm) * eh, wh = (hY / Hm) * eh;
      var cx = wvOX + (w.pos == null ? 0.5 : w.pos) * ew;
      var grp = new Konva.Group({
        x: cx, y: wy, draggable: true,
        dragBoundFunc: function (p) { return { x: Math.max(wvOX + wpx / 2, Math.min(wvOX + ew - wpx / 2, p.x)), y: wy }; }
      });
      grp.add(new Konva.Rect({ x: -wpx / 2, y: 0, width: wpx, height: wh, fill: '#cfe6f5', stroke: '#3a7ca5', strokeWidth: 2, cornerRadius: 1 }));
      grp.add(new Konva.Line({ points: [0, 0, 0, wh], stroke: '#3a7ca5', strokeWidth: 1, listening: false }));
      grp.add(new Konva.Text({ x: -wpx / 2, y: -13, width: wpx, align: 'center', text: '<?php echo __('window') ?>', fontSize: 9, fill: '#3a7ca5', listening: false }));
      grp.on('click tap', function (e) { e.cancelBubble = true; selectWindow(w); });
      grp.on('dragend', function () { w.pos = Math.max(0, Math.min(1, (grp.x() - wvOX) / ew)); persistWindows(); });
      grp.on('dblclick dbltap', function (e) { e.cancelBubble = true; var i = WINDOWS.indexOf(w); if (i >= 0) { WINDOWS.splice(i, 1); if (selectedWin === w) closeWinEdit(); persistWindows(); buildWallView(); } });
      wvLayer.add(grp);
    });
    var lbl = document.getElementById('wvWall').selectedOptions[0];
    wvLayer.add(new Konva.Text({ x: 8, y: 8, text: (lbl ? lbl.text : '') + ' - ' + L.toFixed(1) + 'm x ' + Hm.toFixed(1) + 'm <?php echo __('high; drag to position, search to add') ?>', fontSize: 11, fill: '#495057', listening: false }));
    var loadingTxt = new Konva.Text({ x: 0, y: wvOY + eh / 2 - 10, width: W, align: 'center', text: '<?php echo __('Loading wall...') ?>', fontSize: 16, fontStyle: 'bold', fill: '#6c757d', listening: false });
    wvLayer.add(loadingTxt);
    wvLayer.draw();
    fetch(URLS.placements, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) { loadingTxt.text('<?php echo __('Could not load wall.') ?>'); wvLayer.draw(); return; }
      var items = d.placements.filter(function (p) { return p.wall_or_zone === wvWall; });
      var fresh = 0;
      items.forEach(function (p) {
        if (p.wall_u === null || p.wall_u === undefined) {
          p.wall_u = 0.12 + (fresh % 6) * 0.15;
          p.wall_v = (p.wall_v === null || p.wall_v === undefined) ? 0.55 : p.wall_v;
          fresh++;
          // TODO(parity-phase-2): no wallPos CONTRACT action — keep the layout local-only.
        }
      });
      if (!items.length) { loadingTxt.text('<?php echo __('No objects on this wall yet - assign one via "Hang on wall".') ?>'); wvLayer.draw(); return; }
      var pending = items.length;
      function ready() { pending--; if (pending <= 0) { loadingTxt.destroy(); wvLayer.draw(); } }
      items.forEach(function (p) { wvAddNode(p, ready); });
      wvLayer.draw();
    }).catch(function () { loadingTxt.text('<?php echo __('Could not load wall.') ?>'); wvLayer.draw(); });
  }
  function wvAddNode(p, onReady) {
    var done = false, finish = function () { if (done) return; done = true; if (onReady) onReady(); };
    var u = (p.wall_u != null) ? p.wall_u : 0.5, v = (p.wall_v != null) ? p.wall_v : 0.55;
    var g = new Konva.Group({ x: wvOX + u * wvEW, y: wvOY + (1 - v) * wvEH, draggable: true, name: 'wvitem' });
    g.setAttr('placementId', p.id);
    var r = new Konva.Rect({ x: -WV_NODE / 2, y: -WV_NODE / 2, width: WV_NODE, height: WV_NODE, fill: '#fff', stroke: '#6c757d', strokeWidth: 1, cornerRadius: 3, shadowColor: '#000', shadowBlur: 5, shadowOpacity: 0.15 });
    g.add(r);
    if (p.thumb_url) {
      var im = new Image();
      im.onload = function () { var ki = new Konva.Image({ image: im, x: -WV_NODE / 2 + 2, y: -WV_NODE / 2 + 2, width: WV_NODE - 4, height: WV_NODE - 4 }); g.add(ki); r.moveToBottom(); wvLayer.draw(); finish(); };
      im.onerror = function () { g.add(new Konva.Text({ text: '#' + p.information_object_id, x: -WV_NODE / 2, y: -6, width: WV_NODE, align: 'center', fontSize: 10, fill: '#999' })); wvLayer.draw(); finish(); };
      im.src = p.thumb_url;
    } else { g.add(new Konva.Text({ text: '#' + p.information_object_id, x: -WV_NODE / 2, y: -6, width: WV_NODE, align: 'center', fontSize: 10, fill: '#999' })); finish(); }
    g.on('dragmove', function () {
      var x = g.x(), y = g.y();
      if (wvStepX > 0) { var gx = wvOX + Math.round((x - wvOX) / wvStepX) * wvStepX; if (Math.abs(gx - x) < wvStepX * 0.3) x = gx; }
      if (wvStepY > 0) { var fy = wvOY + wvEH, gy = fy - Math.round((fy - y) / wvStepY) * wvStepY; if (Math.abs(gy - y) < wvStepY * 0.3) y = gy; }
      g.x(Math.max(wvOX, Math.min(wvOX + wvEW, x)));
      g.y(Math.max(wvOY, Math.min(wvOY + wvEH, y)));
    });
    // TODO(parity-phase-2): no wallPos CONTRACT action — wall-view drags are local-only.
    wvLayer.add(g); wvLayer.draw();
  }

  // keep canvas usable on resize
  window.addEventListener('resize', function () {
    var nw = Math.max(320, wrap.clientWidth || W);
    if (Math.abs(nw - W) < 20) return;
    var ratios = layer.find('.placement').map(function (g) { return { g: g, rx: g.x() / W, ry: g.y() / H }; });
    W = nw; H = aspectH(W);
    stage.width(W); stage.height(H);
    ratios.forEach(function (o) { o.g.x(o.rx * W); o.g.y(o.ry * H); });
    drawBackground(); redrawWalls(); drawDoorMarkers(); layer.draw();
  });

  // Embed copy button.
  (function () {
    var b = document.getElementById('embedCopy'); if (!b) return;
    b.addEventListener('click', function () {
      var ta = document.getElementById('embedSnippet'); if (!ta) return;
      ta.select();
      if (navigator.clipboard) { navigator.clipboard.writeText(ta.value); }
      b.innerHTML = '<i class="fas fa-check me-1"></i><?php echo __('Copied') ?>';
    });
  })();

  // ---- Guided tours (audio) authoring (multiple named tours) ----
  (function () {
    // Guided-tour authoring writes via the walkthrough/guided-tour endpoint, which is NOT
    // part of the builder CONTRACT on PSIS. The full authoring UI is heavy; for parity we
    // expose visual route-mode bridges so plan clicks build a stop list, but tour persistence
    // and audio upload are TODO(parity-phase-2).
    window.__exhibStops = function () { return []; };
    window.__exhibAddStop = function () {};
    window.__exhibRouteMode = false;
  })();
})();
</script>
