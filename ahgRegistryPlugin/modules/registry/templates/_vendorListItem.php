<?php
  $vtBg = [
    'developer' => 'bg-primary',
    'integrator' => 'bg-success',
    'consultant' => 'bg-info text-dark',
    'service_provider' => 'bg-warning text-dark',
    'hosting' => 'bg-secondary',
    'hosting_provider' => 'bg-secondary',
    'digitization' => 'bg-dark',
    'training' => 'bg-danger',
    'reseller' => 'bg-info',
  ];
  $rawVt = sfOutputEscaper::unescape($item->vendor_type ?? '[]');
  $vtArr = is_string($rawVt) ? (json_decode($rawVt, true) ?: []) : (is_array($rawVt) ? $rawVt : []);

  $location = trim(implode(', ', array_filter([$item->city ?? '', $item->country ?? ''])));
  $desc = $item->short_description ?? ($item->description ?? '');
?>
<a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $item->slug ?? '']); ?>"
   class="list-group-item list-group-item-action">
  <div class="d-flex align-items-start">
    <?php if (!empty($item->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 56px; height: 56px; object-fit: contain;">
    <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
        <i class="fas fa-handshake text-muted"></i>
      </div>
    <?php endif; ?>

    <div class="flex-grow-1 min-width-0">
      <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-2">
        <h6 class="mb-0 text-body">
          <?php echo htmlspecialchars($item->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($item->is_verified)): ?>
            <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
          <?php endif; ?>
          <?php if (!empty($item->is_featured)): ?>
            <i class="fas fa-star text-warning ms-1" title="<?php echo __('Featured'); ?>"></i>
          <?php endif; ?>
        </h6>
        <?php if (!empty($item->client_count)): ?>
          <span class="small text-muted text-nowrap">
            <i class="fas fa-users me-1"></i><?php echo (int) $item->client_count; ?> <?php echo __('clients'); ?>
          </span>
        <?php endif; ?>
      </div>

      <div class="mb-1">
        <?php foreach ($vtArr as $vt): ?>
          <span class="badge <?php echo $vtBg[$vt] ?? 'bg-secondary'; ?> me-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $vt)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
        <?php if (!empty($item->average_rating) && ($item->rating_count ?? 0) > 0): ?>
          <span class="ms-1 small text-warning">
            <i class="fas fa-star"></i>
            <?php echo number_format((float) $item->average_rating, 1); ?>
            <span class="text-muted">(<?php echo (int) $item->rating_count; ?>)</span>
          </span>
        <?php endif; ?>
      </div>

      <?php if ('' !== $location): ?>
        <div class="small text-muted mb-1">
          <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($location, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($desc)): ?>
        <p class="mb-0 small text-muted">
          <?php echo htmlspecialchars(mb_strimwidth(strip_tags($desc), 0, 220, '...'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
      <?php endif; ?>
    </div>
  </div>
</a>
