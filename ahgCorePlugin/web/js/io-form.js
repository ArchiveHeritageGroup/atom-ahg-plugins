/**
 * Shared edit-form behaviour for every descriptive standard.
 *
 * WHY THIS FILE EXISTS
 *
 * These functions were duplicated inline across six templates - ISAD, RAD,
 * DACS, Dublin Core, MODS and RiC - about 2,600 lines of script that was
 * between 62% and 92% the same and had already drifted into two incompatible
 * variants. Adding one feature meant six edits, and three of the six needed a
 * different patch from the other three.
 *
 * WHY IT LIVES IN ahgCorePlugin
 *
 * Every standard plugin already declares ahgCorePlugin as its only dependency,
 * and ahgCorePlugin is core, locked and always enabled, so shared UI here is
 * consistent with one-plugin-at-a-time delivery: a standard plugin installed on
 * stock AtoM needs only what it declares.
 *
 * Putting it in ahgInformationObjectManagePlugin, where standard-switch.js
 * lives today, would make five plugins depend on a sixth none of them declares.
 * That is already true of standard-switch.js and the @io_term_autocomplete
 * route, and it is a latent break for a standalone install.
 *
 * CONFIGURATION
 *
 * Values from PHP are passed in rather than templated into this file, so it
 * stays a static asset:
 *
 *   window.AHG_IO_FORM = {
 *     actorAcUrl: '...', repoAcUrl: '...', termAcUrl: '...',
 *     termCreateUrl: '...', canCreateTerms: true,
 *     labels: {create: 'Create', remove: 'Remove', createFailed: '...'}
 *   };
 *
 * The functions are also exposed as bare globals because the standard-specific
 * code still inline in each template calls them by name. That keeps the
 * per-template change a deletion rather than a rewrite.
 */
(function (window, document) {
  'use strict';

  var cfg = window.AHG_IO_FORM || {};
  var labels = cfg.labels || {};

  var ACTOR_AC_URL = cfg.actorAcUrl || '';
  var REPO_AC_URL = cfg.repoAcUrl || '';
  var TERM_AC_URL = cfg.termAcUrl || '';
  var TERM_CREATE_URL = cfg.termCreateUrl || '';
  var TERM_CAN_CREATE = !!cfg.canCreateTerms;

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function showDropdown(input, results, onSelect) {
    var dropdown = document.createElement('div');
    dropdown.className = 'list-group position-absolute w-100 ac-dropdown';
    dropdown.style.zIndex = '1050';
    results.forEach(function(item) {
      var a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action py-1 small';
      a.href = '#';
      a.textContent = item.name || '';
      a.addEventListener('click', function(e) {
        e.preventDefault();
        onSelect(item);
        removeDropdownsFor(input);
      });
      dropdown.appendChild(a);
    });
    input.parentNode.style.position = 'relative';
    removeDropdownsFor(input);
    input.parentNode.appendChild(dropdown);
  }

  function removeDropdownsFor(input) {
    var existing = input.parentNode.querySelectorAll('.ac-dropdown');
    existing.forEach(function(el) { el.remove(); });
  }

  function setupAutocomplete(input, buildUrl, onSelect, extraOption) {
    var timeout = null;
    input.addEventListener('input', function() {
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdownsFor(input); return; }
      timeout = setTimeout(function() {
        fetch(buildUrl(q))
          .then(function(r) { return r.json(); })
          .then(function(results) {
            // extraOption lets a caller append an item (used for "Create ...").
            // This branch used to close the dropdown outright, which is why a
            // near-empty vocabulary behaved like a dead text box.
            var items = results || [];
            var extra = extraOption ? extraOption(q, items) : null;

            if (extra) { items = items.concat([extra]); }
            if (!items.length) { removeDropdownsFor(input); return; }

            showDropdown(input, items, onSelect);
          })
          .catch(function() { removeDropdownsFor(input); });
      }, 300);
    });
  }

  function initActorAutocomplete(input) {
    setupAutocomplete(input,
      function(q) { return ACTOR_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        input.value = item.name;
        var hiddenId = input.parentNode.querySelector('input[type=hidden]');
        if (hiddenId) hiddenId.value = item.id;
      }
    );
  }

  function addTermAP(targetId, fieldName, termId, termName) {
    var list = document.getElementById(targetId);
    var div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML =
      '<input type="text" class="form-control" value="' + escHtml(termName) + '" readonly>' +
      '<input type="hidden" name="' + fieldName + '" value="' + termId + '">' +
      '<button type="button" class="btn btn-outline-danger btn-remove-ap">' + (labels.remove || 'Remove') + '</button>';
    list.appendChild(div);
  }

  function initTermAccessPoints() {
    document.querySelectorAll('.term-autocomplete-add').forEach(function(input) {
      var taxonomy = input.getAttribute('data-taxonomy');
      var targetId = input.getAttribute('data-target');
      var fieldName = input.getAttribute('data-name');

      setupAutocomplete(input,
        function(q) { return TERM_AC_URL + '?taxonomy=' + taxonomy + '&query=' + encodeURIComponent(q) + '&limit=10'; },
        function(item) {
          if (item.id !== '__create__') {
            addTermAP(targetId, fieldName, item.id, item.name);
            input.value = '';
            return;
          }

          var body = new URLSearchParams();
          body.append('taxonomy', taxonomy);
          body.append('name', item.term);

          input.disabled = true;

          fetch(TERM_CREATE_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            credentials: 'same-origin',
            body: body.toString()
          })
            .then(function(r) { return r.json().then(function(d) { return {ok: r.ok, data: d}; }); })
            .then(function(res) {
              if (!res.ok || !res.data || !res.data.id) {
                alert(res.data && res.data.error ? res.data.error : (labels.createFailed || 'The term could not be created.'));
                return;
              }
              // A name that already exists comes back rather than erroring, so two
              // people adding the same term converge on one instead of creating two.
              addTermAP(targetId, fieldName, res.data.id, res.data.name);
              input.value = '';
            })
            .catch(function() { alert((labels.createFailed || 'The term could not be created.')); })
            .then(function() { input.disabled = false; input.focus(); });
        },
        function(q, results) {
          if (!TERM_CAN_CREATE) { return null; }

          var exists = results.some(function(r) {
            return (r.name || '').toLowerCase() === q.toLowerCase();
          });

          if (exists) { return null; }

          return {id: '__create__', term: q, name: (labels.create || 'Create') + ' \u201c' + q + '\u201d'};
        }
      );
    });
  }

  // Close dropdowns on an outside click.
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.ac-dropdown') && !e.target.classList.contains('actor-autocomplete')
        && !e.target.classList.contains('actor-autocomplete-add')
        && !e.target.classList.contains('repository-autocomplete')
        && !e.target.classList.contains('term-autocomplete-add')) {
      document.querySelectorAll('.ac-dropdown').forEach(function(d) { d.remove(); });
    }
  });

  window.AhgIoForm = {
    escHtml: escHtml, showDropdown: showDropdown,
    removeDropdownsFor: removeDropdownsFor, setupAutocomplete: setupAutocomplete,
    initActorAutocomplete: initActorAutocomplete,
    initTermAccessPoints: initTermAccessPoints, config: cfg
  };

  window.escHtml = escHtml;
  window.showDropdown = showDropdown;
  window.removeDropdownsFor = removeDropdownsFor;
  window.setupAutocomplete = setupAutocomplete;
  window.initActorAutocomplete = initActorAutocomplete;
  window.addTermAP = addTermAP;

  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', initTermAccessPoints);
  } else {
    initTermAccessPoints();
  }
})(window, document);
