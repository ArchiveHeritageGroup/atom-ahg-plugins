<?php use_helper('Url', 'Tag'); ?>

<?php slot('title'); ?>
  Trending Items
<?php end_slot(); ?>

<div class="heritage-trending py-4">
  <div class="container">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-5 fw-bold mb-3">
          <i class="fas fa-chart-line-arrow me-2"></i>
          Trending Now
        </h1>
        <p class="lead text-muted">Popular items being viewed this week</p>
      </div>
    </div>

    <?php if (!empty($items)): ?>
      <div class="row g-3">
        <?php foreach ($items as $index => $item): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item['slug']]); ?>"
               class="card h-100 text-decoration-none trending-card">
              <?php if ($index < 3): ?>
                <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark">
                  #<?php echo $index + 1; ?>
                </span>
              <?php endif; ?>
              <?php if (!empty($item['thumbnail'])): ?>
                <img src="<?php echo $item['thumbnail']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" style="height: 150px; object-fit: cover;" onerror="this.src='/plugins/ahgThemeB5Plugin/images/placeholder.png'">
              <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                  <i class="fas fa-file-earmark text-muted" style="font-size: 3rem;"></i>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <h5 class="card-title h6"><?php echo htmlspecialchars(substr($item['title'], 0, 60)); ?></h5>
                <?php if (isset($item['view_count'])): ?>
                  <small class="text-muted">
                    <i class="fas fa-eye me-1"></i><?php echo number_format($item['view_count']); ?> views
                  </small>
                <?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No trending data available yet. Browse some items to get started!
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
.trending-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.trending-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
</style>
