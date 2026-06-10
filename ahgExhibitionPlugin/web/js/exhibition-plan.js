/*
 * Exhibition Space — Building PLAN editor (Konva.js 2D blueprint).
 * Faithful port of Heratio plan.blade.php inline script (heratio#1143/#1169/#1171/#1172).
 *
 * Boot data + URL map come from window.AHG_PLAN_BOOT (set in planSuccess.php) instead of
 * Blade-interpolated vars. PSIS body-contract deltas vs Heratio:
 *   - SAVE: {rooms:[{id,x,y,w,d,rot}]}            (Heratio: flat {room_id,x,y,w,d,rot})
 *   - CORR add:  {information_object_id, fx, fy}  (Heratio: {information_object_id, x, y})
 *   - CORR move: {id, fx, fy}                     (Heratio: {placement_id, x, y})
 *   - CORR remove: {id}                           (Heratio: {placement_id})
 *   - No X-CSRF-TOKEN header (PSIS write actions authorise by session).
 *   - Corridor object search: /api/autocomplete/glam?q=… returning [{id,label,value}].
 * Konva drawing/interaction logic is kept verbatim from the Blade.
 */
(function () {
  var BOOT = window.AHG_PLAN_BOOT || {};
  var URLS = BOOT.urls || {};
  var T = BOOT.i18n || {};
  function t(k, fb) { return (T[k] != null) ? T[k] : (fb != null ? fb : k); }

  var SAVE_URL = URLS.save;
  var DOORS_URL = URLS.doors;
  var WINDOWS_URL = URLS.windows;
  var SHAPE_URL = URLS.shape;
  var ADD_ROOM_URL = URLS.addRoom;
  var GROUP_URL = URLS.group;
  var STAIRS_URL = URLS.stairs;
  var ROOM_FLOOR_URL = URLS.roomFloor;
  var ROOM_LOCK_URL = URLS.roomLock;
  var DELETE_ROOM_URL = URLS.deleteRoom;
  var IMG_RECT_URL = URLS.imageRect;
  var CORR_ADD = URLS.corrAdd;
  var CORR_MOVE = URLS.corrMove;
  var CORR_REMOVE = URLS.corrRemove;
  var EDIT_BASE = URLS.editBase || '';   // contains "__SLUG__" placeholder
  var AUTOCOMPLETE = URLS.autocomplete;

  var PLAN = BOOT.plan || { rooms: [], plan_image: null, plan_rect: null, corridor: [], stairs: [] };
  if (!Array.isArray(PLAN.rooms)) PLAN.rooms = [];

  var wrap = document.getElementById('planWrap');
  if (!wrap) return;
  if (typeof Konva === 'undefined') { wrap.innerHTML = '<div class="p-4 text-muted">' + t('canvasFail') + '</div>'; return; }

  function hdr() { return { 'Content-Type': 'application/json', 'Accept': 'application/json' }; }
  function flagSaving() { var e = document.getElementById('planSave'); if (e) e.textContent = t('saving'); }
  function saved() { var e = document.getElementById('planSave'); if (e) e.textContent = t('allSaved'); }

  var W = Math.max(320, wrap.clientWidth || 800), H = Math.round(W * 0.62);
  // Assign default positions (a simple row) to rooms with no plan coords yet.
  var cursor = 1;
  PLAN.rooms.forEach(function (r) {
    if (r.bld_x === null || r.bld_y === null) { r.bld_x = cursor; r.bld_y = 1; cursor += r.w + 1; }
  });
  // Building extent (with margin) -> pixel scale.
  var ext = { w: 30, h: 22 };
  PLAN.rooms.forEach(function (r) { ext.w = Math.max(ext.w, r.bld_x + r.w + 2); ext.h = Math.max(ext.h, r.bld_y + r.d + 2); });
  if (PLAN.plan_rect) { ext.w = Math.max(ext.w, PLAN.plan_rect.x + PLAN.plan_rect.w + 1); ext.h = Math.max(ext.h, PLAN.plan_rect.y + PLAN.plan_rect.h + 1); }
  var scale = Math.min(W / ext.w, H / ext.h);

  var stage = new Konva.Stage({ container: 'planWrap', width: W, height: H });
  var bg = new Konva.Layer(), layer = new Konva.Layer();
  stage.add(bg); stage.add(layer);
  // Blueprint is world-anchored (metres) so it scales/pins with the rooms.
  var planImg = null, planRect = PLAN.plan_rect || { x: 0, y: 0, w: ext.w, h: ext.h };
  if (PLAN.plan_image) {
    var img = new Image(), bpDone = false;
    var drawBlueprint = function () {
      if (bpDone) return; bpDone = true;
      planImg = new Konva.Image({ image: img, x: planRect.x * scale, y: planRect.y * scale, width: planRect.w * scale, height: planRect.h * scale, opacity: 0.85, listening: false });
      bg.add(planImg); planImg.moveToBottom(); bg.batchDraw();
    };
    img.onload = drawBlueprint;
    img.onerror = function () { console.warn('[plan] blueprint failed to load', PLAN.plan_image); };
    img.src = PLAN.plan_image;
    if (img.complete && img.naturalWidth) drawBlueprint();   // already cached
  } else {
    for (var gx = 0; gx <= ext.w; gx += 2) bg.add(new Konva.Line({ points: [gx * scale, 0, gx * scale, H], stroke: '#e3e3e3', listening: false }));
    for (var gy = 0; gy <= ext.h; gy += 2) bg.add(new Konva.Line({ points: [0, gy * scale, W, gy * scale], stroke: '#e3e3e3', listening: false }));
    bg.draw();
  }

  var tr = new Konva.Transformer({ rotateEnabled: true, keepRatio: false, enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'] });
  layer.add(tr);
  // Snap a dragged room's edges to nearby rooms so they sit flush (no void).
  // Effective footprint bounds (metres): the polygon's tight extent for shaped
  // rooms, the full box otherwise. So snap/overlap use the real room, not the box.
  function effBounds(o, bx, by) {
    var sh = (o.shape && o.shape.length >= 3) ? o.shape : null;
    var minx = 0, maxx = 1, minz = 0, maxz = 1;
    if (sh) { minx = 1; maxx = 0; minz = 1; maxz = 0; sh.forEach(function (p) { if (p.x < minx) minx = p.x; if (p.x > maxx) maxx = p.x; if (p.z < minz) minz = p.z; if (p.z > maxz) maxz = p.z; }); }
    var ox = minx * o.w, oy = minz * o.d, ew = (maxx - minx) * o.w, eh = (maxz - minz) * o.d;
    return { x1: bx + ox, y1: by + oy, x2: bx + ox + ew, y2: by + oy + eh, ox: ox, oy: oy, ew: ew, eh: eh };
  }
  function snapRoom(g) {
    var r = g.getAttr('room');
    if (g.rotation()) return;   // axis-aligned snapping only
    var x = g.x() / scale, y = g.y() / scale, TH = 0.7;
    var A = effBounds(r, x, y), bestDX = null, bestDY = null;
    PLAN.rooms.forEach(function (o) {
      if (o === r || o.rot || o.bld_x === null || (o.floor || 0) !== (r.floor || 0) || (r.group && o.group === r.group)) return;
      var B = effBounds(o, o.bld_x, o.bld_y);
      [[A.x1, B.x1], [A.x1, B.x2], [A.x2, B.x1], [A.x2, B.x2]].forEach(function (p) { var dx = p[1] - p[0]; if (Math.abs(dx) < TH && (bestDX === null || Math.abs(dx) < Math.abs(bestDX))) bestDX = dx; });
      [[A.y1, B.y1], [A.y1, B.y2], [A.y2, B.y1], [A.y2, B.y2]].forEach(function (p) { var dy = p[1] - p[0]; if (Math.abs(dy) < TH && (bestDY === null || Math.abs(dy) < Math.abs(bestDY))) bestDY = dy; });
    });
    if (bestDX !== null) g.x((x + bestDX) * scale);
    if (bestDY !== null) g.y((y + bestDY) * scale);
  }
  // True footprint polygon of a room in world (metre) coords.
  function roomPoly(o, x, y) {
    var sh = (o.shape && o.shape.length >= 3) ? o.shape : null;
    if (sh) return sh.map(function (p) { return { x: x + p.x * o.w, y: y + p.z * o.d }; });
    return [{ x: x, y: y }, { x: x + o.w, y: y }, { x: x + o.w, y: y + o.d }, { x: x, y: y + o.d }];
  }
  function otherCorners(g) {
    var r = g.getAttr('room'), out = [];
    PLAN.rooms.forEach(function (o) {
      if (o === r || o.bld_x === null || o.bld_y === null || (o.floor || 0) !== (r.floor || 0)) return;
      roomPoly(o, o.bld_x, o.bld_y).forEach(function (pt) { out.push(pt); });
    });
    return out;
  }
  function snapVertexWorld(pts, wx, wy) {
    var TH = 0.6, bx = wx, by = wy, bdx = TH, bdy = TH, hitX = false, hitY = false;
    pts.forEach(function (pt) {
      var ddx = Math.abs(pt.x - wx); if (ddx < bdx) { bdx = ddx; bx = pt.x; hitX = true; }
      var ddy = Math.abs(pt.y - wy); if (ddy < bdy) { bdy = ddy; by = pt.y; hitY = true; }
    });
    return { x: bx, y: by, snapped: hitX || hitY };
  }
  function ptInPoly(px, py, poly) {
    var inside = false;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      var xi = poly[i].x, yi = poly[i].y, xj = poly[j].x, yj = poly[j].y;
      if (((yi > py) !== (yj > py)) && (px < (xj - xi) * (py - yi) / (yj - yi) + xi)) inside = !inside;
    }
    return inside;
  }
  function segInt(a, b, c, d) {
    function ccw(p, q, r) { return (r.y - p.y) * (q.x - p.x) > (q.y - p.y) * (r.x - p.x); }
    return ccw(a, c, d) !== ccw(b, c, d) && ccw(a, b, c) !== ccw(a, b, d);
  }
  function polyOverlap(A, B) {
    for (var i = 0; i < A.length; i++) {
      var a1 = A[i], a2 = A[(i + 1) % A.length];
      for (var j = 0; j < B.length; j++) {
        var b1 = B[j], b2 = B[(j + 1) % B.length];
        if (segInt(a1, a2, b1, b2)) return true;
      }
    }
    return ptInPoly(A[0].x, A[0].y, B) || ptInPoly(B[0].x, B[0].y, A);
  }
  function polyCentroid(P) { var sx = 0, sy = 0; P.forEach(function (p) { sx += p.x; sy += p.y; }); return { x: sx / P.length, y: sy / P.length }; }
  function resolveOverlap(g) {
    var r = g.getAttr('room');
    if (g.rotation()) return;   // axis-aligned only
    var x = g.x() / scale, y = g.y() / scale;
    for (var pass = 0; pass < 80; pass++) {
      var A = roomPoly(r, x, y), hit = null;
      for (var i = 0; i < PLAN.rooms.length; i++) {
        var o = PLAN.rooms[i];
        if (o === r || o.rot || o.bld_x === null || o.bld_y === null || (o.floor || 0) !== (r.floor || 0)) continue;
        var B = roomPoly(o, o.bld_x, o.bld_y);
        if (polyOverlap(A, B)) { hit = B; break; }
      }
      if (!hit) break;
      var ca = polyCentroid(A), cb = polyCentroid(hit);
      var dx = ca.x - cb.x, dy = ca.y - cb.y, L = Math.hypot(dx, dy) || 1;
      x = Math.max(0, x + (dx / L) * 0.2); y = Math.max(0, y + (dy / L) * 0.2);
    }
    g.x(x * scale); g.y(y * scale);
  }
  // PSIS contract: bulk-save wraps a single room as {rooms:[{id,...}]}.
  function saveRoom(g) {
    var r = g.getAttr('room');
    var x = g.x() / scale, y = g.y() / scale;
    var w = (g.width() * g.scaleX()) / scale, d = (g.height() * g.scaleY()) / scale;
    r.bld_x = x; r.bld_y = y; r.rot = g.rotation();
    fetch(SAVE_URL, { method: 'POST', headers: hdr(),
      body: JSON.stringify({ rooms: [{ id: r.id, x: x, y: y, w: w, d: d, rot: g.rotation() }] }) })
      .then(function (res) { return res.json(); }).then(saved);
  }
  // ---- Doors (manual openings placed on a room's walls) ----
  var DOOR_LBL = { north: t('top'), south: t('bottom'), west: t('left'), east: t('right') };
  var selectedG = null, DT = 7;   // door marker thickness (px)
  function saveDoors(g) {
    var r = g.getAttr('room');
    flagSaving();
    fetch(DOORS_URL, { method: 'POST', headers: hdr(),
      body: JSON.stringify({ room_id: r.id, doors: (r.doors || []).map(function (d) { return { wall: d.wall, edge: d.edge, pos: d.pos, width: d.width, type: d.type || 'open' }; }) }) })
      .then(function (res) { return res.json(); }).then(saved);
  }
  function drawDoors(g) {
    var r = g.getAttr('room');
    g.find('.door').forEach(function (n) { n.destroy(); });
    var ww = r.w * scale, hh = r.d * scale;
    (r.doors || []).forEach(function (d, idx) {
      if (typeof d.edge === 'number' && r.shape && r.shape.length >= 3) {
        var sa = r.shape[d.edge % r.shape.length], sb = r.shape[(d.edge + 1) % r.shape.length];
        var Ax = sa.x * ww, Ay = sa.z * hh, Bx = sb.x * ww, By = sb.z * hh;
        var L = Math.hypot(Bx - Ax, By - Ay) || 1, ux = (Bx - Ax) / L, uy = (By - Ay) / L;
        var dwpx = Math.min((d.width || 1.6) * scale, L);
        var en = new Konva.Rect({ name: 'door', width: dwpx, height: DT, offsetX: dwpx / 2, offsetY: DT / 2, fill: '#fff', stroke: '#0d6efd', strokeWidth: 2, cornerRadius: 1, rotation: Math.atan2(uy, ux) * 180 / Math.PI, draggable: true });
        en.setAttr('doorIdx', idx);
        var t0 = (d.pos == null ? 0.5 : d.pos); en.x(Ax + ux * t0 * L); en.y(Ay + uy * t0 * L);
        en.on('dragmove', function () { var tt = ((en.x() - Ax) * ux + (en.y() - Ay) * uy) / L; tt = Math.max(0, Math.min(1, tt)); en.x(Ax + ux * tt * L); en.y(Ay + uy * tt * L); d.pos = tt; });
        en.on('dragend', function () { saveDoors(g); });
        en.on('dblclick dbltap', function (e) { e.cancelBubble = true; r.doors.splice(en.getAttr('doorIdx'), 1); drawDoors(g); saveDoors(g); if (selectedG === g) refreshDoorList(g); });
        g.add(en);
        return;
      }
      var dw = Math.min((d.width || 1.6) * scale, (d.wall === 'north' || d.wall === 'south') ? ww : hh);
      var node = new Konva.Rect({ name: 'door', fill: '#fff', stroke: '#0d6efd', strokeWidth: 2, draggable: true, cornerRadius: 1 });
      node.setAttr('doorIdx', idx);
      var horiz = (d.wall === 'north' || d.wall === 'south');
      if (horiz) {
        node.width(dw); node.height(DT);
        node.x(d.pos * ww - dw / 2); node.y((d.wall === 'north' ? 0 : hh) - DT / 2);
        node.on('dragmove', function () {
          node.y((d.wall === 'north' ? 0 : hh) - DT / 2);
          var nx = Math.max(0, Math.min(ww - dw, node.x())); node.x(nx);
          d.pos = Math.max(0, Math.min(1, (nx + dw / 2) / ww));
        });
      } else {
        node.width(DT); node.height(dw);
        node.x((d.wall === 'west' ? 0 : ww) - DT / 2); node.y(d.pos * hh - dw / 2);
        node.on('dragmove', function () {
          node.x((d.wall === 'west' ? 0 : ww) - DT / 2);
          var ny = Math.max(0, Math.min(hh - dw, node.y())); node.y(ny);
          d.pos = Math.max(0, Math.min(1, (ny + dw / 2) / hh));
        });
      }
      node.on('dragend', function () { saveDoors(g); });
      node.on('dblclick dbltap', function (e) { e.cancelBubble = true; r.doors.splice(node.getAttr('doorIdx'), 1); drawDoors(g); saveDoors(g); if (selectedG === g) refreshDoorList(g); });
      g.add(node);
    });
    layer.draw();
  }
  function refreshDoorList(g) {
    var el = document.getElementById('doorList'); if (!el) return;
    var r = g.getAttr('room');
    if (!r.doors || !r.doors.length) { el.innerHTML = '<span class="text-muted">' + t('noDoors') + '</span>'; return; }
    el.innerHTML = '';
    r.doors.forEach(function (d, idx) {
      var row = document.createElement('div');
      row.className = 'd-flex align-items-center gap-1 mb-1';
      var lbl = (typeof d.edge === 'number') ? (t('wall') + ' ' + (d.edge + 1)) : (DOOR_LBL[d.wall] || '?');
      var opts = [['open', t('doorway')], ['single', t('single')], ['double', t('double')], ['glass', t('glass')], ['sliding', t('sliding')], ['ornate', t('ornate')]];
      var sel = '<select class="form-select form-select-sm doortype" style="width:96px">' + opts.map(function (o) { return '<option value="' + o[0] + '"' + ((d.type || 'open') === o[0] ? ' selected' : '') + '>' + o[1] + '</option>'; }).join('') + '</select>';
      row.innerHTML = '<span class="badge bg-secondary">' + lbl + '</span>' +
        '<input type="number" class="form-control form-control-sm" style="width:60px" min="0.5" max="6" step="0.1" value="' + d.width + '"><span class="small text-muted">m</span>' +
        sel +
        '<button class="btn btn-sm btn-outline-danger ms-auto" type="button" title="' + t('remove') + '">&times;</button>';
      row.querySelector('input').addEventListener('change', function (e) { d.width = Math.max(0.5, Math.min(6, parseFloat(e.target.value) || 1.6)); drawDoors(g); saveDoors(g); });
      row.querySelector('.doortype').addEventListener('change', function (e) { d.type = e.target.value; saveDoors(g); });
      row.querySelector('button').addEventListener('click', function () { r.doors.splice(idx, 1); drawDoors(g); saveDoors(g); refreshDoorList(g); });
      el.appendChild(row);
    });
  }
  function addDoor(wall) {
    if (!selectedG) return;
    var r = selectedG.getAttr('room');
    if (!r.doors) r.doors = [];
    r.doors.push({ wall: wall, pos: 0.5, width: 1.6, type: 'open' });
    drawDoors(selectedG); saveDoors(selectedG); refreshDoorList(selectedG);
  }
  // ---- Windows (#1172) ----
  var WIN_LBL = { north: t('top'), south: t('bottom'), west: t('left'), east: t('right') };
  function saveWindows(g) {
    var r = g.getAttr('room'); flagSaving();
    fetch(WINDOWS_URL, { method: 'POST', headers: hdr(),
      body: JSON.stringify({ room_id: r.id, windows: r.windows || [] }) })
      .then(function (res) { return res.json(); }).then(saved);
  }
  function drawWinList(g) {
    var el = document.getElementById('winList'); if (!el) return;
    var r = g.getAttr('room');
    if (!r.windows || !r.windows.length) { el.innerHTML = '<span class="text-muted">' + t('noWindows') + '</span>'; return; }
    el.innerHTML = '';
    r.windows.forEach(function (w, idx) {
      var row = document.createElement('div'); row.className = 'd-flex align-items-center gap-1 mb-1';
      var wlbl = (typeof w.edge === 'number') ? (t('wall') + ' ' + (w.edge + 1)) : (WIN_LBL[w.wall] || w.wall);
      row.innerHTML = '<span class="badge bg-info text-dark">' + wlbl + '</span>' +
        '<span class="small text-muted">pos ' + (+w.pos).toFixed(2) + ' · ' + (+w.width).toFixed(1) + 'm</span>' +
        '<button class="btn btn-sm btn-outline-danger ms-auto" type="button" title="' + t('remove') + '">&times;</button>';
      row.querySelector('button').addEventListener('click', function () { r.windows.splice(idx, 1); saveWindows(g); drawWinList(g); });
      el.appendChild(row);
    });
  }
  function updateWinControls(g) {
    var r = g.getAttr('room'), sel = document.getElementById('winWall'); if (!sel) return;
    var hasShape = r.shape && r.shape.length >= 3;
    sel.innerHTML = '';
    if (hasShape) {
      r.shape.forEach(function (p, i) { var o = document.createElement('option'); o.value = 'edge:' + i; o.textContent = t('wall') + ' ' + (i + 1); sel.appendChild(o); });
    } else {
      [['north', t('top')], ['south', t('bottom')], ['west', t('left')], ['east', t('right')]].forEach(function (wd) { var o = document.createElement('option'); o.value = wd[0]; o.textContent = wd[1]; sel.appendChild(o); });
    }
  }
  function addWindow() {
    if (!selectedG) return;
    var r = selectedG.getAttr('room'); if (!r.windows) r.windows = [];
    var wv = document.getElementById('winWall').value;
    var win = {
      pos: Math.max(0, Math.min(1, parseFloat(document.getElementById('winPos').value) || 0.5)),
      width: Math.max(0.4, Math.min(6, parseFloat(document.getElementById('winW').value) || 1.6)),
      sill: Math.max(0.2, Math.min(2, parseFloat(document.getElementById('winSill').value) || 0.9)),
      height: Math.max(0.4, Math.min(3, parseFloat(document.getElementById('winH').value) || 1.3)),
    };
    if (wv.indexOf('edge:') === 0) win.edge = parseInt(wv.slice(5), 10); else win.wall = wv;   // #1172 polygon-edge windows
    r.windows.push(win);
    saveWindows(selectedG); drawWinList(selectedG);
  }
  function updateDoorControls(g) {
    var r = g.getAttr('room'), hasShape = r.shape && r.shape.length >= 3;
    var wallBtns = document.getElementById('doorWallBtns'), edgeBox = document.getElementById('edgeDoorBtns');
    if (wallBtns) wallBtns.style.display = hasShape ? 'none' : '';
    if (!edgeBox) return;
    edgeBox.style.display = hasShape ? 'flex' : 'none';
    edgeBox.innerHTML = '';
    if (hasShape) {
      r.shape.forEach(function (p, i) {
        var b = document.createElement('button'); b.type = 'button'; b.className = 'btn btn-sm btn-outline-primary';
        b.textContent = t('wall') + ' ' + (i + 1);
        b.addEventListener('click', function () { if (!r.doors) r.doors = []; r.doors.push({ edge: i, pos: 0.5, width: 1.6, type: 'open' }); drawDoors(g); saveDoors(g); refreshDoorList(g); });
        edgeBox.appendChild(b);
      });
    }
  }
  function selectRoom(g) {
    if (shapeMode && shapeG && shapeG !== g) { var prev = shapeG; shapeMode = false; shapeG = null; drawShape(prev); }
    selectedG = g; tr.nodes(((shapeMode && shapeG === g) || g.getAttr('room').locked) ? [] : [g]); layer.draw();
    var nm = g.getAttr('room').name;
    var c = document.getElementById('doorCard');
    if (c) { c.style.display = 'block'; document.getElementById('doorRoomName').textContent = nm; refreshDoorList(g); updateDoorControls(g); }
    var wc = document.getElementById('winCard');   // #1172
    if (wc) { wc.style.display = 'block'; document.getElementById('winRoomName').textContent = nm; updateWinControls(g); drawWinList(g); }
    var rc = document.getElementById('roomCard');
    if (rc) {
      rc.style.display = 'block'; document.getElementById('roomCardName').textContent = nm; document.getElementById('rotInput').value = Math.round(g.rotation());
      var el = document.getElementById('roomEditLink'); if (el) el.href = EDIT_BASE.replace('__SLUG__', encodeURIComponent(g.getAttr('room').slug));
      var ug = document.getElementById('ungroupBtn'); if (ug) ug.style.display = g.getAttr('room').group ? 'block' : 'none';
      var rf = document.getElementById('roomFloor'); if (rf) rf.innerHTML = floorOptsHtml(g.getAttr('room').floor || 0);
      var db = document.getElementById('deleteRoomBtn'); if (db) db.style.display = g.getAttr('room').is_current ? 'none' : 'block';
    }
    setShapeBtn(shapeMode && shapeG === g);
  }
  function deselect() {
    if (shapeMode && shapeG) { var prev = shapeG; shapeMode = false; shapeG = null; drawShape(prev); setShapeBtn(false); }
    selectedG = null; tr.nodes([]);
    var c = document.getElementById('doorCard'); if (c) c.style.display = 'none';
    var wc = document.getElementById('winCard'); if (wc) wc.style.display = 'none';
    var rc = document.getElementById('roomCard'); if (rc) rc.style.display = 'none';
    layer.draw();
  }
  document.querySelectorAll('#doorCard [data-door]').forEach(function (b) { b.addEventListener('click', function () { addDoor(b.getAttribute('data-door')); }); });
  (function () { var wb = document.getElementById('winAdd'); if (wb) wb.addEventListener('click', addWindow); })();   // #1172
  (function () { var ug = document.getElementById('ungroupBtn'); if (ug) ug.addEventListener('click', function () { if (selectedG) { ungroupRoom(selectedG); ug.style.display = 'none'; } }); })();   // #1143
  (function () { var jb = document.getElementById('joinBtn'); if (jb) jb.addEventListener('click', function () { joinMode = !joinMode; joinAnchor = null; jb.classList.toggle('btn-warning', joinMode); jb.classList.toggle('btn-outline-warning', !joinMode); drawJoinDots(); }); })();   // #1143 join corners
  (function () { var ub = document.getElementById('undoBtn'); if (ub) ub.addEventListener('click', doUndo); })();   // #1143 undo move
  document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && (e.key === 'z' || e.key === 'Z')) { var a = document.activeElement; if (a && /INPUT|TEXTAREA|SELECT/.test(a.tagName)) return; e.preventDefault(); doUndo(); } });
  document.addEventListener('keydown', function (e) {   // Shift+arrows fine-tune the selected room's size (Shift+Alt = finer)
    if (!selectedG || !e.shiftKey || selectedG.getAttr('room').locked) return;
    var a = document.activeElement; if (a && /INPUT|TEXTAREA|SELECT/.test(a.tagName)) return;
    var step = e.altKey ? 0.1 : 0.25, r = selectedG.getAttr('room'), ch = false;
    if (e.key === 'ArrowRight') { r.w = Math.max(0.5, r.w + step); ch = true; }
    else if (e.key === 'ArrowLeft') { r.w = Math.max(0.5, r.w - step); ch = true; }
    else if (e.key === 'ArrowDown') { r.d = Math.max(0.5, r.d + step); ch = true; }
    else if (e.key === 'ArrowUp') { r.d = Math.max(0.5, r.d - step); ch = true; }
    if (!ch) return;
    e.preventDefault(); pushUndo([r]);
    var rect = selectedG.findOne('.roomrect'); if (rect) { rect.width(r.w * scale); rect.height(r.d * scale); }
    var lbl = selectedG.findOne('Text'); if (lbl) lbl.width(r.w * scale - 6);
    selectedG.width(r.w * scale); selectedG.height(r.d * scale);
    drawDoors(selectedG); drawShape(selectedG); layer.draw(); flagSaving(); saveRoom(selectedG);
  });
  // M + arrows move the selected room (Shift+arrows resizes it); Alt = finer.
  var mDown = false;
  document.addEventListener('keydown', function (e) { if (e.key === 'm' || e.key === 'M') mDown = true; });
  document.addEventListener('keyup', function (e) { if (e.key === 'm' || e.key === 'M') mDown = false; });
  document.addEventListener('keydown', function (e) {
    if (!selectedG || !mDown || selectedG.getAttr('room').locked) return;
    var a = document.activeElement; if (a && /INPUT|TEXTAREA|SELECT/.test(a.tagName)) return;
    var step = e.altKey ? 0.1 : 0.25, r = selectedG.getAttr('room'), ch = false;
    if (e.key === 'ArrowRight') { r.bld_x += step; ch = true; }
    else if (e.key === 'ArrowLeft') { r.bld_x = Math.max(0, r.bld_x - step); ch = true; }
    else if (e.key === 'ArrowDown') { r.bld_y += step; ch = true; }
    else if (e.key === 'ArrowUp') { r.bld_y = Math.max(0, r.bld_y - step); ch = true; }
    if (!ch) return;
    e.preventDefault(); pushUndo([r]);
    selectedG.x(r.bld_x * scale); selectedG.y(r.bld_y * scale);
    layer.draw(); flagSaving(); saveRoom(selectedG);
  });
  (function () {   // bring the selected room to front / send to back (draw order)
    var f = document.getElementById('roomFront'), b = document.getElementById('roomBack');
    if (f) f.addEventListener('click', function () { if (selectedG) { selectedG.moveToTop(); tr.moveToTop(); layer.draw(); } });
    if (b) b.addEventListener('click', function () { if (selectedG) { selectedG.moveToBottom(); tr.moveToTop(); layer.draw(); } });
  })();
  (function () {   // #1169 set the selected room's (or its whole group's) floor
    var rf = document.getElementById('roomFloor'); if (!rf) return;
    rf.addEventListener('change', function () {
      if (!selectedG) return; var r = selectedG.getAttr('room');
      var nf = Math.max(0, parseInt(rf.value, 10) || 0);
      var targets = r.group ? PLAN.rooms.filter(function (o) { return o.group === r.group; }) : [r];
      flagSaving();
      targets.forEach(function (o) { o.floor = nf; fetch(ROOM_FLOOR_URL, { method: 'POST', headers: hdr(), body: JSON.stringify({ room_id: o.id, floor: nf }) }); });
      saved(); drawStairs(); buildFloorView(); applyFloorView();
    });
  })();
  (function () {   // delete the selected room
    var db = document.getElementById('deleteRoomBtn'); if (!db) return;
    db.addEventListener('click', function () {
      if (!selectedG) return; var r = selectedG.getAttr('room');
      if (!confirm(t('deleteRoomConfirm'))) return;
      flagSaving();
      fetch(DELETE_ROOM_URL, { method: 'POST', headers: hdr(), body: JSON.stringify({ room_id: r.id }) }).then(function (x) { return x.json(); }).then(function (d) {
        if (!d.ok) { alert(t('deleteRoomFail')); return; }
        var n = nodeById[r.id]; if (n) n.destroy(); delete nodeById[r.id];
        var idx = PLAN.rooms.indexOf(r); if (idx >= 0) PLAN.rooms.splice(idx, 1);
        deselect(); renderRoomList(); drawStairs(); buildFloorView(); applyFloorView(); layer.draw(); saved();
      });
    });
  })();
  function applyRot(deg) {
    if (!selectedG) return;
    pushUndo([selectedG.getAttr('room')]);
    selectedG.rotation(((deg % 360) + 360) % 360);
    var _ri = document.getElementById('rotInput'); if (_ri) _ri.value = Math.round(selectedG.rotation());
    layer.draw(); flagSaving(); saveRoom(selectedG);
  }
  (function () {
    var rm = document.getElementById('rotMinus'), rp = document.getElementById('rotPlus'), rz = document.getElementById('rotZero'), ri = document.getElementById('rotInput');
    if (rm) rm.addEventListener('click', function () { if (selectedG) applyRot(selectedG.rotation() - 15); });
    if (rp) rp.addEventListener('click', function () { if (selectedG) applyRot(selectedG.rotation() + 15); });
    if (rz) rz.addEventListener('click', function () { applyRot(0); });
    if (ri) ri.addEventListener('change', function (e) { applyRot(parseFloat(e.target.value) || 0); });
  })();

  // ---- Footprint shape (polygon): make L-shapes / cut corners ----
  var shapeMode = false, shapeG = null;
  function defaultShape() { return [{ x: 0, z: 0 }, { x: 1, z: 0 }, { x: 1, z: 1 }, { x: 0, z: 1 }]; }
  function saveShape(g) {
    var r = g.getAttr('room'); flagSaving();
    fetch(SHAPE_URL, { method: 'POST', headers: hdr(),
      body: JSON.stringify({ room_id: r.id, points: (r.shape && r.shape.length >= 3) ? r.shape : null }) })
      .then(function (res) { return res.json(); }).then(saved);
  }
  function updatePoly(g) {
    var r = g.getAttr('room'), ww = r.w * scale, hh = r.d * scale, poly = g.findOne('.shapepoly');
    if (poly && r.shape) { var pts = []; r.shape.forEach(function (p) { pts.push(p.x * ww, p.z * hh); }); poly.points(pts); layer.draw(); }
  }
  function normalizeShape(g) {
    var r = g.getAttr('room'), sh = (r.shape && r.shape.length >= 3) ? r.shape : null; if (!sh) return;
    var minx = 1, maxx = 0, minz = 1, maxz = 0;
    sh.forEach(function (p) { if (p.x < minx) minx = p.x; if (p.x > maxx) maxx = p.x; if (p.z < minz) minz = p.z; if (p.z > maxz) maxz = p.z; });
    if (minx >= -0.005 && maxx <= 1.005 && minz >= -0.005 && maxz <= 1.005) return;
    var lox = Math.min(0, minx), hix = Math.max(1, maxx), loz = Math.min(0, minz), hiz = Math.max(1, maxz);
    var rw = hix - lox, rh = hiz - loz; if (rw < 0.02 || rh < 0.02) return;
    r.bld_x = r.bld_x + lox * r.w; r.bld_y = r.bld_y + loz * r.d; r.w = rw * r.w; r.d = rh * r.d;
    r.shape = sh.map(function (p) { return { x: (p.x - lox) / rw, z: (p.z - loz) / rh }; });
    g.x(r.bld_x * scale); g.y(r.bld_y * scale); g.width(r.w * scale); g.height(r.d * scale);
    var rect = g.findOne('.roomrect'); if (rect) { rect.width(r.w * scale); rect.height(r.d * scale); }
    saveRoom(g); saveShape(g);
  }
  function clearSnapTargets() { layer.find('.snaptarget').forEach(function (n) { n.destroy(); }); }
  function drawSnapTargets(g) {
    clearSnapTargets();
    var r = g.getAttr('room');
    PLAN.rooms.forEach(function (o) {
      if (o === r || o.bld_x === null || o.bld_y === null || (o.floor || 0) !== (r.floor || 0)) return;
      roomPoly(o, o.bld_x, o.bld_y).forEach(function (pt) {
        layer.add(new Konva.Circle({ name: 'snaptarget', x: pt.x * scale, y: pt.y * scale, radius: 6, fill: 'rgba(25,135,84,0.35)', stroke: '#198754', strokeWidth: 1.5, listening: false }));
      });
    });
    g.moveToTop();   // keep the draggable corners above the target dots
  }
  function drawShape(g) {
    g.find('.shapepoly').forEach(function (n) { n.destroy(); });
    g.find('.shapevert').forEach(function (n) { n.destroy(); });
    g.find('.shapeadd').forEach(function (n) { n.destroy(); });
    g.find('.shapenum').forEach(function (n) { n.destroy(); });
    var r = g.getAttr('room'), ww = r.w * scale, hh = r.d * scale;
    var sh = (r.shape && r.shape.length >= 3) ? r.shape : null;
    var rect = g.findOne('.roomrect');
    if (sh) {
      if (rect) rect.opacity(0.12);
      var pts = []; sh.forEach(function (p) { pts.push(p.x * ww, p.z * hh); });
      g.add(new Konva.Line({ name: 'shapepoly', points: pts, closed: true, fill: r.is_current ? 'rgba(13,110,253,.28)' : 'rgba(108,117,125,.26)', stroke: '#0d6efd', strokeWidth: 1.5, listening: false }));
      var cxp = 0, cyp = 0; sh.forEach(function (p) { cxp += p.x * ww; cyp += p.z * hh; }); cxp /= sh.length; cyp /= sh.length;
      sh.forEach(function (p, i) {
        var q = sh[(i + 1) % sh.length], mx = (p.x + q.x) / 2 * ww, my = (p.z + q.z) / 2 * hh;
        var dx = cxp - mx, dy = cyp - my, dl = Math.hypot(dx, dy) || 1; mx += dx / dl * 11; my += dy / dl * 11;
        g.add(new Konva.Circle({ name: 'shapenum', x: mx, y: my, radius: 8, fill: '#0d6efd', opacity: 0.85, listening: false }));
        g.add(new Konva.Text({ name: 'shapenum', x: mx - 8, y: my - 5, width: 16, align: 'center', text: '' + (i + 1), fontSize: 10, fill: '#fff', listening: false }));
      });
    } else if (rect) { rect.opacity(1); }
    if (shapeMode && shapeG === g && sh) {
      sh.forEach(function (p, idx) {
        var q = sh[(idx + 1) % sh.length];
        var ax = p.x * ww, ay = p.z * hh, bx = q.x * ww, by = q.z * hh;
        var hit = new Konva.Line({ name: 'shapeadd', points: [ax, ay, bx, by], stroke: 'rgba(13,110,253,0.001)', strokeWidth: 16, hitStrokeWidth: 16 });
        hit.on('click tap', function (e) {
          e.cancelBubble = true;
          var lp = g.getRelativePointerPosition();
          var ex = bx - ax, ey = by - ay, L2 = ex * ex + ey * ey || 1;
          var tt = Math.max(0, Math.min(1, ((lp.x - ax) * ex + (lp.y - ay) * ey) / L2));
          r.shape.splice(idx + 1, 0, { x: (ax + tt * ex) / ww, z: (ay + tt * ey) / hh });
          drawShape(g); saveShape(g); if (selectedG === g) updateDoorControls(g);
        });
        g.add(hit);
      });
      sh.forEach(function (p, idx) {
        var q = sh[(idx + 1) % sh.length], mx = (p.x + q.x) / 2, mz = (p.z + q.z) / 2;
        var a = new Konva.Circle({ name: 'shapeadd', x: mx * ww, y: mz * hh, radius: 5, fill: '#198754', stroke: '#fff', strokeWidth: 1.5 });
        a.on('click tap', function (e) { e.cancelBubble = true; r.shape.splice(idx + 1, 0, { x: mx, z: mz }); drawShape(g); saveShape(g); if (selectedG === g) updateDoorControls(g); });
        g.add(a);
      });
      sh.forEach(function (p, idx) {
        var v = new Konva.Circle({ name: 'shapevert', x: p.x * ww, y: p.z * hh, radius: 7, fill: '#fff', stroke: '#0d6efd', strokeWidth: 2, draggable: true });
        v.on('dragmove', function () {
          var wx = g.x() / scale + (v.x() / ww) * r.w, wy = g.y() / scale + (v.y() / hh) * r.d;
          var s = snapVertexWorld(otherCorners(g), wx, wy);
          p.x = Math.max(0, Math.min(1, (s.x - g.x() / scale) / r.w));
          p.z = Math.max(0, Math.min(1, (s.y - g.y() / scale) / r.d));
          v.x(p.x * ww); v.y(p.z * hh);
          v.fill(s.snapped ? '#198754' : '#fff');   // green dot = locked onto an existing point
          updatePoly(g);
        });
        v.on('dragend', function () { v.fill('#fff'); saveShape(g); });
        v.on('dblclick dbltap', function (e) { e.cancelBubble = true; if (r.shape.length > 3) { r.shape.splice(idx, 1); drawShape(g); saveShape(g); if (selectedG === g) updateDoorControls(g); } });
        g.add(v);
      });
    }
    if (shapeMode && shapeG === g && sh) drawSnapTargets(g); else if (!shapeMode) clearSnapTargets();
    layer.draw();
  }
  function setShapeBtn(on) { var se = document.getElementById('shapeEdit'); if (!se) return; se.classList.toggle('btn-primary', on); se.classList.toggle('btn-outline-primary', !on); }
  (function () {
    var seBtn = document.getElementById('shapeEdit'), srBtn = document.getElementById('shapeReset');
    if (seBtn) seBtn.addEventListener('click', function () {
      if (!selectedG) return; var r = selectedG.getAttr('room');
      if (r.locked) { alert(t('lockedShape')); return; }
      if (shapeMode && shapeG === selectedG) { shapeMode = false; shapeG = null; setShapeBtn(false); normalizeShape(selectedG); tr.nodes([selectedG]); drawShape(selectedG); }
      else { if (!r.shape || r.shape.length < 3) r.shape = defaultShape(); shapeMode = true; shapeG = selectedG; setShapeBtn(true); tr.nodes([]); drawShape(selectedG); saveShape(selectedG); }
      updateDoorControls(selectedG);
    });
    if (srBtn) srBtn.addEventListener('click', function () {
      if (!selectedG) return; selectedG.getAttr('room').shape = null; shapeMode = false; shapeG = null; setShapeBtn(false);
      tr.nodes([selectedG]); drawShape(selectedG); saveShape(selectedG); updateDoorControls(selectedG);
    });
  })();

  // ---- Room grouping (#1143): rooms that share a snapped wall move as one unit ----
  var nodeById = {};
  var GROUP_COLORS = ['#0d6efd', '#198754', '#d63384', '#fd7e14', '#6f42c1', '#20c997', '#dc3545'];
  function groupColor(key) {
    if (!key) return null;
    var h = 0; for (var i = 0; i < key.length; i++) h = (h * 31 + key.charCodeAt(i)) >>> 0;
    return GROUP_COLORS[h % GROUP_COLORS.length];
  }
  function recolorRoom(g) {
    var r = g.getAttr('room'), rect = g.findOne('.roomrect'); if (!rect) return;
    var gc = groupColor(r.group), base = r.is_current ? '#0d6efd' : '#6c757d';
    rect.stroke(gc || base); rect.strokeWidth(gc ? 3 : 2); rect.dash(gc ? [6, 3] : []);
  }
  function recolorAll() { PLAN.rooms.forEach(function (o) { if (nodeById[o.id]) recolorRoom(nodeById[o.id]); }); layer.draw(); }
  function persistGroups(rooms) {
    if (!rooms.length) return; flagSaving();
    fetch(GROUP_URL, { method: 'POST', headers: hdr(),
      body: JSON.stringify({ groups: rooms.map(function (r) { return { room_id: r.id, group: r.group || null }; }) }) })
      .then(function (res) { return res.json(); }).then(saved);
  }
  function roomsAdjacent(r, o) {
    var eps = 0.3, A = effBounds(r, r.bld_x, r.bld_y), B = effBounds(o, o.bld_x, o.bld_y);
    var xTouch = Math.abs(A.x2 - B.x1) < eps || Math.abs(B.x2 - A.x1) < eps;
    var yTouch = Math.abs(A.y2 - B.y1) < eps || Math.abs(B.y2 - A.y1) < eps;
    var xOverlap = A.x1 < B.x2 - eps && B.x1 < A.x2 - eps;
    var yOverlap = A.y1 < B.y2 - eps && B.y1 < A.y2 - eps;
    return (xTouch && yOverlap) || (yTouch && xOverlap);
  }
  function newGroupKey() { return 'g' + Date.now().toString(36) + Math.floor(Math.random() * 1000).toString(36); }
  function autoGroup(g) {
    var r = g.getAttr('room');
    if (r.bld_x === null || r.bld_y === null || g.rotation()) return;
    var changed = [];
    function mark(o) { if (changed.indexOf(o) < 0) changed.push(o); }
    PLAN.rooms.forEach(function (o) {
      if (o === r || o.bld_x === null || o.bld_y === null || o.rot || (o.floor || 0) !== (r.floor || 0)) return;
      if (!roomsAdjacent(r, o)) return;
      if (r.group && o.group) {
        if (r.group !== o.group) { var from = o.group, to = r.group; PLAN.rooms.forEach(function (q) { if (q.group === from) { q.group = to; mark(q); } }); }
      } else if (r.group) { o.group = r.group; mark(o); }
      else if (o.group) { r.group = o.group; mark(r); }
      else { var k = newGroupKey(); r.group = k; o.group = k; mark(r); mark(o); }
    });
    if (changed.length) { persistGroups(changed); recolorAll(); }
  }
  function ungroupRoom(g) {
    var r = g.getAttr('room'); if (!r.group) return;
    r.group = null; persistGroups([r]); recolorAll();
  }

  // ---- Join corners (#1143) ----
  var joinMode = false, joinAnchor = null;
  function clearJoinDots() { layer.find('.joindot').forEach(function (n) { n.destroy(); }); }
  function drawJoinDots() {
    clearJoinDots();
    if (joinMode) {
      PLAN.rooms.forEach(function (o) {
        if (o.bld_x === null || o.bld_y === null || String(o.floor || 0) !== String(floorView)) return;
        roomPoly(o, o.bld_x, o.bld_y).forEach(function (pt, idx) {
          var isA = joinAnchor && joinAnchor.id === o.id && joinAnchor.idx === idx;
          var c = new Konva.Circle({ name: 'joindot', x: pt.x * scale, y: pt.y * scale, radius: 7, fill: isA ? '#dc3545' : '#0d6efd', stroke: '#fff', strokeWidth: 2 });
          c.on('click tap', function (e) { e.cancelBubble = true; onJoinDot(o, idx, pt.x, pt.y); });
          layer.add(c);
        });
      });
    }
    layer.draw();
  }
  function onJoinDot(room, idx, wx, wy) {
    if (!joinAnchor || joinAnchor.id === room.id) { joinAnchor = { id: room.id, idx: idx, wx: wx, wy: wy }; drawJoinDots(); return; }
    pushUndo([room]);
    var corners = roomPoly(room, room.bld_x, room.bld_y);
    room.bld_x += joinAnchor.wx - corners[idx].x; room.bld_y += joinAnchor.wy - corners[idx].y;
    var n = nodeById[room.id]; if (n) { n.x(room.bld_x * scale); n.y(room.bld_y * scale); flagSaving(); saveRoom(n); autoGroup(n); }
    joinAnchor = null; drawJoinDots();
  }
  // ---- Undo: room moves / joins / rotate / resize ----
  var undoStack = [];
  function snapRoomState(r) { return { id: r.id, bld_x: r.bld_x, bld_y: r.bld_y, rot: r.rot, w: r.w, d: r.d, group: r.group }; }
  function updateUndoBtn() { var b = document.getElementById('undoBtn'); if (b) b.disabled = undoStack.length === 0; }
  function pushUndo(rooms) { undoStack.push(rooms.map(snapRoomState)); if (undoStack.length > 40) undoStack.shift(); updateUndoBtn(); }
  function applyState(s) {
    var r = null; PLAN.rooms.forEach(function (o) { if (o.id === s.id) r = o; }); if (!r) return;
    r.bld_x = s.bld_x; r.bld_y = s.bld_y; r.rot = s.rot; r.w = s.w; r.d = s.d; r.group = s.group;
    var n = nodeById[r.id]; if (!n) return;
    n.x(r.bld_x * scale); n.y(r.bld_y * scale); n.rotation(r.rot || 0);
    var rect = n.findOne('.roomrect'); if (rect) { rect.width(r.w * scale); rect.height(r.d * scale); }
    n.width(r.w * scale); n.height(r.d * scale);
    drawShape(n); recolorRoom(n); flagSaving(); saveRoom(n); persistGroups([r]);
  }
  function doUndo() {
    if (!undoStack.length) return;
    undoStack.pop().forEach(applyState);
    recolorAll(); if (joinMode) drawJoinDots(); layer.draw(); updateUndoBtn();
  }

  // ---- Room lock (#1143) ----
  function alignRoom(r, fill) {
    var TH = fill ? 8 : 1.2, x1 = r.bld_x, x2 = r.bld_x + r.w, z1 = r.bld_y, z2 = r.bld_y + r.d, xs = [], zs = [];
    PLAN.rooms.forEach(function (o) {
      if (o.id === r.id || o.bld_x === null || (o.floor || 0) !== (r.floor || 0)) return;
      xs.push(o.bld_x, o.bld_x + o.w); zs.push(o.bld_y, o.bld_y + o.d);
    });
    function snap(v, arr) { var best = v, bd = TH; arr.forEach(function (a) { if (Math.abs(a - v) < bd) { bd = Math.abs(a - v); best = a; } }); return best; }
    var nx1 = snap(x1, xs), nx2 = snap(x2, xs), nz1 = snap(z1, zs), nz2 = snap(z2, zs);
    if (nx2 - nx1 > 0.5 && nz2 - nz1 > 0.5) { r.bld_x = nx1; r.w = nx2 - nx1; r.bld_y = nz1; r.d = nz2 - nz1; }
  }
  function applyLockVisual(g) {
    var r = g.getAttr('room'); g.draggable(!r.locked); recolorRoom(g);
    var rect = g.findOne('.roomrect'); if (rect && r.locked) { rect.stroke('#212529'); rect.dash([2, 2]); rect.strokeWidth(2); }
    g.find('.lockbadge').forEach(function (n) { n.destroy(); });
    if (r.locked) g.add(new Konva.Text({ name: 'lockbadge', text: '🔒', x: 2, y: Math.max(2, r.d * scale - 16), fontSize: 13, listening: false }));
  }

  function addRoomNode(r) {
    var g = new Konva.Group({ x: r.bld_x * scale, y: r.bld_y * scale, rotation: r.rot || 0, draggable: true });
    g.setAttr('room', r);
    nodeById[r.id] = g;
    var rect = new Konva.Rect({ name: 'roomrect', width: r.w * scale, height: r.d * scale, fill: r.is_current ? 'rgba(13,110,253,.25)' : 'rgba(108,117,125,.2)', stroke: r.is_current ? '#0d6efd' : '#6c757d', strokeWidth: 2 });
    var label = new Konva.Text({ x: 3, y: 3, text: r.name, fontSize: 9, fill: '#212529', width: r.w * scale - 6 });
    g.add(rect); g.add(label);
    g.on('click tap', function (e) { e.cancelBubble = true; selectRoom(g); });
    var grpDrag = null;
    g.on('dragstart', function () {
      var rr = g.getAttr('room');
      var members = rr.group ? PLAN.rooms.filter(function (o) { return o !== rr && o.group === rr.group; }) : [];
      pushUndo([rr].concat(members));
      grpDrag = members.map(function (o) { var n = nodeById[o.id]; return n ? { n: n, x0: n.x(), y0: n.y() } : null; }).filter(Boolean);
      g.setAttr('dragStartX', g.x()); g.setAttr('dragStartY', g.y());
    });
    g.on('dragmove', function () {
      snapRoom(g);
      if (grpDrag && grpDrag.length) {
        var dx = g.x() - g.getAttr('dragStartX'), dy = g.y() - g.getAttr('dragStartY');
        grpDrag.forEach(function (e) { e.n.x(e.x0 + dx); e.n.y(e.y0 + dy); });
      }
    });
    g.on('dragend', function () {
      if (grpDrag && grpDrag.length) {
        var dx = g.x() - g.getAttr('dragStartX'), dy = g.y() - g.getAttr('dragStartY');
        grpDrag.forEach(function (e) { e.n.x(e.x0 + dx); e.n.y(e.y0 + dy); });
        flagSaving(); saveRoom(g); grpDrag.forEach(function (e) { saveRoom(e.n); });
      } else {
        resolveOverlap(g); flagSaving(); saveRoom(g);
      }
      autoGroup(g); grpDrag = null;
    });
    g.on('transformstart', function () { pushUndo([g.getAttr('room')]); });
    g.on('transformend', function () {
      var nw = g.width() * g.scaleX(), nh = g.height() * g.scaleY();
      rect.width(nw); rect.height(nh); label.width(nw - 8); g.scale({ x: 1, y: 1 }); g.width(nw); g.height(nh);
      var r2 = g.getAttr('room'); r2.w = nw / scale; r2.d = nh / scale;
      if (selectedG === g) { var _ri2 = document.getElementById('rotInput'); if (_ri2) _ri2.value = Math.round(g.rotation()); }
      resolveOverlap(g); drawDoors(g); drawShape(g); flagSaving(); saveRoom(g); layer.draw();
    });
    g.width(r.w * scale); g.height(r.d * scale);
    layer.add(g);
    drawDoors(g); drawShape(g); recolorRoom(g); applyLockVisual(g);
    return g;
  }
  PLAN.rooms.forEach(addRoomNode);
  layer.draw();
  stage.on('click tap', function (e) { if (e.target === stage) deselect(); });

  function renderRoomList() {
    var el = document.getElementById('planRoomList');
    if (!el) return;
    el.innerHTML = PLAN.rooms.map(function (r) { return '<div>' + (r.is_current ? '<b>' : '') + r.name + (r.is_current ? '</b>' : '') + ' <span class="text-muted">' + Math.round(r.w) + '×' + Math.round(r.d) + 'm</span></div>'; }).join('');
  }
  renderRoomList();

  // ---- Floor view (#1169) ----
  function floorName(f) { return f === 0 ? t('ground') : (f < 0 ? (t('basement') + (f < -1 ? ' ' + (-f) : '')) : (t('floor') + ' ' + f)); }
  function floorList() { var mn = -1, mx = 4; PLAN.rooms.forEach(function (r) { var f = r.floor || 0; if (f < mn) mn = f; if (f > mx) mx = f; }); var a = []; for (var i = mn; i <= mx; i++) a.push(i); return a; }
  function floorOptsHtml(selVal) { return floorList().map(function (f) { return '<option value="' + f + '"' + (f === selVal ? ' selected' : '') + '>' + floorName(f) + '</option>'; }).join(''); }
  var floorView = '0';   // default to the ground floor
  function buildFloorView() {
    var sel = document.getElementById('floorView'); if (!sel) return;
    var html = '';
    floorList().forEach(function (f) { html += '<option value="' + f + '"' + (String(f) === String(floorView) ? ' selected' : '') + '>' + floorName(f) + '</option>'; });
    sel.innerHTML = html;
  }
  function applyFloorView() {
    PLAN.rooms.forEach(function (r) { var n = nodeById[r.id]; if (n) n.visible(floorView === 'all' || String(r.floor || 0) === String(floorView)); });
    layer.draw();
  }
  (function () { var sel = document.getElementById('floorView'); if (!sel) return; buildFloorView(); applyFloorView(); sel.addEventListener('change', function () { floorView = sel.value; deselect(); applyFloorView(); }); })();
  // #1143 lock/unlock the WHOLE current floor
  (function () {
    var fb = document.getElementById('lockFloorBtn'); if (!fb) return;
    function floorRooms() { return PLAN.rooms.filter(function (r) { return String(r.floor || 0) === String(floorView) && r.bld_x !== null; }); }
    function refresh() { var rs = floorRooms(), all = rs.length && rs.every(function (r) { return r.locked; }); fb.innerHTML = all ? '<i class="fas fa-lock-open me-1"></i>' + t('unlockFloor') : '<i class="fas fa-lock me-1"></i>' + t('lockFloor'); fb.classList.toggle('btn-dark', all); fb.classList.toggle('btn-outline-dark', !all); }
    fb.addEventListener('click', function () {
      var rs = floorRooms(); if (!rs.length) return;
      var lock = !rs.every(function (r) { return r.locked; });
      flagSaving();
      if (lock) {
        var fill = document.getElementById('lockFloorFill') && document.getElementById('lockFloorFill').checked;
        pushUndo(rs);
        for (var pass = 0; pass < 2; pass++) rs.forEach(function (r) { if (!(r.shape && r.shape.length >= 3) && !r.rot) alignRoom(r, fill); });
        rs.forEach(function (r) {
          var n = nodeById[r.id]; if (!n) return;
          var rect = n.findOne('.roomrect'); if (rect) { rect.width(r.w * scale); rect.height(r.d * scale); }
          var lbl = n.findOne('Text'); if (lbl) lbl.width(r.w * scale - 6);
          n.x(r.bld_x * scale); n.y(r.bld_y * scale); n.width(r.w * scale); n.height(r.d * scale);
          drawDoors(n); drawShape(n); saveRoom(n);
        });
      }
      rs.forEach(function (r) { r.locked = lock; var n = nodeById[r.id]; if (n) applyLockVisual(n); fetch(ROOM_LOCK_URL, { method: 'POST', headers: hdr(), body: JSON.stringify({ room_id: r.id, locked: lock }) }); });
      if (lock && selectedG && rs.indexOf(selectedG.getAttr('room')) >= 0) tr.nodes([]);
      saved(); refresh(); layer.draw();
    });
    var sel2 = document.getElementById('floorView'); if (sel2) sel2.addEventListener('change', refresh);
    refresh();
  })();

  // ---- Corridor objects: placed in building space (fraction of the room bbox) ----
  var corrLayer = new Konva.Layer(); stage.add(corrLayer);
  var CORRIDOR = PLAN.corridor || [];
  function bbox() {
    var b = { minX: Infinity, maxX: -Infinity, minZ: Infinity, maxZ: -Infinity };
    PLAN.rooms.forEach(function (r) { b.minX = Math.min(b.minX, r.bld_x); b.maxX = Math.max(b.maxX, r.bld_x + r.w); b.minZ = Math.min(b.minZ, r.bld_y); b.maxZ = Math.max(b.maxZ, r.bld_y + r.d); });
    if (!isFinite(b.minX)) b = { minX: 0, maxX: ext.w, minZ: 0, maxZ: ext.h };
    return b;
  }
  function corrToPx(c) { var b = bbox(); return { x: (b.minX + c.pos_x * (b.maxX - b.minX)) * scale, y: (b.minZ + c.pos_y * (b.maxZ - b.minZ)) * scale }; }
  function pxToCorr(px, py) { var b = bbox(); return { x: Math.max(0, Math.min(1, (px / scale - b.minX) / ((b.maxX - b.minX) || 1))), y: Math.max(0, Math.min(1, (py / scale - b.minZ) / ((b.maxZ - b.minZ) || 1))) }; }
  function removeCorr(c) {
    fetch(CORR_REMOVE, { method: 'POST', headers: hdr(), body: JSON.stringify({ id: c.id }) })
      .then(function (r) { return r.json(); }).then(function (d) { if (d.ok) { var i = CORRIDOR.indexOf(c); if (i >= 0) CORRIDOR.splice(i, 1); drawCorridor(); saved(); } });
  }
  function listCorr() {
    var el = document.getElementById('corridorList'); if (!el) return;
    if (!CORRIDOR.length) { el.innerHTML = '<span class="text-muted">' + t('noneYet') + '</span>'; return; }
    el.innerHTML = '';
    CORRIDOR.forEach(function (c) {
      var row = document.createElement('div');
      row.className = 'd-flex align-items-center gap-1 mb-1';
      row.innerHTML = '<i class="fas fa-shoe-prints text-warning"></i> <span class="text-truncate" style="max-width:150px">' + (c.title || ('#' + c.information_object_id)) + '</span><button class="btn btn-sm btn-outline-danger ms-auto" type="button" title="' + t('remove') + '">&times;</button>';
      row.querySelector('button').addEventListener('click', function () { removeCorr(c); });
      el.appendChild(row);
    });
  }
  function drawCorridor() {
    corrLayer.destroyChildren();
    CORRIDOR.forEach(function (c) {
      var p = corrToPx(c);
      var g = new Konva.Group({ x: p.x, y: p.y, draggable: true, name: 'corr' });
      g.add(new Konva.Circle({ radius: 9, fill: '#fd7e14', stroke: '#fff', strokeWidth: 2 }));
      g.add(new Konva.Text({ text: (c.title || '').substring(0, 16), x: 12, y: -6, fontSize: 11, fill: '#212529' }));
      g.on('dragmove', function () { var f = pxToCorr(g.x(), g.y()); c.pos_x = f.x; c.pos_y = f.y; });
      g.on('dragend', function () { flagSaving(); fetch(CORR_MOVE, { method: 'POST', headers: hdr(), body: JSON.stringify({ id: c.id, fx: c.pos_x, fy: c.pos_y }) }).then(saved); });
      g.on('dblclick dbltap', function (e) { e.cancelBubble = true; removeCorr(c); });
      corrLayer.add(g);
    });
    corrLayer.draw();
    listCorr();
  }
  drawCorridor();
  (function () {
    var el = document.getElementById('corridorAdd'); if (!el || typeof TomSelect === 'undefined') return;
    new TomSelect(el, {
      valueField: 'id', labelField: 'label', searchField: ['label'], maxItems: 1, maxOptions: 15,
      load: function (q, cb) { if (q.length < 2) return cb(); fetch(AUTOCOMPLETE + '?q=' + encodeURIComponent(q) + '&limit=15', { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (rows) { cb(Array.isArray(rows) ? rows : []); }).catch(function () { cb(); }); },
      render: { option: function (d, e) { return '<div>' + e(d.label || d.value || ('#' + d.id)) + ' <small class="text-muted">#' + e(d.id) + '</small></div>'; } },
      onChange: function (val) {
        if (!val) return; var self = this; flagSaving();
        fetch(CORR_ADD, { method: 'POST', headers: hdr(), body: JSON.stringify({ information_object_id: val, fx: 0.5, fy: 0.5 }) })
          .then(function (r) { return r.json(); }).then(function (d) { if (d.ok && d.placement) { CORRIDOR.push(d.placement); drawCorridor(); saved(); } self.clear(true); self.clearOptions(); });
      }
    });
  })();

  // ---- Stairs (#1169) ----
  var STAIRS = PLAN.stairs || [];
  var SAME_FLOOR_MSG = t('sameFloorMsg');
  var stairLayer = new Konva.Layer(); stage.add(stairLayer);
  function saveStairs() { flagSaving(); fetch(STAIRS_URL, { method: 'POST', headers: hdr(), body: JSON.stringify({ stairs: STAIRS }) }).then(function (r) { return r.json(); }).then(saved); }
  function floorOf(id) { var f = 0; PLAN.rooms.forEach(function (r) { if (r.id === id) f = r.floor || 0; }); return f; }
  function roomOpts(selId) { return PLAN.rooms.map(function (r) { return '<option value="' + r.id + '"' + (r.id === selId ? ' selected' : '') + '>' + r.name + ' (' + (r.floor ? (t('floorLc') + ' ' + r.floor) : t('groundLc')) + ')</option>'; }).join(''); }
  function listStairs() {
    var el = document.getElementById('stairList'); if (!el) return;
    if (!STAIRS.length) { el.innerHTML = '<span class="text-muted">' + t('noStairs') + '</span>'; return; }
    el.innerHTML = '';
    STAIRS.forEach(function (st, i) {
      var row = document.createElement('div'); row.className = 'd-flex flex-wrap align-items-center gap-1 mb-2 pb-1 border-bottom';
      row.innerHTML = '<span class="badge bg-warning text-dark">' + t('stair') + ' ' + (i + 1) + '</span>' +
        '<label class="small text-muted mb-0 w-100">' + t('from') + ' <select class="form-select form-select-sm d-inline-block sfr" style="width:160px">' + roomOpts(st.from_room) + '</select></label>' +
        '<label class="small text-muted mb-0 w-100">' + t('to') + ' <select class="form-select form-select-sm d-inline-block str" style="width:160px">' + roomOpts(st.to_room) + '</select></label>' +
        '<label class="small text-muted mb-0">' + t('width') + ' <input type="number" step="0.1" min="0.6" class="form-control form-control-sm d-inline-block sw" style="width:58px" value="' + (st.width || 1.6) + '"></label>' +
        '<label class="small text-muted mb-0">' + t('len') + ' <input type="number" step="0.5" min="1.5" class="form-control form-control-sm d-inline-block sl" style="width:54px" value="' + (st.length || 3) + '"></label>' +
        '<label class="small text-muted mb-0">' + t('len2') + ' <input type="number" step="0.5" min="1.5" class="form-control form-control-sm d-inline-block sl2" style="width:54px" value="' + (st.length2 || st.length || 3) + '"></label>' +
        '<select class="form-select form-select-sm sk d-inline-block" style="width:86px"><option value="straight">' + t('straight') + '</option><option value="elbow">' + t('elbow') + '</option></select>' +
        '<select class="form-select form-select-sm sh d-inline-block" style="width:74px"><option value="right">' + t('right') + '</option><option value="left">' + t('left') + '</option></select>' +
        '<label class="small text-muted mb-0">' + t('rot') + ' <input type="number" step="90" class="form-control form-control-sm d-inline-block sr" style="width:54px" value="' + (st.rot || 0) + '"></label>' +
        '<button class="btn btn-sm btn-outline-danger ms-auto" type="button" title="' + t('remove') + '">&times;</button>';
      row.querySelector('.sfr').addEventListener('change', function (e) {
        var nf = floorOf(+e.target.value);
        if (nf === (st.to_floor == null ? 1 : st.to_floor)) { alert(SAME_FLOOR_MSG); drawStairs(); return; }
        st.from_room = +e.target.value; st.from_floor = nf; saveStairs(); drawStairs();
      });
      row.querySelector('.str').addEventListener('change', function (e) {
        var nf = floorOf(+e.target.value);
        if (nf === (st.from_floor || 0)) { alert(SAME_FLOOR_MSG); drawStairs(); return; }
        st.to_room = +e.target.value; st.to_floor = nf; saveStairs(); drawStairs();
      });
      row.querySelector('.sw').addEventListener('change', function (e) { st.width = Math.max(0.6, Math.min(8, parseFloat(e.target.value) || 1.6)); saveStairs(); drawStairs(); });
      row.querySelector('.sl').addEventListener('change', function (e) { st.length = Math.max(1.5, Math.min(30, parseFloat(e.target.value) || 3)); saveStairs(); drawStairs(); });
      row.querySelector('.sl2').addEventListener('change', function (e) { st.length2 = Math.max(1.5, Math.min(30, parseFloat(e.target.value) || 3)); saveStairs(); drawStairs(); });
      var sk = row.querySelector('.sk'); sk.value = st.kind || 'straight'; sk.addEventListener('change', function (e) { st.kind = e.target.value; saveStairs(); drawStairs(); });
      var sh = row.querySelector('.sh'); sh.value = st.hand || 'right'; sh.addEventListener('change', function (e) { st.hand = e.target.value; saveStairs(); drawStairs(); });
      row.querySelector('.sr').addEventListener('change', function (e) { st.rot = parseInt(e.target.value, 10) || 0; saveStairs(); drawStairs(); });
      row.querySelector('button').addEventListener('click', function () { STAIRS.splice(i, 1); drawStairs(); saveStairs(); });
      el.appendChild(row);
    });
  }
  function drawStairs() {
    stairLayer.destroyChildren();
    STAIRS.forEach(function (st, i) {
      var g = new Konva.Group({ x: st.x * scale, y: st.z * scale, rotation: st.rot || 0, draggable: true });
      var w = Math.max(0.8, st.width || 1.6) * scale, fill = 'rgba(253,126,20,0.45)', stroke = '#fd7e14';
      if (st.kind === 'elbow') {
        var seg = (st.length || 3) * scale, seg2 = (st.length2 || st.length || 3) * scale, hh = (st.hand === 'left') ? -1 : 1;
        g.add(new Konva.Rect({ x: -w / 2, y: 0, width: w, height: seg, fill: fill, stroke: stroke, strokeWidth: 2, cornerRadius: 2 }));
        g.add(new Konva.Rect({ x: hh > 0 ? -w / 2 : -(seg2 + w / 2), y: seg - w, width: seg2 + w, height: w, fill: fill, stroke: stroke, strokeWidth: 2, cornerRadius: 2 }));
      } else {
        var d = (st.length || 3) * scale;
        g.add(new Konva.Rect({ x: -w / 2, y: -d / 2, width: w, height: d, fill: fill, stroke: stroke, strokeWidth: 2, cornerRadius: 2 }));
        for (var s = 1; s < 6; s++) { g.add(new Konva.Line({ points: [-w / 2, -d / 2 + d * s / 6, w / 2, -d / 2 + d * s / 6], stroke: '#fff', strokeWidth: 1, listening: false })); }
      }
      g.add(new Konva.Text({ text: '↑' + (st.from_floor || 0) + '→' + (st.to_floor == null ? 1 : st.to_floor) + (st.kind === 'elbow' ? ' ⌐' : ''), x: -w / 2 - 20, y: -14, width: w + 40, align: 'center', fontSize: 10, fill: '#b8600b', listening: false }));
      g.on('dragmove', function () { st.x = g.x() / scale; st.z = g.y() / scale; });
      g.on('dragend', function () { saveStairs(); });
      g.on('dblclick dbltap', function (e) { e.cancelBubble = true; STAIRS.splice(i, 1); drawStairs(); saveStairs(); });
      stairLayer.add(g);
    });
    stairLayer.draw(); listStairs();
  }
  drawStairs();
  (function () {
    var b = document.getElementById('stairAdd'); if (!b) return;
    b.addEventListener('click', function () {
      var bb = bbox(); var cx = (bb.minX + bb.maxX) / 2, cz = (bb.minZ + bb.maxZ) / 2;
      if (!isFinite(cx)) { cx = 5; cz = 5; }
      var cur = PLAN.rooms.filter(function (r) { return r.is_current; })[0] || PLAN.rooms[0] || null;
      var ff = cur ? (cur.floor || 0) : 0;
      var other = PLAN.rooms.filter(function (r) { return (r.floor || 0) !== ff; })[0] || null;
      var tf = other ? (other.floor || 0) : (ff + 1);
      STAIRS.push({ x: cx, z: cz, from_room: cur ? cur.id : null, to_room: other ? other.id : null, from_floor: ff, to_floor: tf, width: 1.6, length: 3, length2: 3, rot: 0, hand: 'right', kind: 'straight' });
      drawStairs(); saveStairs();
    });
  })();

  // Add a new room WITHOUT rescaling — existing rooms + blueprint stay aligned.
  (function () {
    var b = document.getElementById('addRoomBtn'); if (!b) return;
    b.addEventListener('click', function () {
      b.disabled = true; flagSaving();
      fetch(ADD_ROOM_URL, { method: 'POST', headers: hdr(), body: JSON.stringify({}) })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          b.disabled = false;
          if (!d.ok || !d.room) return;
          var r = d.room;
          var nb = null, maxX2 = -Infinity;
          PLAN.rooms.forEach(function (o) {
            if (o.bld_x === null || o.bld_y === null) return;
            var bb = effBounds(o, o.bld_x, o.bld_y);
            if (bb.x2 > maxX2) { maxX2 = bb.x2; nb = o; }
          });
          if (nb && (maxX2 + r.w) <= ext.w) { r.bld_x = maxX2; r.bld_y = nb.bld_y; r.d = nb.d; }
          else if (nb) {
            var maxY2 = 0; PLAN.rooms.forEach(function (o) { if (o.bld_x === null) return; var bb = effBounds(o, o.bld_x, o.bld_y); if (bb.y2 > maxY2) maxY2 = bb.y2; });
            r.bld_x = 0; r.bld_y = Math.max(0, Math.min(ext.h - r.d, maxY2));
          } else { r.bld_x = 1; r.bld_y = 1; }
          r.bld_x = Math.max(0, r.bld_x); r.bld_y = Math.max(0, r.bld_y);
          PLAN.rooms.push(r);
          var g = addRoomNode(r);
          if (floorView !== 'all') { r.floor = +floorView; fetch(ROOM_FLOOR_URL, { method: 'POST', headers: hdr(), body: JSON.stringify({ room_id: r.id, floor: r.floor }) }); }
          saveRoom(g);
          selectRoom(g);
          renderRoomList(); buildFloorView(); applyFloorView();
        })
        .catch(function () { b.disabled = false; });
    });
  })();

  // Adjust blueprint: move/resize the world-anchored image onto the rooms.
  (function () {
    var b = document.getElementById('planAdjustBtn'); if (!b) return;
    var adjLayer = new Konva.Layer(); stage.add(adjLayer);
    var imgTr = null, adjusting = false;
    function saveRect() {
      var nw = planImg.width() * planImg.scaleX(), nh = planImg.height() * planImg.scaleY();
      planImg.width(nw); planImg.height(nh); planImg.scale({ x: 1, y: 1 });
      planRect = { x: planImg.x() / scale, y: planImg.y() / scale, w: nw / scale, h: nh / scale };
      flagSaving();
      fetch(IMG_RECT_URL, { method: 'POST', headers: hdr(), body: JSON.stringify(planRect) }).then(saved);
    }
    b.addEventListener('click', function () {
      if (!planImg) return;
      adjusting = !adjusting;
      b.classList.toggle('btn-secondary', adjusting); b.classList.toggle('btn-outline-secondary', !adjusting);
      b.innerHTML = adjusting ? '<i class="fas fa-check me-1"></i>' + t('done') : '<i class="fas fa-arrows-alt me-1"></i>' + t('adjustBlueprint');
      if (adjusting) {
        deselect();
        planImg.moveTo(adjLayer); planImg.listening(true); planImg.draggable(true); planImg.opacity(0.75);
        imgTr = new Konva.Transformer({ rotateEnabled: false, keepRatio: false, enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right'] });
        adjLayer.add(imgTr); imgTr.nodes([planImg]);
        planImg.on('dragend.adj transformend.adj', saveRect);
        adjLayer.draw();
      } else {
        if (imgTr) { imgTr.destroy(); imgTr = null; }
        planImg.off('.adj'); planImg.draggable(false); planImg.listening(false); planImg.opacity(0.55);
        planImg.moveTo(bg); bg.draw(); adjLayer.draw();
      }
    });
  })();
})();
