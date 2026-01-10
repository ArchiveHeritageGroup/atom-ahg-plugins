<?php
$rsData = $sf_data->getRaw('rightsStatements');
$ccData = $sf_data->getRaw('ccLicenses');
$tkData = $sf_data->getRaw('tkLabels');
$statsData = $sf_data->getRaw('stats');

// Debug TK
if (!empty($tkData)) { 
    $first = reset($tkData);
    error_log("DEBUG_STATS: type=" . gettype($statsData) . " data=" . json_encode($statsData)); 
}
?>
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
          <?php if (!empty($rsData)): ?>
            <ul class="list-unstyled">
              <?php foreach ($rsData as $categoryOrItem): ?>
                <?php 
                $items = isset($categoryOrItem['labels']) ? $categoryOrItem['labels'] : (is_array($categoryOrItem) ? $categoryOrItem : [$categoryOrItem]);
                foreach ($items as $rs): 
                ?>
                  <li class="mb-2">
                    <a href="<?php echo htmlspecialchars($rs->uri ?? ''); ?>" target="_blank">
                      <?php echo htmlspecialchars($rs->name ?? $rs->code ?? 'Unknown'); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
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
          <?php if (!empty($ccData)): ?>
            <ul class="list-unstyled">
              <?php foreach ($ccData as $cc): ?>
                <li class="mb-2">
                  <a href="<?php echo htmlspecialchars($cc->uri ?? ''); ?>" target="_blank">
                    <?php echo htmlspecialchars($cc->name ?? ('CC ' . ($cc->code ?? ''))); ?>
                  </a>
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
          <?php if (!empty($tkData)): ?>
            <ul class="list-unstyled">
              <?php foreach ($tkData as $categoryOrItem): ?>
                <?php 
                $items = isset($categoryOrItem['labels']) ? $categoryOrItem['labels'] : (is_array($categoryOrItem) ? $categoryOrItem : [$categoryOrItem]);
                foreach ($items as $tk): 
                ?>
                  <li class="mb-2">
                    <?php if (!empty($tk->color)): ?>
                      <span style="display:inline-block;width:12px;height:12px;background:<?php echo htmlspecialchars($tk->color); ?>;border-radius:2px;margin-right:5px;"></span>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($tk->uri ?? ''); ?>" target="_blank">
                      <?php echo htmlspecialchars($tk->name ?? $tk->code ?? 'Unknown'); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
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
  <?php if ($statsData): ?>
  <div class="card mt-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><?php echo __('Rights Coverage Statistics'); ?></h5>
    </div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col"><h3 class="text-primary"><?php echo number_format($statsData["total_objects"] ?? $statsData["objectsWithRights"] ?? 0 ?? 0); ?></h3><small class="text-muted"><?php echo __('Total Objects'); ?></small></div>
        <div class="col"><h3 class="text-primary"><?php echo number_format($statsData["with_rights_statement"] ?? 0 ?? 0); ?></h3><small class="text-muted"><?php echo __('With Rights Statement'); ?></small></div>
        <div class="col"><h3 class="text-success"><?php echo number_format($statsData["with_cc_license"] ?? 0 ?? 0); ?></h3><small class="text-muted"><?php echo __('With CC License'); ?></small></div>
        <div class="col"><h3 class="text-info"><?php echo number_format($statsData["with_tk_labels"] ?? 0 ?? 0); ?></h3><small class="text-muted"><?php echo __('With TK Labels'); ?></small></div>
        <div class="col"><h3 class="text-warning"><?php echo number_format($statsData["active_embargoes"] ?? $statsData["activeEmbargoes"] ?? 0 ?? 0); ?></h3><small class="text-muted"><?php echo __('Active Embargoes'); ?></small></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>
