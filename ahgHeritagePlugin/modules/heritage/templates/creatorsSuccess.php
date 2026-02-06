<?php use_helper('Url', 'Tag'); ?>

<?php slot('title'); ?>
  Creators & People
<?php end_slot(); ?>

<div class="heritage-creators py-4">
  <div class="container">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-5 fw-bold mb-3">
          <i class="fas fa-users me-2"></i>
          Creators & People
        </h1>
        <p class="lead text-muted">Discover collections by the people who created them</p>
      </div>
    </div>

    <!-- Search Box -->
    <div class="row mb-4">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="position-relative">
          <form method="get" action="<?php echo url_for(['module' => 'heritage', 'action' => 'creators']); ?>" id="creatorSearchForm">
            <div class="input-group input-group-lg">
              <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
              </span>
              <input type="text"
                     class="form-control border-start-0"
                     id="creatorSearchInput"
                     name="q"
                     value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>"
                     placeholder="Search creators by name..."
                     autocomplete="off">
              <?php if (!empty($searchQuery)): ?>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'creators']); ?>"
                   class="btn btn-outline-secondary" title="Clear search">
                  <i class="fas fa-times"></i>
                </a>
              <?php endif; ?>
              <button type="submit" class="btn btn-primary">Search</button>
            </div>
          </form>
          <!-- Autocomplete dropdown -->
          <div id="creatorAutocomplete" class="position-absolute w-100 bg-white border rounded-bottom shadow-lg"
               style="display: none; z-index: 1050; max-height: 400px; overflow-y: auto;"></div>
        </div>
      </div>
      <div class="col-12 col-md-4 col-lg-6 d-flex align-items-center mt-3 mt-md-0">
        <?php if (isset($totalItems)): ?>
          <span class="text-muted">
            <?php if (!empty($searchQuery)): ?>
              <i class="fas fa-filter me-1"></i>
              <?php echo number_format($totalItems); ?> results for "<?php echo htmlspecialchars($searchQuery); ?>"
            <?php else: ?>
              <?php echo number_format($totalItems); ?> creators
            <?php endif; ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($creators)): ?>
      <div class="row g-4">
        <?php foreach ($creators as $creator): ?>
          <div class="col-md-6 col-lg-4">
            <a href="<?php echo url_for(['module' => 'actor', 'slug' => $creator['slug']]); ?>"
               class="card h-100 text-decoration-none creator-card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="creator-avatar me-3">
                    <i class="fas fa-user-circle text-primary" style="font-size: 2.5rem;"></i>
                  </div>
                  <div>
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($creator['name']); ?></h5>
                    <?php if (isset($creator['count'])): ?>
                      <span class="badge bg-secondary"><?php echo number_format($creator['count']); ?> items</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="card-footer bg-transparent border-top-0">
                <small class="text-primary">View creator profile <i class="fas fa-arrow-right"></i></small>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if (isset($totalPages) && $totalPages > 1): ?>
        <?php
        // Build pagination URL params
        $paginationParams = ['module' => 'heritage', 'action' => 'creators'];
        if (!empty($searchQuery)) {
            $paginationParams['q'] = $searchQuery;
        }
        ?>
        <nav aria-label="Page navigation" class="mt-4">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(array_merge($paginationParams, ['page' => $page - 1])); ?>">Previous</a>
              </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(array_merge($paginationParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(array_merge($paginationParams, ['page' => $page + 1])); ?>">Next</a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>

    <?php else: ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No creators found.
      </div>
    <?php endif; ?>
  </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.creator-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.creator-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
.autocomplete-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.autocomplete-item:last-child {
  border-bottom: none;
}
.autocomplete-item:hover, .autocomplete-item.active {
  background-color: #f8f9fa;
}
.autocomplete-item .creator-name {
  font-weight: 500;
}
.autocomplete-item .item-count {
  font-size: 0.875rem;
  color: #6c757d;
}
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var searchInput = document.getElementById('creatorSearchInput');
    var autocompleteDiv = document.getElementById('creatorAutocomplete');
    var searchForm = document.getElementById('creatorSearchForm');
    var searchTimeout;
    var activeIndex = -1;
    var results = [];

    if (!searchInput || !autocompleteDiv) return;

    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(searchTimeout);
        activeIndex = -1;

        if (query.length < 2) {
            autocompleteDiv.style.display = 'none';
            autocompleteDiv.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(function() {
            fetch('<?php echo url_for(['module' => 'heritage', 'action' => 'creatorsAutocomplete']); ?>?q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    results = data.results || [];
                    if (results.length > 0) {
                        var html = '';
                        results.forEach(function(item, idx) {
                            html += '<div class="autocomplete-item" data-index="' + idx + '" data-slug="' + item.slug + '">' +
                                '<div>' +
                                    '<i class="fas fa-user-circle text-primary me-2"></i>' +
                                    '<span class="creator-name">' + escapeHtml(item.name) + '</span>' +
                                '</div>' +
                                '<span class="item-count badge bg-secondary">' + item.count + ' items</span>' +
                            '</div>';
                        });
                        autocompleteDiv.innerHTML = html;
                        autocompleteDiv.style.display = 'block';

                        // Add click handlers
                        autocompleteDiv.querySelectorAll('.autocomplete-item').forEach(function(el) {
                            el.addEventListener('click', function() {
                                var slug = this.getAttribute('data-slug');
                                window.location.href = '<?php echo url_for(['module' => 'actor', 'slug' => '']); ?>' + slug;
                            });
                        });
                    } else {
                        autocompleteDiv.innerHTML = '<div class="autocomplete-item text-muted"><i class="fas fa-search me-2"></i>No creators found</div>';
                        autocompleteDiv.style.display = 'block';
                    }
                })
                .catch(function(err) {
                    console.error('Autocomplete error:', err);
                    autocompleteDiv.style.display = 'none';
                });
        }, 250);
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        var items = autocompleteDiv.querySelectorAll('.autocomplete-item[data-slug]');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActiveItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, -1);
            updateActiveItem(items);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            var slug = items[activeIndex].getAttribute('data-slug');
            if (slug) {
                window.location.href = '<?php echo url_for(['module' => 'actor', 'slug' => '']); ?>' + slug;
            }
        } else if (e.key === 'Escape') {
            autocompleteDiv.style.display = 'none';
            activeIndex = -1;
        }
    });

    function updateActiveItem(items) {
        items.forEach(function(item, idx) {
            item.classList.toggle('active', idx === activeIndex);
        });
        if (activeIndex >= 0 && items[activeIndex]) {
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteDiv.contains(e.target)) {
            autocompleteDiv.style.display = 'none';
            activeIndex = -1;
        }
    });

    // Focus shows autocomplete if there are results
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && autocompleteDiv.innerHTML) {
            autocompleteDiv.style.display = 'block';
        }
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
</script>
