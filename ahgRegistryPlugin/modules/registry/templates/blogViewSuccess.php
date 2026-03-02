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

    <!-- Comments section -->
    <?php
      $commentsEnabled = !isset($detail->comments_enabled) || $detail->comments_enabled;
      $disc = isset($discussion) ? $discussion : null;
      $commentCount = $disc ? (int) ($disc['reply_count'] ?? 0) : 0;
      $blogReplyUrl = url_for(['module' => 'registry', 'action' => 'blogReply', 'slug' => $detail->slug]);
    ?>
    <?php if ($commentsEnabled): ?>
    <div id="comments" class="mb-4">
      <h3 class="h5 mb-3">
        <i class="fas fa-comments me-1"></i>
        <?php echo __('%1% Comments', ['%1%' => $commentCount]); ?>
      </h3>

      <?php if ($disc && !empty($disc['replies'])): ?>
        <?php include_partial('registry/replyThread', [
          'replies' => $disc['replies'],
          'discussionId' => (int) $disc['discussion']->id,
          'groupSlug' => '',
          'replyUrl' => $blogReplyUrl,
          'depth' => 0,
        ]); ?>
      <?php endif; ?>

      <?php if ($sf_user->isAuthenticated()): ?>
      <!-- Reply form -->
      <div class="card mt-3" id="reply-form">
        <div class="card-header fw-semibold"><?php echo __('Leave a Comment'); ?></div>
        <div class="card-body">
          <form method="post" action="<?php echo $blogReplyUrl; ?>">
            <div class="mb-3">
              <textarea class="form-control" name="content" rows="4" required placeholder="<?php echo __('Write your comment...'); ?>"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-comment me-1"></i> <?php echo __('Post Comment'); ?>
            </button>
          </form>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-info mt-3">
        <i class="fas fa-sign-in-alt me-1"></i>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'login']); ?>"><?php echo __('Log in'); ?></a>
        <?php echo __('to leave a comment.'); ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Back link -->
    <div class="text-center mt-4 mb-2">
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList']); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Blog'); ?>
      </a>
    </div>

  </div>
</div>

<?php end_slot(); ?>
