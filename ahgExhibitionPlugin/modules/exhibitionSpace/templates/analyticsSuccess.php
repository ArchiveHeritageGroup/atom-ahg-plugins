<?php /* AtoM escaping_strategy=true wraps action vars in sfOutputEscaper; unescape before json_encode/use. */ foreach (["space","days","data","visitors","heatmap","sensor","rooms","timeline","building","placements","roomDims","guidedTour","walls","doors","windows","shape","capacityUnits","furniture","tourObjects","plan","navBtns","stairs","corridorObjects","bootData","wtConfig"] as $__ev) { if (isset($$__ev)) { $$__ev = sfOutputEscaper::unescape($$__ev); } } ?>
<?php
/*
 * Exhibition Space — ANALYTICS dashboard (historical reading trends + visitors + heatmap).
 * Faithful port of Heratio packages/ahg-exhibition resources/views/exhibition-space/analytics.blade.php
 * (heratio#1148 / #1173 / #1187 / #1188). Blade -> Symfony PHP; route() -> url_for(); @json -> json_encode.
 *
 * View vars (set by exhibitionSpaceActions::executeAnalytics):
 *   $space    - the space row (->slug, ->name, ->id)
 *   $days     - selected period (int)
 *   $data     - buildingAnalytics(): ['bucket','labels'[],'metrics'[],'rooms'[{id,name}],'series'{m:{rid:[]}},'summary'{rid:{m:{avg,latest,max}}}]
 *   $visitors - visitorAnalytics(): ['sessions','avg_seconds','devices'{dev:n},'dwell'[{room,seconds}],'top_objects'[{title,views}]]
 *   $heatmap  - visitorHeatmap(): ['rooms'[{x,z,w,d,name,seconds}],'objects'[{x,z,views}],'min_x','max_x','min_z','max_z','max_seconds','max_views']
 *   $sensor   - ['token'=>str,'alerts'=>[{severity,message,at}]] when authenticated, else null
 *
 * AtoM/Symfony notes vs Heratio (Laravel):
 *   - Page is PUBLIC (no requireAuth); the sensor card only renders when $sensor is set (logged-in staff).
 *   - @csrf / csrf_token() dropped: PSIS write actions authorise via session and read the JSON body.
 *   - Carbon ...->diffForHumans() replaced with a tiny inline relative-time helper.
 *   - Chart.js@4.4.1 from cdn.jsdelivr.net with the CSP nonce.
 */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

$JSON = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT;

$days = isset($days) ? (int) $days : 7;
$data = isset($data) && is_array($data) ? $data : ['bucket' => '', 'labels' => [], 'metrics' => [], 'rooms' => [], 'series' => [], 'summary' => []];
$visitors = isset($visitors) && is_array($visitors) ? $visitors : null;
$heatmap = isset($heatmap) && is_array($heatmap) ? $heatmap : null;
$sensor = isset($sensor) && is_array($sensor) ? $sensor : null;

// Relative-time helper (Carbon diffForHumans replacement).
$rel = function ($ts) {
    if (!$ts) {
        return '';
    }
    $t = is_numeric($ts) ? (int) $ts : strtotime((string) $ts);
    if (!$t) {
        return '';
    }
    $d = time() - $t;
    $past = $d >= 0;
    $d = abs($d);
    if ($d < 60) {
        $s = __('just now');

        return $s;
    }
    if ($d < 3600) {
        $v = (int) floor($d / 60);
        $s = $v.' '.__('min');
    } elseif ($d < 86400) {
        $v = (int) floor($d / 3600);
        $s = $v.' '.__('h');
    } else {
        $v = (int) floor($d / 86400);
        $s = $v.' '.__('d');
    }

    return $past ? $s.' '.__('ago') : __('in').' '.$s;
};

$u = function ($action, $extra = []) use ($space) {
    return url_for(array_merge(['module' => 'exhibitionSpace', 'action' => $action, 'slug' => $space->slug], $extra));
};

// Inline nav-action bar (port of _nav-actions.blade.php) — same buttons/order, "analytics" active.
$navBtns = [
    ['key' => 'browse', 'url' => url_for(['module' => 'exhibitionSpace', 'action' => 'browse']), 'icon' => 'fa-th-list', 'label' => __('All spaces')],
    ['key' => 'builder', 'url' => $u('builder'), 'icon' => 'fa-pen-ruler', 'label' => __('Builder')],
    ['key' => 'plan', 'url' => $u('plan'), 'icon' => 'fa-drafting-compass', 'label' => __('Building Plan')],
    ['key' => 'walkthrough', 'url' => $u('walkthrough'), 'icon' => 'fa-vr-cardboard', 'label' => __('Walkthrough')],
    ['key' => 'analytics', 'url' => $u('analytics'), 'icon' => 'fa-chart-line', 'label' => __('Analytics')],
    ['key' => 'forecast', 'url' => $u('forecast'), 'icon' => 'fa-chart-area', 'label' => __('Forecast')],
    ['key' => 'show', 'url' => $u('show'), 'icon' => 'fa-eye', 'label' => __('Open')],
];
$current = 'analytics';

$periods = ['1' => __('24 hours'), '7' => __('7 days'), '30' => __('30 days'), '90' => __('90 days')];
$chartMetrics = ['lux' => __('Light (lux)'), 'temp_c' => __('Temperature (C)'), 'humidity' => __('Humidity (%)'), 'visitors' => __('Visitors')];
?>
<div class="container-fluid px-4 py-3 exhibition-space analytics">
  <div class="d-flex flex-wrap align-items-baseline mb-2 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-chart-area me-2"></i><?php echo __('Analytics') ?> <small class="text-muted"><?php echo esc_entities($space->name) ?></small></h1>
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
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="small text-muted"><?php echo __('Period') ?>:</span>
    <?php foreach ($periods as $d => $lbl): ?>
      <a href="<?php echo $u('analytics', ['days' => $d]) ?>" class="btn btn-sm <?php echo (int) $days === (int) $d ? 'btn-primary' : 'btn-outline-secondary' ?>"><?php echo $lbl ?></a>
    <?php endforeach ?>
    <span class="small text-muted ms-2"><?php echo __('Bucketed by') ?> <?php echo esc_entities($data['bucket']) ?></span>
  </div>

  <?php // heratio#1188 — live sensor binding + conservation alerts (staff only: token is sensitive) ?>
  <?php if ($sensor): ?>
  <div class="card mb-3">
    <div class="card-header py-2"><strong><i class="fas fa-satellite-dish me-1"></i><?php echo __('Live sensor feed') ?></strong> <small class="text-muted"><?php echo __('bind a real light / temperature / humidity sensor to this space') ?></small></div>
    <div class="card-body">
      <?php if (count($sensor['alerts'])): ?>
        <div class="mb-3">
          <div class="fw-bold small mb-1 text-danger"><i class="fas fa-triangle-exclamation me-1"></i><?php echo __('Conservation alerts') ?></div>
          <?php foreach ($sensor['alerts'] as $a): ?>
            <div class="alert alert-<?php echo ($a['severity'] === 'critical') ? 'danger' : 'warning' ?> py-1 px-2 mb-1 small d-flex justify-content-between">
              <span><?php echo esc_entities($a['message']) ?></span><span class="text-muted ms-2"><?php echo esc_entities($rel($a['at'])) ?></span>
            </div>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <div class="small text-muted mb-2"><i class="fas fa-check-circle text-success me-1"></i><?php echo __('No conservation alerts.') ?></div>
      <?php endif ?>
      <div class="small mb-1"><?php echo __('Sensors POST readings to this endpoint with the space token:') ?></div>
      <pre class="small bg-light p-2 rounded" style="white-space:pre-wrap">curl -X POST <?php echo esc_entities($u('recordReadings')) ?> \
  -H "X-Sensor-Token: <?php echo esc_entities($sensor['token']) ?>" -H "Content-Type: application/json" \
  -d '{"readings":[{"metric":"temp_c","value":21.5},{"metric":"humidity","value":52},{"metric":"lux","value":180}]}'</pre>
      <div class="d-flex align-items-center gap-2">
        <code class="small"><?php echo esc_entities($sensor['token']) ?></code>
        <button type="button" id="sxRegen" class="btn btn-sm btn-outline-danger"><?php echo __('Regenerate token') ?></button>
        <span class="small text-muted"><?php echo __('Thresholds: temp 16-24 C, humidity 40-60% RH, light <= 200 lux (or the space target).') ?></span>
      </div>
    </div>
  </div>
  <script<?php echo $nonce ?>>
  (function () {
    var btn = document.getElementById('sxRegen'); if (!btn) return;
    var REGEN_URL = <?php echo json_encode($u('sensorRegen'), $JSON) ?>;
    var CONFIRM = <?php echo json_encode(__('Regenerate the token? Any sensor using the old token will stop working until updated.'), $JSON) ?>;
    btn.addEventListener('click', function () {
      if (!confirm(CONFIRM)) return;
      fetch(REGEN_URL, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: '{}' })
        .then(function (r) { return r.json(); }).then(function (d) { if (d && d.ok) location.reload(); });
    });
  })();
  </script>
  <?php endif ?>

  <?php // heratio#1173 — automatic visitor analytics ?>
  <?php if ($visitors !== null): ?>
  <div class="card mb-3">
    <div class="card-header py-2"><strong><i class="fas fa-users me-1"></i><?php echo __('Visitors') ?></strong> <small class="text-muted"><?php echo __('tracked automatically from the walkthrough') ?></small></div>
    <div class="card-body">
      <div class="row text-center g-2 mb-3">
        <div class="col"><div class="h4 mb-0"><?php echo (int) ($visitors['sessions'] ?? 0) ?></div><div class="small text-muted"><?php echo __('Sessions') ?></div></div>
        <div class="col"><div class="h4 mb-0"><?php echo gmdate('i:s', (int) ($visitors['avg_seconds'] ?? 0)) ?></div><div class="small text-muted"><?php echo __('Avg visit') ?></div></div>
        <div class="col"><div>
          <?php if (!empty($visitors['devices'])): foreach ($visitors['devices'] as $dev => $cnt): ?>
            <span class="badge bg-secondary me-1"><?php echo esc_entities($dev) ?>: <?php echo (int) $cnt ?></span>
          <?php endforeach; else: ?><span class="text-muted small">-</span><?php endif ?>
        </div><div class="small text-muted"><?php echo __('Devices') ?></div></div>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="fw-bold small mb-1"><?php echo __('Dwell time per room') ?></div>
          <?php if (!empty($visitors['dwell'])): foreach ($visitors['dwell'] as $dw): ?>
            <div class="d-flex justify-content-between small border-bottom py-1"><span><?php echo esc_entities($dw['room']) ?></span><span><?php echo gmdate('i:s', (int) $dw['seconds']) ?></span></div>
          <?php endforeach; else: ?><div class="text-muted small"><?php echo __('No data yet.') ?></div><?php endif ?>
        </div>
        <div class="col-md-6">
          <div class="fw-bold small mb-1"><?php echo __('Most-viewed objects') ?></div>
          <?php if (!empty($visitors['top_objects'])): foreach ($visitors['top_objects'] as $o): ?>
            <?php $t = (string) ($o['title'] ?? ''); $t = mb_strlen($t) > 40 ? mb_substr($t, 0, 40).'…' : $t; ?>
            <div class="d-flex justify-content-between small border-bottom py-1"><span><?php echo esc_entities($t) ?></span><span><?php echo (int) $o['views'] ?></span></div>
          <?php endforeach; else: ?><div class="text-muted small"><?php echo __('No data yet.') ?></div><?php endif ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif ?>

  <?php // heratio#1187 — visitor heatmap (rooms shaded by dwell, dots = object attention) ?>
  <?php if ($heatmap !== null && count($heatmap['rooms'])): ?>
  <div class="card mb-3">
    <div class="card-header py-2"><strong><i class="fas fa-fire me-1"></i><?php echo __('Visitor heatmap') ?></strong> <small class="text-muted"><?php echo __('rooms shaded by time spent; red dots = object attention') ?></small></div>
    <div class="card-body">
      <canvas id="heatCanvas" style="width:100%;max-width:760px;display:block;margin:0 auto;background:#f8f9fa;border-radius:6px"></canvas>
      <div class="small text-muted mt-2 d-flex align-items-center gap-2 justify-content-center">
        <span><?php echo __('Less time') ?></span>
        <span style="display:inline-block;width:120px;height:10px;border-radius:5px;background:linear-gradient(90deg,#dfe7ef,#4e9be8,#46c06b,#f4d03f,#e8553a)"></span>
        <span><?php echo __('More time') ?></span>
      </div>
    </div>
  </div>
  <script<?php echo $nonce ?>>
  (function () {
    var H = <?php echo json_encode($heatmap, $JSON) ?>;
    var NOVISITS = <?php echo json_encode(__('no visits'), $JSON) ?>;
    var cv = document.getElementById('heatCanvas'); if (!cv || !H.rooms || !H.rooms.length) return;
    var W = 760, Hh = 430; cv.width = W; cv.height = Hh;
    var ctx = cv.getContext('2d');
    var bw = (H.max_x - H.min_x) || 1, bd = (H.max_z - H.min_z) || 1, pad = 26;
    var sc = Math.min((W - 2 * pad) / bw, (Hh - 2 * pad) / bd);
    var ox = (W - bw * sc) / 2 - H.min_x * sc, oy = (Hh - bd * sc) / 2 - H.min_z * sc;
    function heat(r) {
      var st = [[223,231,239],[78,155,232],[70,192,107],[244,208,63],[232,85,58]];
      var t = Math.max(0, Math.min(1, r)) * (st.length - 1), i = Math.floor(t), f = t - i;
      var a = st[i], b = st[Math.min(i + 1, st.length - 1)];
      return 'rgb(' + Math.round(a[0]+(b[0]-a[0])*f) + ',' + Math.round(a[1]+(b[1]-a[1])*f) + ',' + Math.round(a[2]+(b[2]-a[2])*f) + ')';
    }
    ctx.clearRect(0, 0, W, Hh);
    H.rooms.forEach(function (rm) {
      var x = ox + rm.x * sc, y = oy + rm.z * sc, w = rm.w * sc, h = rm.d * sc;
      var ratio = H.max_seconds > 0 ? rm.seconds / H.max_seconds : 0;
      ctx.fillStyle = rm.seconds > 0 ? heat(ratio) : '#eef1f4';
      ctx.fillRect(x, y, w, h);
      ctx.strokeStyle = '#adb5bd'; ctx.lineWidth = 1; ctx.strokeRect(x, y, w, h);
      ctx.fillStyle = '#212529'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(rm.name || '', x + w / 2, y + h / 2 - 3);
      ctx.fillStyle = '#495057'; ctx.font = '10px sans-serif';
      var lbl = rm.seconds > 0 ? (Math.floor(rm.seconds / 60) + 'm ' + (rm.seconds % 60) + 's') : NOVISITS;
      ctx.fillText(lbl, x + w / 2, y + h / 2 + 11);
    });
    (H.objects || []).forEach(function (o) {
      var x = ox + o.x * sc, y = oy + o.z * sc;
      var rr = 4 + (H.max_views > 0 ? (o.views / H.max_views) * 10 : 0);
      ctx.beginPath(); ctx.arc(x, y, rr, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(232,85,58,0.55)'; ctx.fill();
      ctx.strokeStyle = 'rgba(150,30,15,0.8)'; ctx.lineWidth = 1; ctx.stroke();
    });
  })();
  </script>
  <?php endif ?>

  <?php if (count($data['labels']) === 0): ?>
    <div class="alert alert-info"><?php echo __('No readings in this period yet. Use "Simulate live data" in the Digital Twin Builder, or POST sensor readings to the space readings endpoint.') ?></div>
  <?php endif ?>

  <div class="row g-3" id="charts">
    <?php foreach ($chartMetrics as $m => $title): ?>
      <div class="col-lg-6">
        <div class="card"><div class="card-header py-2"><strong><?php echo $title ?></strong></div>
          <div class="card-body"><canvas id="chart-<?php echo $m ?>" height="160" data-metric="<?php echo $m ?>"></canvas></div>
        </div>
      </div>
    <?php endforeach ?>
  </div>

  <div class="card mt-3">
    <div class="card-header py-2"><strong><?php echo __('Summary') ?></strong></div>
    <div class="card-body p-0"><div class="table-responsive">
      <table class="table table-sm table-hover mb-0 small align-middle">
        <thead class="table-light"><tr>
          <th><?php echo __('Room') ?></th>
          <th class="text-end"><?php echo __('Light avg/latest') ?></th>
          <th class="text-end"><?php echo __('Temp avg/latest') ?></th>
          <th class="text-end"><?php echo __('Humidity avg/latest') ?></th>
          <th class="text-end"><?php echo __('Visitors avg/peak') ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($data['rooms'] as $rm): ?>
          <?php $s = $data['summary'][$rm['id']] ?? []; ?>
          <tr>
            <td><?php echo esc_entities($rm['name']) ?></td>
            <td class="text-end"><?php echo esc_entities($s['lux']['avg'] ?? '—') ?> / <?php echo esc_entities($s['lux']['latest'] ?? '—') ?></td>
            <td class="text-end"><?php echo esc_entities($s['temp_c']['avg'] ?? '—') ?> / <?php echo esc_entities($s['temp_c']['latest'] ?? '—') ?></td>
            <td class="text-end"><?php echo esc_entities($s['humidity']['avg'] ?? '—') ?> / <?php echo esc_entities($s['humidity']['latest'] ?? '—') ?></td>
            <td class="text-end"><?php echo esc_entities($s['visitors']['avg'] ?? '—') ?> / <?php echo esc_entities($s['visitors']['max'] ?? '—') ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"<?php echo $nonce ?>></script>
  <script<?php echo $nonce ?>>
  (function () {
    var DATA = <?php echo json_encode($data, $JSON) ?>;
    if (typeof Chart === 'undefined') return;
    function colour(i) { return 'hsl(' + ((i * 67) % 360) + ',65%,48%)'; }
    DATA.metrics.forEach(function (m) {
      var cv = document.getElementById('chart-' + m); if (!cv) return;
      var datasets = DATA.rooms.map(function (rm, i) {
        return { label: rm.name, data: (DATA.series[m] && DATA.series[m][rm.id]) ? DATA.series[m][rm.id] : [], borderColor: colour(i), backgroundColor: colour(i), spanGaps: true, tension: 0.25, pointRadius: 2, borderWidth: 2 };
      });
      new Chart(cv.getContext('2d'), {
        type: 'line',
        data: { labels: DATA.labels, datasets: datasets },
        options: { responsive: true, interaction: { mode: 'nearest' }, plugins: { legend: { labels: { boxWidth: 10, font: { size: 10 } } } }, scales: { x: { ticks: { maxTicksLimit: 8, font: { size: 9 } } }, y: { beginAtZero: true } } }
      });
    });
  })();
  </script>
</div>
