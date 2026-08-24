/**
 * Archival description TomSelect remote-load picker.
 *
 * The accession form's "Archival description area" is a multiple <select> that
 * the server renders EMPTY - there is no sensible way to preload every
 * description in the catalogue. Without an initialiser it is therefore an empty
 * box with nothing to choose, which is what it had been: the template referenced
 * /atom-framework/public/js/io-tom-select.js, a file that did not exist (404),
 * and stock AtoM's nginx would not have served it from that path anyway.
 *
 * Simpler than the donor picker beside it: this field is inline rather than in a
 * Bootstrap modal, so none of the modal bridging applies - no editRow
 * pre-population, no contact auto-fetch, no dropdownParent escape.
 *
 * The endpoint returns an HTML fragment, not JSON, and ONLY when asked as an
 * XHR - without X-Requested-With it renders the whole themed page (43 KB of it),
 * whose tables would parse into nonsense options.
 */
(function () {
  'use strict';

  function parseAutocompleteHtml(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var rows = [];

    doc.querySelectorAll('tbody tr td a').forEach(function (a) {
      var href = a.getAttribute('href');

      if (!href) {
        return;
      }

      rows.push({
        value: href,
        text: (a.getAttribute('title') || a.textContent || '').trim()
      });
    });

    return rows;
  }

  function remoteUrlFor(el) {
    // The template carries the URL on the surrounding accordion body rather than
    // on the select itself.
    var holder = el.closest('[data-io-remote-url]');

    return holder ? holder.getAttribute('data-io-remote-url') : el.getAttribute('data-remote-url');
  }

  function initSelect(el) {
    if (el.tomselect || typeof TomSelect === 'undefined') {
      return;
    }

    var url = remoteUrlFor(el);

    if (!url) {
      return;
    }

    new TomSelect(el, {
      valueField: 'value',
      labelField: 'text',
      searchField: 'text',
      maxOptions: 50,
      preload: false,
      create: false,
      persist: false,
      // Render the results on <body> rather than inside the control. The field
      // sits in a Bootstrap accordion, and `.accordion-item` carries
      // overflow:hidden - measured at 168px tall - which clipped the absolutely
      // positioned dropdown so only one result was ever visible. Same clipping
      // problem the donor picker hit inside a scrollable modal.
      dropdownParent: 'body',
      placeholder: el.getAttribute('data-placeholder') || 'Search archival descriptions',
      load: function (query, callback) {
        if (!query.length) {
          callback();

          return;
        }

        var sep = url.indexOf('?') > -1 ? '&' : '?';

        fetch(url + sep + 'query=' + encodeURIComponent(query) + '&limit=20', {
          // Without this the endpoint returns the entire themed page.
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        })
          .then(function (r) { return r.ok ? r.text() : ''; })
          .then(function (html) { callback(html ? parseAutocompleteHtml(html) : []); })
          .catch(function () { callback(); });
      },
      render: {
        no_results: function (data, escape) {
          return '<div class="no-results p-2 text-muted">No descriptions match "' + escape(data.input) + '"</div>';
        }
      }
    });
  }

  function initAll() {
    document.querySelectorAll('select.tom-remote-io').forEach(initSelect);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
