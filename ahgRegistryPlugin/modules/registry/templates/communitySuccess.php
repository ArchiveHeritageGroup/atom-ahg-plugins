<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Community Hub'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Community')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Community Hub'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupBrowse']); ?>" class="btn btn-outline-primary btn-sm me-1">
      <i class="fas fa-users me-1"></i> <?php echo __('All Groups'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList']); ?>" class="btn btn-outline-primary btn-sm me-1">
      <i class="fas fa-blog me-1"></i> <?php echo __('Blog'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'newsletterSubscribe']); ?>" class="btn btn-outline-success btn-sm">
      <i class="fas fa-envelope me-1"></i> <?php echo __('Newsletter'); ?>
    </a>
  </div>
</div>

<!-- Featured groups -->
<?php if (!empty($featuredGroups)): ?>
<div class="mb-5">
  <h2 class="h5 mb-3"><?php echo __('Featured Groups'); ?></h2>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
    <?php foreach ($featuredGroups as $grp): ?>
      <?php include_partial('registry/groupCard', ['item' => $grp]); ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">

  <!-- Trending discussions -->
  <div class="col-lg-7">
    <h2 class="h5 mb-3"><?php echo __('Trending Discussions'); ?></h2>
    <?php if (!empty($recentDiscussions)): ?>
    <div class="list-group list-group-flush">
      <?php foreach ($recentDiscussions as $disc): ?>
      <div class="list-group-item px-0">
        <div class="d-flex align-items-start">
          <div class="me-3 text-center" style="min-width: 50px;">
            <div class="fw-bold text-primary"><?php echo (int) $disc->reply_count; ?></div>
            <small class="text-muted"><?php echo __('replies'); ?></small>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-1">
              <?php
                $discGroupSlug = $disc->group_slug ?? '';
                $discUrl = $discGroupSlug ? url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $discGroupSlug, 'id' => (int) $disc->id]) : '#';
              ?>
              <a href="<?php echo $discUrl; ?>" class="text-decoration-none"><?php echo htmlspecialchars($disc->title, ENT_QUOTES, 'UTF-8'); ?></a>
            </h6>
            <small class="text-muted">
              <?php echo htmlspecialchars($disc->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($disc->group_name)): ?>
                <?php echo __('in'); ?> <strong><?php echo htmlspecialchars($disc->group_name, ENT_QUOTES, 'UTF-8'); ?></strong>
              <?php endif; ?>
              &middot; <?php echo date('M j, Y', strtotime($disc->created_at)); ?>
            </small>
            <?php if (!empty($disc->topic_type)): ?>
              <span class="badge bg-info text-dark ms-2"><?php echo htmlspecialchars($disc->topic_type, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
          <div class="text-muted small text-nowrap ms-2">
            <i class="fas fa-eye"></i> <?php echo (int) ($disc->view_count ?? 0); ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-muted"><?php echo __('No discussions yet. Join a group and start a conversation!'); ?></p>
    <?php endif; ?>
  </div>

  <!-- Sidebar -->
  <div class="col-lg-5">

    <!-- Latest blog posts -->
    <?php if (!empty($latestBlog)): ?>
    <div class="mb-4">
      <h2 class="h5 mb-3"><?php echo __('Latest Blog Posts'); ?></h2>
      <div class="row row-cols-1 g-3">
        <?php foreach ($latestBlog as $post): ?>
        <div class="col">
          <div class="card h-100">
            <?php if (!empty($post->featured_image_path)): ?>
            <img src="<?php echo htmlspecialchars($post->featured_image_path, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="" style="height: 120px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-body py-2">
              <h6 class="card-title mb-1">
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $post->slug]); ?>" class="text-decoration-none">
                  <?php echo htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </h6>
              <small class="text-muted"><?php echo date('M j, Y', strtotime($post->published_at ?? $post->created_at)); ?></small>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick actions -->
    <div class="card">
      <div class="card-header fw-semibold"><?php echo __('Get Involved'); ?></div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupBrowse']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-users me-2 text-primary"></i> <?php echo __('Join a User Group'); ?>
        </a>
        <?php if (!empty($currentUserEmail)): ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupCreate']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-plus-circle me-2 text-success"></i> <?php echo __('Create a Group'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogNew']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-pen me-2 text-info"></i> <?php echo __('Write a Blog Post'); ?>
        </a>
        <?php endif; ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'map']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-map-marker-alt me-2 text-danger"></i> <?php echo __('View Map'); ?>
        </a>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>
