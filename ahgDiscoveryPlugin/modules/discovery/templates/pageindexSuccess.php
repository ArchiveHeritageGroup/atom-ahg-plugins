<?php decorate_with('layout_1col'); ?>

<?php use_helper('I18N'); ?>

<?php
$initialQuery  = $sf_data->getRaw('query') ?? '';
$initialType   = $sf_data->getRaw('type') ?? 'all';
$results       = sfOutputEscaper::unescape($results ?? []);
$indexCounts   = sfOutputEscaper::unescape($indexCounts ?? []);
$totalMatches  = (int) ($totalMatches ?? 0);
$searchPerformed = (bool) ($searchPerformed ?? false);
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-tree me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0"><?php echo __('PageIndex Discovery'); ?></h1>
      <span class="text-muted"><?php echo __('LLM-driven search across indexed document trees'); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div id="pageindex-app" class="discovery-container">

  <!-- Search Input -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body p-4">
      <form id="pageindex-form" method="get" action="/discovery/pageindex">
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-white border-end-0">
            <i class="fas fa-search text-muted"></i>
          </span>
          <input type="text"
                 id="pageindex-query"
                 name="q"
                 class="form-control border-start-0 ps-0"
                 placeholder="<?php echo __('Ask a question about indexed records...'); ?>"
                 value="<?php echo htmlspecialchars($initialQuery, ENT_QUOTES, 'UTF-8'); ?>"
                 autocomplete="off"
                 autofocus>
          <button id="pageindex-search-btn" class="btn btn-primary px-4" type="submit">
            <i class="fas fa-search me-1"></i> <?php echo __('Search'); ?>
          </button>
        </div>

        <!-- Type Filter -->
        <div class="mt-3 d-flex align-items-center gap-2">
          <small class="text-muted me-1"><i class="fas fa-filter me-1"></i><?php echo __('Document type:'); ?></small>
          <select id="pageindex-type" name="type" class="form-select form-select-sm" style="width: auto;">
            <option value="all"<?php echo $initialType === 'all' ? ' selected' : ''; ?>><?php echo __('All'); ?></option>
            <option value="ead"<?php echo $initialType === 'ead' ? ' selected' : ''; ?>><?php echo __('EAD Finding Aids'); ?></option>
            <option value="pdf"<?php echo $initialType === 'pdf' ? ' selected' : ''; ?>><?php echo __('PDF Documents'); ?></option>
            <option value="rico"<?php echo $initialType === 'rico' ? ' selected' : ''; ?>><?php echo __('RiC-O Metadata'); ?></option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <!-- Index Status Summary -->
  <div class="card mb-4">
    <div class="card-body">
      <h6 class="mb-3"><i class="fas fa-database me-2"></i><?php echo __('Index Status'); ?></h6>
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div class="text-center p-2 rounded bg-light">
            <div class="fs-4 fw-bold text-primary"><?php echo (int) ($indexCounts['total'] ?? 0); ?></div>
            <small class="text-muted"><?php echo __('Total Indexed'); ?></small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-center p-2 rounded bg-light">
            <div class="fs-4 fw-bold text-success"><?php echo (int) ($indexCounts['ead'] ?? 0); ?></div>
            <small class="text-muted"><?php echo __('EAD'); ?></small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-center p-2 rounded bg-light">
            <div class="fs-4 fw-bold text-info"><?php echo (int) ($indexCounts['pdf'] ?? 0); ?></div>
            <small class="text-muted"><?php echo __('PDF'); ?></small>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="text-center p-2 rounded bg-light">
            <div class="fs-4 fw-bold text-warning"><?php echo (int) ($indexCounts['rico'] ?? 0); ?></div>
            <small class="text-muted"><?php echo __('RiC-O'); ?></small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Spinner (for async API calls) -->
  <div id="pageindex-loading" class="text-center py-5 d-none">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden"><?php echo __('Searching...'); ?></span>
    </div>
    <p class="text-muted mt-3"><?php echo __('LLM is reasoning over indexed trees...'); ?></p>
  </div>

  <?php if ($searchPerformed && !empty($results)): ?>

  <!-- Results Summary -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <span class="fw-bold"><?php echo $totalMatches; ?> <?php echo __('match'); ?><?php echo $totalMatches !== 1 ? 'es' : ''; ?></span>
      <span class="text-muted ms-1"><?php echo __('across'); ?> <?php echo count($results); ?> <?php echo __('indexed record'); ?><?php echo count($results) !== 1 ? 's' : ''; ?></span>
    </div>
  </div>

  <!-- Results -->
  <?php foreach ($results as $treeResult): ?>
  <div class="card mb-3 shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <div>
        <?php
          $typeBadgeClass = 'bg-secondary';
          $typeLabel = strtoupper($treeResult['object_type'] ?? '');
          if ($treeResult['object_type'] === 'ead') { $typeBadgeClass = 'bg-success'; }
          elseif ($treeResult['object_type'] === 'pdf') { $typeBadgeClass = 'bg-info'; }
          elseif ($treeResult['object_type'] === 'rico') { $typeBadgeClass = 'bg-warning text-dark'; }
        ?>
        <span class="badge <?php echo $typeBadgeClass; ?> me-2"><?php echo $typeLabel; ?></span>
        <?php if (!empty($treeResult['record_slug'])): ?>
          <a href="/<?php echo htmlspecialchars($treeResult['record_slug'], ENT_QUOTES, 'UTF-8'); ?>" class="fw-bold text-decoration-none">
            <?php echo htmlspecialchars($treeResult['record_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </a>
        <?php else: ?>
          <span class="fw-bold"><?php echo htmlspecialchars($treeResult['record_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>
      <small class="text-muted"><?php echo count($treeResult['matches'] ?? []); ?> <?php echo __('nodes matched'); ?></small>
    </div>

    <?php if (!empty($treeResult['reasoning'])): ?>
    <div class="card-body border-bottom bg-white">
      <small class="text-muted"><i class="fas fa-brain me-1"></i><?php echo __('LLM Reasoning:'); ?></small>
      <p class="mb-0 mt-1 fst-italic"><?php echo htmlspecialchars($treeResult['reasoning'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>

    <div class="card-body p-0">
      <?php foreach ($treeResult['matches'] ?? [] as $match): ?>
      <div class="pageindex-match-row p-3 border-bottom">
        <div class="d-flex align-items-start justify-content-between">
          <div class="flex-grow-1">
            <!-- Breadcrumb Path -->
            <?php if (!empty($match['breadcrumb'])): ?>
            <nav class="mb-1">
              <ol class="breadcrumb breadcrumb-sm mb-0" style="font-size: 0.78rem;">
                <?php foreach ($match['breadcrumb'] as $i => $crumb): ?>
                  <?php $isLast = ($i === count($match['breadcrumb']) - 1); ?>
                  <li class="breadcrumb-item<?php echo $isLast ? ' active' : ''; ?>">
                    <?php if (!empty($crumb['level'])): ?>
                      <span class="text-muted">[<?php echo htmlspecialchars($crumb['level'], ENT_QUOTES, 'UTF-8'); ?>]</span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($crumb['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                  </li>
                <?php endforeach; ?>
              </ol>
            </nav>
            <?php endif; ?>

            <!-- Node Title -->
            <div class="fw-semibold"><?php echo htmlspecialchars($match['node_title'] ?? $match['node_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>

            <!-- Node Summary -->
            <?php if (!empty($match['node_summary'])): ?>
            <div class="text-muted mt-1" style="font-size: 0.88rem;">
              <?php echo htmlspecialchars($match['node_summary'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <!-- Match Reason -->
            <?php if (!empty($match['reason'])): ?>
            <div class="mt-1">
              <small class="text-success"><i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars($match['reason'], ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <?php endif; ?>

            <!-- Keywords -->
            <?php if (!empty($match['node_keywords'])): ?>
            <div class="mt-1">
              <?php foreach ($match['node_keywords'] as $kw): ?>
                <span class="badge bg-light text-muted me-1" style="font-size: 0.7rem;"><?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Relevance Score -->
          <?php
            $relevance = isset($match['relevance']) ? (float) $match['relevance'] : 0;
            $pct = (int) round($relevance * 100);
            $barColor = $pct >= 70 ? '#198754' : ($pct >= 40 ? '#fd7e14' : '#6c757d');
          ?>
          <div class="ms-3 flex-shrink-0 text-end" style="min-width: 70px;" title="<?php echo __('Relevance:'); ?> <?php echo $pct; ?>%">
            <span class="fw-bold" style="color: <?php echo $barColor; ?>;"><?php echo $pct; ?>%</span>
            <div style="height: 4px; width: 60px; background: #e9ecef; border-radius: 2px; margin-top: 2px;">
              <div style="height: 100%; width: <?php echo $pct; ?>%; background: <?php echo $barColor; ?>; border-radius: 2px;"></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php elseif ($searchPerformed): ?>

  <!-- No Results -->
  <div class="text-center py-5">
    <i class="fas fa-search fa-3x text-muted mb-3"></i>
    <h4 class="text-muted"><?php echo __('No results found'); ?></h4>
    <p class="text-muted"><?php echo __('No matching nodes found in indexed trees. Try different keywords or index more records.'); ?></p>
  </div>

  <?php elseif (!$searchPerformed): ?>

  <!-- Prompt to search -->
  <div class="text-center py-5">
    <i class="fas fa-tree fa-3x text-muted mb-3"></i>
    <h4 class="text-muted"><?php echo __('Search indexed document trees'); ?></h4>
    <p class="text-muted"><?php echo __('Enter a query above to search across all indexed records using LLM reasoning.'); ?></p>
  </div>

  <?php endif; ?>

</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
/* PageIndex Discovery Styles */
.discovery-container { max-width: 960px; margin: 0 auto; }

#pageindex-query:focus { box-shadow: none; border-color: #dee2e6; }
.input-group-lg .input-group-text { font-size: 1rem; }

.pageindex-match-row { transition: background 0.15s; }
.pageindex-match-row:hover { background: #fafbfc; }
.pageindex-match-row:last-child { border-bottom: none !important; }

.breadcrumb-sm .breadcrumb-item + .breadcrumb-item::before {
  content: "\203A";
  padding: 0 0.35rem;
  color: #6c757d;
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
(function() {
  'use strict';

  var form       = document.getElementById('pageindex-form');
  var queryInput = document.getElementById('pageindex-query');
  var loadingEl  = document.getElementById('pageindex-loading');

  // Async API search (for future use: fetch from /discovery/pageindex/api instead of page reload)
  function doAsyncSearch(query, type) {
    loadingEl.classList.remove('d-none');

    fetch('/discovery/pageindex/api', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        query: query,
        object_type: (type === 'all') ? null : type
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      loadingEl.classList.add('d-none');
      if (data.success) {
        // For now, the server-side rendering handles display.
        // This endpoint is available for future AJAX-based UIs.
        console.log('[pageindex] API returned', data.total_matches, 'matches across', (data.results || []).length, 'trees');
      }
    })
    .catch(function(err) {
      loadingEl.classList.add('d-none');
      console.error('[pageindex]', err);
    });
  }

  // Submit form on Enter (already default for form, but ensure it works)
  queryInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      form.submit();
    }
  });

})();
</script>

<?php end_slot(); ?>
