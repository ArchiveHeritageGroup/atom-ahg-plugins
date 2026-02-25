<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('AtoM Community Hub'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<!-- Hero banner -->
<div class="hero-banner text-white px-4 py-3 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <h1 class="h3 fw-bold mb-1"><?php echo __('AtoM Community Hub'); ?></h1>
      <p class="mb-2 small opacity-75"><?php echo __('The global directory for AtoM institutions, vendors, and archival software.'); ?></p>
      <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'search']); ?>">
        <div class="input-group input-group-sm" style="max-width:500px;">
          <input type="text" class="form-control" name="q" placeholder="<?php echo __('Search institutions, vendors, software...'); ?>">
          <button type="submit" class="btn btn-light">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </form>
    </div>
    <div class="col-lg-4 d-none d-lg-block text-end">
      <span class="d-inline-flex gap-1 opacity-25">
        <i class="fas fa-earth-americas fa-4x"></i>
        <i class="fas fa-earth-europe fa-4x"></i>
        <i class="fas fa-earth-asia fa-4x"></i>
      </span>
    </div>
  </div>
</div>

<!-- Stats row -->
<?php if (!empty($stats)): ?>
<div class="row g-3 mb-5">
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse']); ?>" class="card text-center h-100 text-decoration-none">
      <div class="card-body">
        <div class="display-6 fw-bold text-primary"><?php echo number_format($stats['institutions'] ?? 0); ?></div>
        <div class="text-muted small"><?php echo __('Institutions'); ?></div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>" class="card text-center h-100 text-decoration-none">
      <div class="card-body">
        <div class="display-6 fw-bold text-success"><?php echo number_format($stats['vendors'] ?? 0); ?></div>
        <div class="text-muted small"><?php echo __('Vendors'); ?></div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>" class="card text-center h-100 text-decoration-none">
      <div class="card-body">
        <div class="display-6 fw-bold text-info"><?php echo number_format($stats['software'] ?? 0); ?></div>
        <div class="text-muted small"><?php echo __('Software'); ?></div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupBrowse']); ?>" class="card text-center h-100 text-decoration-none">
      <div class="card-body">
        <div class="display-6 fw-bold text-warning"><?php echo number_format($stats['groups'] ?? 0); ?></div>
        <div class="text-muted small"><?php echo __('User Groups'); ?></div>
      </div>
    </a>
  </div>
</div>
<?php endif; ?>

<!-- Featured institutions -->
<?php if (!empty($featuredInstitutions)): ?>
<div class="mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><?php echo __('Featured Institutions'); ?></h2>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionBrowse']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
    <?php foreach ($featuredInstitutions as $inst): ?>
      <?php include_partial('registry/institutionCard', ['item' => $inst]); ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Featured vendors -->
<?php if (!empty($featuredVendors)): ?>
<div class="mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><?php echo __('Featured Vendors'); ?></h2>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorBrowse']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
    <?php foreach ($featuredVendors as $v): ?>
      <?php include_partial('registry/vendorCard', ['item' => $v]); ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Featured software -->
<?php if (!empty($featuredSoftware)): ?>
<div class="mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><?php echo __('Featured Software'); ?></h2>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
  </div>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
    <?php foreach ($featuredSoftware as $sw): ?>
      <?php include_partial('registry/softwareCard', ['item' => $sw]); ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-5">
  <!-- Recent blog posts -->
  <div class="col-lg-6">
    <?php if (!empty($recentBlog)): ?>
    <h2 class="h4 mb-3"><?php echo __('Recent Blog Posts'); ?></h2>
    <div class="list-group list-group-flush">
      <?php foreach ($recentBlog as $post): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $post->slug]); ?>" class="list-group-item list-group-item-action">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="mb-1"><?php echo htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8'); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars($post->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo date('M j, Y', strtotime($post->published_at ?? $post->created_at)); ?></small>
          </div>
          <span class="badge bg-secondary"><?php echo htmlspecialchars($post->category ?? 'news', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-2">
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogList']); ?>" class="small"><?php echo __('View all posts'); ?> &rarr;</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent discussions -->
  <div class="col-lg-6">
    <?php if (!empty($recentDiscussions)): ?>
    <h2 class="h4 mb-3"><?php echo __('Recent Discussions'); ?></h2>
    <div class="list-group list-group-flush">
      <?php foreach ($recentDiscussions as $disc): ?>
      <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h6 class="mb-1"><?php echo htmlspecialchars($disc->title, ENT_QUOTES, 'UTF-8'); ?></h6>
            <small class="text-muted">
              <?php echo htmlspecialchars($disc->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
              &middot; <?php echo (int) $disc->reply_count; ?> <?php echo __('replies'); ?>
              &middot; <?php echo date('M j, Y', strtotime($disc->created_at)); ?>
            </small>
          </div>
          <span class="badge bg-info text-dark"><?php echo htmlspecialchars($disc->topic_type ?? 'discussion', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-2">
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'community']); ?>" class="small"><?php echo __('View community hub'); ?> &rarr;</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Quick links -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border-primary h-100">
      <div class="card-body text-center">
        <i class="fas fa-university fa-2x text-primary mb-2"></i>
        <h5 class="card-title"><?php echo __('Register Institution'); ?></h5>
        <p class="card-text small text-muted"><?php echo __('Add your institution to the global AtoM directory.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionRegister']); ?>" class="btn btn-outline-primary btn-sm"><?php echo __('Register'); ?></a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-success h-100">
      <div class="card-body text-center">
        <i class="fas fa-handshake fa-2x text-success mb-2"></i>
        <h5 class="card-title"><?php echo __('Register as Vendor'); ?></h5>
        <p class="card-text small text-muted"><?php echo __('List your services and reach AtoM institutions worldwide.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorRegister']); ?>" class="btn btn-outline-success btn-sm"><?php echo __('Register'); ?></a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-info h-100">
      <div class="card-body text-center">
        <i class="fas fa-users fa-2x text-info mb-2"></i>
        <h5 class="card-title"><?php echo __('Browse Groups'); ?></h5>
        <p class="card-text small text-muted"><?php echo __('Join user groups, participate in discussions, and collaborate.'); ?></p>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupBrowse']); ?>" class="btn btn-outline-info btn-sm"><?php echo __('Browse'); ?></a>
      </div>
    </div>
  </div>
</div>

<!-- Newsletter CTA -->
<div class="card bg-light border-0 mt-4 mb-3">
  <div class="card-body py-4">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h5 class="mb-1"><i class="fas fa-envelope text-primary me-2"></i><?php echo __('Stay Connected'); ?></h5>
        <p class="text-muted mb-0 small"><?php echo __('Subscribe to our newsletter for updates on new institutions, software releases, and community events.'); ?></p>
      </div>
      <div class="col-md-4 text-md-end mt-2 mt-md-0">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'newsletterSubscribe']); ?>" class="btn btn-primary">
          <i class="fas fa-paper-plane me-1"></i> <?php echo __('Subscribe'); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>
