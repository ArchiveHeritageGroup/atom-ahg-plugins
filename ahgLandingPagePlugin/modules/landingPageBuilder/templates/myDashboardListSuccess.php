<?php
/**
 * User Dashboard List
 */
use_helper('Date', 'Text');
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">My Dashboards</h1>
      <p class="text-muted mb-0">Manage your personal dashboards</p>
    </div>
    <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardCreate']) ?>"
       class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Create Dashboard
    </a>
  </div>

  <?php if (count($pages) === 0): ?>
    <div class="text-center py-5">
      <i class="bi bi-grid-3x3-gap display-1 text-muted"></i>
      <h3 class="mt-3 text-muted">No Dashboards Yet</h3>
      <p class="text-muted">Create your first personal dashboard to get started</p>
      <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardCreate']) ?>"
         class="btn btn-primary btn-lg mt-2">
        <i class="bi bi-plus-lg"></i> Create Dashboard
      </a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($pages as $page): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 <?php echo !$page->is_active ? 'border-warning' : '' ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0">
                  <?php echo esc_entities($page->name) ?>
                </h5>
                <div>
                  <?php if ($page->is_default): ?>
                    <span class="badge bg-primary">Default</span>
                  <?php endif ?>
                </div>
              </div>

              <p class="card-text text-muted small mb-3">
                <?php if ($page->description): ?>
                  <?php echo esc_entities(truncate_text($page->description, 100)) ?>
                <?php else: ?>
                  <em>No description</em>
                <?php endif ?>
              </p>

              <div class="d-flex gap-2">
                <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboard']) ?>"
                   class="btn btn-outline-secondary btn-sm flex-grow-1">
                  View
                </a>
                <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardEdit']) ?>"
                   class="btn btn-primary btn-sm flex-grow-1">
                  Edit
                </a>
              </div>
            </div>
            <div class="card-footer bg-transparent text-muted small">
              Updated <?php echo format_date($page->updated_at, 'f') ?>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
