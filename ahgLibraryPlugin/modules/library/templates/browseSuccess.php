<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Library'); ?></h1>
<?php end_slot(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <?php if (!empty($totalWorks)): ?>
      <span class="text-muted"><?php echo __('%1% work(s) / %2% items', ['%1%' => $totalWorks, '%2%' => $total]); ?></span>
    <?php elseif (!empty($total)): ?>
      <span class="text-muted"><?php echo __('%1% items', ['%1%' => $total]); ?></span>
    <?php endif; ?>
  </div>
  <div>
    <?php if (!empty($clusters) || !empty($items)): ?>
      <?php /* FRBR Clustering Toggle */ ?>
      <form method="get" action="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>" class="d-inline">
        <input type="hidden" name="frbr_cluster" value="<?php echo $frbrOn ? '0' : '1'; ?>">
        <button type="submit" class="btn btn-sm <?php echo $frbrOn ? 'btn-primary' : 'btn-outline-secondary'; ?>">
          <i class="fas fa-layer-group me-1"></i>
          <?php if ($frbrOn): ?>
            <?php echo __('Group by work (ON)'); ?>
          <?php else: ?>
            <?php echo __('Show flat list'); ?>
          <?php endif; ?>
        </button>
      </form>
    <?php endif; ?>
    <a href="<?php echo url_for(['module' => 'library', 'action' => 'edit']); ?>" class="btn btn-success ms-2">
      <i class="fas fa-plus me-2"></i><?php echo __('Add new item'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-info ms-2">
      <i class="fas fa-chart-bar me-2"></i><?php echo __('Library Reports'); ?>
    </a>
  </div>
</div>

<?php
  $clusters  = $sf_data->getRaw('clusters');
  $items     = $sf_data->getRaw('items');
  $frbrOn    = $sf_data->getRaw('frbrOn');
?>

<?php if (empty($clusters) && empty($items)): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No library items found. Click "Add new item" to create one.'); ?>
  </div>

<?php elseif ($frbrOn && !empty($clusters)): ?>
  <?php /* ============================================================
       FRBR CLUSTERED VIEW — one card per work, manifestation expander
       ============================================================ */ ?>

  <div class="row">
    <?php foreach ($clusters as $cluster): ?>
      <?php
        $primary = $cluster['primary'];
        $manifestations = $cluster['manifestations'];
        $count = $cluster['count'];
        $workKey = $cluster['work_key'];

        // Normalise display data
        $primaryArray = is_object($primary) ? (array) $primary : $primary;
        $primaryTitle = $primaryArray['title'] ?? $primary['title'] ?? __('Untitled');
        $primarySlug  = !empty($primaryArray['slug']) ? $primaryArray['slug'] : '';
        $isbn         = $primaryArray['isbn'] ?? '';
        $cleanIsbn    = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
        $creators     = $primaryArray['creators'] ?? [];
        $creatorNames = implode(', ', array_slice($creators, 0, 2));
        $pubDate      = $primaryArray['publication_date'] ?? '';
        $pub          = $primaryArray['publisher'] ?? '';
        $matType      = $primaryArray['material_type'] ?? '';
        $callNum      = $primaryArray['call_number'] ?? '';
      ?>
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm frbr-work-card"
             data-work-key="<?php echo esc_entities($workKey ?: ''); ?>">

          <?php /* Cover */ ?>
          <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
               style="height: 100px; overflow: hidden;">
            <?php if (!empty($cleanIsbn)): ?>
              <img src="/plugins/ahgLibraryPlugin/web/cover-proxy.php?isbn=<?php echo $cleanIsbn; ?>&size=M"
                   alt="Cover" class="img-fluid" style="max-height: 100px; width: auto;"
                   onerror="this.parentElement.innerHTML='<i class=\'fas fa-book fa-4x text-muted\'></i>'">
            <?php else: ?>
              <i class="fas fa-book fa-4x text-muted"></i>
            <?php endif; ?>
          </div>

          <div class="card-body">
            <h5 class="card-title" style="font-size:1rem;">
              <?php if (!empty($primarySlug)): ?>
                <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $primarySlug]); ?>"
                   class="text-decoration-none">
                  <?php echo esc_entities(mb_strimwidth($primaryTitle, 0, 80, '...')); ?>
                </a>
              <?php else: ?>
                <?php echo esc_entities(mb_strimwidth($primaryTitle, 0, 80, '...')); ?>
              <?php endif; ?>
            </h5>

            <?php if (!empty($creatorNames)): ?>
              <p class="card-text text-muted small mb-1">
                <i class="fas fa-user me-1"></i>
                <?php echo esc_entities($creatorNames); ?>
              </p>
            <?php endif; ?>

            <?php if (!empty($pub) || !empty($pubDate)): ?>
              <p class="card-text small mb-1 text-muted">
                <?php if (!empty($pub)): ?><span class="me-2"><?php echo esc_entities($pub); ?></span><?php endif; ?>
                <?php if (!empty($pubDate)): ?><span><?php echo esc_entities($pubDate); ?></span><?php endif; ?>
              </p>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-1 mb-1">
              <?php if (!empty($matType)): ?>
                <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($matType)); ?></span>
              <?php endif; ?>
              <?php if (!empty($callNum)): ?>
                <span class="badge bg-info text-dark"><?php echo esc_entities($callNum); ?></span>
              <?php endif; ?>
              <?php if ($count > 1): ?>
                <span class="badge bg-primary">
                  <i class="fas fa-copy me-1"></i><?php echo __('%1% editions', ['%1%' => $count]); ?>
                </span>
              <?php endif; ?>
            </div>

            <?php /* ============================================================
                 Manifestation Expander (hidden — shown on demand)
                 ============================================================ */ ?>
            <?php if ($count > 1): ?>
              <div class="manifestations-panel mt-2 border-top pt-2">
                <details class="frbr-manifestations">
                  <summary class="small text-primary fw-bold" style="cursor:pointer; list-style:none;">
                    <i class="fas fa-chevron-down me-1"></i>
                    <?php echo __('Show editions (%1%)', ['%1%' => $count]); ?>
                  </summary>
                  <ul class="list-unstyled small mb-0 mt-1">
                    <?php foreach ($manifestations as $idx => $m): ?>
                      <?php if ($idx === 0) continue; // skip primary (shown in header) ?>
                      <?php
                        $mArr  = is_object($m) ? (array) $m : $m;
                        $mTitle  = $mArr['title'] ?? '';
                        $mSlug   = $mArr['slug'] ?? '';
                        $mMat    = $mArr['material_type'] ?? '';
                        $mDate   = $mArr['publication_date'] ?? '';
                        $mCall   = $mArr['call_number'] ?? '';
                        $mIsbn   = $mArr['isbn'] ?? '';
                        $mCrea   = $mArr['creators'] ?? [];
                        $mCreaStr = implode(', ', array_slice($mCrea, 0, 1));
                      ?>
                      <li class="py-1 border-top">
                        <?php if (!empty($mSlug)): ?>
                          <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $mSlug]); ?>"
                             class="text-decoration-none">
                            <?php echo esc_entities(mb_strimwidth($mTitle ?: $primaryTitle, 0, 70, '...')); ?>
                          </a>
                        <?php else: ?>
                          <span><?php echo esc_entities(mb_strimwidth($mTitle ?: $primaryTitle, 0, 70, '...')); ?></span>
                        <?php endif; ?>
                        <div class="text-muted">
                          <?php if (!empty($mCreaStr)): ?>
                            <span class="me-2"><?php echo esc_entities($mCreaStr); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($mDate)): ?>
                            <span class="me-2"><?php echo esc_entities($mDate); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($mMat)): ?>
                            <span class="badge bg-secondary text-white ms-1"><?php echo esc_entities($mMat); ?></span>
                          <?php endif; ?>
                          <?php if (!empty($mCall)): ?>
                            <span class="text-muted ms-1"><?php echo esc_entities($mCall); ?></span>
                          <?php endif; ?>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </details>
              </div>
            <?php endif; ?>

          </div>

          <div class="card-footer bg-transparent">
            <div class="btn-group w-100">
              <?php if (!empty($primarySlug)): ?>
                <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $primarySlug]); ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-eye me-1"></i><?php echo __('View'); ?>
                </a>
                <a href="<?php echo url_for(['module' => 'library', 'action' => 'edit', 'slug' => $primarySlug]); ?>"
                   class="btn btn-sm btn-outline-secondary">
                  <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif (!empty($items)): ?>
  <?php /* ============================================================
       FLAT VIEW — paginated literal cards (original browse behaviour)
       ============================================================ */ ?>

  <div class="row">
    <?php foreach ($items as $item): ?>
      <?php
        $slug     = $item['slug'] ?? '';
        $title    = $item['title'] ?? __('Untitled');
        $isbn     = $item['isbn'] ?? '';
        $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
        $creators = implode(', ', array_slice((array) ($item['creators'] ?? []), 0, 2));
        $pub      = $item['publisher'] ?? '';
        $pubDate  = $item['publication_date'] ?? '';
        $matType  = $item['material_type'] ?? '';
        $callNum  = $item['call_number'] ?? '';
      ?>
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
               style="height: 100px; overflow: hidden;">
            <?php if (!empty($cleanIsbn)): ?>
              <img src="/plugins/ahgLibraryPlugin/web/cover-proxy.php?isbn=<?php echo $cleanIsbn; ?>&size=M"
                   alt="Cover" class="img-fluid" style="max-height: 100px; width: auto;"
                   onerror="this.parentElement.innerHTML='<i class=\'fas fa-book fa-4x text-muted\'></i>'">
            <?php else: ?>
              <i class="fas fa-book fa-4x text-muted"></i>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <h5 class="card-title">
              <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $slug]); ?>"
                 class="text-decoration-none">
                <?php echo esc_entities($title); ?>
              </a>
            </h5>
            <?php if (!empty($creators)): ?>
              <p class="card-text text-muted small mb-2">
                <i class="fas fa-user me-1"></i><?php echo esc_entities($creators); ?>
              </p>
            <?php endif; ?>
            <?php if (!empty($pub) || !empty($pubDate)): ?>
              <p class="card-text small mb-2">
                <i class="fas fa-building me-1 text-muted"></i>
                <?php echo esc_entities(implode(', ', array_filter([$pub, $pubDate]))); ?>
              </p>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-1">
              <?php if (!empty($matType)): ?>
                <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($matType)); ?></span>
              <?php endif; ?>
              <?php if (!empty($callNum)): ?>
                <span class="badge bg-info text-dark"><?php echo esc_entities($callNum); ?></span>
              <?php endif; ?>
              <?php if (!empty($item['isbn'])): ?>
                <span class="badge bg-light text-dark" title="ISBN">
                  <code><?php echo esc_entities($item['isbn']); ?></code>
                </span>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-footer bg-transparent">
            <div class="btn-group w-100">
              <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $slug]); ?>"
                 class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye me-1"></i><?php echo __('View'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'library', 'action' => 'edit', 'slug' => $slug]); ?>"
                 class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php /* Pagination */ ?>
  <?php if (($totalPages ?? 1) > 1): ?>
    <nav aria-label="Library catalog pagination">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => $page - 1]); ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>

        <?php
          $startPage = max(1, $page - 2);
          $endPage   = min($totalPages, $page + 2);
        ?>
        <?php if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => 1]); ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
          <li class="page-item <?php echo (int) $p === (int) $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => $p]); ?>">
              <?php echo $p; ?>
            </a>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
          <?php if ($endPage < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => $totalPages]); ?>">
              <?php echo $totalPages; ?>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => $page + 1]); ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>

<?php endif; ?>
