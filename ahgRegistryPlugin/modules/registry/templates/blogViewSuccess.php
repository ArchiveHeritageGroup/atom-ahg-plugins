<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $post; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->title, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Blog'), 'url' => url_for(['module' => 'registry', 'action' => 'blogList'])],
  ['label' => htmlspecialchars(mb_substr($detail->title, 0, 50), ENT_QUOTES, 'UTF-8') . (mb_strlen($detail->title) > 50 ? '...' : '')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <!-- Featured image -->
    <?php if (!empty($detail->featured_image_path)): ?>
    <div class="mb-4 rounded-3 overflow-hidden">
      <img src="<?php echo htmlspecialchars($detail->featured_image_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="img-fluid w-100" style="max-height: 400px; object-fit: cover;">
    </div>
    <?php endif; ?>

    <!-- Title and meta -->
    <h1 class="h2 mb-3"><?php echo htmlspecialchars($detail->title, ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center">
        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
          <i class="fas fa-user text-muted"></i>
        </div>
        <div>
          <strong><?php echo htmlspecialchars($detail->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
          <?php if (!empty($detail->author_type)): ?>
            <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars(ucfirst($detail->author_type), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <br><small class="text-muted"><?php echo date('F j, Y', strtotime($detail->published_at ?? $detail->created_at)); ?></small>
        </div>
      </div>
      <div>
        <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->category ?? 'news')), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="text-muted small">
        <i class="fas fa-eye me-1"></i> <?php echo number_format($detail->view_count ?? 0); ?> <?php echo __('views'); ?>
      </div>
    </div>

    <!-- Content -->
    <article class="mb-4">
      <?php
        $rawBlogContent = sfOutputEscaper::unescape($detail->content);
        $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr><img><table><thead><tbody><tr><th><td><span><div><dl><dt><dd><sub><sup>';
        if (preg_match('/<[a-z][\s\S]*>/i', $rawBlogContent)) {
          echo strip_tags($rawBlogContent, $allowedTags);
        } else {
          echo nl2br(htmlspecialchars($rawBlogContent, ENT_QUOTES, 'UTF-8'));
        }
      ?>
    </article>

    <!-- Tags -->
    <?php if (!empty($detail->tags)): ?>
    <div class="mb-4">
      <?php
        $rawTags = sfOutputEscaper::unescape($detail->tags);
        $tags = is_string($rawTags) ? json_decode($rawTags, true) : (array) $rawTags;
        if (is_array($tags)):
          foreach ($tags as $tag): ?>
            <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>

    <hr>

    <!-- Related posts -->
    <?php if (!empty($post['related'])): ?>
    <div class="mb-4">
      <h2 class="h5 mb-3"><?php echo __('Related Posts'); ?></h2>
      <div class="row row-cols-1 row-cols-md-2 g-3">
        <?php foreach ($post['related'] as $rel): ?>
        <div class="col">
          <div class="card h-100">
            <?php if (!empty($rel->featured_image_path)): ?>
            <img src="<?php echo htmlspecialchars($rel->featured_image_path, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="" style="height: 120px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-body py-2">
              <h6 class="card-title mb-1">
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $rel->slug]); ?>" class="text-decoration-none">
                  <?php echo htmlspecialchars($rel->title, ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </h6>
              <small class="text-muted"><?php echo date('M j, Y', strtotime($rel->published_at ?? $rel->created_at)); ?></small>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Back link -->
    <div class="text-center">
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList']); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Blog'); ?>
      </a>
    </div>

  </div>
</div>

<?php end_slot(); ?>
