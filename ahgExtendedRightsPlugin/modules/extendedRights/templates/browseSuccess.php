<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-copyright me-2"></i><?php echo __('Browse Rights'); ?></h1>

  <div class="row">
    <!-- Rights Statements -->
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><?php echo __('RightsStatements.org'); ?></h5>
        </div>
        <div class="card-body">
          <p class="text-muted small"><?php echo __('Standardized rights statements for cultural heritage.'); ?></p>
          <?php if (!empty($rightsStatements) && count($rightsStatements) > 0): ?>
            <ul class="list-unstyled">
              <?php foreach ($rightsStatements as $rs): ?>
                <li class="mb-2">
                  <?php if (!empty($rs->uri)): ?>
                    <a href="<?php echo htmlspecialchars($rs->uri); ?>" target="_blank">
                      <?php echo htmlspecialchars($rs->name ?? $rs->code); ?>
                    </a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($rs->name ?? $rs->code); ?>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted"><?php echo __('No rights statements configured.'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Creative Commons -->
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><?php echo __('Creative Commons'); ?></h5>
        </div>
        <div class="card-body">
          <p class="text-muted small"><?php echo __('Open licensing for sharing and reuse.'); ?></p>
          <?php if (!empty($ccLicenses) && count($ccLicenses) > 0): ?>
            <ul class="list-unstyled">
              <?php foreach ($ccLicenses as $cc): ?>
                <li class="mb-2">
                  <?php if (!empty($cc->uri)): ?>
                    <a href="<?php echo htmlspecialchars($cc->uri); ?>" target="_blank">
                      <?php echo htmlspecialchars($cc->name ?? $cc->code); ?>
                    </a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($cc->name ?? $cc->code); ?>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted"><?php echo __('No Creative Commons licenses configured.'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TK Labels -->
    <div class="col-md-4 mb-4">
      <div class="card h-100">
        <div class="card-header" style="background-color: #1a4d2e; color: white;">
          <h5 class="mb-0"><?php echo __('Traditional Knowledge Labels'); ?></h5>
        </div>
        <div class="card-body">
          <p class="text-muted small"><?php echo __('Labels for Indigenous cultural heritage.'); ?></p>
          <?php if (!empty($tkLabels) && count($tkLabels) > 0): ?>
            <ul class="list-unstyled">
              <?php foreach ($tkLabels as $tk): ?>
                <li class="mb-2">
                  <?php if (!empty($tk->icon_url)): ?>
                    <img src="<?php echo htmlspecialchars($tk->icon_url); ?>" alt="" style="width: 20px; height: 20px;" class="me-1">
                  <?php endif; ?>
                  <?php if (!empty($tk->uri)): ?>
                    <a href="<?php echo htmlspecialchars($tk->uri); ?>" target="_blank">
                      <?php echo htmlspecialchars($tk->name ?? $tk->code); ?>
                    </a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($tk->name ?? $tk->code); ?>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-muted"><?php echo __('No TK Labels configured.'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics -->
  <?php if (isset($stats)): ?>
  <div class="card mt-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Rights Coverage Statistics'); ?></h5>
    </div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col">
          <h3><?php echo number_format($stats->total_objects ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('Total Objects'); ?></small>
        </div>
        <div class="col">
          <h3><?php echo number_format($stats->with_rights_statement ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('With Rights Statement'); ?></small>
        </div>
        <div class="col">
          <h3><?php echo number_format($stats->with_creative_commons ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('With CC License'); ?></small>
        </div>
        <div class="col">
          <h3><?php echo number_format($stats->with_tk_labels ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('With TK Labels'); ?></small>
        </div>
        <div class="col">
          <h3><?php echo number_format($stats->active_embargoes ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('Active Embargoes'); ?></small>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>
