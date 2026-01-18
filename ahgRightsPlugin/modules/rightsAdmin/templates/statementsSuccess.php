<?php echo get_partial('header', ['title' => 'Rights Statements & Licenses']); ?>

<div class="container-fluid">
  <div class="row">
    <?php include_partial('rightsAdmin/sidebar', ['active' => 'statements']); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-balance-scale me-2"></i><?php echo __('Rights Statements & Licenses'); ?></h1>
      </div>

      <!-- Rights Statements -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <img src="https://rightsstatements.org/files/icons/rightss.logo.svg" alt="Rights Statements" height="24" class="me-2">
            <?php echo __('Rights Statements'); ?>
          </h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">
            Rights Statements are a set of 12 standardized statements designed to communicate the copyright 
            and re-use status of digital objects. Learn more at 
            <a href="https://rightsstatements.org" target="_blank">rightsstatements.org</a>.
          </p>

          <?php 
          $categories = [
            'in_copyright' => ['title' => 'In Copyright', 'color' => 'danger'],
            'no_copyright' => ['title' => 'No Copyright', 'color' => 'success'],
            'other' => ['title' => 'Other', 'color' => 'secondary'],
          ];
          ?>

          <?php foreach ($categories as $category => $meta): ?>
          <h6 class="text-<?php echo $meta['color']; ?> mt-4 mb-3"><?php echo $meta['title']; ?></h6>
          <div class="row">
            <?php foreach ($rightsStatements as $stmt): ?>
              <?php if ($stmt->category === $category): ?>
              <div class="col-md-6 mb-3">
                <div class="card h-100">
                  <div class="card-body">
                    <h6 class="card-title">
                      <span class="badge bg-<?php echo $meta['color']; ?> me-2"><?php echo esc_entities($stmt->code); ?></span>
                      <?php echo esc_entities($stmt->name); ?>
                    </h6>
                    <p class="card-text small text-muted"><?php echo esc_entities($stmt->description); ?></p>
                    <a href="<?php echo $stmt->uri; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                      <i class="fas fa-external-link-alt me-1"></i>View Statement
                    </a>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Creative Commons -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <img src="https://mirrors.creativecommons.org/presskit/logos/cc.logo.svg" alt="Creative Commons" height="24" class="me-2">
            <?php echo __('Creative Commons Licenses'); ?>
          </h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">
            Creative Commons licenses provide a simple, standardized way to give the public permission 
            to use creative work. Learn more at 
            <a href="https://creativecommons.org" target="_blank">creativecommons.org</a>.
          </p>

          <div class="row">
            <?php foreach ($ccLicenses as $license): ?>
            <div class="col-md-6 mb-3">
              <div class="card h-100">
                <div class="card-body">
                  <div class="d-flex align-items-start">
                    <img src="<?php echo esc_entities($license->badge_url); ?>" alt="<?php echo esc_entities($license->code); ?>" height="31" class="me-3">
                    <div>
                      <h6 class="card-title mb-1"><?php echo esc_entities($license->name); ?></h6>
                      <p class="card-text small text-muted mb-2"><?php echo esc_entities($license->human_readable); ?></p>
                      <div class="small">
                        <?php if ($license->allows_commercial): ?>
                          <span class="badge bg-success me-1">Commercial OK</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark me-1">Non-Commercial</span>
                        <?php endif; ?>
                        <?php if ($license->allows_derivatives): ?>
                          <span class="badge bg-info me-1">Derivatives OK</span>
                        <?php else: ?>
                          <span class="badge bg-danger me-1">No Derivatives</span>
                        <?php endif; ?>
                        <?php if ($license->requires_share_alike): ?>
                          <span class="badge bg-primary">Share Alike</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer bg-transparent">
                  <a href="<?php echo $license->uri; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-external-link-alt me-1"></i>View License
                  </a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Usage Guide -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Usage Guide'); ?></h5>
        </div>
        <div class="card-body">
          <h6>When to use Rights Statements:</h6>
          <ul>
            <li><strong>In Copyright statements</strong> - For works that are still under copyright protection</li>
            <li><strong>No Copyright statements</strong> - For works in the public domain or with specific use restrictions</li>
            <li><strong>Other statements</strong> - When copyright status is unclear or not yet evaluated</li>
          </ul>

          <h6>When to use Creative Commons:</h6>
          <ul>
            <li>When you (or the rights holder) want to grant specific permissions for reuse</li>
            <li>For works you own or have permission to license</li>
            <li>When you want to enable open access with clear terms</li>
          </ul>

          <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note:</strong> Rights Statements describe the copyright status of a work. 
            Creative Commons licenses are applied by the rights holder to grant permissions. 
            They serve different purposes and may be used together.
          </div>
        </div>
      </div>

    </main>
  </div>
</div>
