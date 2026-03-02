<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Blog & News'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Blog')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Blog & News'); ?></h1>
  <?php if ($sf_user->isAuthenticated()): ?>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogNew']); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-pen me-1"></i> <?php echo __('Write Post'); ?>
  </a>
  <?php endif; ?>
</div>

<!-- Category filter tabs -->
<div class="mb-4">
  <?php
    $categories = ['' => __('All'), 'news' => __('News'), 'announcement' => __('Announcement'), 'tutorial' => __('Tutorial'), 'case_study' => __('Case Study'), 'release' => __('Release'), 'community' => __('Community')];
    $currentCat = $sf_request->getParameter('category', '');
  ?>
  <ul class="nav nav-pills nav-fill flex-wrap">
    <?php foreach ($categories as $val => $label): ?>
    <li class="nav-item">
      <a class="nav-link<?php echo $currentCat === $val ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList', 'category' => $val]); ?>">
        <?php echo $label; ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</div>

<!-- Search bar -->
<div class="mb-4">
  <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'blogList']); ?>">
    <?php if ($currentCat): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($currentCat, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <div class="input-group">
      <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search posts...'); ?>">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i>
      </button>
    </div>
  </form>
</div>

<?php if (!empty($result['items'])): ?>

<!-- Featured/pinned posts -->
<?php
  $pinned = [];
  $regular = [];
  foreach ($result['items'] as $post) {
    if (!empty($post->is_pinned) || !empty($post->is_featured)) {
      $pinned[] = $post;
    } else {
      $regular[] = $post;
    }
  }
?>

<?php if (!empty($pinned)): ?>
<div class="mb-4">
  <?php foreach ($pinned as $post): ?>
  <div class="card mb-3 border-primary">
    <div class="row g-0">
      <?php if (!empty($post->featured_image_path)): ?>
      <div class="col-md-4">
        <img src="<?php echo htmlspecialchars($post->featured_image_path, ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded-start h-100" alt="" style="object-fit: cover; max-height: 200px;">
      </div>
      <?php endif; ?>
      <div class="col-md-<?php echo !empty($post->featured_image_path) ? '8' : '12'; ?>">
        <div class="card-body">
          <div class="mb-1">
            <?php if (!empty($post->is_featured)): ?><span class="badge bg-warning text-dark me-1"><i class="fas fa-star"></i> <?php echo __('Featured'); ?></span><?php endif; ?>
            <?php if (!empty($post->is_pinned)): ?><span class="badge bg-info text-dark me-1"><i class="fas fa-thumbtack"></i> <?php echo __('Pinned'); ?></span><?php endif; ?>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($post->category ?? 'news', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <h5 class="card-title">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $post->slug]); ?>" class="text-decoration-none">
              <?php echo htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h5>
          <?php if (!empty($post->excerpt)): ?>
          <p class="card-text"><?php echo htmlspecialchars($post->excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
          <small class="text-muted">
            <?php echo htmlspecialchars($post->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            &middot; <?php echo date('M j, Y', strtotime($post->published_at ?? $post->created_at)); ?>
            <?php if (isset($post->comment_count) && (int) $post->comment_count > 0): ?>
              &middot; <i class="fas fa-comments"></i> <?php echo (int) $post->comment_count; ?>
            <?php endif; ?>
          </small>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Regular blog posts grid -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
  <?php foreach ($regular as $post): ?>
    <?php include_partial('registry/blogCard', ['post' => $post]); ?>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 12; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList', 'page' => $page - 1, 'category' => $currentCat, 'q' => $sf_request->getParameter('q', '')]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList', 'page' => $i, 'category' => $currentCat, 'q' => $sf_request->getParameter('q', '')]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList', 'page' => $page + 1, 'category' => $currentCat, 'q' => $sf_request->getParameter('q', '')]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No posts found'); ?></h5>
  <p class="text-muted"><?php echo __('No blog posts match your criteria.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList']); ?>" class="btn btn-primary"><?php echo __('View All Posts'); ?></a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
