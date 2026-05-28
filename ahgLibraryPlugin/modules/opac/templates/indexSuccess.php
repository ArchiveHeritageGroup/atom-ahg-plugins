<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-book-open me-2"></i><?php echo __('Library Catalog'); ?></h1>
<?php end_slot(); ?>

<!-- Search Bar -->
<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label fw-bold"><?php echo __('Search the catalog'); ?></label>
          <input type="text" name="q" class="form-control form-control-lg"
                 placeholder="<?php echo __('Search by keyword, title, author, ISBN...'); ?>"
                 value="<?php echo esc_entities($q); ?>" autofocus>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Search in'); ?></label>
          <select name="search_type" class="form-select form-select-lg">
            <option value="keyword" <?php echo $searchType === 'keyword' ? 'selected' : ''; ?>><?php echo __('Keyword'); ?></option>
            <option value="title" <?php echo $searchType === 'title' ? 'selected' : ''; ?>><?php echo __('Title'); ?></option>
            <option value="author" <?php echo $searchType === 'author' ? 'selected' : ''; ?>><?php echo __('Author'); ?></option>
            <option value="subject" <?php echo $searchType === 'subject' ? 'selected' : ''; ?>><?php echo __('Subject'); ?></option>
            <option value="isbn" <?php echo $searchType === 'isbn' ? 'selected' : ''; ?>><?php echo __('ISBN'); ?></option>
            <option value="call_number" <?php echo $searchType === 'call_number' ? 'selected' : ''; ?>><?php echo __('Call Number'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
          </button>
        </div>
      </div>
    </form>

    <!-- FRBR Clustering Toggle -->
    <?php if (!empty($results) || !empty($clusters)): ?>
      <div class="mt-2 d-flex align-items-center gap-2">
        <label class="form-check-label small text-muted" for="frbrToggle">
          <i class="fas fa-layer-group me-1"></i><?php echo __('Group editions by work (FRBR)'); ?>
        </label>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" role="switch"
                 id="frbrToggle" name="frbr_cluster" value="1"
                 <?php echo $frbrCluster ? 'checked' : ''; ?>
                 onchange="this.form.submit()">
        </div>
        <?php if ($frbrCluster && $totalWorks > 0): ?>
          <span class="badge bg-success ms-2">
            <?php echo __('%1% work(s)', ['%1%' => $totalWorks]); ?>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
  $results     = $sf_data->getRaw('results');
  $clusters    = $sf_data->getRaw('clusters');
  $facets      = $sf_data->getRaw('facets');
  $newArrivals = $sf_data->getRaw('newArrivals');
  $popular     = $sf_data->getRaw('popular');
  $frbrCluster = $sf_data->getRaw('frbrCluster');
  $totalWorks  = $sf_data->getRaw('totalWorks') ?? 0;
  $hasQuery    = !empty($sf_data->getRaw('q')) || !empty($sf_data->getRaw('materialType'));
?>

<?php if ($hasQuery): ?>
  <!-- Search Results -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">
      <?php if ($frbrCluster && $totalWorks > 0): ?>
        <?php echo __('%1% work(s) found (%2% manifestations)', ['%1%' => $totalWorks, '%2%' => $total]); ?>
      <?php else: ?>
        <?php echo __('%1% result(s) found', ['%1%' => $total]); ?>
      <?php endif; ?>
    </span>
    <form method="get" action="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>" class="d-inline">
      <input type="hidden" name="q" value="<?php echo esc_entities($q); ?>">
      <input type="hidden" name="search_type" value="<?php echo esc_entities($searchType); ?>">
      <input type="hidden" name="material_type" value="<?php echo esc_entities($materialType); ?>">
      <input type="hidden" name="frbr_cluster" value="<?php echo $frbrCluster ? '1' : '0'; ?>">
      <select name="sort" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>><?php echo __('Sort: Relevance'); ?></option>
        <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>><?php echo __('Sort: Title A-Z'); ?></option>
        <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>><?php echo __('Sort: Newest'); ?></option>
        <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>><?php echo __('Sort: Oldest'); ?></option>
        <option value="call_number" <?php echo $sort === 'call_number' ? 'selected' : ''; ?>><?php echo __('Sort: Call Number'); ?></option>
      </select>
    </form>
  </div>

  <div class="row">
    <!-- Facets Sidebar -->
    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-header fw-bold"><?php echo __('Refine Results'); ?></div>
        <div class="card-body">
          <?php if (!empty($facets['material_types'])): ?>
            <h6 class="fw-bold"><?php echo __('Material Type'); ?></h6>
            <ul class="list-unstyled mb-3">
              <?php foreach ($facets['material_types'] as $mt): ?>
                <li class="mb-1">
                  <a href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($mt->material_type ?? ''); ?>&frbr_cluster=<?php echo $frbrCluster ? '1' : '0'; ?>&sort=<?php echo urlencode($sort); ?>"
                     class="text-decoration-none <?php echo $materialType === ($mt->material_type ?? '') ? 'fw-bold text-primary' : ''; ?>">
                    <?php echo esc_entities($mt->material_type ?: __('Unspecified')); ?>
                    <span class="badge bg-secondary ms-1"><?php echo (int) $mt->cnt; ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if (!empty($facets['publication_years'])): ?>
            <h6 class="fw-bold"><?php echo __('Publication Year'); ?></h6>
            <ul class="list-unstyled mb-0">
              <?php foreach (array_slice($facets['publication_years'], 0, 10) as $yr): ?>
                <li class="mb-1">
                  <a href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&publication_year=<?php echo urlencode($yr->year ?? ''); ?>&frbr_cluster=<?php echo $frbrCluster ? '1' : '0'; ?>&sort=<?php echo urlencode($sort); ?>"
                     class="text-decoration-none">
                    <?php echo esc_entities($yr->year ?? __('Unknown')); ?>
                    <span class="badge bg-secondary ms-1"><?php echo (int) $yr->cnt; ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Results List -->
    <div class="col-md-9">

      <?php if (empty($results) && empty($clusters)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i><?php echo __('No results found. Try a different search term or broaden your criteria.'); ?>
        </div>

      <?php elseif ($frbrCluster && !empty($clusters)): ?>
        <!-- FRBR Work-Set Results -->
        <?php foreach ($clusters as $cluster): ?>
          <?php
            $primary = $cluster['primary'];
            $manifestations = $cluster['manifestations'];
            $workKey = $cluster['work_key'];
            $count = $cluster['count'];
          ?>
          <div class="card mb-3 frbr-work-card" data-work-key="<?php echo esc_entities($workKey); ?>">
            <?php /* Primary item header */ ?>
            <div class="card-header d-flex justify-content-between align-items-center bg-light">
              <div>
                <h5 class="mb-0">
                  <a href="<?php echo url_for(['module' => 'opac', 'action' => 'view', 'id' => $primary->id]); ?>"
                     class="text-decoration-none text-dark">
                    <?php echo esc_entities($primary->title ?? __('Untitled')); ?>
                  </a>
                </h5>
                <?php if (!empty($primary->primary_creator)): ?>
                  <small class="text-muted">
                    <i class="fas fa-user me-1"></i><?php echo esc_entities($primary->primary_creator); ?>
                  </small>
                <?php endif; ?>
              </div>
              <div class="d-flex align-items-center gap-2">
                <?php if ($count > 1): ?>
                  <span class="badge bg-primary">
                    <i class="fas fa-copy me-1"></i><?php echo __('%1% editions', ['%1%' => $count]); ?>
                  </span>
                <?php endif; ?>
                <?php
                  $avail = (int) ($primary->available_copies ?? 0);
                  $totalCopies = (int) ($primary->total_copies ?? 0);
                ?>
                <span class="<?php echo $avail > 0 ? 'text-success' : 'text-danger'; ?> fw-bold small">
                  <?php echo __('%1%/%2% avail.', ['%1%' => $avail, '%2%' => $totalCopies]); ?>
                </span>
              </div>
            </div>

            <?php /* Manifestation list */ ?>
            <div class="card-body p-0">
              <?php foreach ($manifestations as $idx => $item): ?>
                <?php
                  $itemAvail = (int) ($item->available_copies ?? 0);
                  $itemTotal = (int) ($item->total_copies ?? 0);
                  $hiddenClass = $idx > 0 ? 'frbr-manifestation d-none' : '';
                ?>
                <div class="manifestation-row px-3 py-2 <?php echo $hiddenClass; ?> <?php echo $idx > 0 ? 'border-top' : ''; ?>"
                     data-manifestation-index="<?php echo $idx; ?>">
                  <?php if ($idx === 0): ?>
                    <?php /* Primary row — already has title in header */ ?>
                    <div class="row align-items-center">
                      <div class="col">
                        <p class="mb-0 small text-muted">
                          <?php if (!empty($item->publisher)): ?>
                            <span class="me-3"><i class="fas fa-building me-1"></i><?php echo esc_entities($item->publisher); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->publication_date)): ?>
                            <span class="me-3"><i class="fas fa-calendar me-1"></i><?php echo esc_entities($item->publication_date); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->material_type)): ?>
                            <span class="badge bg-info text-dark"><?php echo esc_entities($item->material_type); ?></span>
                          <?php endif; ?>
                        </p>
                        <p class="mb-0 small">
                          <?php if (!empty($item->call_number)): ?>
                            <span class="me-3"><strong><?php echo __('Call #:'); ?></strong> <?php echo esc_entities($item->call_number); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->isbn)): ?>
                            <span><strong><?php echo __('ISBN:'); ?></strong> <?php echo esc_entities($item->isbn); ?></span>
                          <?php endif; ?>
                        </p>
                      </div>
                    </div>
                  <?php else: ?>
                    <?php /* Secondary manifestation row — title shown inline */ ?>
                    <div class="row align-items-center">
                      <div class="col">
                        <a href="<?php echo url_for(['module' => 'opac', 'action' => 'view', 'id' => $item->id]); ?>"
                           class="text-decoration-none">
                          <?php echo esc_entities(mb_strimwidth($item->title ?? $primary->title ?? __('Untitled'), 0, 100, '...')); ?>
                        </a>
                        <p class="mb-0 small text-muted">
                          <?php if (!empty($item->edition)): ?>
                            <span class="me-2"><?php echo esc_entities($item->edition); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->publisher)): ?>
                            <span class="me-2"><?php echo esc_entities($item->publisher); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->publication_date)): ?>
                            <span class="me-2"><?php echo esc_entities($item->publication_date); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->material_type)): ?>
                            <span class="badge bg-secondary text-white ms-1"><?php echo esc_entities($item->material_type); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item->call_number)): ?>
                            <span class="ms-2 text-muted"><?php echo esc_entities($item->call_number); ?></span>
                          <?php endif; ?>
                        </p>
                      </div>
                      <div class="col-auto text-end">
                        <span class="<?php echo $itemAvail > 0 ? 'text-success' : 'text-danger'; ?> fw-bold small">
                          <?php echo __('%1%/%2%', ['%1%' => $itemAvail, '%2%' => $itemTotal]); ?>
                        </span>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>

              <?php /* Show more button */ ?>
              <?php if ($count > 1): ?>
                <div class="px-3 pb-2">
                  <button class="btn btn-sm btn-outline-secondary frbr-toggle-btn"
                          data-work-key="<?php echo esc_entities($workKey); ?>"
                          data-shown="1" data-total="<?php echo $count; ?>">
                    <i class="fas fa-chevron-down me-1"></i>
                    <?php echo __('Show %1% more editions', ['%1%' => $count - 1]); ?>
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

      <?php elseif (!empty($results)): ?>
        <!-- Flat (non-FRBR) Results -->
        <?php foreach ($results as $item): ?>
          <div class="card mb-3">
            <div class="card-body">
              <div class="row">
                <div class="col">
                  <h5 class="mb-1">
                    <a href="<?php echo url_for(['module' => 'opac', 'action' => 'view', 'id' => $item->id]); ?>" class="text-decoration-none">
                      <?php echo esc_entities($item->title ?? __('Untitled')); ?>
                    </a>
                  </h5>
                  <?php if (!empty($item->primary_creator)): ?>
                    <p class="text-muted mb-1">
                      <i class="fas fa-user me-1"></i><?php echo esc_entities($item->primary_creator); ?>
                    </p>
                  <?php endif; ?>
                  <p class="mb-1">
                    <?php if (!empty($item->publisher)): ?>
                      <span class="me-3"><i class="fas fa-building me-1"></i><?php echo esc_entities($item->publisher); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item->publication_date)): ?>
                      <span class="me-3"><i class="fas fa-calendar me-1"></i><?php echo esc_entities($item->publication_date); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item->material_type)): ?>
                      <span class="badge bg-info text-dark"><?php echo esc_entities($item->material_type); ?></span>
                    <?php endif; ?>
                  </p>
                  <p class="mb-0 small text-muted">
                    <?php if (!empty($item->call_number)): ?>
                      <span class="me-3"><strong><?php echo __('Call #:'); ?></strong> <?php echo esc_entities($item->call_number); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item->isbn)): ?>
                      <span class="me-3"><strong><?php echo __('ISBN:'); ?></strong> <?php echo esc_entities($item->isbn); ?></span>
                    <?php endif; ?>
                    <?php
                      $avail = (int) ($item->available_copies ?? 0);
                      $totalCopies = (int) ($item->total_copies ?? 0);
                    ?>
                    <span class="<?php echo $avail > 0 ? 'text-success' : 'text-danger'; ?> fw-bold">
                      <?php echo __('%1% of %2% copies available', ['%1%' => $avail, '%2%' => $totalCopies]); ?>
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="<?php echo __('Catalog results pagination'); ?>">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($materialType); ?>&frbr_cluster=<?php echo $frbrCluster ? '1' : '0'; ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>">
                  &laquo; <?php echo __('Previous'); ?>
                </a>
              </li>
            <?php endif; ?>

            <?php
              $startPage = max(1, $page - 2);
              $endPage   = min($totalPages, $page + 2);
            ?>
            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
              <li class="page-item <?php echo $p === (int) $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($materialType); ?>&frbr_cluster=<?php echo $frbrCluster ? '1' : '0'; ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $p; ?>">
                  <?php echo $p; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($materialType); ?>&frbr_cluster=<?php echo $frbrCluster ? '1' : '0'; ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>">
                  <?php echo __('Next'); ?> &raquo;
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>

  <!-- FRBR Show/Hide toggle script -->
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.frbr-toggle-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var workKey = btn.dataset.workKey;
        var card = document.querySelector('.frbr-work-card[data-work-key="' + workKey + '"]');
        var allRows = card.querySelectorAll('.manifestation-row');
        var shown = parseInt(btn.dataset.shown, 10);
        var total = parseInt(btn.dataset.total, 10);

        if (btn.textContent.includes('more')) {
          // Expand: reveal all hidden manifestations
          allRows.forEach(function (row, i) {
            if (i > shown - 1) row.classList.remove('d-none');
          });
          btn.innerHTML = '<i class="fas fa-chevron-up me-1"></i><?php echo __('Hide editions'); ?>';
          btn.dataset.shown = total;
        } else {
          // Collapse: hide all except primary
          allRows.forEach(function (row, i) {
            if (i > 0) row.classList.add('d-none');
          });
          btn.innerHTML = '<i class="fas fa-chevron-down me-1"></i><?php echo __('Show %1% more editions', ['%1%' => '']); ?>'.replace('%1%', total - 1);
          btn.dataset.shown = '1';
        }
      });
    });
  });
  </script>

<?php else: ?>
  <!-- Discovery: New Arrivals & Popular -->

  <?php if (!empty($newArrivals)): ?>
    <h3 class="mb-3"><i class="fas fa-star me-2 text-warning"></i><?php echo __('New Arrivals'); ?></h3>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-5">
      <?php foreach ($newArrivals as $item): ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
              <?php if (!empty($item->cover_image_url)): ?>
                <img src="<?php echo esc_entities($item->cover_image_url); ?>" alt="" class="mb-2" style="max-height: 120px; max-width: 100%;">
              <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 120px;">
                  <i class="fas fa-book fa-3x text-muted"></i>
                </div>
              <?php endif; ?>
              <h6 class="card-title mb-1">
                <a href="<?php echo url_for(['module' => 'opac', 'action' => 'view', 'id' => $item->id]); ?>" class="text-decoration-none">
                  <?php echo esc_entities(mb_strimwidth($item->title ?? __('Untitled'), 0, 60, '...')); ?>
                </a>
              </h6>
              <?php if (!empty($item->publisher)): ?>
                <small class="text-muted"><?php echo esc_entities($item->publisher); ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($popular)): ?>
    <h3 class="mb-3"><i class="fas fa-fire me-2 text-danger"></i><?php echo __('Popular'); ?></h3>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3 mb-4">
      <?php foreach ($popular as $item): ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
              <?php if (!empty($item->cover_image_url)): ?>
                <img src="<?php echo esc_entities($item->cover_image_url); ?>" alt="" class="mb-2" style="max-height: 120px; max-width: 100%;">
              <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 120px;">
                  <i class="fas fa-book fa-3x text-muted"></i>
                </div>
              <?php endif; ?>
              <h6 class="card-title mb-1">
                <a href="<?php echo url_for(['module' => 'opac', 'action' => 'view', 'id' => $item->id]); ?>" class="text-decoration-none">
                  <?php echo esc_entities(mb_strimwidth($item->title ?? __('Untitled'), 0, 60, '...')); ?>
                </a>
              </h6>
              <?php if (!empty($item->call_number)): ?>
                <small class="text-muted"><?php echo esc_entities($item->call_number); ?></small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (empty($newArrivals) && empty($popular)): ?>
    <div class="text-center py-5">
      <i class="fas fa-search fa-4x text-muted mb-3"></i>
      <h4 class="text-muted"><?php echo __('Search the library catalog above'); ?></h4>
      <p class="text-muted"><?php echo __('Find books, journals, and other materials by keyword, title, author, subject, ISBN, or call number.'); ?></p>
    </div>
  <?php endif; ?>

<?php endif; ?>