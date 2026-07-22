/* ahgSAHRAPlugin - front-end behaviour */
(function () {
  'use strict';

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // Application form: site type-ahead + dig-area loader.
  var appForm = document.querySelector('form[data-search-url][data-areas-url]');
  if (appForm) {
    var searchUrl = appForm.getAttribute('data-search-url');
    var areasUrl = appForm.getAttribute('data-areas-url');
    var input = document.getElementById('sahra-site-search');
    var hiddenId = document.getElementById('sahra-site-id');
    var hiddenName = document.getElementById('sahra-site-name');
    var results = document.getElementById('sahra-site-results');
    var chosen = document.getElementById('sahra-site-chosen');
    var chosenTitle = document.getElementById('sahra-site-chosen-title');
    var clearBtn = document.getElementById('sahra-site-clear');
    var areasWrap = document.getElementById('sahra-areas-wrap');
    var areas = document.getElementById('sahra-areas');
    var timer = null;

    function hideResults() { results.classList.add('d-none'); results.innerHTML = ''; }

    function loadAreas(siteId) {
      areas.innerHTML = '<div class="text-muted small">Loading dig areas...</div>';
      areasWrap.classList.remove('d-none');
      fetch(areasUrl + '?site_id=' + encodeURIComponent(siteId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (rows) {
          if (!rows || !rows.length) {
            areas.innerHTML = '<div class="text-muted small">No child records - this site has no catalogued dig areas.</div>';
            return;
          }
          areas.innerHTML = '';
          rows.forEach(function (a) {
            var id = 'digarea_' + a.id;
            var wrap = document.createElement('div');
            wrap.className = 'form-check';
            wrap.innerHTML = '<input class="form-check-input" type="checkbox" name="dig_area_ids[]" value="' + a.id + '" id="' + id + '">' +
              '<label class="form-check-label" for="' + id + '">' + escapeHtml(a.title) +
              (a.identifier ? ' <small class="text-muted">(' + escapeHtml(a.identifier) + ')</small>' : '') + '</label>';
            areas.appendChild(wrap);
          });
        })
        .catch(function () { areas.innerHTML = '<div class="text-danger small">Could not load dig areas.</div>'; });
    }

    function pick(item) {
      hiddenId.value = item.id;
      hiddenName.value = item.title;
      chosenTitle.textContent = item.title + (item.children > 0 ? ' (' + item.children + ' child records)' : '');
      chosen.classList.remove('d-none');
      input.classList.add('d-none');
      hideResults();
      loadAreas(item.id);
    }

    if (input) {
      input.addEventListener('input', function () {
        var q = input.value.trim();
        if (timer) clearTimeout(timer);
        if (q.length < 2) { hideResults(); return; }
        timer = setTimeout(function () {
          fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
              results.innerHTML = '';
              if (!rows || !rows.length) {
                results.innerHTML = '<div class="list-group-item text-muted small">No matching records</div>';
                results.classList.remove('d-none');
                return;
              }
              rows.forEach(function (item) {
                var a = document.createElement('button');
                a.type = 'button';
                a.className = 'list-group-item list-group-item-action';
                var meta = [];
                if (item.identifier) meta.push(item.identifier);
                if (item.children > 0) meta.push(item.children + ' children');
                a.innerHTML = '<span>' + escapeHtml(item.title) + '</span>' + (meta.length ? ' <small class="text-muted">- ' + escapeHtml(meta.join(' · ')) + '</small>' : '');
                a.addEventListener('click', function () { pick(item); });
                results.appendChild(a);
              });
              results.classList.remove('d-none');
            })
            .catch(hideResults);
        }, 250);
      });
    }
    if (clearBtn) clearBtn.addEventListener('click', function () {
      hiddenId.value = ''; hiddenName.value = '';
      chosen.classList.add('d-none'); input.classList.remove('d-none'); input.value = ''; input.focus();
      areasWrap.classList.add('d-none'); areas.innerHTML = '';
    });
    document.addEventListener('click', function (e) {
      if (input && !input.contains(e.target) && !results.contains(e.target)) hideResults();
    });
  }

  // Application form: add/remove supporting-document upload rows.
  var docAdd = document.getElementById('sahra-doc-add');
  var docInputs = document.getElementById('sahra-doc-inputs');
  if (docAdd && docInputs) {
    docAdd.addEventListener('click', function () {
      var row = docInputs.querySelector('.sahra-doc-row');
      if (!row) return;
      var clone = row.cloneNode(true);
      var input = clone.querySelector('input[type=file]');
      if (input) input.value = '';
      docInputs.appendChild(clone);
    });
    docInputs.addEventListener('click', function (e) {
      var btn = e.target.closest('.sahra-doc-remove');
      if (btn && docInputs.querySelectorAll('.sahra-doc-row').length > 1) {
        btn.closest('.sahra-doc-row').remove();
      }
    });
  }

  // Decision form: show the "issued" fields only when outcome = issued.
  document.querySelectorAll('form[data-sahra-decision]').forEach(function (form) {
    var issuedBlock = form.querySelector('[data-decision-issued]');
    function apply() {
      var checked = form.querySelector('[data-decision-outcome]:checked');
      var issued = checked && checked.value === 'issued';
      if (issuedBlock) {
        issuedBlock.style.display = issued ? '' : 'none';
      }
    }
    form.querySelectorAll('[data-decision-outcome]').forEach(function (r) {
      r.addEventListener('change', apply);
    });
    apply();
  });
})();
