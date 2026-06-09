/* #136 — Exhibition Space builder: drag/resize objects on a floor plan, save layout. */
(function () {
  'use strict';
  var cfg = window.EXH_BUILDER || {};
  var canvas = document.getElementById('exh-canvas');
  var tray = document.getElementById('exh-tray');
  var inspector = document.getElementById('exh-inspector');
  if (!canvas) { return; }

  var items = [];
  try { items = JSON.parse(document.getElementById('exh-data').textContent || '[]'); } catch (e) { items = []; }

  var byId = {};          // id -> item
  var selectedId = null;

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function renderItem(it) {
    var el = document.createElement('div');
    el.className = 'exh-item';
    el.dataset.id = it.id;
    el.style.cssText = 'position:absolute;background:#fff;border:2px solid rgba(0,0,0,.35);border-radius:2px;box-shadow:0 5px 12px rgba(0,0,0,.28);overflow:hidden;cursor:move;';
    el.style.left = (it.x || 0) + 'px';
    el.style.top = (it.y || 0) + 'px';
    el.style.width = it.w + 'px';
    el.style.height = it.h + 'px';
    el.style.zIndex = it.z || 0;
    var media = it.thumb
      ? '<img src="' + esc(it.thumb) + '" alt="" style="width:100%;height:100%;object-fit:cover;pointer-events:none">'
      : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;font-size:11px;padding:4px;color:#333;background:#efe9d8;pointer-events:none">' + esc(it.title) + '</div>';
    el.innerHTML = media
      + '<span class="exh-resize" style="position:absolute;right:0;bottom:0;width:16px;height:16px;background:rgba(0,0,0,.55);cursor:nwse-resize"></span>'
      + (it.tour ? '<span class="exh-tour" style="position:absolute;top:2px;left:2px;background:#0dcaf0;color:#003;border-radius:8px;font-size:11px;padding:0 5px">' + it.tour + '</span>' : '');
    canvas.appendChild(el);
    bindDrag(el, it);
    return el;
  }

  function renderTray() {
    tray.innerHTML = '';
    var unplaced = items.filter(function (i) { return i.x === null || i.x === undefined; });
    document.getElementById('exh-tray-count').textContent = unplaced.length || '';
    if (!unplaced.length) { tray.innerHTML = '<div class="text-muted small p-2">All objects placed.</div>'; return; }
    unplaced.forEach(function (it) {
      var chip = document.createElement('div');
      chip.className = 'exh-tray-item d-flex align-items-center gap-2 p-1 mb-1 border rounded';
      chip.style.cursor = 'grab';
      chip.dataset.id = it.id;
      var thumb = it.thumb ? '<img src="' + esc(it.thumb) + '" style="width:34px;height:34px;object-fit:cover;border-radius:2px">' : '<span class="badge bg-secondary">#' + it.io + '</span>';
      chip.innerHTML = thumb + '<span class="small text-truncate">' + esc(it.title) + '</span>';
      chip.addEventListener('mousedown', function (ev) { startPlaceFromTray(ev, it); });
      tray.appendChild(chip);
    });
  }

  function startPlaceFromTray(ev, it) {
    ev.preventDefault();
    var rect = canvas.getBoundingClientRect();
    it.x = Math.max(0, ev.clientX - rect.left - it.w / 2);
    it.y = Math.max(0, ev.clientY - rect.top - it.h / 2);
    var el = renderItem(it);
    renderTray();
    select(it.id);
    // begin dragging immediately
    beginDrag(el, it, ev, false);
  }

  // ── drag / resize ────────────────────────────────────────────────────────
  function bindDrag(el, it) {
    el.addEventListener('mousedown', function (ev) {
      var resizing = ev.target.classList.contains('exh-resize');
      select(it.id);
      beginDrag(el, it, ev, resizing);
    });
  }

  function beginDrag(el, it, ev, resizing) {
    ev.preventDefault();
    var rect = canvas.getBoundingClientRect();
    var sx = ev.clientX, sy = ev.clientY, ox = it.x || 0, oy = it.y || 0, ow = it.w, oh = it.h;
    function move(e) {
      if (resizing) {
        it.w = Math.max(30, ow + (e.clientX - sx));
        it.h = Math.max(30, oh + (e.clientY - sy));
        el.style.width = it.w + 'px'; el.style.height = it.h + 'px';
      } else {
        it.x = Math.min(canvas.clientWidth - 10, Math.max(0, ox + (e.clientX - sx)));
        it.y = Math.min(canvas.clientHeight - 10, Math.max(0, oy + (e.clientY - sy)));
        el.style.left = it.x + 'px'; el.style.top = it.y + 'px';
      }
    }
    function up() { document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); }
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup', up);
  }

  // ── selection / inspector ────────────────────────────────────────────────
  function select(id) {
    selectedId = id;
    Array.prototype.forEach.call(canvas.querySelectorAll('.exh-item'), function (b) {
      b.style.outline = b.dataset.id == id ? '3px solid #0d6efd' : 'none';
    });
    var it = byId[id];
    if (!it) { inspector.style.display = 'none'; return; }
    inspector.style.display = '';
    document.getElementById('exh-insp-title').textContent = it.title.slice(0, 40);
    document.getElementById('exh-wall').value = it.wall || 'north';
    document.getElementById('exh-size').value = Math.round((it.w + it.h) / 2);
    document.getElementById('exh-tour').value = it.tour || '';
  }

  // ── save ─────────────────────────────────────────────────────────────────
  function save() {
    var placed = items.filter(function (i) { return i.x !== null && i.x !== undefined; });
    // assign z by DOM order (paint order)
    placed.forEach(function (it, idx) { it.z = idx; });
    var payload = placed.map(function (i) {
      return { id: i.id, pos_x: Math.round(i.x), pos_y: Math.round(i.y), item_w: Math.round(i.w), item_h: Math.round(i.h), wall: i.wall, z_order: i.z, rotation: 0, tour_order: i.tour || '' };
    });
    var btn = document.getElementById('exh-save');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving…';
    var body = new URLSearchParams(); body.set('items', JSON.stringify(payload));
    fetch(cfg.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: body.toString(), credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i>Save layout';
        btn.classList.remove('btn-success'); btn.classList.add(j.success === false ? 'btn-danger' : 'btn-success');
        flash(j.success === false ? ('Error: ' + (j.message || 'save failed')) : ('Saved ' + (j.data && j.data.saved !== undefined ? j.data.saved : '') + ' placement(s).'), j.success === false);
      })
      .catch(function () { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i>Save layout'; flash('Network error saving layout.', true); });
  }

  function flash(msg, err) {
    var d = document.createElement('div');
    d.className = 'alert ' + (err ? 'alert-danger' : 'alert-success') + ' position-fixed top-0 end-0 m-3 shadow';
    d.style.zIndex = 2000; d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(function () { d.remove(); }, 3500);
  }

  // ── init ─────────────────────────────────────────────────────────────────
  items.forEach(function (it) { byId[it.id] = it; if (it.x !== null && it.x !== undefined) { renderItem(it); } });
  renderTray();
  document.getElementById('exh-save').addEventListener('click', save);
  canvas.addEventListener('mousedown', function (e) { if (e.target === canvas) { selectedId = null; inspector.style.display = 'none'; Array.prototype.forEach.call(canvas.querySelectorAll('.exh-item'), function (b) { b.style.outline = 'none'; }); } });

  document.getElementById('exh-wall').addEventListener('change', function () { if (byId[selectedId]) { byId[selectedId].wall = this.value; } });
  document.getElementById('exh-size').addEventListener('input', function () {
    var it = byId[selectedId]; if (!it) { return; }
    it.w = it.h = parseInt(this.value, 10);
    var el = canvas.querySelector('.exh-item[data-id="' + selectedId + '"]');
    if (el) { el.style.width = it.w + 'px'; el.style.height = it.h + 'px'; }
  });
  document.getElementById('exh-tour').addEventListener('input', function () {
    var it = byId[selectedId]; if (!it) { return; }
    it.tour = this.value ? parseInt(this.value, 10) : null;
    var el = canvas.querySelector('.exh-item[data-id="' + selectedId + '"]');
    if (el) { var b = el.querySelector('.exh-tour'); if (b) { b.remove(); } if (it.tour) { var s = document.createElement('span'); s.className = 'exh-tour'; s.style.cssText = 'position:absolute;top:2px;left:2px;background:#0dcaf0;color:#003;border-radius:8px;font-size:11px;padding:0 5px'; s.textContent = it.tour; el.appendChild(s); } }
  });
  document.getElementById('exh-unplace').addEventListener('click', function () {
    var it = byId[selectedId]; if (!it) { return; }
    it.x = it.y = null;
    var el = canvas.querySelector('.exh-item[data-id="' + selectedId + '"]'); if (el) { el.remove(); }
    inspector.style.display = 'none'; renderTray();
  });
})();
