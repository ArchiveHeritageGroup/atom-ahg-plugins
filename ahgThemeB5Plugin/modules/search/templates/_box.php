<form
  id="search-box"
  class="d-flex flex-grow-1 my-2"
  role="search"
  action="<?php echo url_for('@glam_browse'); ?>">
  <h2 class="visually-hidden"><?php echo __('Search'); ?></h2>
  <input type="hidden" name="topLod" value="0">
  <input type="hidden" name="sort" value="relevance">
  <div class="input-group flex-nowrap">
    <button
      id="search-box-options"
      class="btn btn-sm atom-btn-secondary dropdown-toggle"
      type="button"
      data-bs-toggle="dropdown"
      data-bs-auto-close="outside"
      aria-expanded="false">
      <i class="fas fa-cog" aria-hidden="true"></i>
      <span class="visually-hidden"><?php echo __('Search options'); ?></span>
    </button>
    <div class="dropdown-menu mt-2" aria-labelledby="search-box-options">
      <?php if (sfConfig::get('app_multi_repository')) { ?>
        <div class="px-3 py-2">
          <div class="form-check">
            <input
              class="form-check-input"
              type="radio"
              name="repos"
              id="search-realm-global"
              checked
              value>
            <label class="form-check-label" for="search-realm-global">
              <?php echo __('Global search'); ?>
            </label>
          </div>
          <?php if (isset($repository)) { ?>
            <div class="form-check">
              <input
                class="form-check-input"
                type="radio"
                name="repos"
                id="search-realm-repo"
                value="<?php echo $repository->id; ?>">
              <label class="form-check-label" for="search-realm-repo">
                <?php echo __('Search <span>%1%</span>', ['%1%' => render_title($repository)]); ?>
              </label>
            </div>
          <?php } ?>
          <?php if (isset($altRepository)) { ?>
            <div class="form-check">
              <input
                class="form-check-input"
                type="radio"
                name="repos"
                id="search-realm-alt-repo"
                value="<?php echo $altRepository->id; ?>">
              <label class="form-check-label" for="search-realm-alt-repo">
                <?php echo __('Search <span>%1%</span>', ['%1%' => render_title($altRepository)]); ?>
              </label>
            </div>
          <?php } ?>
        </div>
        <div class="dropdown-divider"></div>
      <?php } ?>
      <a class="dropdown-item" href="<?php echo url_for('@glam_browse') . '?showAdvanced=true&topLevel=0'; ?>">
        <?php echo __('Advanced search'); ?>
      </a>
      <div class="dropdown-divider"></div>
      <div class="px-3 py-2">
        <div class="form-check form-switch">
          <input
            class="form-check-input"
            type="checkbox"
            id="semantic-search-toggle"
            name="semantic"
            value="1"
            <?php echo ($sf_request->getParameter('semantic') == '1') ? 'checked' : ''; ?>>
          <label class="form-check-label" for="semantic-search-toggle">
            <i class="fas fa-brain me-1" aria-hidden="true"></i>
            <?php echo __('Semantic search'); ?>
          </label>
        </div>
        <small class="text-muted d-block mt-1">
          <?php echo __('Expand search with synonyms'); ?>
        </small>
      </div>
    </div>
    <input
      id="search-box-input"
      class="form-control form-control-sm dropdown-toggle"
      type="search"
      name="query"
      autocomplete="off"
      value="<?php echo $sf_request->query; ?>"
      placeholder="<?php echo sfConfig::get('app_ui_label_globalSearch'); ?>"
      data-url="<?php echo url_for(['module' => 'search', 'action' => 'autocomplete']); ?>"
      data-bs-toggle="dropdown"
      aria-label="<?php echo sfConfig::get('app_ui_label_globalSearch'); ?>"
      aria-expanded="false">
    <ul id="search-box-results" class="dropdown-menu mt-2" aria-labelledby="search-box-input"></ul>
    <button class="btn btn-sm atom-btn-secondary" type="submit">
      <i class="fas fa-search" aria-hidden="true"></i>
      <span class="visually-hidden"><?php echo __('Search in browse page'); ?></span>
    </button>
  </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  var searchBox = document.getElementById('search-box');
  var queryInput = document.getElementById('search-box-input');
  var semanticToggle = document.getElementById('semantic-search-toggle');

  if (!searchBox || !queryInput || !semanticToggle) return;

  searchBox.addEventListener('submit', function(e) {
    var query = queryInput.value.trim();
    var semanticEnabled = semanticToggle.checked;

    if (!semanticEnabled || !query) return;

    // Prevent immediate submission
    e.preventDefault();

    // Fetch expansions and then submit
    fetch('<?php echo url_for(['module' => 'semanticSearchAdmin', 'action' => 'testExpand']); ?>?query=' + encodeURIComponent(query))
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success && Object.keys(data.expansions).length > 0) {
          // Build expanded query
          var expandedTerms = [];
          for (var term in data.expansions) {
            expandedTerms = expandedTerms.concat(data.expansions[term]);
          }

          if (expandedTerms.length > 0) {
            queryInput.value = query + ' ' + expandedTerms.join(' ');
          }
        }

        // Disable semantic param since we already expanded
        semanticToggle.disabled = true;

        // Submit the form
        searchBox.submit();
      })
      .catch(function(error) {
        // On error, submit with original query
        searchBox.submit();
      });
  });
})();
</script>
