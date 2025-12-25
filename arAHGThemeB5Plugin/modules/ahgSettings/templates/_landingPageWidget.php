<?php
/**
 * Landing Page Widget for AHG Settings Dashboard
 */
$service = new \AtomExtensions\Services\LandingPageService();
$pages = $service->getAllPages();
$defaultPage = null;
foreach ($pages as $page) {
    if ($page->is_default) {
        $defaultPage = $page;
        break;
    }
}
?>

<div class="card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-window-stack text-primary"></i> Landing Pages
    </h5>
    <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'list']) ?>" 
       class="btn btn-sm btn-outline-primary">
      Manage
    </a>
  </div>
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <span class="h3 mb-0"><?php echo count($pages) ?></span>
        <span class="text-muted">pages</span>
      </div>
      <?php if ($defaultPage): ?>
        <span class="badge bg-primary">
          Default: <?php echo esc_entities($defaultPage->name) ?>
        </span>
      <?php endif ?>
    </div>
    
    <?php if (count($pages) > 0): ?>
      <ul class="list-group list-group-flush small">
        <?php foreach (array_slice($pages->toArray(), 0, 3) as $page): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center px-0">
            <span>
              <?php if (!$page->is_active): ?>
                <i class="bi bi-eye-slash text-warning" title="Inactive"></i>
              <?php endif ?>
              <?php echo esc_entities($page->name) ?>
            </span>
            <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'edit', 'id' => $page->id]) ?>" 
               class="text-decoration-none">
              <i class="bi bi-pencil"></i>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    <?php else: ?>
      <p class="text-muted mb-0 small">No landing pages created yet.</p>
    <?php endif ?>
  </div>
  <div class="card-footer bg-transparent">
    <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'create']) ?>" 
       class="btn btn-primary btn-sm w-100">
      <i class="bi bi-plus-lg"></i> Create New Page
    </a>
  </div>
</div>
