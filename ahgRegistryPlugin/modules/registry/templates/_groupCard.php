<?php
  $gtBg = [
    'regional' => 'bg-primary',
    'topic' => 'bg-info text-dark',
    'software' => 'bg-success',
    'institutional' => 'bg-warning text-dark',
  ];
  $gt = $item->group_type ?? '';
  $gtClass = $gtBg[$gt] ?? 'bg-secondary';
?>
<div class="col">
  <div class="card h-100">
    <div class="card-body">
      <div class="d-flex align-items-start mb-2">
        <?php if (!empty($item->logo_path)): ?>
          <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 48px; height: 48px; object-fit: contain;">
        <?php else: ?>
          <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
            <i class="fas fa-users text-muted"></i>
          </div>
        <?php endif; ?>
        <div class="min-width-0">
          <h6 class="card-title mb-1">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $item->slug ?? '']); ?>" class="text-decoration-none stretched-link">
              <?php echo htmlspecialchars($item->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($item->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
            <?php endif; ?>
          </h6>
          <span class="badge <?php echo $gtClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $gt)), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>

      <?php if (!empty($item->is_virtual)): ?>
        <div class="small text-muted mb-2">
          <span class="badge bg-light text-dark border"><i class="fas fa-globe me-1"></i><?php echo __('Virtual'); ?></span>
        </div>
      <?php elseif (!empty($item->city) || !empty($item->country)): ?>
        <div class="small text-muted mb-2">
          <i class="fas fa-map-marker-alt me-1"></i>
          <?php echo htmlspecialchars(implode(', ', array_filter([$item->city ?? '', $item->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <div class="small text-muted mb-2">
        <i class="fas fa-users me-1"></i>
        <?php echo (int) ($item->member_count ?? 0); ?> <?php echo __('members'); ?>
      </div>

      <?php if (!empty($item->meeting_frequency)): ?>
      <div class="small text-muted mb-2">
        <i class="fas fa-calendar me-1"></i>
        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item->meeting_frequency)), ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <?php
        $desc = $item->description ?? '';
        if (!empty($desc)):
      ?>
      <p class="card-text small text-muted mb-0">
        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($desc), 0, 100, '...'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>
