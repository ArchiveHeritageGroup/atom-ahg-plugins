<?php use_helper('Url', 'Tag'); ?>

<?php slot('title'); ?>
  <?php echo isset($currentCategory) ? $currentCategory['name'] . ' - Explore' : 'Explore Our Collections'; ?>
<?php end_slot(); ?>

<div class="heritage-explore py-4">
  <div class="container">

    <?php if (!isset($currentCategory)): ?>
      <!-- Explore Categories Grid -->
      <div class="row mb-4">
        <div class="col-12">
          <h1 class="display-5 fw-bold mb-3">Explore Our Collections</h1>
          <p class="lead text-muted">Discover archives through different perspectives</p>
        </div>
      </div>

      <div class="row g-4">
        <?php foreach ($categories as $cat): ?>
          <div class="col-md-6 col-lg-4">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $cat['code']]); ?>"
               class="card h-100 text-decoration-none explore-card"
               style="background-color: <?php echo $cat['background_color']; ?>; color: <?php echo $cat['text_color']; ?>;">
              <?php if ($cat['cover_image']): ?>
                <div class="card-img-top" style="height: 150px; background: url('<?php echo $cat['cover_image']; ?>') center/cover;"></div>
              <?php endif; ?>
              <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                  <i class="<?php echo $cat['icon']; ?> fs-3 me-2"></i>
                  <h3 class="card-title h4 mb-0"><?php echo htmlspecialchars($cat['name']); ?></h3>
                </div>
                <?php if ($cat['tagline']): ?>
                  <p class="card-text opacity-75"><?php echo htmlspecialchars($cat['tagline']); ?></p>
                <?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Category Items View -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>">Heritage</a>
          </li>
          <li class="breadcrumb-item">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore']); ?>">Explore</a>
          </li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($currentCategory['name']); ?></li>
        </ol>
      </nav>

      <div class="row mb-4">
        <div class="col-12">
          <h1 class="display-5 fw-bold mb-2">
            <i class="<?php echo $currentCategory['icon']; ?> me-2"></i>
            <?php echo htmlspecialchars($currentCategory['name']); ?>
          </h1>
          <?php if ($currentCategory['description']): ?>
            <p class="lead text-muted"><?php echo htmlspecialchars($currentCategory['description']); ?></p>
          <?php endif; ?>
          <?php if (isset($totalItems)): ?>
            <p class="text-muted"><?php echo number_format($totalItems); ?> items found</p>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($items)): ?>
        <?php if ($currentCategory['display_style'] === 'grid'): ?>
          <!-- Grid display -->
          <div class="row g-3">
            <?php foreach ($items as $item): ?>
              <div class="col-6 col-md-4 col-lg-3">
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => '', $currentCategory['source_reference'] => [$item['name']]]); ?>"
                   class="card h-100 text-decoration-none explore-item-card">
                  <div class="card-body text-center">
                    <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                    <?php if (isset($item['count'])): ?>
                      <span class="badge bg-secondary"><?php echo number_format($item['count']); ?> items</span>
                    <?php endif; ?>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>

        <?php elseif ($currentCategory['display_style'] === 'list'): ?>
          <!-- List display -->
          <div class="list-group">
            <?php foreach ($items as $item): ?>
              <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => '', $currentCategory['source_reference'] => [$item['name']]]); ?>"
                 class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <?php echo htmlspecialchars($item['name']); ?>
                <?php if (isset($item['count'])): ?>
                  <span class="badge bg-primary rounded-pill"><?php echo number_format($item['count']); ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
          <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $currentCategory['code'], 'page' => $page - 1]); ?>">Previous</a>
                </li>
              <?php endif; ?>

              <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $currentCategory['code'], 'page' => $i]); ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => $currentCategory['code'], 'page' => $page + 1]); ?>">Next</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>

      <?php else: ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          No items found in this category.
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<style>
.explore-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.explore-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
.explore-item-card {
  transition: transform 0.2s ease;
}
.explore-item-card:hover {
  transform: translateY(-3px);
}
</style>
