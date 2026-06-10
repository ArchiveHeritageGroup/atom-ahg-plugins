<?php /* heratio#1186 PSIS port — AI Exhibition Designer: theme -> AI-curated draft (rooms + object thumbnails + labels) -> Build real spaces. */ ?>
<?php $n = sfConfig::get('csp_nonce', ''); $nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<div class="container-fluid px-4 py-3 exhibition-space ai-designer">
  <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
    <h1 class="h4 mb-0"><i class="fas fa-wand-magic-sparkles me-2 text-primary"></i><?php echo __('AI Exhibition Designer') ?></h1>
    <span class="text-muted small"><?php echo __('A theme in, a draft exhibition out') ?></span>
    <a href="<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'browse']) ?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="fas fa-arrow-left me-1"></i><?php echo __('Exhibition spaces') ?></a>
  </div>
  <p class="text-muted small"><?php echo __('Describe a theme. PSIS searches the catalogue and the AI curates a draft exhibition - rooms, a selection of objects, and a one-line label for each. Review it here, then build a real Exhibition Space from the draft.') ?></p>

  <div class="input-group mb-2" style="max-width:680px">
    <input type="text" id="geTheme" class="form-control" placeholder="<?php echo esc_entities(__('e.g. women in the liberation struggle, Victorian furniture, WWI letters')) ?>" maxlength="200">
    <button type="button" id="geGo" class="btn btn-primary"><i class="fas fa-wand-magic-sparkles me-1"></i><?php echo __('Design it') ?></button>
  </div>
  <div class="d-flex flex-wrap gap-1 mb-2" id="geChips"></div>
  <div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="gePublished" checked>
    <label class="form-check-label small text-muted" for="gePublished"><?php echo __('Published records only') ?></label>
  </div>

  <div id="geErr" class="alert alert-warning" style="display:none"></div>
  <div id="geOk" class="alert alert-success" style="display:none"></div>
  <div id="geResult"></div>
</div>

<script<?php echo $nonce ?>>
(function () {
  var SUGGEST_URL = '<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'generateSuggest']) ?>';
  var BUILD_URL = '<?php echo url_for(['module' => 'exhibitionSpace', 'action' => 'generateBuild']) ?>';
  var themeEl = document.getElementById('geTheme'),
      goBtn = document.getElementById('geGo'),
      errEl = document.getElementById('geErr'),
      okEl = document.getElementById('geOk'),
      res = document.getElementById('geResult');
  var lastDraft = null;

  var L = {
    curating: <?php echo json_encode(__('Curating…'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    designIt: <?php echo json_encode(__('Design it'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    building: <?php echo json_encode(__('Building…'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    buildIt: <?php echo json_encode(__('Build this exhibition'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    buildHint: <?php echo json_encode(__('Creates a real Exhibition Space - one room per card above, each object placed - then opens it in the builder.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    noMatch: <?php echo json_encode(__('No catalogue objects matched that theme. Try different or broader words.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    noDraft: <?php echo json_encode(__('Could not draft an exhibition for that theme. Try again or rephrase.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    wrong: <?php echo json_encode(__('Something went wrong. Please try again.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    noBuild: <?php echo json_encode(__('Could not build the exhibition from this draft. Please try again.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    wrongBuild: <?php echo json_encode(__('Something went wrong while building. Please try again.'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    built: <?php echo json_encode(__('Exhibition built. Opening the builder…'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    objects: <?php echo json_encode(__('objects'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  };

  var samples = [
    <?php echo json_encode(__('women in the liberation struggle'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    <?php echo json_encode(__('Victorian furniture'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    <?php echo json_encode(__('maritime history'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    <?php echo json_encode(__('colonial-era photography'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  ];
  var chips = document.getElementById('geChips');
  samples.forEach(function (s) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'btn btn-sm btn-outline-secondary';
    b.textContent = s;
    b.addEventListener('click', function () { themeEl.value = s; run(); });
    chips.appendChild(b);
  });

  function esc(t) { var d = document.createElement('div'); d.textContent = (t === null || t === undefined) ? '' : t; return d.innerHTML; }

  function run() {
    var theme = themeEl.value.trim();
    if (!theme) { themeEl.focus(); return; }
    errEl.style.display = 'none'; okEl.style.display = 'none'; res.innerHTML = '';
    goBtn.disabled = true;
    goBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i>' + esc(L.curating);
    fetch(SUGGEST_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        theme: theme,
        published_only: document.getElementById('gePublished').checked ? 1 : 0
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        goBtn.disabled = false;
        goBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>' + esc(L.designIt);
        var draft = (d && d.draft) ? d.draft : null;
        var rooms = draft && draft.rooms ? draft.rooms : null;
        if (!d || !d.ok || !rooms || !rooms.length) {
          errEl.style.display = 'block';
          errEl.textContent = (draft && draft.candidate_count === 0) ? L.noMatch
            : ((d && d.error) ? d.error : L.noDraft);
          return;
        }
        renderDraft(theme, rooms);
      })
      .catch(function () {
        goBtn.disabled = false;
        goBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i>' + esc(L.designIt);
        errEl.style.display = 'block';
        errEl.textContent = L.wrong;
      });
  }

  function renderDraft(theme, rooms) {
    var html = '<div class="row g-3">';
    rooms.forEach(function (rm) {
      var objs = rm.objects || [];
      var title = rm.title || rm.room || '';
      html += '<div class="col-md-6 col-xl-4"><div class="card h-100">'
        + '<div class="card-header py-2"><i class="fas fa-door-open me-1 text-primary"></i><strong>' + esc(title) + '</strong>'
        + ' <span class="badge bg-secondary ms-1">' + objs.length + '</span></div>';
      if (rm.label) {
        html += '<div class="card-body pb-0 pt-2"><div class="small text-muted fst-italic">' + esc(rm.label) + '</div></div>';
      }
      html += '<ul class="list-group list-group-flush">';
      objs.forEach(function (o) {
        var thumb = o.thumb_url
          ? '<img src="' + esc(o.thumb_url) + '" alt="" class="rounded me-2 flex-shrink-0" style="width:48px;height:48px;object-fit:cover">'
          : '<span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted me-2 flex-shrink-0" style="width:48px;height:48px"><i class="fas fa-image"></i></span>';
        html += '<li class="list-group-item d-flex align-items-start">' + thumb
          + '<div class="flex-grow-1"><div class="small fw-bold">' + esc(o.title)
          + (o.year ? ' <span class="badge bg-light text-dark border ms-1">' + esc('' + o.year) + '</span>' : '') + '</div>'
          + (o.label ? '<div class="small text-muted">' + esc(o.label) + '</div>' : '') + '</div></li>';
      });
      html += '</ul></div></div>';
    });
    html += '</div>';
    html += '<div class="d-flex flex-wrap align-items-center gap-2 mt-3">'
      + '<button type="button" id="geBuild" class="btn btn-success"><i class="fas fa-cubes me-1"></i>' + esc(L.buildIt) + '</button>'
      + '<span class="small text-muted">' + esc(L.buildHint) + '</span></div>';
    res.innerHTML = html;

    // Keep the full draft (titles + labels + object ids) so the build reproduces it.
    lastDraft = {
      theme: theme,
      rooms: rooms.map(function (rm) {
        return {
          title: rm.title || rm.room || '',
          label: rm.label || '',
          objects: (rm.objects || []).map(function (o) { return { id: o.id, title: o.title, label: o.label }; })
        };
      })
    };
    var buildBtn = document.getElementById('geBuild');
    if (buildBtn) { buildBtn.addEventListener('click', build); }
  }

  function build() {
    if (!lastDraft || !lastDraft.rooms || !lastDraft.rooms.length) { return; }
    var btn = document.getElementById('geBuild');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i>' + esc(L.building);
    errEl.style.display = 'none'; okEl.style.display = 'none';
    fetch(BUILD_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ draft: lastDraft })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.ok && d.builder_url) {
          okEl.style.display = 'block';
          okEl.textContent = L.built;
          window.location.href = d.builder_url;
          return;
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cubes me-1"></i>' + esc(L.buildIt);
        errEl.style.display = 'block';
        errEl.textContent = (d && d.error) ? d.error : L.noBuild;
      })
      .catch(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cubes me-1"></i>' + esc(L.buildIt);
        errEl.style.display = 'block';
        errEl.textContent = L.wrongBuild;
      });
  }

  goBtn.addEventListener('click', run);
  themeEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); run(); } });
})();
</script>
