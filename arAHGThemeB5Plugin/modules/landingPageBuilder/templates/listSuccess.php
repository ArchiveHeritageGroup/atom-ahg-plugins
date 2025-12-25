<?php
/**
 * Landing Page List - Admin View
 */
use_helper('Date', 'Text');
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-1">Landing Pages</h1>
      <p class="text-muted mb-0">Manage your site's landing pages</p>
    </div>
    <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'create']) ?>" 
       class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Create New Page
    </a>
  </div>

  <?php if (count($pages) === 0): ?>
    <div class="text-center py-5">
      <i class="bi bi-file-earmark-plus display-1 text-muted"></i>
      <h3 class="mt-3 text-muted">No Landing Pages Yet</h3>
      <p class="text-muted">Create your first landing page to get started</p>
      <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'create']) ?>" 
         class="btn btn-primary btn-lg mt-2">
        <i class="bi bi-plus-lg"></i> Create Landing Page
      </a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($pages as $page): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card page-list-card h-100 <?php echo !$page->is_active ? 'border-warning' : '' ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0">
                  <?php echo esc_entities($page->name) ?>
                </h5>
                <div>
                  <?php if ($page->is_default): ?>
                    <span class="badge bg-primary">Default</span>
                  <?php endif ?>
                  <?php if (!$page->is_active): ?>
                    <span class="badge bg-warning text-dark">Inactive</span>
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
              
              <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                <span>
                  <i class="bi bi-grid-3x3-gap"></i>
                  <?php echo $page->block_count ?> blocks
                </span>
                <span>
                  <code>/<?php echo esc_entities($page->slug) ?></code>
                </span>
              </div>
              
              <div class="d-flex gap-2">
                <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'edit', 'id' => $page->id]) ?>" 
                   class="btn btn-primary btn-sm flex-grow-1">
                  Edit
                </a>
                <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'preview', 'id' => $page->id]) ?>" 
                   class="btn btn-outline-secondary btn-sm" target="_blank" title="Preview">
                  Preview
                </a>
                <?php if ($page->is_active): ?>
                  <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'index', 'slug' => $page->slug]) ?>" 
                     class="btn btn-outline-secondary btn-sm" target="_blank" title="View Live">
                    View
                  </a>
                <?php endif ?>
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
