<?php use_helper('Url', 'Tag'); ?>

<?php slot('title'); ?>
  <?php echo isset($currentPeriod) ? $currentPeriod['name'] . ' - Timeline' : 'Historical Timeline'; ?>
<?php end_slot(); ?>

<div class="heritage-timeline py-4">
  <div class="container">

    <?php if (!isset($currentPeriod)): ?>
      <!-- Timeline Overview -->
      <div class="row mb-4">
        <div class="col-12">
          <h1 class="display-5 fw-bold mb-3">
            <i class="fas fa-clock-history me-2"></i>
            Journey Through Time
          </h1>
          <p class="lead text-muted">Explore our collections by historical period</p>
        </div>
      </div>

      <!-- Timeline visualization -->
      <div class="timeline-container position-relative">
        <div class="timeline-line"></div>

        <?php foreach ($periods as $index => $period): ?>
          <div class="timeline-period <?php echo $index % 2 === 0 ? 'left' : 'right'; ?> mb-4">
            <div class="timeline-dot"></div>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline', 'period_id' => $period['id']]); ?>"
               class="card timeline-card text-decoration-none"
               <?php if ($period['background_color']): ?>style="border-left: 4px solid <?php echo $period['background_color']; ?>;"<?php endif; ?>>
              <?php if ($period['cover_image']): ?>
                <div class="card-img-top" style="height: 120px; background: url('<?php echo $period['cover_image']; ?>') center/cover;"></div>
              <?php endif; ?>
              <div class="card-body">
                <h3 class="card-title h5"><?php echo htmlspecialchars($period['name']); ?></h3>
                <p class="card-subtitle text-primary mb-2"><?php echo htmlspecialchars($period['year_label']); ?></p>
                <?php if ($period['description']): ?>
                  <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($period['description'], 0, 150)); ?>...</p>
                <?php endif; ?>
                <?php if ($period['item_count'] > 0): ?>
                  <span class="badge bg-secondary"><?php echo number_format($period['item_count']); ?> items</span>
                <?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Period Items View -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>">Heritage</a>
          </li>
          <li class="breadcrumb-item">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline']); ?>">Timeline</a>
          </li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($currentPeriod['name']); ?></li>
        </ol>
      </nav>

      <div class="row mb-4">
        <div class="col-12">
          <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($currentPeriod['name']); ?></h1>
          <p class="h4 text-primary mb-3"><?php echo htmlspecialchars($currentPeriod['year_label']); ?></p>
          <?php if ($currentPeriod['description']): ?>
            <p class="lead text-muted"><?php echo htmlspecialchars($currentPeriod['description']); ?></p>
          <?php endif; ?>
          <?php if (isset($totalItems)): ?>
            <p class="text-muted"><?php echo number_format($totalItems); ?> items from this period</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Period Navigation -->
      <div class="d-flex flex-wrap gap-2 mb-4">
        <?php foreach ($periods as $period): ?>
          <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline', 'period_id' => $period['id']]); ?>"
             class="btn btn-sm <?php echo $period['id'] == $currentPeriod['id'] ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo htmlspecialchars($period['short_name']); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($items)): ?>
        <div class="row g-3">
          <?php foreach ($items as $item): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item['slug']]); ?>"
                 class="card h-100 text-decoration-none heritage-result-card">
                <?php if (!empty($item['thumbnail'])): ?>
                  <img src="<?php echo $item['thumbnail']; ?>" class="card-img-top heritage-thumb" alt="<?php echo htmlspecialchars($item['title']); ?>" style="height: 150px; object-fit: cover;" onerror="this.src='/plugins/ahgThemeB5Plugin/images/placeholder.png'">
                <?php else: ?>
                  <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                    <i class="fas fa-file-earmark text-muted" style="font-size: 3rem;"></i>
                  </div>
                <?php endif; ?>
                <div class="card-body">
                  <h5 class="card-title h6"><?php echo htmlspecialchars(substr($item['title'], 0, 60)); ?></h5>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
          <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline', 'period_id' => $currentPeriod['id'], 'page' => $page - 1]); ?>">Previous</a>
                </li>
              <?php endif; ?>

              <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline', 'period_id' => $currentPeriod['id'], 'page' => $i]); ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline', 'period_id' => $currentPeriod['id'], 'page' => $page + 1]); ?>">Next</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>

      <?php else: ?>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          No items found for this period. Try adjusting your search or select another time period.
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.timeline-container {
  padding-left: 50%;
}
.timeline-line {
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #0d6efd, #6c757d);
}
.timeline-period {
  position: relative;
  width: 45%;
}
.timeline-period.left {
  margin-left: -95%;
  text-align: right;
}
.timeline-period.right {
  margin-left: 5%;
}
.timeline-dot {
  position: absolute;
  width: 16px;
  height: 16px;
  background: #0d6efd;
  border-radius: 50%;
  border: 3px solid #fff;
  box-shadow: 0 0 0 2px #0d6efd;
}
.timeline-period.left .timeline-dot {
  right: -58px;
  top: 20px;
}
.timeline-period.right .timeline-dot {
  left: -58px;
  top: 20px;
}
.timeline-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.timeline-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
  .timeline-container {
    padding-left: 30px;
  }
  .timeline-line {
    left: 10px;
  }
  .timeline-period,
  .timeline-period.left,
  .timeline-period.right {
    width: 100%;
    margin-left: 0;
    text-align: left;
  }
  .timeline-period.left .timeline-dot,
  .timeline-period.right .timeline-dot {
    left: -22px;
    right: auto;
  }
}
</style>
