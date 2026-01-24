<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Library'); ?></h1>
<?php end_slot(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <span class="text-muted"><?php echo __('%1% items', ['%1%' => $total]); ?></span>
  </div>
  <div>
    <a href="<?php echo url_for(['module' => 'library', 'action' => 'edit']); ?>" class="btn btn-success">
      <i class="fas fa-plus me-2"></i><?php echo __('Add new item'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>" class="btn btn-outline-info ms-2">
      <i class="fas fa-chart-bar me-2"></i><?php echo __("Library Reports"); ?>
    </a>
  </div>
</div>

<?php if (empty($items)): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No library items found. Click "Add new item" to create one.'); ?>
  </div>
<?php else: ?>

  <div class="row">
    <?php foreach ($sf_data->getRaw("items") as $item): ?>
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
          
          <!-- Cover Image -->
          <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 100px; overflow: hidden;">
            <?php 
              $coverPath = $item['cover_path'] ?? '';
              $coverName = $item['cover_name'] ?? '';
              $coverUrl = (!empty($coverPath) && !empty($coverName)) ? $coverPath . $coverName : '';
              $isbn = $item['isbn'] ?? '';
              $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
            ?>
            <?php if (!empty($coverUrl)): ?>
              <img src="<?php echo esc_entities($coverUrl); ?>" alt="Cover" 
                   class="img-fluid" style="max-height: 100px; width: auto;"
                   onerror="this.parentElement.innerHTML='<i class=\'fas fa-book fa-4x text-muted\'></i>'">
            <?php elseif (!empty($cleanIsbn)): ?>
              <img src="/plugins/ahgLibraryPlugin/web/cover-proxy.php?isbn=<?php echo $cleanIsbn; ?>&size=M" alt="Cover" 
                   class="img-fluid" style="max-height: 100px; width: auto;"
                   onerror="this.parentElement.innerHTML='<i class=\'fas fa-book fa-4x text-muted\'></i>'">
            <?php else: ?>
              <i class="fas fa-book fa-4x text-muted"></i>
            <?php endif; ?>
          </div>

          <div class="card-body">
            <h5 class="card-title">
              <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $item['slug']]); ?>" class="text-decoration-none">
                <?php echo esc_entities($item['title']); ?>
              </a>
            </h5>
            
            <?php if (!empty($item['creators'])): ?>
              <p class="card-text text-muted small mb-2">
                <i class="fas fa-user me-1"></i>
                <?php echo esc_entities(implode(', ', array_slice((array) $item['creators'], 0, 2))); ?>
                <?php if (count((array) $item['creators']) > 2): ?>
                  <em><?php echo __('et al.'); ?></em>
                <?php endif; ?>
              </p>
            <?php endif; ?>

            <?php if (!empty($item['publisher']) || !empty($item['publication_date'])): ?>
              <p class="card-text small mb-2">
                <i class="fas fa-building me-1 text-muted"></i>
                <?php 
                  $pubInfo = [];
                  if (!empty($item['publisher'])) $pubInfo[] = $item['publisher'];
                  if (!empty($item['publication_date'])) $pubInfo[] = $item['publication_date'];
                  echo esc_entities(implode(', ', $pubInfo));
                ?>
              </p>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-1">
              <?php if (!empty($item['material_type'])): ?>
                <span class="badge bg-secondary"><?php echo esc_entities(ucfirst($item['material_type'])); ?></span>
              <?php endif; ?>
              <?php if (!empty($item['call_number'])): ?>
                <span class="badge bg-info"><?php echo esc_entities($item['call_number']); ?></span>
              <?php endif; ?>
              <?php if (!empty($item['isbn'])): ?>
                <span class="badge bg-light text-dark" title="ISBN"><code><?php echo esc_entities($item['isbn']); ?></code></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="card-footer bg-transparent">
            <div class="btn-group w-100">
              <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $item['slug']]); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye me-1"></i><?php echo __('View'); ?>
              </a>
              <a href="<?php echo url_for(['module' => 'library', 'action' => 'edit', 'slug' => $item['slug']]); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
              </a>
            </div>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
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
          $endPage = min($totalPages, $page + 2);
        ?>
        
        <?php if ($startPage > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => 1]); ?>">1</a>
          </li>
          <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($endPage < $totalPages): ?>
          <?php if ($endPage < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php endif; ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'browse', 'page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
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
