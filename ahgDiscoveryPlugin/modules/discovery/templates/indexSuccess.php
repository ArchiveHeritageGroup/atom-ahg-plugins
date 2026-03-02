<?php decorate_with('layout_1col'); ?>

<?php use_helper('I18N'); ?>

<?php
$popularTopics = sfOutputEscaper::unescape($popularTopics ?? []);
$initialQuery = $sf_data->getRaw('query') ?? '';
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-compass me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0"><?php echo __('Discover'); ?></h1>
      <span class="text-muted"><?php echo __('Search across collections using natural language'); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div id="discovery-app" class="discovery-container">

  <!-- Search Input -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body p-4">
      <div class="input-group input-group-lg">
        <span class="input-group-text bg-white border-end-0">
          <i class="fas fa-search text-muted"></i>
        </span>
        <input type="text"
               id="discovery-query"
               class="form-control border-start-0 ps-0"
               placeholder="<?php echo __('Ask a question... e.g. "photographs of District Six in the 1960s"'); ?>"
               value="<?php echo htmlspecialchars($initialQuery, ENT_QUOTES, 'UTF-8'); ?>"
               autocomplete="off"
               autofocus>
        <button id="discovery-search-btn" class="btn btn-primary px-4" type="button">
          <i class="fas fa-search me-1"></i> <?php echo __('Discover'); ?>
        </button>
      </div>

      <!-- Search Mode Selector -->
      <div class="mt-3 d-flex align-items-center gap-2">
        <small class="text-muted me-1"><i class="fas fa-sliders-h me-1"></i>Search mode:</small>
        <div class="btn-group btn-group-sm" role="group" id="discovery-mode-group">
          <button type="button" class="btn btn-outline-primary active" data-mode="standard"
                  title="<?php echo __('Keyword search using Elasticsearch'); ?>">
            <i class="fas fa-search me-1"></i><?php echo __('Standard'); ?>
          </button>
          <button type="button" class="btn btn-outline-primary" data-mode="semantic"
                  title="<?php echo __('Standard + NER entity matching'); ?>">
            <i class="fas fa-brain me-1"></i><?php echo __('Semantic'); ?>
          </button>
          <button type="button" class="btn btn-outline-primary" data-mode="vector"
                  title="<?php echo __('Standard + Semantic + vector similarity (requires Qdrant)'); ?>">
            <i class="fas fa-project-diagram me-1"></i><?php echo __('Vector'); ?>
          </button>
        </div>
      </div>

      <!-- Query Expansion Info (hidden until search) -->
      <div id="discovery-expansion" class="mt-3 d-none">
        <small class="text-muted">
          <i class="fas fa-lightbulb me-1"></i>
          <span id="expansion-text"></span>
        </small>
      </div>
    </div>
  </div>

  <!-- Popular Topics (shown when no results) -->
  <?php if (!empty($popularTopics)): ?>
  <div id="discovery-popular" class="mb-4">
    <h5 class="text-muted mb-3">
      <i class="fas fa-fire-alt me-1"></i> <?php echo __('Popular searches'); ?>
    </h5>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($popularTopics as $topic): ?>
        <button class="btn btn-outline-secondary btn-sm discovery-topic-btn"
                data-query="<?php echo htmlspecialchars($topic['query'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars($topic['query']); ?>
          <span class="badge bg-light text-muted ms-1"><?php echo (int) $topic['count']; ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Loading Spinner -->
  <div id="discovery-loading" class="text-center py-5 d-none">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden"><?php echo __('Searching...'); ?></span>
    </div>
    <p class="text-muted mt-3"><?php echo __('Searching across collections...'); ?></p>
  </div>

  <!-- Results Summary -->
  <div id="discovery-summary" class="d-none mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <span id="result-count" class="fw-bold"></span>
        <span class="text-muted ms-2" id="result-time"></span>
      </div>
      <div class="btn-group btn-group-sm" role="group">
        <button type="button" class="btn btn-outline-secondary active" id="view-grouped"
                title="<?php echo __('Group by collection'); ?>">
          <i class="fas fa-layer-group"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" id="view-flat"
                title="<?php echo __('Flat list'); ?>">
          <i class="fas fa-list"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Grouped Results Container -->
  <div id="discovery-results-grouped"></div>

  <!-- Flat Results Container -->
  <div id="discovery-results-flat" class="d-none"></div>

  <!-- No Results Message -->
  <div id="discovery-no-results" class="text-center py-5 d-none">
    <i class="fas fa-search fa-3x text-muted mb-3"></i>
    <h4 class="text-muted"><?php echo __('No results found'); ?></h4>
    <p class="text-muted"><?php echo __('Try different keywords or a broader search term.'); ?></p>
  </div>

  <!-- Pagination -->
  <div id="discovery-pagination" class="d-none">
    <nav aria-label="<?php echo __('Discovery results pagination'); ?>">
      <ul class="pagination justify-content-center" id="pagination-list"></ul>
    </nav>
  </div>

</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
/* Discovery Page Styles */
.discovery-container { max-width: 960px; margin: 0 auto; }

#discovery-query:focus { box-shadow: none; border-color: #dee2e6; }
.input-group-lg .input-group-text { font-size: 1rem; }

/* Collection Card */
.discovery-collection {
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  margin-bottom: 1.25rem;
  overflow: hidden;
}
.discovery-collection-header {
  background: #f8f9fa;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e9ecef;
  cursor: pointer;
}
.discovery-collection-header:hover { background: #e9ecef; }
.discovery-collection-header h5 { margin: 0; font-size: 0.95rem; }
.discovery-collection-body { padding: 0; }

/* Result Card */
.discovery-result {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.15s;
}
.discovery-result:last-child { border-bottom: none; }
.discovery-result:hover { background: #fafbfc; }
.discovery-result-title {
  font-weight: 600;
  color: #0d6efd;
  text-decoration: none;
  font-size: 0.95rem;
}
.discovery-result-title:hover { text-decoration: underline; }
.discovery-result-meta {
  font-size: 0.8rem;
  color: #6c757d;
  margin-top: 0.25rem;
}
.discovery-result-meta span + span::before {
  content: "\00b7";
  margin: 0 0.4rem;
}
.discovery-result-scope {
  font-size: 0.85rem;
  color: #495057;
  margin-top: 0.35rem;
  line-height: 1.5;
}
.discovery-result-scope mark {
  background: #fff3cd;
  padding: 0 2px;
  border-radius: 2px;
}
.discovery-result-reasons {
  margin-top: 0.35rem;
}
.discovery-result-reasons .badge {
  font-weight: 400;
  font-size: 0.7rem;
}
.discovery-result-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
  flex-shrink: 0;
}

/* Entity Tags */
.entity-tag {
  font-size: 0.7rem;
  padding: 0.15rem 0.4rem;
  border-radius: 3px;
  display: inline-block;
  margin: 1px;
}
.entity-tag-PERSON { background: #d1ecf1; color: #0c5460; }
.entity-tag-ORG    { background: #d4edda; color: #155724; }
.entity-tag-GPE    { background: #fff3cd; color: #856404; }
.entity-tag-DATE   { background: #e2e3e5; color: #383d41; }
.entity-tag-LOC    { background: #cce5ff; color: #004085; }

/* Popular Topics */
.discovery-topic-btn { border-radius: 1rem; font-size: 0.85rem; }
.discovery-topic-btn:hover { background: #0d6efd; color: white; border-color: #0d6efd; }

/* View Toggle */
#view-grouped.active, #view-flat.active { background: #0d6efd; color: white; border-color: #0d6efd; }

/* Mode Toggle */
#discovery-mode-group .btn.active { background: #0d6efd; color: white; border-color: #0d6efd; }
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
(function() {
  'use strict';

  var searchInput  = document.getElementById('discovery-query');
  var searchBtn    = document.getElementById('discovery-search-btn');
  var loadingEl    = document.getElementById('discovery-loading');
  var summaryEl    = document.getElementById('discovery-summary');
  var groupedEl    = document.getElementById('discovery-results-grouped');
  var flatEl       = document.getElementById('discovery-results-flat');
  var noResultsEl  = document.getElementById('discovery-no-results');
  var paginationEl = document.getElementById('discovery-pagination');
  var paginationList = document.getElementById('pagination-list');
  var expansionEl  = document.getElementById('discovery-expansion');
  var expansionText = document.getElementById('expansion-text');
  var popularEl    = document.getElementById('discovery-popular');
  var viewGrouped  = document.getElementById('view-grouped');
  var viewFlat     = document.getElementById('view-flat');

  var currentPage = 1;
  var currentQuery = '';
  var currentMode = 'standard';
  var searchTimer = null;
  var sessionId = Math.random().toString(36).substr(2, 12);

  // ─── Event Bindings ──────────────────────────────────────────

  searchBtn.addEventListener('click', function() { doSearch(1); });

  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doSearch(1); }
  });

  // Debounced auto-search after 600ms pause
  searchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
      if (searchInput.value.trim().length >= 3) { doSearch(1); }
    }, 600);
  });

  // Popular topic click
  document.querySelectorAll('.discovery-topic-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      searchInput.value = this.dataset.query;
      doSearch(1);
    });
  });

  // Search mode toggle
  document.querySelectorAll('#discovery-mode-group button').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('#discovery-mode-group button').forEach(function(b) { b.classList.remove('active'); });
      this.classList.add('active');
      currentMode = this.dataset.mode;
      if (currentQuery) { doSearch(1); }
    });
  });

  // View toggle
  viewGrouped.addEventListener('click', function() {
    viewGrouped.classList.add('active');
    viewFlat.classList.remove('active');
    groupedEl.classList.remove('d-none');
    flatEl.classList.add('d-none');
  });

  viewFlat.addEventListener('click', function() {
    viewFlat.classList.add('active');
    viewGrouped.classList.remove('active');
    flatEl.classList.remove('d-none');
    groupedEl.classList.add('d-none');
  });

  // ─── Search ─────────────────────────────────────────────────

  function doSearch(page) {
    var q = searchInput.value.trim();
    if (!q) return;

    currentQuery = q;
    currentPage = page || 1;

    // Update URL without reload
    var url = new URL(window.location);
    url.searchParams.set('q', q);
    if (page > 1) { url.searchParams.set('page', page); }
    else { url.searchParams.delete('page'); }
    if (currentMode !== 'standard') { url.searchParams.set('mode', currentMode); }
    else { url.searchParams.delete('mode'); }
    history.replaceState(null, '', url);

    // Show loading
    loadingEl.classList.remove('d-none');
    summaryEl.classList.add('d-none');
    groupedEl.innerHTML = '';
    flatEl.innerHTML = '';
    noResultsEl.classList.add('d-none');
    paginationEl.classList.add('d-none');
    if (popularEl) popularEl.classList.add('d-none');

    var startTime = performance.now();

    fetch('/discovery/search?q=' + encodeURIComponent(q) + '&page=' + currentPage + '&limit=20&mode=' + currentMode)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        loadingEl.classList.add('d-none');
        var elapsed = Math.round(performance.now() - startTime);

        if (!data.success || data.total === 0) {
          noResultsEl.classList.remove('d-none');
          return;
        }

        // Show summary
        summaryEl.classList.remove('d-none');
        var modeLabels = { standard: 'Standard', semantic: 'Semantic', vector: 'Vector' };
        var modeLabel = modeLabels[data.mode || currentMode] || 'Standard';
        document.getElementById('result-count').textContent = data.total + ' result' + (data.total !== 1 ? 's' : '');
        document.getElementById('result-time').textContent = '(' + (elapsed / 1000).toFixed(1) + 's \u2022 ' + modeLabel + ')';

        // Show query expansion info
        showExpansion(data.expanded);

        // Render results
        renderGrouped(data.collections || []);
        renderFlat(data.results || []);

        // Pagination
        if (data.pages > 1) {
          renderPagination(data.page, data.pages);
          paginationEl.classList.remove('d-none');
        }
      })
      .catch(function(err) {
        loadingEl.classList.add('d-none');
        noResultsEl.classList.remove('d-none');
        console.error('[discovery]', err);
      });
  }

  // ─── Expansion Info ─────────────────────────────────────────

  function showExpansion(expanded) {
    if (!expanded) { expansionEl.classList.add('d-none'); return; }

    var parts = [];
    if (expanded.synonyms && expanded.synonyms.length) {
      parts.push('Also searching: ' + expanded.synonyms.slice(0, 5).join(', '));
    }
    if (expanded.entityTerms && expanded.entityTerms.length) {
      parts.push('Entities: ' + expanded.entityTerms.slice(0, 5).join(', '));
    }
    if (expanded.dateRange) {
      var dr = expanded.dateRange;
      if (dr.start && dr.end && dr.start !== dr.end) {
        parts.push('Date range: ' + dr.start + '–' + dr.end);
      } else if (dr.start) {
        parts.push('Year: ' + dr.start);
      }
    }

    if (parts.length) {
      expansionText.textContent = parts.join(' \u2022 ');
      expansionEl.classList.remove('d-none');
    } else {
      expansionEl.classList.add('d-none');
    }
  }

  // ─── Render Grouped Results ──────────────────────────────────

  function renderGrouped(collections) {
    var html = '';
    collections.forEach(function(col) {
      var slug = col.fonds_slug ? col.fonds_slug : '';
      var title = escHtml(col.fonds_title || 'Ungrouped');
      var count = col.records ? col.records.length : 0;
      var colId = 'col-' + (col.fonds_id || 0);

      html += '<div class="discovery-collection">';
      html += '<div class="discovery-collection-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#' + colId + '">';
      html += '<h5><i class="fas fa-archive me-2 text-muted"></i>';
      if (slug) { html += '<a href="/' + encodeURI(slug) + '" class="text-decoration-none" onclick="event.stopPropagation()">' + title + '</a>'; }
      else { html += title; }
      html += '</h5>';
      html += '<span class="badge bg-primary rounded-pill">' + count + '</span>';
      html += '</div>';
      html += '<div class="collapse show" id="' + colId + '">';
      html += '<div class="discovery-collection-body">';
      (col.records || []).forEach(function(r) {
        html += renderResultCard(r);
      });
      html += '</div></div></div>';
    });

    groupedEl.innerHTML = html;
    bindClickTracking(groupedEl);
  }

  // ─── Render Flat Results ────────────────────────────────────

  function renderFlat(results) {
    var html = '';
    results.forEach(function(r) {
      html += renderResultCard(r);
    });
    flatEl.innerHTML = html;
    bindClickTracking(flatEl);
  }

  // ─── Single Result Card ─────────────────────────────────────

  function renderResultCard(r) {
    var slug = r.slug || '';
    var title = escHtml(r.title || 'Untitled');
    var scope = r.scope_and_content || '';
    var level = r.level_of_description || '';
    var dates = r.date_range || '';
    var creator = r.creator || '';
    var repo = r.repository || '';
    var thumb = r.thumbnail_url || '';
    var reasons = r.match_reasons || [];
    var entities = r.entities || [];
    var highlights = r.highlights || {};

    // Use highlighted title/scope if available
    var hlTitle = null;
    for (var k in highlights) {
      if (k.indexOf('title') !== -1 && highlights[k].length) {
        hlTitle = highlights[k][0];
      }
    }
    var hlScope = null;
    for (var k2 in highlights) {
      if (k2.indexOf('scopeAndContent') !== -1 && highlights[k2].length) {
        hlScope = highlights[k2][0];
      }
    }

    var html = '<div class="discovery-result d-flex">';

    // Thumbnail
    if (thumb) {
      html += '<div class="me-3 flex-shrink-0">';
      html += '<img src="' + escHtml(thumb) + '" class="discovery-result-thumb" alt="" loading="lazy">';
      html += '</div>';
    }

    html += '<div class="flex-grow-1 min-width-0">';

    // Title + similarity score
    html += '<div class="d-flex align-items-start justify-content-between">';
    html += '<a href="/' + encodeURI(slug) + '" class="discovery-result-title" data-object-id="' + (r.object_id || '') + '">';
    html += hlTitle || title;
    html += '</a>';
    if (typeof r.score === 'number') {
      var pct = Math.round(r.score * 100);
      var barColor = pct >= 70 ? '#198754' : pct >= 40 ? '#fd7e14' : '#6c757d';
      html += '<span class="ms-2 flex-shrink-0 text-nowrap" style="min-width:80px;" title="Similarity: ' + pct + '%">';
      html += '<small class="fw-bold" style="color:' + barColor + '">' + pct + '%</small>';
      html += '<div style="height:4px;width:60px;background:#e9ecef;border-radius:2px;margin-top:2px;">';
      html += '<div style="height:100%;width:' + pct + '%;background:' + barColor + ';border-radius:2px;"></div>';
      html += '</div>';
      html += '</span>';
    }
    html += '</div>';

    // Metadata line
    var metaParts = [];
    if (level) metaParts.push('<span>' + escHtml(level) + '</span>');
    if (dates) metaParts.push('<span><i class="fas fa-calendar-alt me-1"></i>' + escHtml(dates) + '</span>');
    if (creator) metaParts.push('<span><i class="fas fa-user me-1"></i>' + escHtml(creator) + '</span>');
    if (repo) metaParts.push('<span><i class="fas fa-building me-1"></i>' + escHtml(repo) + '</span>');
    if (metaParts.length) {
      html += '<div class="discovery-result-meta">' + metaParts.join('') + '</div>';
    }

    // Scope and content
    if (hlScope || scope) {
      html += '<div class="discovery-result-scope">' + (hlScope || escHtml(scope)) + '</div>';
    }

    // Entity tags (top 5)
    if (entities.length) {
      html += '<div class="discovery-result-reasons mt-1">';
      entities.slice(0, 5).forEach(function(ent) {
        var cls = 'entity-tag entity-tag-' + (ent.type || 'DEFAULT');
        html += '<span class="' + cls + '">' + escHtml(ent.value) + '</span> ';
      });
      html += '</div>';
    }

    // Match reason badges
    if (reasons.length) {
      html += '<div class="discovery-result-reasons">';
      reasons.forEach(function(reason) {
        var badgeClass = 'bg-light text-muted';
        if (reason === 'KEYWORD') badgeClass = 'bg-info bg-opacity-10 text-info';
        else if (reason === 'SEMANTIC') badgeClass = 'bg-primary bg-opacity-10 text-primary';
        else if (reason.startsWith('ENTITY:')) badgeClass = 'bg-success bg-opacity-10 text-success';
        else if (reason === 'SIBLING' || reason === 'CHILD') badgeClass = 'bg-warning bg-opacity-10 text-warning';
        var label = reason.startsWith('ENTITY:') ? reason.substring(7) : reason.toLowerCase();
        html += '<span class="badge ' + badgeClass + ' me-1">' + escHtml(label) + '</span>';
      });
      html += '</div>';
    }

    html += '</div></div>';
    return html;
  }

  // ─── Pagination ─────────────────────────────────────────────

  function renderPagination(page, pages) {
    var html = '';

    // Previous
    html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '">';
    html += '<a class="page-link" href="#" data-page="' + (page - 1) + '">&laquo;</a></li>';

    // Page numbers (show max 7)
    var start = Math.max(1, page - 3);
    var end = Math.min(pages, start + 6);
    start = Math.max(1, end - 6);

    for (var i = start; i <= end; i++) {
      html += '<li class="page-item' + (i === page ? ' active' : '') + '">';
      html += '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
    }

    // Next
    html += '<li class="page-item' + (page >= pages ? ' disabled' : '') + '">';
    html += '<a class="page-link" href="#" data-page="' + (page + 1) + '">&raquo;</a></li>';

    paginationList.innerHTML = html;

    paginationList.querySelectorAll('a.page-link').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        var p = parseInt(this.dataset.page, 10);
        if (p >= 1) {
          doSearch(p);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  // ─── Click Tracking ─────────────────────────────────────────

  function bindClickTracking(container) {
    container.querySelectorAll('.discovery-result-title').forEach(function(link) {
      link.addEventListener('click', function() {
        var objectId = this.dataset.objectId;
        if (objectId && currentQuery) {
          navigator.sendBeacon('/discovery/click', new URLSearchParams({
            query: currentQuery,
            object_id: objectId,
            session_id: sessionId
          }));
        }
      });
    });
  }

  // ─── Utility ────────────────────────────────────────────────

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ─── Auto-search if query in URL ────────────────────────────

  // Restore mode and query from URL
  var urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('mode') && ['standard', 'semantic', 'vector'].indexOf(urlParams.get('mode')) !== -1) {
    currentMode = urlParams.get('mode');
    document.querySelectorAll('#discovery-mode-group button').forEach(function(b) { b.classList.remove('active'); });
    var modeBtn = document.querySelector('#discovery-mode-group button[data-mode="' + currentMode + '"]');
    if (modeBtn) modeBtn.classList.add('active');
  }
  if (urlParams.get('q')) {
    searchInput.value = urlParams.get('q');
    doSearch(parseInt(urlParams.get('page') || '1', 10));
  }

})();
</script>

<?php end_slot(); ?>
