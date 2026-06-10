<?php
/*
 * Exhibition Space — Building PLAN editor (Konva.js 2D blueprint).
 * Faithful port of Heratio packages/ahg-exhibition resources/views/exhibition-space/plan.blade.php
 * (heratio#1143 / #1169 / #1171 / #1172). Blade -> Symfony PHP template; route() -> url_for();
 * fetch URLs map to the shared CONTRACT actions on module "exhibitionSpace".
 *
 * View vars (set by exhibitionSpaceActions::executePlan): $space, $plan (getBuildingPlan()).
 * $plan = ['rooms'=>[...], 'plan_image'=>str|null, 'plan_rect'=>{x,y,w,h}|null,
 *          'corridor'=>[...], 'stairs'=>[...], 'bbox'=>{...}].
 *
 * AtoM/Symfony notes vs Heratio (Laravel):
 *   - @csrf / csrf_token() dropped: PSIS write actions authorise via session (isAuthenticated())
 *     and read the JSON body; no per-request CSRF token surface.
 *   - The page action calls requireAuth(), so it is always rendered for a logged-in user; the
 *     Blade @auth blocks are still guarded with $isAuth to stay a 1:1 structural replica.
 *   - Corridor object search uses the existing JSON endpoint /api/autocomplete/glam (ahgAPIPlugin),
 *     which returns [{id,label,value,...}] — TomSelect mapped to labelField 'label', query param 'q'.
 *   - konva + tom-select loaded from cdn.jsdelivr.net (CSP-allowed) with the CSP nonce.
 */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

$isAuth = $sf_user->isAuthenticated();

$JSON = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT;

$plan = isset($plan) && is_array($plan) ? $plan : ['rooms' => [], 'plan_image' => null, 'plan_rect' => null, 'corridor' => [], 'stairs' => []];

// URL map — every CONTRACT action via url_for(). Doors/Windows/Shape already exist on PSIS.
$u = function ($action) use ($space) {
    return url_for(['module' => 'exhibitionSpace', 'action' => $action, 'slug' => $space->slug]);
};
$urls = [
    'save' => $u('planSave'),
    'doors' => $u('planDoors'),
    'windows' => $u('planWindows'),
    'shape' => $u('planShape'),
    'addRoom' => $u('planAddRoom'),
    'group' => $u('planGroup'),
    'stairs' => $u('planStairs'),
    'roomFloor' => $u('planRoomFloor'),
    'roomLock' => $u('planRoomLock'),
    'deleteRoom' => $u('planDeleteRoom'),
    'imageRect' => $u('planImageRect'),
    'image' => $u('planImage'),
    'imageClear' => $u('planImageClear'),
    'corrAdd' => $u('planCorridorAdd'),
    'corrMove' => $u('planCorridorMove'),
    'corrRemove' => $u('planCorridorRemove'),
    'editBase' => url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => '__SLUG__']),
    'autocomplete' => '/api/autocomplete/glam',
];
?>
<div class="container-fluid px-4 py-3 exhibition-space plan">
  <div class="d-flex flex-wrap align-items-baseline mb-2 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-drafting-compass me-2"></i><?php echo __('Building Plan') ?> <small class="text-muted"><?php echo esc_entities($space->name) ?></small></h1>
    <?php if ($isAuth): ?><button type="button" id="addRoomBtn" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i><?php echo __('Add room') ?></button><?php endif ?>
    <?php if ($isAuth): ?><button type="button" id="joinBtn" class="btn btn-sm btn-outline-warning" title="<?php echo __('Click a corner dot on one room, then a corner dot on another - the second room moves so the corners meet.') ?>"><i class="fas fa-link me-1"></i><?php echo __('Join corners') ?></button><?php endif ?>
    <?php if ($isAuth): ?><button type="button" id="undoBtn" class="btn btn-sm btn-outline-secondary" disabled title="<?php echo __('Undo the last room move (Ctrl+Z)') ?>"><i class="fas fa-undo me-1"></i><?php echo __('Undo move') ?></button><?php endif ?>
    <?php if ($isAuth): ?><select id="floorView" class="form-select form-select-sm" style="width:auto" title="<?php echo __('Show only rooms on one floor') ?>"></select><?php endif ?>
    <?php if ($isAuth): ?><div class="form-check form-switch small d-inline-block align-middle ms-1" title="<?php echo __('When locking, extend walls to close gaps between rooms') ?>"><input class="form-check-input" type="checkbox" id="lockFloorFill"><label class="form-check-label" for="lockFloorFill"><?php echo __('fill gaps') ?></label></div><?php endif ?>
    <?php if ($isAuth): ?><button type="button" id="lockFloorBtn" class="btn btn-sm btn-outline-dark" title="<?php echo __('Lock this whole floor: align all walls flush (and fill gaps if ticked); rooms can no longer be moved/resized. Click again to unlock the floor.') ?>"><i class="fas fa-lock me-1"></i><?php echo __('Lock floor') ?></button><?php endif ?>
    <a href="<?php echo $u('builder') ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-cubes me-1"></i><?php echo __('Builder') ?></a>
    <a href="<?php echo $u('walkthrough') ?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="fas fa-walking me-1"></i><?php echo __('Walkthrough') ?></a>
    <a href="<?php echo $u('show') ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back to space') ?></a>
  </div>
  <p class="text-muted small mb-3"><?php echo __('Drag rooms to position them; use the corner handles to resize. Upload a blueprint to trace over. Saved automatically; the 3D walkthrough follows this layout.') ?></p>

  <?php if ($sf_user->hasFlash('notice')): ?><div class="alert alert-success py-2"><?php echo $sf_user->getFlash('notice') ?></div><?php endif ?>

  <div class="row g-3">
    <div class="col-lg-3">
      <?php if ($isAuth): ?>
      <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="fas fa-map me-1"></i><?php echo __('Blueprint') ?></strong></div>
        <div class="card-body">
          <form method="POST" action="<?php echo $urls['image'] ?>" enctype="multipart/form-data" class="mb-2">
            <input type="file" name="plan_image" accept="image/*" class="form-control form-control-sm mb-2" required>
            <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-upload me-1"></i><?php echo __('Upload blueprint') ?></button>
          </form>
          <?php if (!empty($plan['plan_image'])): ?>
          <button type="button" id="planAdjustBtn" class="btn btn-sm btn-outline-secondary w-100 mb-2"><i class="fas fa-arrows-alt me-1"></i><?php echo __('Adjust blueprint') ?></button>
          <small class="text-muted d-block mb-2"><?php echo __('The blueprint is pinned to the floor plan in metres, so it stays aligned with the rooms when you zoom or add rooms. Click Adjust to drag/resize it onto the rooms.') ?></small>
          <form method="POST" action="<?php echo $urls['imageClear'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-times me-1"></i><?php echo __('Clear blueprint') ?></button>
          </form>
          <?php endif ?>
        </div>
      </div>
      <?php endif ?>
      <div class="card mb-3">
        <div class="card-header py-2"><strong><i class="fas fa-th-large me-1"></i><?php echo __('Rooms') ?></strong></div>
        <div class="card-body p-2"><div id="planRoomList" class="small text-muted"></div></div>
      </div>
      <?php if ($isAuth): ?>
      <div class="card mb-3" id="roomCard" style="display:none;">
        <div class="card-header py-2"><strong><i class="fas fa-sync-alt me-1"></i><?php echo __('Selected room') ?></strong> <span class="small text-muted" id="roomCardName"></span></div>
        <div class="card-body p-2">
          <a href="#" target="_blank" rel="noopener" id="roomEditLink" class="btn btn-sm btn-outline-secondary w-100 mb-2"><i class="fas fa-edit me-1"></i><?php echo __('Edit room details') ?></a>
          <div class="input-group input-group-sm mb-1">
            <span class="input-group-text"><i class="fas fa-layer-group me-1"></i><?php echo __('Floor') ?></span>
            <select id="roomFloor" class="form-select"></select>
          </div>
          <small class="text-muted d-block mb-2"><?php echo __('Put this room on a floor. If it is grouped, the whole suite moves with it. Use the Floor dropdown at the top to view one floor at a time.') ?></small>
          <div class="btn-group btn-group-sm w-100 mb-2">
            <button type="button" class="btn btn-outline-secondary" id="roomFront" title="<?php echo __('Bring room to front (draw on top of overlapping rooms)') ?>"><i class="fas fa-arrow-up me-1"></i><?php echo __('Front') ?></button>
            <button type="button" class="btn btn-outline-secondary" id="roomBack" title="<?php echo __('Send room to back') ?>"><i class="fas fa-arrow-down me-1"></i><?php echo __('Back') ?></button>
          </div>
          <label class="form-label small mb-1"><?php echo __('Rotation (degrees)') ?></label>
          <div class="input-group input-group-sm mb-2">
            <button type="button" class="btn btn-outline-secondary" id="rotMinus" title="<?php echo __('Rotate left 15°') ?>"><i class="fas fa-undo"></i></button>
            <input type="number" id="rotInput" class="form-control text-center" step="1" value="0">
            <button type="button" class="btn btn-outline-secondary" id="rotPlus" title="<?php echo __('Rotate right 15°') ?>"><i class="fas fa-redo"></i></button>
            <button type="button" class="btn btn-outline-secondary" id="rotZero" title="<?php echo __('Reset') ?>">0</button>
          </div>
          <small class="text-muted d-block"><?php echo __('You can also drag the round handle on the room to rotate it.') ?></small>
          <hr class="my-2">
          <label class="form-label small mb-1"><?php echo __('Footprint shape') ?></label>
          <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-1" id="shapeEdit"><i class="fas fa-draw-polygon me-1"></i><?php echo __('Edit room shape') ?></button>
          <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="shapeReset"><?php echo __('Reset to rectangle') ?></button>
          <small class="text-muted d-block mt-1" id="shapeHint"><?php echo __('Make L-shapes or cut corners. Drag a corner; click a + to add one; double-click to remove.') ?></small>
          <hr class="my-2">
          <button type="button" class="btn btn-sm btn-outline-warning w-100" id="ungroupBtn" style="display:none;"><i class="fas fa-object-ungroup me-1"></i><?php echo __('Ungroup from suite') ?></button>
          <small class="text-muted d-block mt-1"><?php echo __('Rooms that share a wall auto-group and move together (matching dashed outline). Ungroup to move this one on its own.') ?></small>
          <hr class="my-2">
          <button type="button" class="btn btn-sm btn-outline-danger w-100" id="deleteRoomBtn"><i class="fas fa-trash me-1"></i><?php echo __('Delete room') ?></button>
        </div>
      </div>
      <div class="card mb-3" id="doorCard" style="display:none;">
        <div class="card-header py-2"><strong><i class="fas fa-door-open me-1"></i><?php echo __('Doors') ?></strong> <span class="small text-muted" id="doorRoomName"></span></div>
        <div class="card-body p-2">
          <p class="small text-muted mb-2"><?php echo __('Add a door to a wall, then drag it along that wall to position it. Double-click a door to remove it.') ?></p>
          <div class="btn-group btn-group-sm w-100 mb-2" role="group" id="doorWallBtns">
            <button type="button" class="btn btn-outline-primary" data-door="north"><?php echo __('Top') ?></button>
            <button type="button" class="btn btn-outline-primary" data-door="south"><?php echo __('Bottom') ?></button>
            <button type="button" class="btn btn-outline-primary" data-door="west"><?php echo __('Left') ?></button>
            <button type="button" class="btn btn-outline-primary" data-door="east"><?php echo __('Right') ?></button>
          </div>
          <div id="edgeDoorBtns" class="d-flex flex-wrap gap-1 mb-2" style="display:none;"></div>
          <div id="doorList" class="small"></div>
        </div>
      </div>
      <div class="card mb-3" id="winCard" style="display:none;">
        <div class="card-header py-2"><strong><i class="fas fa-window-maximize me-1"></i><?php echo __('Windows') ?></strong> <span class="small text-muted" id="winRoomName"></span></div>
        <div class="card-body p-2">
          <p class="small text-muted mb-2"><?php echo __('Add windows to a wall - they show as glass openings in the 3D walkthrough, inside and out. Works on rectangular and shaped rooms (pick the wall below).') ?></p>
          <div class="input-group input-group-sm mb-2">
            <select id="winWall" class="form-select"><option value="north"><?php echo __('Top') ?></option><option value="south"><?php echo __('Bottom') ?></option><option value="west"><?php echo __('Left') ?></option><option value="east"><?php echo __('Right') ?></option></select>
            <button type="button" id="winAdd" class="btn btn-outline-primary"><i class="fas fa-plus me-1"></i><?php echo __('Add') ?></button>
          </div>
          <div class="row g-1 mb-2">
            <div class="col"><label class="small text-muted d-block"><?php echo __('Pos') ?><input id="winPos" type="number" step="0.05" min="0" max="1" value="0.5" class="form-control form-control-sm"></label></div>
            <div class="col"><label class="small text-muted d-block"><?php echo __('Width') ?><input id="winW" type="number" step="0.1" min="0.4" max="6" value="1.6" class="form-control form-control-sm"></label></div>
            <div class="col"><label class="small text-muted d-block"><?php echo __('Sill') ?><input id="winSill" type="number" step="0.1" min="0.2" max="2" value="0.9" class="form-control form-control-sm"></label></div>
            <div class="col"><label class="small text-muted d-block"><?php echo __('Ht') ?><input id="winH" type="number" step="0.1" min="0.4" max="3" value="1.3" class="form-control form-control-sm"></label></div>
          </div>
          <div id="winList" class="small"></div>
        </div>
      </div>
      <div class="card" id="corridorCard">
        <div class="card-header py-2"><strong><i class="fas fa-shoe-prints me-1"></i><?php echo __('Corridor objects') ?></strong></div>
        <div class="card-body p-2">
          <p class="small text-muted mb-2"><?php echo __('Place objects in the open space between rooms. Search to add, then drag the dot. Double-click to remove.') ?></p>
          <select id="corridorAdd" class="form-control form-control-sm mb-2"><option value=""><?php echo __('Search an object to add…') ?></option></select>
          <div id="corridorList" class="small text-muted"></div>
        </div>
      </div>
      <div class="card mt-3" id="stairsCard">
        <div class="card-header py-2"><strong><i class="fas fa-stairs me-1"></i><?php echo __('Stairs') ?></strong></div>
        <div class="card-body p-2">
          <p class="small text-muted mb-2"><?php echo __('Link floors. Add a staircase, drag it onto the plan (place it under the upper-floor room), set which floors it connects. In the 3D walkthrough, walk to it and click the steps to change floor.') ?></p>
          <button type="button" id="stairAdd" class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="fas fa-plus me-1"></i><?php echo __('Add staircase') ?></button>
          <div id="stairList" class="small text-muted"></div>
        </div>
      </div>
      <?php endif ?>
    </div>
    <div class="col-lg-9">
      <div class="card">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong><?php echo __('Plan') ?></strong>
          <span class="small text-muted"><span id="planSave"><?php echo __('All changes saved') ?></span></span>
        </div>
        <div class="card-body p-0"><div id="planWrap" style="width:100%;background:#f4f4f4;border-radius:0 0 .375rem .375rem;overflow:hidden;"></div></div>
      </div>
    </div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"<?php echo $nonce ?>></script>
<script src="https://cdn.jsdelivr.net/npm/konva@9.3.6/konva.min.js"<?php echo $nonce ?>></script>
<script<?php echo $nonce ?>>
window.AHG_PLAN_BOOT = {
  urls: <?php echo json_encode($urls, $JSON) ?>,
  plan: <?php echo json_encode($plan, $JSON) ?>,
  isAuth: <?php echo $isAuth ? 'true' : 'false' ?>,
  i18n: <?php echo json_encode([
      'canvasFail' => __('Canvas failed to load.'),
      'saving' => __('Saving...'),
      'allSaved' => __('All changes saved'),
      'top' => __('Top'), 'bottom' => __('Bottom'), 'left' => __('Left'), 'right' => __('Right'),
      'wall' => __('Wall'), 'remove' => __('Remove'),
      'noDoors' => __('No doors yet.'), 'noWindows' => __('No windows yet.'),
      'doorway' => __('Doorway'), 'single' => __('Single'), 'double' => __('Double'),
      'glass' => __('Glass'), 'sliding' => __('Sliding'), 'ornate' => __('Ornate'),
      'lockedShape' => __('This room is locked. Unlock it first to edit its shape.'),
      'deleteRoomConfirm' => __('Delete this room and everything placed in it? This cannot be undone.'),
      'deleteRoomFail' => __('Could not delete this room (you cannot delete the room you opened the plan from).'),
      'ground' => __('Ground'), 'basement' => __('Basement'), 'floor' => __('Floor'),
      'floorLc' => __('floor'), 'groundLc' => __('ground'),
      'noneYet' => __('None yet.'), 'noStairs' => __('No stairs yet.'),
      'stair' => __('Stair'), 'from' => __('from'), 'to' => __('to'),
      'width' => __('width'), 'len' => __('len'), 'len2' => __('len2'),
      'straight' => __('Straight'), 'elbow' => __('Elbow'), 'rot' => __('rot'),
      'unlockFloor' => __('Unlock floor'), 'lockFloor' => __('Lock floor'),
      'sameFloorMsg' => __('Stairs must link two DIFFERENT floors. Those rooms are on the same floor - set one to a different floor first (Selected room > Floor), or just place the rooms next to each other for a walk-through doorway.'),
      'done' => __('Done'), 'adjustBlueprint' => __('Adjust blueprint'),
  ], $JSON) ?>
};
</script>
<script src="/plugins/ahgExhibitionPlugin/web/js/exhibition-plan.js?v=1"<?php echo $nonce ?>></script>
