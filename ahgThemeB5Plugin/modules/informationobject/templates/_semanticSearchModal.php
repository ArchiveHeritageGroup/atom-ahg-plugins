<?php
/**
 * Semantic Search Modal
 *
 * Provides a dedicated search interface with semantic search capabilities
 * for the GLAM browse page.
 */

// Get current search parameters
$currentQuery = $sf_request->getParameter('query', '');
$semanticEnabled = $sf_request->getParameter('semantic') == '1';
?>

<!-- Semantic Search Modal -->
<div class="modal fade" id="semanticSearchModal" tabindex="-1" aria-labelledby="semanticSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="semanticSearchModalLabel">
          <i class="fas fa-brain me-2"></i><?php echo __('Semantic Search'); ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
      </div>
      <form id="semantic-search-form" action="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" method="get">
        <div class="modal-body">
          <!-- Search Input -->
          <div class="mb-4">
            <label for="semantic-query-input" class="form-label fw-bold">
              <?php echo __('Search Query'); ?>
            </label>
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input
                type="text"
                class="form-control"
                id="semantic-query-input"
                name="query"
                value="<?php echo htmlspecialchars($currentQuery); ?>"
                placeholder="<?php echo __('Enter search terms...'); ?>"
                autofocus>
            </div>
          </div>

          <!-- Semantic Search Toggle -->
          <div class="card mb-4">
            <div class="card-body">
              <div class="form-check form-switch">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="modal-semantic-toggle"
                  name="semantic"
                  value="1"
                  <?php echo $semanticEnabled ? 'checked' : ''; ?>>
                <label class="form-check-label fw-bold" for="modal-semantic-toggle">
                  <i class="fas fa-brain me-1 text-primary"></i>
                  <?php echo __('Enable Semantic Search'); ?>
                </label>
              </div>
              <p class="text-muted small mt-2 mb-0">
                <?php echo __('When enabled, your search will be expanded with synonyms and related terms. For example, searching for "archive" will also find "repository", "depot", "record office", etc.'); ?>
              </p>
            </div>
          </div>

          <!-- Query Expansion Preview -->
          <div id="expansion-preview" class="card bg-light" style="display: none;">
            <div class="card-header">
              <i class="fas fa-expand-arrows-alt me-1"></i>
              <?php echo __('Query Expansion Preview'); ?>
            </div>
            <div class="card-body">
              <div id="expansion-loading" style="display: none;">
                <i class="fas fa-spinner fa-spin me-1"></i>
                <?php echo __('Loading expansions...'); ?>
              </div>
              <div id="expansion-content"></div>
            </div>
          </div>

          <!-- Hidden fields to preserve current filters -->
          <?php
          $preserveParams = ['glamType', 'repos', 'collection', 'levels', 'mediatypes', 'languages', 'sort', 'sortDir'];
          foreach ($preserveParams as $param) {
              $value = $sf_request->getParameter($param);
              if ($value) {
                  echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($value) . '">';
              }
          }
          ?>
          <input type="hidden" name="topLod" value="0">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var queryInput = document.getElementById('semantic-query-input');
  var semanticToggle = document.getElementById('modal-semantic-toggle');
  var expansionPreview = document.getElementById('expansion-preview');
  var expansionContent = document.getElementById('expansion-content');
  var expansionLoading = document.getElementById('expansion-loading');
  var searchForm = document.getElementById('semantic-search-form');
  var debounceTimer = null;
  var cachedExpansions = {};

  function updateExpansionPreview() {
    var query = queryInput.value.trim();
    var semanticEnabled = semanticToggle.checked;

    if (!query || !semanticEnabled) {
      expansionPreview.style.display = 'none';
      return;
    }

    expansionPreview.style.display = 'block';
    expansionLoading.style.display = 'block';
    expansionContent.innerHTML = '';

    fetch('<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'testExpand']); ?>?query=' + encodeURIComponent(query))
      .then(function(response) { return response.json(); })
      .then(function(data) {
        expansionLoading.style.display = 'none';
        if (data.success && Object.keys(data.expansions).length > 0) {
          // Cache the expansions for form submission
          cachedExpansions[query] = data.expansions;

          var html = '';
          for (var term in data.expansions) {
            html += '<div class="mb-2"><strong>' + escapeHtml(term) + '</strong> <i class="fas fa-arrow-right text-muted mx-1"></i> ';
            html += data.expansions[term].map(function(s) {
              return '<span class="badge bg-secondary me-1">' + escapeHtml(s) + '</span>';
            }).join('');
            html += '</div>';
          }
          expansionContent.innerHTML = html;
        } else {
          cachedExpansions[query] = null;
          expansionContent.innerHTML = '<span class="text-muted"><?php echo __('No expansions found for this query.'); ?></span>';
        }
      })
      .catch(function(error) {
        expansionLoading.style.display = 'none';
        expansionContent.innerHTML = '<span class="text-danger"><?php echo __('Error loading expansions'); ?></span>';
      });
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Debounced input handler
  queryInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(updateExpansionPreview, 500);
  });

  // Toggle change handler
  semanticToggle.addEventListener('change', updateExpansionPreview);

  // Form submission handler - expand query before submitting
  searchForm.addEventListener('submit', function(e) {
    var query = queryInput.value.trim();
    var semanticEnabled = semanticToggle.checked;

    // DEBUG: Show what we're working with
    var debugInfo = 'SEMANTIC SEARCH DEBUG:\n';
    debugInfo += 'Query: "' + query + '"\n';
    debugInfo += 'Semantic enabled: ' + semanticEnabled + '\n';
    debugInfo += 'Cached expansions: ' + JSON.stringify(cachedExpansions, null, 2) + '\n';

    if (semanticEnabled && query) {
      // If not cached yet, fetch synchronously (blocking)
      if (!cachedExpansions[query]) {
        e.preventDefault();
        debugInfo += 'Fetching expansions (not cached)...\n';

        fetch('<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'testExpand']); ?>?query=' + encodeURIComponent(query))
          .then(function(response) { return response.json(); })
          .then(function(data) {
            debugInfo += 'Response: ' + JSON.stringify(data, null, 2) + '\n';

            if (data.success && Object.keys(data.expansions).length > 0) {
              var expandedTerms = [];
              for (var term in data.expansions) {
                expandedTerms = expandedTerms.concat(data.expansions[term]);
              }

              if (expandedTerms.length > 0) {
                var originalQuery = query;
                var expandedQuery = query + ' ' + expandedTerms.join(' ');
                queryInput.value = expandedQuery;

                debugInfo += 'Original query: "' + originalQuery + '"\n';
                debugInfo += 'Expanded query: "' + expandedQuery + '"\n';
              }
            } else {
              debugInfo += 'No expansions found.\n';
            }

            // Show debug popup
            alert(debugInfo);

            // Disable semantic param and submit
            var semanticInput = searchForm.querySelector('input[name="semantic"]');
            if (semanticInput) {
              semanticInput.disabled = true;
            }
            searchForm.submit();
          })
          .catch(function(error) {
            debugInfo += 'Error: ' + error.message + '\n';
            alert(debugInfo);
            searchForm.submit();
          });
        return;
      }

      // Use cached expansions
      debugInfo += 'Using cached expansions\n';
      var expandedTerms = [];
      for (var term in cachedExpansions[query]) {
        expandedTerms = expandedTerms.concat(cachedExpansions[query][term]);
      }

      if (expandedTerms.length > 0) {
        var originalQuery = query;
        var expandedQuery = query + ' ' + expandedTerms.join(' ');
        queryInput.value = expandedQuery;

        debugInfo += 'Original query: "' + originalQuery + '"\n';
        debugInfo += 'Expanded query: "' + expandedQuery + '"\n';

        var semanticInput = searchForm.querySelector('input[name="semantic"]');
        if (semanticInput) {
          semanticInput.disabled = true;
        }
      }
    }

    // Show debug popup
    alert(debugInfo);
  });

  // Initial preview on modal open
  var modal = document.getElementById('semanticSearchModal');
  modal.addEventListener('shown.bs.modal', function() {
    queryInput.focus();
    if (queryInput.value) {
      updateExpansionPreview();
    }
  });
});
</script>
