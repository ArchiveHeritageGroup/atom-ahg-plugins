<?php
  $statusColors = [
    'online' => 'success',
    'offline' => 'danger',
    'maintenance' => 'warning',
    'decommissioned' => 'secondary',
  ];
?>
<?php if (!empty($instances)): ?>
<div class="list-group list-group-flush">
  <?php foreach ($instances as $inst): ?>
    <?php
      $sColor = $statusColors[$inst->status ?? 'offline'] ?? 'secondary';
      $statusLabel = ucfirst($inst->status ?? 'offline');
    ?>
    <div class="list-group-item px-0">
      <div class="d-flex align-items-start">
        <!-- Status indicator -->
        <div class="me-2 mt-1 flex-shrink-0">
          <span class="d-inline-block rounded-circle bg-<?php echo $sColor; ?>" style="width: 10px; height: 10px;" title="<?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>"></span>
        </div>

        <div class="flex-grow-1 min-width-0">
          <!-- Name and URL -->
          <strong class="small"><?php echo htmlspecialchars($inst->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
          <?php if (!empty($inst->url)): ?>
            <br><a href="<?php echo htmlspecialchars($inst->url, ENT_QUOTES, 'UTF-8'); ?>" class="small text-decoration-none" target="_blank" rel="noopener">
              <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $inst->url), ENT_QUOTES, 'UTF-8'); ?>
              <i class="fas fa-external-link-alt ms-1" style="font-size: 0.7em;"></i>
            </a>
          <?php endif; ?>

          <!-- Type badge + software -->
          <div class="mt-1">
            <?php if (!empty($inst->instance_type)): ?>
              <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $inst->instance_type)), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if (!empty($inst->software)): ?>
              <small class="text-muted">
                <?php echo htmlspecialchars($inst->software, ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($inst->software_version)): ?>
                  <span class="badge bg-secondary">v<?php echo htmlspecialchars($inst->software_version, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
              </small>
            <?php elseif (!empty($inst->software_version)): ?>
              <span class="badge bg-secondary">v<?php echo htmlspecialchars($inst->software_version, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>

          <!-- Sync status -->
          <?php if (!empty($inst->sync_enabled)): ?>
            <div class="mt-1">
              <small class="text-muted">
                <i class="fas fa-sync-alt me-1"></i>
                <?php if (!empty($inst->last_heartbeat_at)): ?>
                  <?php
                    $hbTime = strtotime($inst->last_heartbeat_at);
                    $diff = time() - $hbTime;
                    if ($diff < 60) {
                      $hbAgo = __('just now');
                    } elseif ($diff < 3600) {
                      $hbAgo = sprintf(__('%d min ago'), (int) floor($diff / 60));
                    } elseif ($diff < 86400) {
                      $hbAgo = sprintf(__('%d hours ago'), (int) floor($diff / 3600));
                    } else {
                      $hbAgo = sprintf(__('%d days ago'), (int) floor($diff / 86400));
                    }
                  ?>
                  <?php echo __('Last sync: %1%', ['%1%' => $hbAgo]); ?>
                <?php else: ?>
                  <?php echo __('Never synced'); ?>
                <?php endif; ?>
              </small>
            </div>
          <?php endif; ?>

          <!-- Technical details -->
          <div class="mt-1">
            <?php if (!empty($inst->os_environment)): ?>
              <small class="text-muted me-2"><i class="fas fa-desktop me-1"></i><?php echo htmlspecialchars($inst->os_environment, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
            <?php if (!empty($inst->hosting)): ?>
              <small class="text-muted me-2"><i class="fas fa-cloud me-1"></i><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $inst->hosting)), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
            <?php if (!empty($inst->descriptive_standard)): ?>
              <small class="text-muted me-2"><i class="fas fa-book me-1"></i><?php echo htmlspecialchars($inst->descriptive_standard, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
            <?php if (!empty($inst->storage_gb)): ?>
              <small class="text-muted"><i class="fas fa-hdd me-1"></i><?php echo number_format((float) $inst->storage_gb, 1); ?> GB</small>
            <?php endif; ?>
          </div>

          <!-- Description -->
          <?php if (!empty($inst->description)): ?>
            <div class="mt-1">
              <?php $rawDesc = sfOutputEscaper::unescape($inst->description); ?>
              <small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($rawDesc, 0, 200, '...'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
          <?php endif; ?>

          <!-- Record counts -->
          <?php if (!empty($inst->record_count) || !empty($inst->digital_object_count)): ?>
            <div class="mt-1">
              <?php if (!empty($inst->record_count)): ?>
                <small class="text-muted me-2"><i class="fas fa-database me-1"></i><?php echo number_format((int) $inst->record_count); ?> <?php echo __('records'); ?></small>
              <?php endif; ?>
              <?php if (!empty($inst->digital_object_count)): ?>
                <small class="text-muted"><i class="fas fa-file-image me-1"></i><?php echo number_format((int) $inst->digital_object_count); ?> <?php echo __('digital objects'); ?></small>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="ms-2 flex-shrink-0">
          <a href="/registry/instances/<?php echo (int) $inst->id; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
            <i class="fas fa-eye"></i>
          </a>
          <?php if (!empty($canEdit)): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstanceEdit', 'id' => (int) $inst->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit'); ?>">
            <i class="fas fa-edit"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-muted small mb-0"><?php echo __('No instances listed.'); ?></p>
<?php endif; ?>
