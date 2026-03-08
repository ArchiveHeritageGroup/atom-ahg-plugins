<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-book-open me-2"></i><?php echo __('Library Catalog'); ?></h1>
<?php end_slot(); ?>

<!-- Search Bar -->
<div class="card mb-4 shadow-sm">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
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
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php
  $results    = $sf_data->getRaw('results');
  $facets     = $sf_data->getRaw('facets');
  $newArrivals = $sf_data->getRaw('newArrivals');
  $popular    = $sf_data->getRaw('popular');
  $hasQuery   = !empty($sf_data->getRaw('q')) || !empty($sf_data->getRaw('materialType'));
?>

<?php if ($hasQuery): ?>
  <!-- Search Results -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted">
      <?php echo __('%1% result(s) found', ['%1%' => $total]); ?>
    </span>
    <form method="get" action="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>" class="d-inline">
      <input type="hidden" name="q" value="<?php echo esc_entities($q); ?>">
      <input type="hidden" name="search_type" value="<?php echo esc_entities($searchType); ?>">
      <input type="hidden" name="material_type" value="<?php echo esc_entities($materialType); ?>">
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
                  <a href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($mt->material_type ?? ''); ?>&sort=<?php echo urlencode($sort); ?>"
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
                  <a href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&publication_year=<?php echo urlencode($yr->year ?? ''); ?>&sort=<?php echo urlencode($sort); ?>"
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
      <?php if (empty($results)): ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i><?php echo __('No results found. Try a different search term or broaden your criteria.'); ?>
        </div>
      <?php else: ?>
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

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <nav aria-label="<?php echo __('Catalog results pagination'); ?>">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($materialType); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>">
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
                  <a class="page-link" href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($materialType); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $p; ?>">
                    <?php echo $p; ?>
                  </a>
                </li>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo url_for(['module' => 'opac', 'action' => 'index']); ?>?q=<?php echo urlencode($q); ?>&search_type=<?php echo urlencode($searchType); ?>&material_type=<?php echo urlencode($materialType); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>">
                    <?php echo __('Next'); ?> &raquo;
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

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
