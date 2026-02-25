<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?>Sign In — AtoM Registry<?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<!-- Welcome banner -->
<div class="bg-primary text-white rounded-3 px-4 py-3 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <h1 class="h4 fw-bold mb-1">Welcome to the AtoM Registry</h1>
      <p class="mb-0 small opacity-85">The global directory and community hub for Galleries, Libraries, Archives, Museums and Digital Asset Management institutions. Sign in to manage your profiles, join groups, and connect with the community.</p>
    </div>
    <div class="col-lg-4 d-none d-lg-block text-end">
      <i class="fas fa-landmark fa-3x opacity-25"></i>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Left: Login form + stats -->
  <div class="col-lg-5">
    <div class="card shadow-sm mb-3">
      <div class="card-body p-4">
        <h4 class="card-title mb-1"><i class="fas fa-sign-in-alt me-2 text-primary"></i>Sign In</h4>
        <p class="text-muted small mb-3">Access your institution dashboard, vendor profiles, and group discussions.</p>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (sfContext::getInstance()->getRequest()->getParameter('registered') === 'pending'): ?>
          <div class="alert alert-info py-2 small"><i class="fas fa-clock me-1"></i> Account created! An administrator will review and activate your account. You will be able to sign in once approved.</div>
        <?php elseif (sfContext::getInstance()->getRequest()->getParameter('registered')): ?>
          <div class="alert alert-success py-2 small"><i class="fas fa-check-circle me-1"></i> Account created successfully! Please sign in below.</div>
        <?php endif; ?>

        <?php if (sfContext::getInstance()->getRequest()->getParameter('error')): ?>
          <div class="alert alert-warning py-2 small">
            <?php
              $err = sfContext::getInstance()->getRequest()->getParameter('error');
              $msgs = [
                'oauth_denied' => 'Social login was cancelled.',
                'oauth_failed' => 'Social login failed. Please try again.',
                'oauth_not_configured' => 'That login provider is not configured.',
                'invalid_state' => 'Security check failed. Please try again.',
              ];
              echo '<i class="fas fa-exclamation-triangle me-1"></i> ' . htmlspecialchars($msgs[$err] ?? 'An error occurred.', ENT_QUOTES, 'UTF-8');
            ?>
          </div>
        <?php endif; ?>

        <form method="post" action="/registry/login">
          <?php if (sfContext::getInstance()->getRequest()->getParameter('return')): ?>
            <input type="hidden" name="return" value="<?php echo htmlspecialchars(sfContext::getInstance()->getRequest()->getParameter('return'), ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
              <input type="email" class="form-control" id="email" name="email" required autofocus placeholder="you@example.com">
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-sign-in-alt me-1"></i> Sign In
          </button>
        </form>

        <?php if (!empty($oauthProviders)): ?>
          <div class="text-center my-2">
            <span class="text-muted small">or continue with</span>
          </div>
          <div class="d-flex flex-wrap gap-2 justify-content-center mb-2">
            <?php
              $providerIcons = [
                'google' => ['fab fa-google', '#DB4437'],
                'facebook' => ['fab fa-facebook-f', '#1877F2'],
                'github' => ['fab fa-github', '#333'],
                'linkedin' => ['fab fa-linkedin-in', '#0A66C2'],
                'microsoft' => ['fab fa-microsoft', '#00A4EF'],
              ];
              foreach ($oauthProviders as $p):
                $icon = $providerIcons[$p][0] ?? 'fas fa-globe';
                $color = $providerIcons[$p][1] ?? '#666';
            ?>
              <a href="/registry/oauth/<?php echo $p; ?>" class="btn btn-outline-secondary btn-sm" title="<?php echo ucfirst($p); ?>">
                <i class="<?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i> <?php echo ucfirst($p); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <hr class="my-3">
        <p class="text-center small mb-0">
          New here? <a href="/registry/register" class="fw-semibold">Create a free account</a>
        </p>
      </div>
    </div>

    <!-- Quick stats -->
    <?php
      $db = Illuminate\Database\Capsule\Manager::class;
      $instCount = $db::table('registry_institution')->where('is_active', 1)->count();
      $vendorCount = $db::table('registry_vendor')->where('is_active', 1)->count();
      $softwareCount = $db::table('registry_software')->where('is_active', 1)->count();
      $groupCount = $db::table('registry_user_group')->where('is_active', 1)->count();
    ?>
    <div class="row g-2">
      <div class="col-6">
        <div class="card text-center border-0 bg-light">
          <div class="card-body py-2">
            <div class="h5 fw-bold text-primary mb-0"><?php echo number_format($instCount); ?></div>
            <small class="text-muted">Institutions</small>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card text-center border-0 bg-light">
          <div class="card-body py-2">
            <div class="h5 fw-bold text-success mb-0"><?php echo number_format($vendorCount); ?></div>
            <small class="text-muted">Vendors</small>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card text-center border-0 bg-light">
          <div class="card-body py-2">
            <div class="h5 fw-bold text-info mb-0"><?php echo number_format($softwareCount); ?></div>
            <small class="text-muted">Software</small>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card text-center border-0 bg-light">
          <div class="card-body py-2">
            <div class="h5 fw-bold text-warning mb-0"><?php echo number_format($groupCount); ?></div>
            <small class="text-muted">User Groups</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Community info + groups -->
  <div class="col-lg-7">

    <!-- What is the AtoM Registry -->
    <div class="card shadow-sm mb-3">
      <div class="card-body p-4">
        <h5 class="card-title mb-3"><i class="fas fa-info-circle me-2 text-info"></i>What is the AtoM Registry?</h5>
        <p class="small text-muted mb-3">The AtoM Registry is a free, open platform that connects the global community of archivists, librarians, curators, developers, and digital preservation specialists.</p>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="d-flex align-items-start">
              <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-2 flex-shrink-0">
                <i class="fas fa-university text-primary"></i>
              </div>
              <div>
                <h6 class="small fw-semibold mb-1">Institution Directory</h6>
                <p class="small text-muted mb-0">Register your archive, library, museum, or gallery. Showcase your collections and connect with peers worldwide.</p>
              </div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="d-flex align-items-start">
              <div class="rounded-circle bg-success bg-opacity-10 p-2 me-2 flex-shrink-0">
                <i class="fas fa-building text-success"></i>
              </div>
              <div>
                <h6 class="small fw-semibold mb-1">Vendor Marketplace</h6>
                <p class="small text-muted mb-0">Find developers, consultants, and service providers specializing in archival and digital preservation systems.</p>
              </div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="d-flex align-items-start">
              <div class="rounded-circle bg-info bg-opacity-10 p-2 me-2 flex-shrink-0">
                <i class="fas fa-cube text-info"></i>
              </div>
              <div>
                <h6 class="small fw-semibold mb-1">Software Catalog</h6>
                <p class="small text-muted mb-0">Browse and compare archival software, plugins, and tools. Track versions and read community reviews.</p>
              </div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="d-flex align-items-start">
              <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-2 flex-shrink-0">
                <i class="fas fa-users text-warning"></i>
              </div>
              <div>
                <h6 class="small fw-semibold mb-1">Community & Groups</h6>
                <p class="small text-muted mb-0">Join regional and topic-based user groups, participate in discussions, and share knowledge with the community.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Join the Community -->
    <div class="card shadow-sm border-info mb-3">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="card-title mb-0"><i class="fas fa-user-friends me-2 text-info"></i>Join the Community</h5>
          <a href="/registry/register" class="btn btn-info btn-sm text-white"><i class="fas fa-user-plus me-1"></i> Create Account</a>
        </div>

        <?php if (!empty($groups)): ?>
          <div class="list-group list-group-flush mb-3">
            <?php foreach ($groups as $g): ?>
              <a href="/registry/groups/<?php echo htmlspecialchars($g->slug, ENT_QUOTES, 'UTF-8'); ?>" class="list-group-item list-group-item-action py-2 px-0 border-0">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold small"><?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?></div>
                    <small class="text-muted">
                      <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $g->group_type ?? 'regional')), ENT_QUOTES, 'UTF-8'); ?>
                      <?php if (!empty($g->country)): ?>&middot; <?php echo htmlspecialchars($g->country, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                      &middot; <?php echo (int) $g->member_count; ?> members
                    </small>
                  </div>
                  <span class="badge bg-info bg-opacity-10 text-info"><i class="fas fa-chevron-right"></i></span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="row g-2">
          <div class="col-sm-4">
            <a href="/registry/institutions" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-university me-1"></i> Browse Institutions</a>
          </div>
          <div class="col-sm-4">
            <a href="/registry/vendors" class="btn btn-outline-success btn-sm w-100"><i class="fas fa-building me-1"></i> Browse Vendors</a>
          </div>
          <div class="col-sm-4">
            <a href="/registry/software" class="btn btn-outline-info btn-sm w-100"><i class="fas fa-cube me-1"></i> Browse Software</a>
          </div>
        </div>
      </div>
    </div>

    <!-- What you can do after signing in -->
    <div class="card bg-light border-0">
      <div class="card-body p-3">
        <h6 class="fw-semibold small mb-2"><i class="fas fa-check-circle text-success me-1"></i> After signing in, you can:</h6>
        <div class="row small text-muted">
          <div class="col-sm-6">
            <ul class="mb-0 ps-3">
              <li>Register and manage your institution's public profile</li>
              <li>Add your AtoM instances and track deployments</li>
              <li>Manage contacts and vendor relationships</li>
            </ul>
          </div>
          <div class="col-sm-6">
            <ul class="mb-0 ps-3">
              <li>Join user groups and start discussions</li>
              <li>Write blog posts and share knowledge</li>
              <li>Review vendors and software products</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<?php end_slot(); ?>
