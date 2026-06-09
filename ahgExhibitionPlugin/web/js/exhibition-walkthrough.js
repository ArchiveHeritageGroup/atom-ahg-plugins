/* #136 — Exhibition Space 2.5D walkthrough: pan/zoom scene + guided tour. */
(function () {
  'use strict';
  var vp = document.getElementById('exh-viewport');
  var scene = document.getElementById('exh-scene');
  if (!vp || !scene) { return; }
  var items = [];
  try { items = JSON.parse(document.getElementById('exh-wt-data').textContent || '[]'); } catch (e) { items = []; }
  var BORDER = 18;

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  // Render framed objects into the scene.
  items.forEach(function (it) {
    var el = document.createElement('div');
    el.style.cssText = 'position:absolute;background:#fff;padding:6px;border-radius:2px;box-shadow:0 10px 22px rgba(0,0,0,.5);';
    el.style.left = (it.x + BORDER) + 'px'; el.style.top = (it.y + BORDER) + 'px';
    el.style.width = it.w + 'px'; el.style.height = it.h + 'px';
    el.title = it.title;
    el.innerHTML = it.thumb
      ? '<img src="' + esc(it.thumb) + '" alt="" style="width:100%;height:100%;object-fit:cover;border:1px solid #ccc">'
      : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;font-size:12px;color:#333;background:#efe9d8;border:1px solid #cbb98f">' + esc(it.title) + '</div>';
    scene.appendChild(el);
  });

  // ── pan / zoom ─────────────────────────────────────────────────────────────
  var panX = 0, panY = 0, zoom = 1;
  function apply(anim) {
    scene.style.transition = anim ? 'transform .7s cubic-bezier(.4,0,.2,1)' : 'none';
    scene.style.transform = 'translate(' + panX + 'px,' + panY + 'px) scale(' + zoom + ')';
  }
  function fit() {
    var sw = scene.offsetWidth, sh = scene.offsetHeight;
    zoom = Math.min(vp.clientWidth / sw, vp.clientHeight / sh) * 0.92;
    panX = (vp.clientWidth - sw * zoom) / 2;
    panY = (vp.clientHeight - sh * zoom) / 2;
    apply(false);
  }
  // center a point (scene coords) in the viewport at a given zoom
  function focusOn(cx, cy, z, anim) {
    zoom = z;
    panX = vp.clientWidth / 2 - cx * z;
    panY = vp.clientHeight / 2 - cy * z;
    apply(anim);
  }

  var dragging = false, sx, sy, opx, opy;
  vp.addEventListener('mousedown', function (e) { dragging = true; sx = e.clientX; sy = e.clientY; opx = panX; opy = panY; vp.style.cursor = 'grabbing'; });
  document.addEventListener('mousemove', function (e) { if (!dragging) { return; } panX = opx + (e.clientX - sx); panY = opy + (e.clientY - sy); apply(false); });
  document.addEventListener('mouseup', function () { dragging = false; vp.style.cursor = 'grab'; });
  vp.addEventListener('wheel', function (e) {
    e.preventDefault();
    var rect = vp.getBoundingClientRect();
    var mx = e.clientX - rect.left, my = e.clientY - rect.top;
    var factor = e.deltaY < 0 ? 1.12 : 0.89;
    var nz = Math.max(0.15, Math.min(4, zoom * factor));
    // zoom toward cursor
    panX = mx - (mx - panX) * (nz / zoom);
    panY = my - (my - panY) * (nz / zoom);
    zoom = nz; apply(false);
  }, { passive: false });

  // ── guided tour ────────────────────────────────────────────────────────────
  var stops = items.filter(function (i) { return i.tour; }).sort(function (a, b) { return a.tour - b.tour; });
  // fall back: if no explicit tour, use all objects in placement order
  if (!stops.length) { stops = items.slice(); }
  var ti = -1;
  var cap = document.getElementById('exh-caption');
  var capStep = document.getElementById('exh-cap-step');
  var capTitle = document.getElementById('exh-cap-title');
  var btnStart = document.getElementById('exh-tour-start');
  var btnPrev = document.getElementById('exh-tour-prev');
  var btnNext = document.getElementById('exh-tour-next');
  var btnStop = document.getElementById('exh-tour-stop');

  function showStop(i) {
    if (i < 0 || i >= stops.length) { return; }
    ti = i;
    var it = stops[i];
    focusOn(it.x + BORDER + it.w / 2, it.y + BORDER + it.h / 2, 1.5, true);
    cap.style.display = '';
    capStep.textContent = 'Stop ' + (i + 1) + ' of ' + stops.length;
    capTitle.textContent = it.title;
    btnPrev.disabled = i === 0;
    btnNext.disabled = i === stops.length - 1;
  }
  function startTour() {
    if (!stops.length) { return; }
    btnStart.style.display = 'none';
    [btnPrev, btnNext, btnStop].forEach(function (b) { b.style.display = ''; });
    showStop(0);
  }
  function stopTour() {
    btnStart.style.display = '';
    [btnPrev, btnNext, btnStop].forEach(function (b) { b.style.display = 'none'; });
    cap.style.display = 'none';
    fit();
  }
  btnStart.addEventListener('click', startTour);
  btnPrev.addEventListener('click', function () { showStop(ti - 1); });
  btnNext.addEventListener('click', function () { showStop(ti + 1); });
  btnStop.addEventListener('click', stopTour);
  document.addEventListener('keydown', function (e) {
    if (btnNext.style.display === 'none') { return; }
    if (e.key === 'ArrowRight') { showStop(Math.min(ti + 1, stops.length - 1)); }
    if (e.key === 'ArrowLeft') { showStop(Math.max(ti - 1, 0)); }
    if (e.key === 'Escape') { stopTour(); }
  });

  // init
  if (!items.length) {
    vp.insertAdjacentHTML('beforeend', '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;opacity:.7">No objects placed yet — open the Builder to lay out this space.</div>');
  } else {
    fit();
  }
  window.addEventListener('resize', function () { if (ti < 0) { fit(); } });
})();
