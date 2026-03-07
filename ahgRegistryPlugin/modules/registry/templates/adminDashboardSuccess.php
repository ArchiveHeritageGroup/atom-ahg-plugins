<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Registry Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin')],
]]); ?>

<h1 class="h3 mb-4"><?php echo __('Registry Admin'); ?></h1>

<!-- Stats cards -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-5">

  <div class="col">
    <div class="card h-100 border-start border-primary border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Institutions'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['institutions'] ?? 0); ?></div>
          </div>
          <i class="fas fa-university fa-2x text-primary opacity-50"></i>
        </div>
        <?php if (($stats['institutions_pending'] ?? 0) > 0): ?>
        <div class="mt-2">
          <span class="badge bg-warning text-dark"><?php echo (int) $stats['institutions_pending']; ?> <?php echo __('pending verification'); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-success border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Vendors'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['vendors'] ?? 0); ?></div>
          </div>
          <i class="fas fa-handshake fa-2x text-success opacity-50"></i>
        </div>
        <?php if (($stats['vendors_pending'] ?? 0) > 0): ?>
        <div class="mt-2">
          <span class="badge bg-warning text-dark"><?php echo (int) $stats['vendors_pending']; ?> <?php echo __('pending verification'); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-info border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Software'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['software'] ?? 0); ?></div>
          </div>
          <i class="fas fa-code fa-2x text-info opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-secondary border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Instances'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['instances'] ?? 0); ?></div>
          </div>
          <i class="fas fa-server fa-2x text-secondary opacity-50"></i>
        </div>
        <?php if (($stats['instances_online'] ?? 0) > 0): ?>
        <div class="mt-2">
          <span class="badge bg-success"><?php echo (int) $stats['instances_online']; ?> <?php echo __('online'); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-purple border-4" style="border-left-color: #6f42c1 !important;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Groups'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['groups'] ?? 0); ?></div>
          </div>
          <i class="fas fa-users fa-2x opacity-50" style="color: #6f42c1;"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-warning border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Discussions'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['discussions'] ?? 0); ?></div>
          </div>
          <i class="fas fa-comments fa-2x text-warning opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-danger border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Blog Posts'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['blog_posts'] ?? 0); ?></div>
          </div>
          <i class="fas fa-blog fa-2x text-danger opacity-50"></i>
        </div>
        <?php if (($stats['blog_pending'] ?? 0) > 0): ?>
        <div class="mt-2">
          <span class="badge bg-warning text-dark"><?php echo (int) $stats['blog_pending']; ?> <?php echo __('pending review'); ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="card h-100 border-start border-4" style="border-left-color: #fd7e14 !important;">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small mb-1"><?php echo __('Reviews'); ?></div>
            <div class="h3 mb-0"><?php echo number_format($stats['reviews'] ?? 0); ?></div>
          </div>
          <i class="fas fa-star fa-2x opacity-50" style="color: #fd7e14;"></i>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Quick links -->
<h2 class="h5 mb-3"><?php echo __('Quick Links'); ?></h2>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutions']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-check-circle fa-2x text-primary mb-2"></i>
        <h6 class="card-title"><?php echo __('Verify Institutions'); ?></h6>
        <?php if (($stats['institutions_pending'] ?? 0) > 0): ?>
        <span class="badge bg-warning text-dark"><?php echo (int) $stats['institutions_pending']; ?> <?php echo __('pending'); ?></span>
        <?php endif; ?>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminVendors']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-check-double fa-2x text-success mb-2"></i>
        <h6 class="card-title"><?php echo __('Verify Vendors'); ?></h6>
        <?php if (($stats['vendors_pending'] ?? 0) > 0): ?>
        <span class="badge bg-warning text-dark"><?php echo (int) $stats['vendors_pending']; ?> <?php echo __('pending'); ?></span>
        <?php endif; ?>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminBlog']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-blog fa-2x text-danger mb-2"></i>
        <h6 class="card-title"><?php echo __('Moderate Blog'); ?></h6>
        <?php if (($stats['blog_pending'] ?? 0) > 0): ?>
        <span class="badge bg-warning text-dark"><?php echo (int) $stats['blog_pending']; ?> <?php echo __('pending'); ?></span>
        <?php endif; ?>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSync']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-sync-alt fa-2x text-info mb-2"></i>
        <h6 class="card-title"><?php echo __('Sync Dashboard'); ?></h6>
        <?php if (($stats['instances_online'] ?? 0) > 0): ?>
        <span class="badge bg-success"><?php echo (int) $stats['instances_online']; ?> <?php echo __('online'); ?></span>
        <?php endif; ?>
      </div>
    </a>
  </div>

</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mt-1">

  <div class="col">
    <a href="/registry/admin/users" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-user-check fa-2x text-primary mb-2"></i>
        <h6 class="card-title"><?php echo __('User Approval'); ?></h6>
        <?php if (($stats['users_pending'] ?? 0) > 0): ?>
        <span class="badge bg-warning text-dark"><?php echo (int) $stats['users_pending']; ?> <?php echo __('pending'); ?></span>
        <?php endif; ?>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="/registry/admin/users/manage" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-users-cog fa-2x text-secondary mb-2"></i>
        <h6 class="card-title"><?php echo __('Manage Users'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSoftware']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-code fa-2x text-info mb-2"></i>
        <h6 class="card-title"><?php echo __('Manage Software'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandards']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-balance-scale fa-2x text-danger mb-2"></i>
        <h6 class="card-title"><?php echo __('Manage Standards'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSetupGuides']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-book-open fa-2x text-secondary mb-2"></i>
        <h6 class="card-title"><?php echo __('Setup Guides'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErd']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-project-diagram fa-2x text-primary mb-2"></i>
        <h6 class="card-title"><?php echo __('ERD Documentation'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminGroups']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-users fa-2x mb-2" style="color: #6f42c1;"></i>
        <h6 class="card-title"><?php echo __('Manage Groups'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDiscussions']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-comments fa-2x text-warning mb-2"></i>
        <h6 class="card-title"><?php echo __('Moderate Discussions'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletters']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-newspaper fa-2x text-success mb-2"></i>
        <h6 class="card-title"><?php echo __('Newsletters'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-envelope-open-text fa-2x mb-2" style="color: #fd7e14;"></i>
        <h6 class="card-title"><?php echo __('Subscribers'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminEmail']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-at fa-2x text-danger mb-2"></i>
        <h6 class="card-title"><?php echo __('Email Settings'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminFooter']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-shoe-prints fa-2x text-info mb-2"></i>
        <h6 class="card-title"><?php echo __('Footer'); ?></h6>
      </div>
    </a>
  </div>

  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSettings']); ?>" class="card text-decoration-none h-100">
      <div class="card-body text-center">
        <i class="fas fa-cog fa-2x text-secondary mb-2"></i>
        <h6 class="card-title"><?php echo __('Settings'); ?></h6>
      </div>
    </a>
  </div>

</div>

<?php end_slot(); ?>
