<?php use_helper('Url', 'Tag'); ?>

<?php slot('title'); ?>
  Featured Collections
<?php end_slot(); ?>

<div class="heritage-collections py-4">
  <div class="container">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-5 fw-bold mb-3">
          <i class="fas fa-layer-group me-2"></i>
          Featured Collections
        </h1>
        <p class="lead text-muted">Curated collections highlighting our most significant holdings</p>
      </div>
    </div>

    <?php if (!empty($collections)): ?>
      <div class="row g-4">
        <?php foreach ($collections as $collection): ?>
          <div class="col-md-6 <?php echo $collection['is_featured'] ? 'col-lg-6' : 'col-lg-4'; ?>">
            <div class="card h-100 collection-card <?php echo $collection['is_featured'] ? 'featured' : ''; ?>"
                 <?php if ($collection['background_color']): ?>style="border-left: 4px solid <?php echo $collection['background_color']; ?>;"<?php endif; ?>>
              <?php if ($collection['cover_image']): ?>
                <div class="card-img-top position-relative" style="height: <?php echo $collection['is_featured'] ? '250px' : '180px'; ?>; background: url('<?php echo $collection['cover_image']; ?>') center/cover;">
                  <?php if ($collection['is_featured']): ?>
                    <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">
                      <i class="fas fa-star-fill me-1"></i>Featured
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <h3 class="card-title h5"><?php echo htmlspecialchars($collection['title']); ?></h3>
                <?php if ($collection['subtitle']): ?>
                  <p class="card-subtitle text-muted mb-2"><?php echo htmlspecialchars($collection['subtitle']); ?></p>
                <?php endif; ?>
                <?php if ($collection['description']): ?>
                  <p class="card-text"><?php echo htmlspecialchars(substr($collection['description'], 0, 200)); ?>...</p>
                <?php endif; ?>
                <?php if ($collection['curator_note']): ?>
                  <div class="border-start border-primary border-3 ps-3 mt-3 bg-light p-2 rounded">
                    <small class="text-muted"><i class="fas fa-quote me-1"></i><?php echo htmlspecialchars(substr($collection['curator_note'], 0, 100)); ?>...</small>
                  </div>
                <?php endif; ?>
              </div>
              <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                <div>
                  <?php if ($collection['item_count'] > 0): ?>
                    <span class="badge bg-secondary me-2"><?php echo number_format($collection['item_count']); ?> items</span>
                  <?php endif; ?>
                  <?php if ($collection['image_count'] > 0): ?>
                    <span class="badge bg-info"><?php echo number_format($collection['image_count']); ?> images</span>
                  <?php endif; ?>
                </div>
                <?php if ($collection['link_type'] === 'search' && $collection['link_reference']): ?>
                  <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $collection['link_reference']]); ?>" class="btn btn-outline-primary btn-sm">
                    Explore <i class="fas fa-arrow-right"></i>
                  </a>
                <?php elseif ($collection['link_type'] === 'collection' && $collection['link_reference']): ?>
                  <a href="<?php echo $collection['link_reference']; ?>" class="btn btn-outline-primary btn-sm">
                    View Collection <i class="fas fa-arrow-right"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No featured collections available yet. Check back soon!
      </div>
    <?php endif; ?>
  </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.collection-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.collection-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
.collection-card.featured {
  border-width: 2px;
}
</style>
