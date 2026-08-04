/**
 * ahgSeadragonPlugin - viewer boot.
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

    var assets = el.dataset.assets || '/plugins/ahgSeadragonPlugin/web/openseadragon';
    // A IIIF image service is preferred; without one (no image server installed)
    // OpenSeadragon can still show a single image via its simple-image mode.
    var tiles = el.dataset.manifest;
    if (el.dataset.tileSource) {
      tiles = { type: 'image', url: el.dataset.tileSource };
    }
    if (!tiles) { return; }

    OpenSeadragon({
      id: el.id,
      prefixUrl: assets + '/images/',
      tileSources: tiles,
      showNavigator: true,
      sequenceMode: false
    });
  }

  function init() {
    var nodes = document.querySelectorAll('[data-viewer="openseadragon"]');
    if (!nodes.length) { return; }

    if (typeof OpenSeadragon === 'undefined') {
      var base = nodes[0].dataset.assets || '/plugins/ahgSeadragonPlugin/web/openseadragon';
      var s = document.createElement('script');
      s.src = base + '/openseadragon.min.js';
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
