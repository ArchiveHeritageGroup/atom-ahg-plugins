<?php
/*
 * Exhibition Space — first-person 3D walkthrough (three.js).
 * PSIS Symfony port of Heratio ahg-exhibition walkthrough.blade.php (#136 / heratio#1138).
 *
 * The whole three.js scene lives in web/js/exhibition-walkthrough-3d.js; this template
 * supplies the markup + a nonce'd window.EXH_WT config (data, i18n, optional URLs).
 *
 * Action vars (executeWalkthrough): $space, $building, $placements, $roomDims,
 * $guidedTour, $walls, $doors, $windows, $shape.
 */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

// $building (getWalkthroughBuilding) already carries rooms[].stops[] with every per-object
// field the 3D engine reads. Provide single-room fallbacks for the JS too (harmless when
// $building->rooms is present, which it always is here).
$buildingArr = isset($building) ? $building : null;
$stopsFallback = [];
if (is_array($buildingArr) && ! empty($buildingArr['rooms'])) {
    foreach ($buildingArr['rooms'] as $rm) {
        if (! empty($rm['is_current']) && ! empty($rm['stops'])) {
            $stopsFallback = $rm['stops'];
            break;
        }
    }
}

$hasContent = false;
if (is_array($buildingArr) && ! empty($buildingArr['rooms'])) {
    foreach ($buildingArr['rooms'] as $rm) {
        if (! empty($rm['stops'])) { $hasContent = true; break; }
    }
}

// i18n map: English key => localized string. The JS T() helper looks strings up by key.
$i18nKeys = [
    '3D engine failed to load.', 'ALARM! Put', 'back!', 'Basement', 'Default',
    'Delete this graffiti?', 'Docent', 'Door', 'Explore freely instead', 'Floor',
    'Following', 'Follow the docent', 'Full tour', 'Generating AI description...',
    'Graffiti text (leave blank to cancel):', 'Ground', 'Guided tour live:', 'here now',
    'Humidity', 'Light', 'Narrating... (Esc to stop)', 'No description available.',
    'No description available for this object.', 'No live readings yet.',
    'On a PC or laptop you can roam the gallery freely.', 'Reading description... (Esc to stop)',
    'Right this way.', 'Sorry, I could not answer that right now.',
    'Sorry, the record does not let me answer that right now.', 'STAIRS ↑', 'STAIRS ↓',
    'Start guided tour', 'Start tour', 'Stop following', 'Stop tour', 'Take a guided tour',
    'Tell me about this', 'Temp', 'that', 'The docent is thinking…', 'Visitor', 'Visitors',
    'Voice selected.', 'Walk to this object', 'When is it from?', 'Who made it?',
    'Why does it matter?', 'You', 'you are alone here', 'You are leading a tour',
];
$i18n = [];
foreach ($i18nKeys as $k) {
    $i18n[$k] = __($k);
}

$wtConfig = [
    'slug' => $space->slug,
    'csrf' => '',
    'auth' => $sf_user->isAuthenticated(),
    'building' => $buildingArr,
    'stops' => $stopsFallback,
    'walls' => isset($walls) ? $walls : [],
    'floorplan' => $space->floorplan_image_path ?? null,
    'guidedTour' => isset($guidedTour) ? $guidedTour : [],
    'annotations' => [],            // TODO(parity-phase-2): persisted wall annotations not ported
    'describeUrl' => null,          // TODO(parity-phase-2): AI object-describe endpoint not ported
    'i18n' => $i18n,
];

$jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<div class="exhibition-space walkthrough-3d">
  <div class="d-flex flex-wrap align-items-baseline mb-2 gap-2 px-3 pt-3">
    <h1 class="mb-0 flex-grow-1">
      <i class="fas fa-vr-cardboard me-2"></i><?php echo __('3D Walkthrough') ?>
      <small class="text-muted" id="wtSpaceName"><?php echo esc_entities($space->name) ?></small>
    </h1>
    <?php if ($sf_user->isAuthenticated()): ?>
      <a id="editBuilderBtn"
         href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'builder', 'slug' => $space->slug]) ?>"
         data-tmpl="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'builder', 'slug' => '__SLUG__']) ?>"
         class="btn btn-sm btn-outline-primary">
        <i class="fas fa-cubes me-1"></i><?php echo __('Edit in Builder') ?>
      </a>
    <?php endif ?>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i><?php echo __('Close') ?></a>
  </div>

  <?php if (! $hasContent): ?>
    <div class="alert alert-info mx-3">
      <?php echo __('Nothing has been placed in this space yet.') ?>
      <?php if ($sf_user->isAuthenticated()): ?>
        <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'builder', 'slug' => $space->slug]) ?>"><?php echo __('Open the Digital Twin Builder') ?></a>.
      <?php endif ?>
    </div>
  <?php else: ?>
    <div class="card mx-3">
      <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong><?php echo __('Virtual gallery') ?></strong>
        <span class="small text-muted"><?php echo __('Click to enter. Move: W A S D. Look: mouse. Select: click an object. Exit: Esc.') ?></span>
      </div>
      <div class="card-body p-0">
        <div id="room" style="position:relative;width:100%;height:70vh;min-height:420px;background:#1a1d21;border-radius:0;overflow:hidden;">
          <div id="roomBlocker" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.55);z-index:5;cursor:pointer;">
            <div class="text-center text-white">
              <div style="font-size:2rem;"><i class="fas fa-vr-cardboard"></i></div>
              <div class="fw-bold mt-2"><?php echo __('Click to enter the gallery') ?></div>
              <div class="small text-white-50 mt-1"><?php echo __('W A S D to walk, mouse to look, click an object for details, Esc to exit') ?></div>
              <div class="small text-white-50"><?php echo __('Press H any time for the full controls') ?></div>
            </div>
          </div>
          <div id="roomCrosshair" style="position:absolute;top:50%;left:50%;width:16px;height:16px;margin:-8px 0 0 -8px;border-radius:50%;background:#000;border:2px solid rgba(255,255,255,.85);box-sizing:border-box;z-index:4;display:none;pointer-events:none;"></div>
          <img id="figurePointer" alt="" style="position:absolute;top:50%;left:50%;height:120px;margin-top:-110px;transform:translateX(-50%);z-index:4;display:none;pointer-events:none;opacity:.92;filter:drop-shadow(0 2px 3px rgba(0,0,0,.5));">
          <div id="figureHint" class="bg-dark text-white px-2 py-1 rounded small" style="position:absolute;bottom:90px;left:50%;transform:translateX(-50%);z-index:7;display:none;"><i class="fas fa-person-walking me-1"></i><?php echo __('Wheel: change person - click: place (this view only)') ?></div>
          <div id="objectPointer" style="position:absolute;top:50%;left:50%;margin:-12px 0 0 -11px;z-index:5;display:none;pointer-events:none;color:#fff;font-size:22px;text-shadow:0 1px 3px rgba(0,0,0,.8);"><i class="fas fa-hand-pointer"></i></div>
          <div id="roomLoading" style="position:absolute;bottom:8px;left:8px;z-index:4;color:#ccc;font-size:.8rem;"><?php echo __('Loading gallery...') ?></div>
          <div id="wtAlarm" style="position:absolute;inset:0;background:#ff0000;opacity:0;z-index:9;pointer-events:none;display:none;"></div>
          <div id="wtAlarmBar" style="position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);z-index:10;display:none;text-align:center;">
            <div class="bg-danger text-white px-4 py-2 rounded-pill shadow fw-bold"><i class="fas fa-triangle-exclamation me-2"></i><span id="wtAlarmText"></span></div>
            <button type="button" id="wtAlarmOff" class="btn btn-light btn-sm rounded-pill mt-2 shadow"><i class="fas fa-bell-slash me-1"></i><?php echo __('Silence alarm') ?></button>
          </div>
          <div id="wtHeight" class="bg-dark text-white px-2 py-1 rounded small" style="position:absolute;bottom:8px;left:50%;transform:translateX(-50%);z-index:7;display:none;"></div>
          <div id="wtNarr" class="bg-primary text-white px-2 py-1 rounded small" style="position:absolute;bottom:34px;left:50%;transform:translateX(-50%);z-index:7;display:none;"><i class="fas fa-volume-high me-1"></i><?php echo __('Reading description... (Esc to stop)') ?></div>
          <button id="roomHelpBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:8px;z-index:6;opacity:.85;" title="<?php echo __('Controls') ?>"><i class="fas fa-question"></i></button>
          <button id="roomMapBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:44px;z-index:6;opacity:.85;" title="<?php echo __('Building map') ?>"><i class="fas fa-map"></i></button>
          <button id="roomLiveBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:80px;z-index:6;opacity:.85;" title="<?php echo __('Live data') ?>"><i class="fas fa-temperature-half"></i></button>
          <button id="wtPeopleBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:116px;z-index:6;opacity:.85;" title="<?php echo __('People here') ?>"><i class="fas fa-users"></i> <span id="wtPeopleCount">1</span></button>
          <button id="wtTorchBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:156px;z-index:6;opacity:.85;" title="<?php echo __('Torch (F)') ?>"><i class="fas fa-lightbulb"></i></button>
          <button id="wtGraffitiBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:192px;z-index:6;opacity:.85;" title="<?php echo __('Graffiti: click a wall to tag it') ?>"><i class="fas fa-spray-can"></i></button>
          <button id="wtTourPlayBtn" type="button" class="btn btn-sm btn-success" style="position:absolute;top:8px;right:228px;z-index:6;opacity:.9;display:none;" title="<?php echo __('Play guided tour') ?>"><i class="fas fa-play"></i></button>
          <button id="wtStealBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:264px;z-index:6;opacity:.85;" title="<?php echo __('Steal mode: click an object to trigger the alarm') ?>"><i class="fas fa-mask"></i></button>
          <button id="wtFigureBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:300px;z-index:6;opacity:.85;" title="<?php echo __('Figures: wheel to pick a person, click to place') ?>"><i class="fas fa-person-walking"></i></button>
          <button id="wtSunBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:336px;z-index:6;opacity:.85;" title="<?php echo __('Sun & shadows (off / morning / noon / afternoon)') ?>"><i class="fas fa-sun"></i></button>
          <button id="wtNightBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:372px;z-index:6;opacity:.85;" title="<?php echo __('Night mode (walk with the flashlight) - N') ?>"><i class="fas fa-moon"></i></button>
          <button id="wtFsBtn" type="button" class="btn btn-sm btn-dark" style="position:absolute;top:8px;right:408px;z-index:6;opacity:.85;" title="<?php echo __('Fullscreen') ?>"><i class="fas fa-expand"></i></button>
          <button id="wt360Btn" type="button" class="btn btn-sm btn-info" style="position:absolute;top:8px;right:444px;z-index:6;opacity:.9;display:none;" title="<?php echo __('360 / Matterport view of this room') ?>"><i class="fas fa-vr-cardboard me-1"></i>360</button>
          <div id="wt360Overlay" style="position:absolute;inset:0;z-index:20;background:#000;display:none;">
            <iframe id="wt360Frame" src="" allow="fullscreen; xr-spatial-tracking; gyroscope; accelerometer" allowfullscreen style="border:0;width:100%;height:100%;"></iframe>
            <button id="wt360Close" type="button" class="btn btn-light btn-sm rounded-pill shadow" style="position:absolute;top:10px;right:10px;z-index:21;"><i class="fas fa-times me-1"></i><?php echo __('Close 360 view') ?></button>
          </div>
          <div id="wtTourBanner" class="bg-dark text-white px-3 py-2 rounded small" style="position:absolute;bottom:64px;left:50%;transform:translateX(-50%);z-index:7;display:none;max-width:86%;text-align:center;box-shadow:0 4px 16px rgba(0,0,0,.5);">
            <div id="wtTourText" style="max-height:30vh;overflow-y:auto;"></div>
            <div class="mt-2 text-nowrap">
              <button type="button" id="wtTourTextBtn" class="btn btn-sm btn-outline-light py-0" title="<?php echo __('Show/hide narration text') ?>"><i class="fas fa-closed-captioning"></i></button>
              <button type="button" id="wtTourPrevBtn" class="btn btn-sm btn-outline-light py-0" title="<?php echo __('Previous stop') ?>"><i class="fas fa-step-backward"></i></button>
              <button type="button" id="wtTourWalkBtn" class="btn btn-sm btn-outline-light py-0" title="<?php echo __('Walk around this object, then Space or Next to continue') ?>"><i class="fas fa-person-walking me-1"></i><?php echo __('Walk') ?></button>
              <button type="button" id="wtTourNextBtn" class="btn btn-sm btn-light py-0" title="<?php echo __('Next stop (Space)') ?>"><?php echo __('Next') ?> <i class="fas fa-step-forward"></i></button>
              <button type="button" id="wtTourStopBtn" class="btn btn-sm btn-outline-light py-0"><i class="fas fa-stop"></i></button>
            </div>
          </div>
          <div id="wtTourQuick" style="position:absolute;bottom:18px;left:50%;transform:translateX(-50%);z-index:8;display:none;text-align:center;width:90%;max-width:360px;">
            <select id="wtTourQuickSel" class="form-select form-select-sm mb-2 d-none"></select>
            <button type="button" id="wtTourQuickBtn" class="btn btn-success btn-lg rounded-pill shadow w-100"><i class="fas fa-play me-2"></i><?php echo __('Start guided tour') ?></button>
          </div>
          <div id="wtPeople" class="bg-dark text-white p-2 rounded small" style="position:absolute;top:46px;right:8px;z-index:8;width:240px;display:none;box-shadow:0 4px 16px rgba(0,0,0,.5);">
            <div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-bold"><i class="fas fa-users me-1"></i><?php echo __('In this exhibition') ?></span><button type="button" id="wtPeopleClose" class="btn-close btn-close-white btn-sm" aria-label="<?php echo __('Close') ?>"></button></div>
            <input id="wtNameInput" class="form-control form-control-sm mb-2" placeholder="<?php echo __('Your name') ?>" maxlength="40">
            <div id="wtPeopleList"></div>
            <button id="wtFollowBtn" type="button" class="btn btn-sm btn-warning w-100 mt-2" style="display:none;"><i class="fas fa-shoe-prints me-1"></i><?php echo __('Follow the docent') ?></button>
          </div>
          <div id="wtHereNow" class="bg-dark text-white px-2 py-1 rounded-pill small" style="position:absolute;top:10px;left:50%;transform:translateX(-50%);z-index:7;opacity:.9;box-shadow:0 2px 8px rgba(0,0,0,.45);cursor:pointer;" title="<?php echo __('People in this exhibition right now') ?>"><i class="fas fa-users me-1"></i><span id="wtHereNowN">1</span> <span id="wtHereNowLbl"><?php echo __('here now') ?></span></div>
          <div id="wtDocentBanner" class="bg-primary text-white px-3 py-2 rounded small" style="position:absolute;top:46px;left:50%;transform:translateX(-50%);z-index:7;display:none;max-width:80%;text-align:center;box-shadow:0 4px 16px rgba(0,0,0,.5);"></div>
          <div id="wtLive" class="bg-dark text-white p-2 rounded small" style="position:absolute;top:46px;left:8px;z-index:7;width:230px;display:none;box-shadow:0 4px 16px rgba(0,0,0,.5);">
            <div class="fw-bold mb-1"><i class="fas fa-temperature-half me-1"></i><?php echo __('Live conditions') ?></div>
            <div id="wtLiveBody"></div>
          </div>
          <div id="wtMinimap" class="bg-dark text-white p-2 rounded" style="position:absolute;top:46px;right:8px;z-index:7;width:260px;display:none;box-shadow:0 4px 16px rgba(0,0,0,.5);">
            <div class="d-flex justify-content-between align-items-center mb-1"><span class="small fw-bold"><i class="fas fa-map me-1"></i><?php echo __('Building — tap a room to enter') ?></span><button type="button" id="wtMiniClose" class="btn-close btn-close-white btn-sm" aria-label="<?php echo __('Close') ?>"></button></div>
            <div id="wtMiniSvg"></div>
          </div>
          <div id="roomHelp" class="bg-dark text-white p-3 rounded small" style="position:absolute;top:46px;right:8px;z-index:6;max-width:260px;display:none;">
            <div class="fw-bold mb-2"><i class="fas fa-gamepad me-1"></i><?php echo __('Controls') ?></div>
            <ul class="mb-0 ps-3">
              <li><?php echo __('Click gallery to enter') ?></li>
              <li><?php echo __('Forward / back: mouse wheel') ?></li>
              <li><?php echo __('Stand taller / crouch: hold U + mouse wheel') ?></li>
              <li><?php echo __('Virtual reality: tap the VR button (headset required); left stick moves, right stick turns') ?></li>
              <li><?php echo __('Look around: move the mouse') ?></li>
              <li><?php echo __('Open info: click an object (or a numbered button)') ?></li>
              <li><?php echo __('Hear description read aloud: hold T (Talk) + click an object') ?></li>
              <li><?php echo __('Force a fresh AI description: hold G + click an object') ?></li>
              <li><?php echo __('Zoom in / out: Z') ?></li>
              <li><?php echo __('Torch (light dark corners): F or the bulb button') ?></li>
              <li><?php echo __('Night mode (walk with the flashlight): N or the moon button') ?></li>
              <li><?php echo __('Graffiti: tap the spray-can, then click a wall to tag it') ?></li>
              <li><?php echo __('Steal (sets off the alarm!): tap the mask, then click an object (or hold S + click)') ?></li>
              <li><?php echo __('Open full record (new tab): V') ?></li>
              <li><?php echo __('Close info: click or Esc') ?></li>
              <li><?php echo __('Exit gallery: Esc') ?></li>
              <li class="mt-1 text-info"><?php echo __('Touch: drag to look, pinch to zoom, tap an object, tap a numbered button to travel') ?></li>
            </ul>
            <div class="mt-2" id="wtTourPick" style="display:none;">
              <label class="form-label mb-1"><i class="fas fa-route me-1"></i><?php echo __('Guided tour') ?></label>
              <select id="wtTourSel" class="form-select form-select-sm"></select>
            </div>
            <div class="mt-2">
              <label class="form-label mb-1"><i class="fas fa-microphone-lines me-1"></i><?php echo __('Narration voice') ?></label>
              <select id="wtVoiceSel" class="form-select form-select-sm"><option value=""><?php echo __('Default') ?></option></select>
            </div>
          </div>
          <div id="wtInlay" style="position:absolute;left:50%;bottom:14px;transform:translateX(-50%);z-index:6;max-width:520px;width:92%;max-height:38vh;overflow-y:auto;display:none;background:rgba(20,22,26,.85);color:#fff;border-radius:.5rem;padding:14px 16px;box-shadow:0 4px 16px rgba(0,0,0,.45);">
            <button type="button" id="inlayClose" class="btn-close btn-close-white" style="position:absolute;top:8px;right:10px;" aria-label="<?php echo __('Close') ?>"></button>
            <h6 id="inlayTitle" class="fw-bold mb-1 pe-4"></h6>
            <p id="inlayDesc" class="small mb-2" style="max-height:22vh;overflow:auto;"></p>
            <a id="inlayRec" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="fas fa-external-link-alt me-1"></i><?php echo __('View full record') ?> <span class="badge bg-secondary ms-1">V</span></a>
            <div id="wtAsk" class="mt-2">
              <div class="input-group input-group-sm">
                <input id="wtAskInput" type="text" class="form-control" placeholder="<?php echo __('Ask about this, or “take me to…”') ?>" maxlength="200">
                <button id="wtAskBtn" type="button" class="btn btn-info" title="<?php echo __('Ask') ?>"><i class="fas fa-comment-dots"></i></button>
              </div>
              <div id="wtAskChips" class="d-flex flex-wrap gap-1 mt-1"></div>
              <div id="wtAskAnswer" class="small mt-2" style="display:none;background:rgba(255,255,255,.08);border-radius:.4rem;padding:6px 8px;"></div>
            </div>
            <div id="wtRelated" class="mt-2" style="display:none;">
              <div class="small text-white-50 mb-1"><i class="fas fa-wand-magic-sparkles me-1"></i><?php echo __('You might also like') ?></div>
              <div id="wtRelatedItems" class="d-flex flex-wrap gap-1"></div>
            </div>
          </div>
          <div id="recOverlay" style="position:absolute;inset:0;z-index:20;display:none;background:rgba(10,12,15,.96);">
            <div style="position:absolute;top:8px;right:10px;z-index:21;display:flex;gap:6px;">
              <a id="recOpenTab" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="<?php echo __('Open in new tab') ?>"><i class="fas fa-external-link-alt"></i></a>
              <button type="button" id="recClose" class="btn btn-sm btn-warning fw-semibold" title="<?php echo __('Back to the gallery') ?>"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back to gallery') ?></button>
            </div>
            <iframe id="recFrame" title="<?php echo __('Full record') ?>" style="position:absolute;inset:0;width:100%;height:100%;border:0;background:#fff;"></iframe>
          </div>
        </div>
        <div id="roomNav" class="d-flex gap-1 p-2 overflow-auto border-top bg-light" style="white-space:nowrap;"></div>
      </div>
    </div>

    <?php // three.js r137: last release with the non-module examples/js globals (PointerLockControls etc.) + WebP textures. ?>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/build/three.min.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/loaders/GLTFLoader.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/loaders/DRACOLoader.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/loaders/OBJLoader.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/loaders/STLLoader.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/loaders/PLYLoader.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/loaders/PCDLoader.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/controls/PointerLockControls.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/controls/OrbitControls.js"></script>
    <script<?php echo $nonce ?> src="https://cdn.jsdelivr.net/npm/three@0.137.5/examples/js/webxr/VRButton.js"></script>
    <script<?php echo $nonce ?> src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script<?php echo $nonce ?> type="application/json" id="exh-wt-config"><?php echo json_encode($wtConfig, $jsonFlags) ?></script>
    <script<?php echo $nonce ?>>
      try { window.EXH_WT = JSON.parse(document.getElementById('exh-wt-config').textContent); } catch (e) { window.EXH_WT = {}; }
    </script>
    <script<?php echo $nonce ?> src="/plugins/ahgExhibitionPlugin/web/js/exhibition-walkthrough-3d.js"></script>
  <?php endif ?>
</div>
