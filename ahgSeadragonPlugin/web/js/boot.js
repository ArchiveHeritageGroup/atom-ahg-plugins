/**
 * ahgSeadragonPlugin - viewer boot.
 *
 * The renderer emits markup and data-* only (no inline script or style: AtoM's CSP
 * has no 'unsafe-inline'), so this file turns the container into a viewer. Loaded by
 * ahgIiifPlugin's injector, which derives the path from data-rendered-by.
 */
(function () {
  'use strict';

  var SELECTOR = '[data-viewer="openseadragon"]';

  function assetsFor(el) {
    return (el && el.dataset.assets) || '/plugins/ahgSeadragonPlugin/web/openseadragon';
  }

  function mount(el) {
    if (el.dataset.booted) { return; }
    el.dataset.booted = '1';

    // A IIIF image service is preferred; without one (no image server installed)
    // OpenSeadragon can still show a single image via its simple-image mode.
    var tiles = el.dataset.manifest;
    if (el.dataset.tileSource) {
      tiles = { type: 'image', url: el.dataset.tileSource };
    }
    if (!tiles) { return; }

    OpenSeadragon({
      id: el.id,
      prefixUrl: assetsFor(el) + '/images/',
      tileSources: tiles,
      showNavigator: true,
      sequenceMode: false
    });
  }

  /**
   * Load the library once, then run cb. Both the initial pass and the
   * ahg:viewer-shown handler go through here - booting a pane that became visible
   * later must still be able to load the library, which is what a naive
   * "only init visible panes at load" version got wrong.
   */
  function withLibrary(el, cb) {
    if (typeof OpenSeadragon !== 'undefined') { cb(); return; }

    if (withLibrary.loading) { withLibrary.queue.push(cb); return; }
    withLibrary.loading = true;
    withLibrary.queue = [cb];

    var s = document.createElement('script');
    s.src = assetsFor(el) + '/openseadragon.min.js';
    s.onload = function () {
      withLibrary.loading = false;
      withLibrary.queue.forEach(function (fn) { fn(); });
      withLibrary.queue = [];
    };
    document.head.appendChild(s);
  }

  function bootIfVisible(el) {
    // A hidden pane measures zero, so the viewer would size itself to nothing.
    if (el.offsetParent === null) { return; }
    withLibrary(el, function () { mount(el); });
  }

  function init() {
    document.querySelectorAll(SELECTOR).forEach(bootIfVisible);
  }

  // Fired by ahgIiifPlugin's viewer-switch.js when a pane becomes visible.
  document.addEventListener('ahg:viewer-shown', function (e) {
    var el = e.target;
    if (el && el.dataset && el.dataset.viewer === 'openseadragon') {
      withLibrary(el, function () { mount(el); });
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
