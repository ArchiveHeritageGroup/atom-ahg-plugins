<?php
  $catBg = [
    'ams' => 'bg-primary', 'ims' => 'bg-primary',
    'dam' => 'bg-success', 'dams' => 'bg-success',
    'cms' => 'bg-info text-dark', 'glam' => 'bg-info text-dark',
    'ils' => 'bg-warning text-dark',
    'preservation' => 'bg-dark',
    'digitization' => 'bg-secondary',
    'discovery' => 'bg-danger',
    'utility' => 'bg-secondary', 'plugin' => 'bg-secondary',
    'theme' => 'bg-secondary', 'integration' => 'bg-secondary',
    'other' => 'bg-info text-dark',
  ];
  $catLabels = [
    'ams' => 'AMS', 'ims' => 'IMS', 'dam' => 'DAM', 'dams' => 'DAMS',
    'cms' => 'CMS', 'glam' => 'GLAM', 'preservation' => 'Preservation',
    'digitization' => 'Digitization', 'discovery' => 'Discovery',
    'utility' => 'Utility', 'plugin' => 'Plugin', 'theme' => 'Theme',
    'integration' => 'Integration', 'other' => 'Other',
  ];
  $rawCat = isset($item->category) ? \sfOutputEscaper::unescape($item->category) : '';
  $catList = [];
  if ('' !== (string) $rawCat) {
    $decoded = json_decode((string) $rawCat, true);
    $catList = is_array($decoded) ? $decoded : [(string) $rawCat];
  }

  $licenseBg = [
    'open_source' => 'bg-success', 'proprietary' => 'bg-danger',
    'freemium' => 'bg-warning text-dark', 'saas' => 'bg-primary',
  ];
  $lic = $item->license ?? '';
  $licClass = $licenseBg[strtolower(str_replace(' ', '_', $lic))] ?? 'bg-secondary';

  $pricingBg = [
    'free' => 'bg-success', 'subscription' => 'bg-primary',
    'one_time' => 'bg-info text-dark', 'per_user' => 'bg-warning text-dark',
    'custom' => 'bg-secondary',
  ];
  $pm = $item->pricing_model ?? '';
  $pmClass = $pricingBg[strtolower(str_replace(' ', '_', $pm))] ?? 'bg-secondary';

  $desc = $item->short_description ?? ($item->description ?? '');
?>
<a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $item->slug ?? '']); ?>"
   class="list-group-item list-group-item-action">
  <div class="d-flex align-items-start">
    <?php if (!empty($item->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 56px; height: 56px; object-fit: contain;">
    <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
        <i class="fas fa-code text-muted"></i>
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
        <div class="text-nowrap small">
          <?php if (!empty($item->latest_version)): ?>
            <span class="badge bg-secondary">v<?php echo htmlspecialchars($item->latest_version, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($pm)): ?>
            <span class="badge <?php echo $pmClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pm)), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="mb-1">
        <?php foreach ($catList as $catVal): ?>
          <?php $catKey = strtolower((string) $catVal); $catClass = $catBg[$catKey] ?? 'bg-info text-dark'; $catLabel = $catLabels[$catKey] ?? ucfirst((string) $catVal); ?>
          <span class="badge <?php echo $catClass; ?>"><?php echo htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
        <?php if (!empty($lic)): ?>
          <span class="badge <?php echo $licClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $lic)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if (!empty($item->average_rating) && ($item->rating_count ?? 0) > 0): ?>
          <span class="ms-2 small text-warning">
            <i class="fas fa-star"></i>
            <?php echo number_format((float) $item->average_rating, 1); ?>
            <span class="text-muted">(<?php echo (int) $item->rating_count; ?>)</span>
          </span>
        <?php endif; ?>
        <?php if (!empty($item->institution_count)): ?>
          <span class="ms-2 small text-muted">
            <i class="fas fa-building"></i> <?php echo (int) $item->institution_count; ?>
          </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($desc)): ?>
        <p class="mb-0 small text-muted">
          <?php echo htmlspecialchars(mb_strimwidth(strip_tags($desc), 0, 220, '...'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
      <?php endif; ?>
    </div>
  </div>
</a>
