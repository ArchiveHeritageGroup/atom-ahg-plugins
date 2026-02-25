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
?>
<div class="col">
  <div class="card h-100">
    <div class="card-body">
      <div class="d-flex align-items-start mb-2">
        <?php if (!empty($item->logo_path)): ?>
          <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 48px; height: 48px; object-fit: contain;">
        <?php else: ?>
          <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
            <i class="fas fa-handshake text-muted"></i>
          </div>
        <?php endif; ?>
        <div class="min-width-0">
          <h6 class="card-title mb-1">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $item->slug ?? '']); ?>" class="text-decoration-none stretched-link">
              <?php echo htmlspecialchars($item->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($item->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
            <?php endif; ?>
          </h6>
          <?php foreach ($vtArr as $vt): ?><span class="badge <?php echo $vtBg[$vt] ?? 'bg-secondary'; ?> me-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $vt)), ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?>
        </div>
      </div>

      <?php if (!empty($item->country)): ?>
      <div class="small text-muted mb-2">
        <i class="fas fa-map-marker-alt me-1"></i>
        <?php echo htmlspecialchars(implode(', ', array_filter([$item->city ?? '', $item->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($item->client_count)): ?>
      <div class="small text-muted mb-2">
        <i class="fas fa-users me-1"></i>
        <?php echo (int) $item->client_count; ?> <?php echo __('clients'); ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($item->average_rating) && ($item->rating_count ?? 0) > 0): ?>
      <div class="mb-2">
        <?php include_partial('registry/ratingStars', ['rating' => (float) $item->average_rating, 'count' => (int) ($item->rating_count ?? 0)]); ?>
      </div>
      <?php endif; ?>

      <?php
        $desc = $item->short_description ?? ($item->description ?? '');
        if (!empty($desc)):
      ?>
      <p class="card-text small text-muted mb-0">
        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($desc), 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>
