<?php /* AtoM escaping_strategy=true wraps action vars in sfOutputEscaper; unescape before json_encode/use. */ foreach (["space","days","data","visitors","heatmap","sensor","rooms","timeline","building","placements","roomDims","guidedTour","walls","doors","windows","shape","capacityUnits","furniture","tourObjects","plan","navBtns","stairs","corridorObjects","bootData","wtConfig"] as $__ev) { if (isset($$__ev)) { $$__ev = sfOutputEscaper::unescape($$__ev); } } ?>
<?php
/*
 * Exhibition Space — CONSERVATION FORECAST (simulation & prediction).
 * Faithful port of Heratio packages/ahg-exhibition resources/views/exhibition-space/forecast.blade.php
 * (heratio#1147 / #1189 time-scrubber). Blade -> Symfony PHP; route() -> url_for(); @json -> json_encode.
 *
 * View vars (set by exhibitionSpaceActions::executeForecast):
 *   $space    - the space row (->slug, ->name, ->id)
 *   $rooms    - buildingForecast(): [{name,avg_lux,lux_target,annual_dose,budget,pct_of_budget,
 *                                     days_to_budget,risk(ok|warn|alert|none),avg_visitors,peak_visitors,capacity}]
 *   $timeline - conservationTimeline(): ['rooms'[{id,name,x,z,w,d}],'buckets'[{label,future}],'status'{rid:[green|amber|red|none]},'min_x','max_x','min_z','max_z']
 *
 * AtoM/Symfony notes vs Heratio (Laravel): page is PUBLIC; Chart.js not needed here (pure canvas + what-if calc).
 * Inline <script>/<style> carry the CSP nonce.
 */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

$JSON = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT;

$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
$timeline = isset($timeline) && is_array($timeline) ? $timeline : null;

$u = function ($action, $extra = []) use ($space) {
    return url_for(array_merge(['module' => 'exhibitionSpace', 'action' => $action, 'slug' => $space->slug], $extra));
};

// Inline nav-action bar (port of _nav-actions.blade.php) — same buttons/order, "forecast" active.
$navBtns = [
    ['key' => 'browse', 'url' => url_for(['module' => 'exhibitionSpace', 'action' => 'browse']), 'icon' => 'fa-th-list', 'label' => __('All spaces')],
    ['key' => 'builder', 'url' => $u('builder'), 'icon' => 'fa-pen-ruler', 'label' => __('Builder')],
    ['key' => 'plan', 'url' => $u('plan'), 'icon' => 'fa-drafting-compass', 'label' => __('Building Plan')],
    ['key' => 'walkthrough', 'url' => $u('walkthrough'), 'icon' => 'fa-vr-cardboard', 'label' => __('Walkthrough')],
    ['key' => 'analytics', 'url' => $u('analytics'), 'icon' => 'fa-chart-line', 'label' => __('Analytics')],
    ['key' => 'forecast', 'url' => $u('forecast'), 'icon' => 'fa-chart-area', 'label' => __('Forecast')],
    ['key' => 'show', 'url' => $u('show'), 'icon' => 'fa-eye', 'label' => __('Open')],
];
$current = 'forecast';

$badgeMap = ['ok' => 'success', 'warn' => 'warning', 'alert' => 'danger', 'none' => 'secondary'];
?>
<div class="container-fluid px-4 py-3 exhibition-space forecast">
  <div class="d-flex flex-wrap align-items-baseline mb-2 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-chart-line me-2"></i><?php echo __('Conservation forecast') ?> <small class="text-muted"><?php echo esc_entities($space->name) ?></small></h1>
    <div class="d-flex flex-wrap gap-1 exhibition-nav-actions">
      <?php foreach ($navBtns as $b): ?>
        <?php if ($current === $b['key']): ?>
          <span class="btn btn-sm btn-outline-primary active disabled" aria-current="page"><i class="fas <?php echo $b['icon'] ?> me-1"></i><?php echo $b['label'] ?></span>
        <?php else: ?>
          <a href="<?php echo $b['url'] ?>" class="btn btn-sm btn-outline-primary" title="<?php echo $b['label'] ?>"><i class="fas <?php echo $b['icon'] ?> me-1"></i><?php echo $b['label'] ?></a>
        <?php endif ?>
      <?php endforeach ?>
    </div>
  </div>
  <p class="text-muted small mb-3"><?php echo __('Projected from the last 30 days of light readings: annual light dose (lux-hours) vs the material light budget, time until the budget is reached, and visitor load. Budgets follow international museum guidance (very sensitive 50k, sensitive 150k, durable 600k lux-hours/year).') ?></p>

  <?php // heratio#1189 — conservation time-scrubber: drag time, watch the rooms change colour ?>
  <?php if ($timeline !== null && count($timeline['rooms'])): ?>
  <div class="card mb-3">
    <div class="card-header py-2"><strong><i class="fas fa-clock-rotate-left me-1"></i><?php echo __('Conservation time machine') ?></strong> <small class="text-muted"><?php echo __('drag time; rooms shade by conservation status') ?></small></div>
    <div class="card-body">
      <canvas id="tlCanvas" style="width:100%;max-width:760px;display:block;margin:0 auto;background:#f8f9fa;border-radius:6px"></canvas>
      <div class="d-flex align-items-center gap-2 mt-2" style="max-width:760px;margin:0 auto">
        <input type="range" id="tlSlider" class="form-range flex-grow-1" min="0" max="0" step="1">
        <span id="tlLabel" class="badge bg-secondary" style="min-width:120px"></span>
      </div>
      <div class="small text-muted mt-1 d-flex gap-3 justify-content-center">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#46c06b"></span> <?php echo __('OK') ?></span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#f4d03f"></span> <?php echo __('Watch') ?></span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#e8553a"></span> <?php echo __('At risk') ?></span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#eef1f4"></span> <?php echo __('No data') ?></span>
      </div>
    </div>
  </div>
  <script<?php echo $nonce ?>>
  (function () {
    var T = <?php echo json_encode($timeline, $JSON) ?>;
    var PROJECTED = <?php echo json_encode(__('projected'), $JSON) ?>;
    var cv = document.getElementById('tlCanvas'); if (!cv || !T.rooms || !T.rooms.length || !T.buckets.length) return;
    var slider = document.getElementById('tlSlider'), label = document.getElementById('tlLabel');
    var W = 760, H = 430; cv.width = W; cv.height = H; var ctx = cv.getContext('2d');
    var bw = (T.max_x - T.min_x) || 1, bd = (T.max_z - T.min_z) || 1, pad = 26;
    var sc = Math.min((W - 2 * pad) / bw, (H - 2 * pad) / bd);
    var ox = (W - bw * sc) / 2 - T.min_x * sc, oy = (H - bd * sc) / 2 - T.min_z * sc;
    var COL = { green: '#46c06b', amber: '#f4d03f', red: '#e8553a', none: '#eef1f4' };
    var todayIdx = 0; for (var i = 0; i < T.buckets.length; i++) { if (!T.buckets[i].future) todayIdx = i; }
    slider.max = T.buckets.length - 1; slider.value = todayIdx;
    function draw(idx) {
      var b = T.buckets[idx];
      label.textContent = b.label + (b.future ? ' · ' + PROJECTED : '');
      label.className = 'badge ' + (b.future ? 'bg-info' : 'bg-secondary');
      ctx.clearRect(0, 0, W, H);
      T.rooms.forEach(function (rm) {
        var st = (T.status[rm.id] || [])[idx] || 'none';
        var x = ox + rm.x * sc, y = oy + rm.z * sc, w = rm.w * sc, h = rm.d * sc;
        ctx.fillStyle = COL[st] || COL.none; ctx.fillRect(x, y, w, h);
        ctx.strokeStyle = '#adb5bd'; ctx.lineWidth = 1; ctx.strokeRect(x, y, w, h);
        ctx.fillStyle = '#212529'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(rm.name || '', x + w / 2, y + h / 2 + 3);
      });
    }
    slider.addEventListener('input', function () { draw(+slider.value); });
    draw(todayIdx);
  })();
  </script>
  <?php endif ?>

  <?php // What-if simulator ?>
  <div class="card mb-3">
    <div class="card-header py-2"><strong><i class="fas fa-sliders-h me-1"></i><?php echo __('What-if simulator') ?></strong></div>
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label small mb-0"><?php echo __('Light level (lux)') ?></label><input type="number" id="wLux" class="form-control form-control-sm" value="200" min="0" step="10"></div>
        <div class="col-auto"><label class="form-label small mb-0"><?php echo __('Display hours/day') ?></label><input type="number" id="wHours" class="form-control form-control-sm" value="8" min="0" max="24" step="0.5"></div>
        <div class="col-auto"><label class="form-label small mb-0"><?php echo __('Lux target') ?></label><input type="number" id="wTarget" class="form-control form-control-sm" value="200" min="0" step="10"></div>
      </div>
      <div id="wOut" class="mt-3 small"></div>
    </div>
  </div>

  <?php // Per-room forecast ?>
  <div class="card">
    <div class="card-header py-2"><strong><?php echo __('Per-room forecast') ?></strong></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Room') ?></th>
              <th class="text-end"><?php echo __('Avg lux') ?></th>
              <th class="text-end"><?php echo __('Target') ?></th>
              <th class="text-end"><?php echo __('Annual dose') ?></th>
              <th class="text-end"><?php echo __('Budget') ?></th>
              <th class="text-end"><?php echo __('% of budget') ?></th>
              <th class="text-end"><?php echo __('Days to budget') ?></th>
              <th><?php echo __('Risk') ?></th>
              <th class="text-end"><?php echo __('Visitors (avg/peak)') ?></th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($rooms)): foreach ($rooms as $r): ?>
            <?php $badge = $badgeMap[$r['risk']] ?? 'secondary'; ?>
            <tr>
              <td><?php echo esc_entities($r['name']) ?></td>
              <td class="text-end"><?php echo $r['avg_lux'] !== null ? esc_entities($r['avg_lux']) : '—' ?></td>
              <td class="text-end"><?php echo $r['lux_target'] !== null ? (int) $r['lux_target'] : '—' ?></td>
              <td class="text-end"><?php echo $r['annual_dose'] !== null ? number_format($r['annual_dose']) : '—' ?></td>
              <td class="text-end"><?php echo number_format($r['budget']) ?></td>
              <td class="text-end"><?php echo $r['pct_of_budget'] !== null ? esc_entities($r['pct_of_budget']).'%' : '—' ?></td>
              <td class="text-end"><?php echo $r['days_to_budget'] !== null ? number_format($r['days_to_budget']) : '—' ?></td>
              <td><span class="badge bg-<?php echo $badge ?>"><?php echo strtoupper($r['risk']) ?></span></td>
              <td class="text-end">
                <?php echo $r['avg_visitors'] !== null ? esc_entities($r['avg_visitors']) : '—' ?> / <?php echo $r['peak_visitors'] !== null ? (int) $r['peak_visitors'] : '—' ?>
                <?php if ($r['capacity'] !== null): ?><small class="text-muted"> (<?php echo (int) $r['capacity'] ?>)</small><?php endif ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="9" class="p-3 text-muted"><?php echo __('No rooms in this building.') ?></td></tr>
          <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <p class="text-muted small mt-2"><?php echo __('No readings yet? Open the Digital Twin Builder and use "Simulate live data", or POST sensor readings to the space readings endpoint.') ?></p>

  <script<?php echo $nonce ?>>
  (function () {
    var OPEN_DAYS = 312;
    var I18N = <?php echo json_encode([
        'annualDose' => __('Annual dose'),
        'luxHours' => __('lux-hours'),
        'budget' => __('Budget'),
        'ofBudget' => __('of budget'),
        'reachesIn' => __('Reaches budget in'),
        'displayDays' => __('display-days'),
    ], $JSON) ?>;
    function budgetFor(t) { if (t === '' || t == null) return 150000; t = +t; if (t <= 50) return 50000; if (t <= 200) return 150000; return 600000; }
    function calc() {
      var lux = parseFloat(document.getElementById('wLux').value) || 0;
      var hrs = parseFloat(document.getElementById('wHours').value) || 0;
      var target = document.getElementById('wTarget').value;
      var budget = budgetFor(target);
      var annual = lux * hrs * OPEN_DAYS;
      var pct = budget > 0 ? (annual / budget * 100) : 0;
      var days = (lux > 0 && hrs > 0) ? Math.round(budget / (lux * hrs)) : null;
      var risk = pct > 150 ? ['danger', 'ALERT'] : (pct > 100 ? ['warning', 'WARN'] : ['success', 'OK']);
      document.getElementById('wOut').innerHTML =
        I18N.annualDose + ': <b>' + Math.round(annual).toLocaleString() + '</b> ' + I18N.luxHours + ' &middot; ' +
        I18N.budget + ': ' + budget.toLocaleString() + ' &middot; ' +
        '<b>' + pct.toFixed(1) + '%</b> ' + I18N.ofBudget + ' ' +
        '<span class="badge bg-' + risk[0] + '">' + risk[1] + '</span> &middot; ' +
        I18N.reachesIn + ' <b>' + (days != null ? days.toLocaleString() + ' ' + I18N.displayDays : '—') + '</b>';
    }
    ['wLux', 'wHours', 'wTarget'].forEach(function (id) { document.getElementById(id).addEventListener('input', calc); });
    calc();
  })();
  </script>
</div>
