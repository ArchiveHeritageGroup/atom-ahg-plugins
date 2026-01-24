<?php use_helper('Text'); ?>

<div class="row">
  <div class="col-md-3">
    <div class="sidebar">
      <h4>Rights Vocabularies</h4>
      <ul class="nav flex-column">
        <li class="nav-item"><a href="#rights-statements">RightsStatements.org</a></li>
        <li class="nav-item"><a href="#creative-commons">Creative Commons</a></li>
        <li class="nav-item"><a href="#tk-labels">TK Labels</a></li>
      </ul>
    </div>
  </div>

  <div class="col-md-9">
    <h1>Extended Rights Management</h1>

    <?php if ($sf_user->hasFlash('notice')): ?>
      <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Rights Statements -->
      <div class="col-md-4 mb-4">
        <div class="card h-100" id="rights-statements">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">RightsStatements.org</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">Standardized rights statements for cultural heritage institutions.</p>
            <?php if (!empty($rightsStatements) && count($rightsStatements) > 0): ?>
              <ul class="list-unstyled">
                <?php foreach ($rightsStatements as $rs): ?>
                  <li class="mb-2">
                    <?php if (!empty($rs->icon_filename)): ?>
                      <img src="/plugins/ahgExtendedRightsPlugin/web/images/rights/<?php echo $rs->icon_filename; ?>" 
                           alt="" style="width: 20px; height: 20px;" class="me-1">
                    <?php endif; ?>
                    <?php if (!empty($rs->uri)): ?>
                      <a href="<?php echo $rs->uri; ?>" target="_blank" title="<?php echo esc_entities($rs->description ?? ''); ?>">
                        <?php echo esc_entities($rs->name ?? $rs->code); ?>
                      </a>
                    <?php else: ?>
                      <?php echo esc_entities($rs->name ?? $rs->code); ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No rights statements configured.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Creative Commons -->
      <div class="col-md-4 mb-4">
        <div class="card h-100" id="creative-commons">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0">Creative Commons</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">Open licensing for sharing and reuse.</p>
            <?php if (!empty($ccLicenses) && count($ccLicenses) > 0): ?>
              <ul class="list-unstyled">
                <?php foreach ($ccLicenses as $cc): ?>
                  <li class="mb-2">
                    <?php if (!empty($cc->icon_filename)): ?>
                      <img src="/plugins/ahgExtendedRightsPlugin/web/images/cc/<?php echo $cc->icon_filename; ?>" 
                           alt="" style="height: 20px;" class="me-1">
                    <?php endif; ?>
                    <?php if (!empty($cc->uri)): ?>
                      <a href="<?php echo $cc->uri; ?>" target="_blank" title="<?php echo esc_entities($cc->description ?? ''); ?>">
                        <?php echo esc_entities($cc->name ?? $cc->code); ?>
                      </a>
                    <?php else: ?>
                      <?php echo esc_entities($cc->name ?? $cc->code); ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No Creative Commons licenses configured.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- TK Labels -->
      <div class="col-md-4 mb-4">
        <div class="card h-100" id="tk-labels">
          <div class="card-header" style="background-color: #1a4d2e; color: white;">
            <h5 class="mb-0">Traditional Knowledge Labels</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">Labels for Indigenous cultural heritage.</p>
            <?php if (!empty($tkLabels) && count($tkLabels) > 0): ?>
              <ul class="list-unstyled">
                <?php foreach ($tkLabels as $tk): ?>
                  <li class="mb-2">
                    <?php if (!empty($tk->icon_filename)): ?>
                      <img src="/plugins/ahgExtendedRightsPlugin/web/images/tk/<?php echo $tk->icon_filename; ?>" 
                           alt="" style="width: 20px; height: 20px;" class="me-1">
                    <?php elseif (!empty($tk->icon_url)): ?>
                      <img src="<?php echo $tk->icon_url; ?>" 
                           alt="" style="width: 20px; height: 20px;" class="me-1">
                    <?php endif; ?>
                    <?php if (!empty($tk->uri)): ?>
                      <a href="<?php echo $tk->uri; ?>" target="_blank">
                        <?php echo esc_entities($tk->name ?? $tk->code); ?>
                      </a>
                    <?php else: ?>
                      <?php echo esc_entities($tk->name ?? $tk->code); ?>
                    <?php endif; ?>
                    <?php if (!empty($tk->category_name)): ?>
                      <small class="text-muted">(<?php echo esc_entities($tk->category_name); ?>)</small>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No TK Labels configured.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <?php if (isset($stats)): ?>
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">Rights Coverage Statistics</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col">
            <h3><?php echo number_format($stats->total_objects ?? 0); ?></h3>
            <small class="text-muted">Total Objects</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($stats->with_rights_statement ?? 0); ?></h3>
            <small class="text-muted">With Rights Statement</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($stats->with_creative_commons ?? 0); ?></h3>
            <small class="text-muted">With CC License</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($stats->with_tk_labels ?? 0); ?></h3>
            <small class="text-muted">With TK Labels</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($stats->active_embargoes ?? 0); ?></h3>
            <small class="text-muted">Active Embargoes</small>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Admin Actions -->
    <?php if ($sf_user->isAuthenticated() && $sf_user->hasCredential('administrator')): ?>
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">Administration</h5>
      </div>
      <div class="card-body">
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'batch']); ?>" class="btn btn-primary">
          <i class="fas fa-layer-group"></i> Batch Assign Rights
        </a>
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'embargoes']); ?>" class="btn btn-warning">
          <i class="fas fa-lock"></i> Manage Embargoes
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
