/**
 * Switches between viewer panes when more than one viewer plugin is enabled.
 *
 * Panes are rendered up front but hidden. A viewer is only booted when it first
 * becomes visible - OpenSeadragon and Mirador both size themselves from their
 * container, and a container with display:none measures zero, so booting a hidden
 * pane produces a viewer with no dimensions.
 */
(function () {
  'use strict';

  function show(root, plugin) {
    root.querySelectorAll('.ahg-viewer-pane').forEach(function (pane) {
      var match = pane.dataset.viewerPlugin === plugin;
      pane.hidden = !match;
      if (match) {
        // Tell that plugin's boot script the pane is now measurable.
        pane.querySelectorAll('[data-viewer]').forEach(function (el) {
          el.dispatchEvent(new CustomEvent('ahg:viewer-shown', { bubbles: true }));
        });
      }
    });

    root.querySelectorAll('.ahg-viewer-tab').forEach(function (tab) {
      tab.classList.toggle('is-active', tab.dataset.viewerTarget === plugin);
    });
  }

  function init() {
    document.querySelectorAll('.ahg-iiif-viewer').forEach(function (root) {
      root.querySelectorAll('.ahg-viewer-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
          show(root, tab.dataset.viewerTarget);
        });
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
