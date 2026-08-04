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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
