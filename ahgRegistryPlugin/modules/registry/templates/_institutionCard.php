<?php
  $typeBg = [
    'archive' => 'bg-primary',
    'library' => 'bg-success',
    'museum' => 'bg-purple',
    'gallery' => 'bg-orange',
    'dam' => 'bg-teal',
    'heritage_site' => 'bg-warning text-dark',
    'research_centre' => 'bg-info text-dark',
    'government' => 'bg-secondary',
    'university' => 'bg-dark',
  ];
  $type = $item->institution_type ?? '';
  $typeClass = $typeBg[$type] ?? 'bg-secondary';
  $typeStyle = '';
  if ('museum' === $type) { $typeStyle = 'background-color:#6f42c1!important;'; }
  elseif ('gallery' === $type) { $typeStyle = 'background-color:#fd7e14!important;'; }
  elseif ('dam' === $type) { $typeStyle = 'background-color:#20c997!important;'; }
?>
<div class="col">
  <div class="card h-100">
    <div class="card-body">
      <div class="d-flex align-items-start mb-2">
        <?php if (!empty($item->logo_path)): ?>
          <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 48px; height: 48px; object-fit: contain;">
        <?php else: ?>
          <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
            <i class="fas fa-university text-muted"></i>
          </div>
        <?php endif; ?>
        <div class="min-width-0">
          <h6 class="card-title mb-1">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $item->slug ?? '']); ?>" class="text-decoration-none stretched-link">
              <?php echo htmlspecialchars($item->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($item->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
            <?php endif; ?>
          </h6>
          <span class="badge <?php echo $typeClass; ?>" style="<?php echo $typeStyle; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type)), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>

      <?php if (!empty($item->city) || !empty($item->country)): ?>
      <div class="small text-muted mb-2">
        <i class="fas fa-map-marker-alt me-1"></i>
        <?php echo htmlspecialchars(implode(', ', array_filter([$item->city ?? '', $item->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <?php
        $sectors = [];
        if (!empty($item->glam_sectors)) {
          $rawGlamSectors = sfOutputEscaper::unescape($item->glam_sectors);
          $sectors = is_string($rawGlamSectors) ? json_decode($rawGlamSectors, true) : (array) $rawGlamSectors;
        }
        if (is_array($sectors) && count($sectors) > 0):
      ?>
      <div class="mb-2">
        <?php foreach ($sectors as $sector): ?>
          <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($sector, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($item->instance_count)): ?>
      <div class="small text-muted mb-2">
        <i class="fas fa-server me-1"></i>
        <?php echo (int) $item->instance_count; ?> <?php echo __('instance(s)'); ?>
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
