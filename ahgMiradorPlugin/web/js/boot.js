/**
 * ahgMiradorPlugin - viewer boot.
 *
 * The renderer emits markup and data-* only (no inline script), so this file is
 * what turns the container into a viewer. Loaded by ahgIiifPlugin's injector,
 * which derives the path from the container's data-rendered-by attribute.
 */
(function () {
  'use strict';

  function boot(el) {
    if (el.dataset.booted) { return; }

    // The renderer mounts Mirador in an iframe (see viewer.html). If one is already
    // present the viewer is running in its own document and mounting again here
    // would put a SECOND, inline Mirador on top of it - which is what produced the
    // stray black panels: two instances, one of them fighting Bootstrap.
    if (el.querySelector('iframe')) { el.dataset.booted = '1'; return; }

    el.dataset.booted = '1';

    var manifest = el.dataset.manifest;
    if (!manifest) { return; }

    Mirador.viewer({
      id: el.id,
      windows: [{ manifestId: manifest }]
    });
  }

  function init() {
    var nodes = document.querySelectorAll('[data-viewer="mirador"]');
    if (!nodes.length) { return; }

    if (typeof Mirador === 'undefined') {
      var base = nodes[0].dataset.assets || '/plugins/ahgMiradorPlugin/web/mirador';
      var s = document.createElement('script');
      s.src = base + '/mirador.min.js';
      s.onload = function () { nodes.forEach(boot); };
      document.head.appendChild(s);
      return;
    }

    nodes.forEach(boot);
  }

  // A hidden pane measures zero, so booting it yields a viewer with no dimensions.
  // The switcher fires ahg:viewer-shown when a pane becomes visible.
  document.addEventListener('ahg:viewer-shown', function (e) {
    var el = e.target;
    if (el && el.dataset && el.dataset.viewer === 'mirador') { boot(el); }
  });

  function initVisible() {
    document.querySelectorAll('[data-viewer="mirador"]').forEach(function (el) {
      if (el.offsetParent !== null) { init(); }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVisible);
  } else {
    initVisible();
  }
})();
