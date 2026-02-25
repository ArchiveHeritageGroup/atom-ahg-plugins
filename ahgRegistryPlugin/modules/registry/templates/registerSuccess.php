<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?>Create Account — AtoM Registry<?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<div class="row justify-content-center py-3">
  <div class="col-lg-10 col-xl-9">
    <div class="row g-4">

      <!-- Registration form -->
      <div class="col-md-7">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h4 class="card-title mb-1"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
            <p class="text-muted small mb-3">Join the AtoM community and manage your institutions, software, and groups.</p>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post" action="/registry/register">
              <div class="mb-3">
                <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars(sfContext::getInstance()->getRequest()->getParameter('name', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Your full name">
              </div>

              <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars(sfContext::getInstance()->getRequest()->getParameter('email', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="you@example.com">
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="password" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Min 8 characters">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="password_confirm" class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
              </div>

              <!-- User groups to join -->
              <?php if (!empty($groups)): ?>
                <hr>
                <h6 class="small fw-semibold mb-2"><i class="fas fa-users me-1 text-info"></i> Join User Groups (optional)</h6>
                <p class="text-muted small mb-2">Select groups to join when you create your account.</p>
                <div class="row row-cols-1 g-2 mb-3" style="max-height:280px;overflow-y:auto;">
                  <?php foreach ($groups as $g): ?>
                    <div class="col">
                      <div class="form-check border rounded p-2 ps-4">
                        <input class="form-check-input" type="checkbox" name="groups[]" value="<?php echo (int) $g->id; ?>" id="group_<?php echo (int) $g->id; ?>">
                        <label class="form-check-label w-100" for="group_<?php echo (int) $g->id; ?>">
                          <span class="fw-semibold small"><?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?></span>
                          <br>
                          <small class="text-muted">
                            <?php echo htmlspecialchars(ucfirst($g->group_type ?? 'regional'), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($g->country)): ?>&middot; <?php echo htmlspecialchars($g->country, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                            &middot; <?php echo (int) $g->member_count; ?> members
                          </small>
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- Newsletter subscription -->
              <hr>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="newsletter_subscribe" value="1" id="newsletter_subscribe" checked>
                <label class="form-check-label" for="newsletter_subscribe">
                  <i class="fas fa-envelope text-primary me-1"></i>
                  <span class="small fw-semibold"><?php echo __('Subscribe to newsletter'); ?></span>
                  <br><small class="text-muted"><?php echo __('Get updates on new institutions, vendors, software releases, and community events.'); ?></small>
                </label>
              </div>

              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-user-plus me-1"></i> Create Account
              </button>
            </form>

            <hr class="my-3">
            <p class="text-center small mb-0">
              Already have an account? <a href="/registry/login">Sign in</a>
            </p>
          </div>
        </div>
      </div>

      <!-- Info sidebar -->
      <div class="col-md-5">
        <div class="card bg-light border-0 mb-3">
          <div class="card-body">
            <h6 class="fw-semibold mb-3">What you can do</h6>
            <div class="small">
              <div class="mb-2"><i class="fas fa-university text-primary me-2"></i> Register and manage your institution's profile</div>
              <div class="mb-2"><i class="fas fa-building text-success me-2"></i> Register as a vendor/developer for AtoM sector</div>
              <div class="mb-2"><i class="fas fa-cube text-info me-2"></i> List your software products and plugins</div>
              <div class="mb-2"><i class="fas fa-users text-warning me-2"></i> Join user groups and participate in discussions</div>
              <div class="mb-2"><i class="fas fa-rss text-danger me-2"></i> Publish blog posts and share knowledge</div>
              <div class="mb-0"><i class="fas fa-star text-secondary me-2"></i> Review vendors and software products</div>
            </div>
          </div>
        </div>

        <div class="card bg-light border-0">
          <div class="card-body">
            <h6 class="fw-semibold mb-2">For Institutions</h6>
            <p class="small text-muted mb-2">After creating your account, register your archive, library, museum, or gallery. Add your instances, contacts, and software stack.</p>
            <h6 class="fw-semibold mb-2">For Vendors</h6>
            <p class="small text-muted mb-0">List your services, connect with AtoM institutions, and showcase your software products to the global community.</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php end_slot(); ?>
