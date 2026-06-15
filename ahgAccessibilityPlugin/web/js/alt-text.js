/*
 * ahgAccessibilityPlugin — front-end alt-text enhancer (WCAG 1.1.1).
 *
 * Loaded on every page; no-ops unless the current page is an information-object
 * record that has authored image alt text. It asks the consumer API for the
 * record's image masters and applies the authored alternative text to matching
 * <img> elements that currently lack a meaningful alt attribute. Pure vanilla
 * JS, no inline script (CSP-safe), no theme changes.
 */
(function () {
  'use strict';

  // Path segments AtoM uses for non-record pages — never treat these as a slug.
  var RESERVED = {
    admin: 1, index: 1, user: 1, settings: 1, search: 1, browse: 1,
    accessibility: 1, taxonomy: 1, actor: 1, repository: 1, informationobject: 1,
    digitalobject: 1, sf: 1, api: 1, oai: 1
  };

  function currentSlug() {
    var parts = window.location.pathname.split('/').filter(Boolean);
    if (!parts.length) return null;
    var last = decodeURIComponent(parts[parts.length - 1]);
    if (!last || RESERVED[last] || RESERVED[parts[0]]) return null;
    if (last.indexOf('.') !== -1) return null; // looks like a file, not a slug
    return last;
  }

  // Filename base used to match a derivative <img> back to its master: strip the
  // extension and any trailing _<usage-id> AtoM appends to derivatives.
  function baseName(name) {
    if (!name) return '';
    var n = String(name).split('/').pop();
    n = n.replace(/\.[a-z0-9]+$/i, '');
    n = n.replace(/_\d+$/, '');
    return n.toLowerCase();
  }

  function isPlaceholderAlt(alt) {
    if (!alt) return true;
    var a = alt.trim().toLowerCase();
    return a === '' || a === 'image' || a === 'thumbnail' || a.indexOf('.jpg') !== -1 ||
      a.indexOf('.png') !== -1 || a.indexOf('.tif') !== -1 || a.indexOf('.webp') !== -1;
  }

  function apply(images) {
    if (!images || !images.length) return;
    var imgs = document.querySelectorAll('img');
    images.forEach(function (img) {
      var alt = img.alt && (img.alt.en || img.alt[Object.keys(img.alt)[0]]);
      if (!alt) return;
      var wanted = baseName(img.name);
      if (!wanted) return;
      for (var i = 0; i < imgs.length; i++) {
        var el = imgs[i];
        var src = el.getAttribute('src') || '';
        if (src && baseName(src) === wanted && isPlaceholderAlt(el.getAttribute('alt'))) {
          el.setAttribute('alt', alt);
          el.setAttribute('data-ahg-alt', 'authored');
        }
      }
    });
  }

  function run() {
    var slug = currentSlug();
    if (!slug) return;
    var url = '/accessibility/alt-text/api/slug/' + encodeURIComponent(slug);
    fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) { if (data && data.images) apply(data.images); })
      .catch(function () { /* best-effort: never break the page */ });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
