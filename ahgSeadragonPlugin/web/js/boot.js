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

  // Height, applied through the CSSOM. A style attribute is dropped by AtoM's
  // CSP without reporting anything; the CSSOM is not covered by CSP.
  function applyHeight(el) {
    var h = el.dataset.height;
    if (h && /^\d+(px|%|vh)$/.test(h)) { el.style.height = h; }
  }

  /**
   * Turn a IIIF Presentation manifest into tile sources OpenSeadragon can open.
   *
   * This is the part that was missing entirely. The viewer was handed the
   * manifest URL directly as tileSources, and a Presentation manifest is not a
   * tile source - OpenSeadragon answers "Unable to load TileSource" and shows
   * nothing. The implementation this plugin replaced did it properly
   * (ahgIiifPlugin/modules/iiif/templates/viewerSuccess.php:100): walk the
   * canvases, take each image service id, append /info.json.
   *
   * Both Presentation 2 and 3 are handled, because our own manifests carry both
   * shapes for client compatibility.
   */
  function tileSourcesFromManifest(manifest) {
    var out = [];

    // v3: items[] -> items[] (AnnotationPage) -> items[] (Annotation) -> body
    (manifest.items || []).forEach(function (canvas) {
      (canvas.items || []).forEach(function (page) {
        (page.items || []).forEach(function (anno) {
          var body = anno.body || {};
          var svc = [].concat(body.service || []);
          var id = svc.length ? (svc[0].id || svc[0]['@id']) : null;
          if (id) {
            out.push(id.replace(/\/$/, '') + '/info.json');
          } else if (body.id) {
            // No image service - a flat image beats an empty viewer.
            out.push({ type: 'image', url: body.id });
          }
        });
      });
    });

    if (out.length) { return out; }

    // v2: sequences[] -> canvases[] -> images[] -> resource.service
    (manifest.sequences || []).forEach(function (seq) {
      (seq.canvases || []).forEach(function (canvas) {
        (canvas.images || []).forEach(function (img) {
          var res = img.resource || {};
          var id = (res.service || {})['@id'] || (res.service || {}).id;
          if (id) {
            out.push(id.replace(/\/$/, '') + '/info.json');
          } else if (res['@id']) {
            out.push({ type: 'image', url: res['@id'] });
          }
        });
      });
    });

    return out;
  }

  /**
   * Defaults, restored from the two implementations this plugin replaced.
   *
   * crossOriginPolicy is the one that is easy to omit and expensive to: without
   * it, canvas operations fail as soon as tiles come from a different origin to
   * the page, which is exactly the arrangement our own image-server guide
   * recommends.
   */
  function defaults(el, tiles) {
    return {
      id: el.id,
      prefixUrl: assetsFor(el) + '/images/',
      tileSources: tiles,
      showNavigator: true,
      navigatorPosition: 'BOTTOM_RIGHT',
      // From the page count, not hardcoded false. A multi-page document opened
      // on page one with no way to reach page two.
      sequenceMode: tiles.length > 1,
      showReferenceStrip: tiles.length > 1,
      referenceStripScroll: 'horizontal',
      showRotationControl: true,
      showFlipControl: true,
      gestureSettingsMouse: { clickToZoom: true, scrollToZoom: true },
      crossOriginPolicy: 'Anonymous',
      animationTime: 0.5,
      zoomPerClick: 1.5,
      maxZoomPixelRatio: 4,
      visibilityRatio: 0.5,
      constrainDuringPan: true,
      tileRetryMax: 3,
      tileRetryDelay: 2000
    };
  }

  // Caller overrides, restricted to an allowlist. data-* is page content and is
  // only as trustworthy as the page that carries it.
  var ALLOWED = [
    'showNavigator', 'navigatorPosition', 'sequenceMode', 'showReferenceStrip',
    'referenceStripScroll', 'showRotationControl', 'showFlipControl',
    'showZoomControl', 'showHomeControl', 'showFullPageControl',
    'gestureSettingsMouse', 'gestureSettingsTouch', 'crossOriginPolicy',
    'animationTime', 'zoomPerClick', 'zoomPerScroll', 'maxZoomPixelRatio',
    'minZoomLevel', 'maxZoomLevel', 'defaultZoomLevel', 'visibilityRatio',
    'constrainDuringPan', 'wrapHorizontal', 'wrapVertical', 'immediateRender',
    'imageSmoothingEnabled', 'tileRetryMax', 'tileRetryDelay', 'preload',

    // Collection display. OpenSeadragon can lay a whole collection out as a
    // grid natively, which is close to what a finding aid wants and is
    // otherwise built separately.
    'collectionMode', 'collectionRows', 'collectionColumns', 'collectionLayout',
    'collectionTileSize', 'collectionTileMargin',

    // Touch and pen. Absent from the allowlist, a tablet reader got library
    // defaults that nobody here had tested and no site could change.
    'gestureSettingsPen', 'gestureSettingsUnknown', 'pinchRotate', 'pinchToZoom',
    'flickEnabled', 'flickMinSpeed', 'flickMomentum', 'dblClickToZoom',
    'dblClickDragToZoom', 'zoomPerDblClickDrag',

    // Networking. ajaxHeaders is how an authenticated image server is reached;
    // without it there was no way to send a token with a tile request. These
    // arrive from the renderer, which builds them server side from settings -
    // they are not read from anything a reader controls.
    'ajaxHeaders', 'ajaxWithCredentials', 'loadTilesWithAjax', 'timeout',

    // Sequence, beyond the two the defaults set.
    'initialPage', 'preserveViewport', 'navPrevNextWrap', 'showSequenceControl',
    'sequenceControlAnchor'
  ];

  function withOverrides(config, el) {
    if (!el.dataset.options) { return config; }

    try {
      var passed = JSON.parse(el.dataset.options);
      Object.keys(passed).forEach(function (k) {
        if (ALLOWED.indexOf(k) !== -1) { config[k] = passed[k]; }
      });
    } catch (e) {
      // A malformed override must not cost the reader the viewer.
    }

    return config;
  }

  function open(el, tiles) {
    if (!tiles || !tiles.length) { return; }
    OpenSeadragon(withOverrides(defaults(el, tiles), el));
  }

  function mount(el) {
    if (el.dataset.booted) { return; }
    el.dataset.booted = '1';

    applyHeight(el);

    // A direct image URL, for installs with no IIIF image service at all.
    if (el.dataset.tileSource) {
      open(el, [{ type: 'image', url: el.dataset.tileSource }]);

      return;
    }

    if (!el.dataset.manifest) { return; }

    fetch(el.dataset.manifest, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (m) { open(el, tileSourcesFromManifest(m)); })
      .catch(function () {
        // Leave the container empty rather than throwing. The switcher offers
        // other viewers, and one failing must not take the page with it.
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
