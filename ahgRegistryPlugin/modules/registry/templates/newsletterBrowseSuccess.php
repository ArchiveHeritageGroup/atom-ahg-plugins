<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Newsletters'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Newsletters')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Newsletters'); ?></h1>
  <a href="/registry/newsletter/subscribe" class="btn btn-primary btn-sm">
    <i class="fas fa-envelope me-1"></i> <?php echo __('Subscribe'); ?>
  </a>
</div>

<?php if (empty($newsletters['items'])): ?>
  <div class="text-center py-5">
    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
    <p class="text-muted"><?php echo __('No newsletters have been published yet.'); ?></p>
    <a href="/registry/newsletter/subscribe" class="btn btn-outline-primary mt-2">
      <i class="fas fa-envelope me-1"></i> <?php echo __('Subscribe to be notified'); ?>
    </a>
  </div>
<?php else: ?>

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($newsletters['items'] as $nl): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Sent'); ?></span>
            <small class="text-muted">
              <?php echo date('j M Y', strtotime($nl->sent_at ?? $nl->created_at)); ?>
            </small>
          </div>
          <h5 class="card-title mb-2">
            <a href="/registry/newsletters/<?php echo (int) $nl->id; ?>" class="text-decoration-none text-dark">
              <?php echo htmlspecialchars($nl->subject, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h5>
          <?php if (!empty($nl->excerpt)): ?>
            <p class="card-text text-muted small"><?php echo htmlspecialchars($nl->excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php else: ?>
            <p class="card-text text-muted small"><?php echo htmlspecialchars(mb_substr(strip_tags($nl->content), 0, 150), ENT_QUOTES, 'UTF-8'); ?>...</p>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent border-top-0">
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
              <i class="fas fa-users me-1"></i><?php echo number_format($nl->recipient_count ?? 0); ?> <?php echo __('recipients'); ?>
            </small>
            <a href="/registry/newsletters/<?php echo (int) $nl->id; ?>" class="btn btn-sm btn-outline-primary">
              <?php echo __('Read'); ?> <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php
  $total = $newsletters['total'] ?? 0;
  $page = $newsletters['page'] ?? 1;
  $limit = 12;
  $pages = ceil($total / $limit);
  if ($pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
      <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
        <a class="page-link" href="/registry/newsletters?page=<?php echo $p; ?>"><?php echo $p; ?></a>
      </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

<?php endif; ?>

<?php end_slot(); ?>
