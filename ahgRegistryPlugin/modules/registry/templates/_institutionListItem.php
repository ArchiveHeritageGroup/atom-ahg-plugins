<?php
  $typeBg = [
    'archive' => 'bg-primary text-white',
    'library' => 'bg-success text-white',
    'museum' => 'bg-purple text-white',
    'gallery' => 'bg-orange text-white',
    'dam' => 'bg-teal text-white',
    'heritage_site' => 'bg-warning text-dark',
    'research_centre' => 'bg-info text-dark',
    'government' => 'bg-secondary text-white',
    'university' => 'bg-dark text-white',
    'academic' => 'bg-danger text-white',
    'community' => 'bg-success text-white',
    'private' => 'bg-secondary text-white',
  ];
  $type = $item->institution_type ?? '';
  $typeClass = $typeBg[$type] ?? 'bg-secondary';
  $typeStyle = '';
  if ('museum' === $type) { $typeStyle = 'background-color:#6f42c1!important;'; }
  elseif ('gallery' === $type) { $typeStyle = 'background-color:#fd7e14!important;'; }
  elseif ('dam' === $type) { $typeStyle = 'background-color:#20c997!important;'; }

  $sectors = [];
  if (!empty($item->glam_sectors)) {
    $rawGlamSectors = sfOutputEscaper::unescape($item->glam_sectors);
    $sectors = is_string($rawGlamSectors) ? json_decode($rawGlamSectors, true) : (array) $rawGlamSectors;
  }
  if (!is_array($sectors)) { $sectors = []; }

  $location = trim(implode(', ', array_filter([$item->city ?? '', $item->country ?? ''])));
  $desc = $item->short_description ?? ($item->description ?? '');
  $isMine = !empty($myInstitutionIds) && is_array($myInstitutionIds) && in_array($item->id, $myInstitutionIds);
?>
<a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $item->slug ?? '']); ?>"
   class="list-group-item list-group-item-action">
  <div class="d-flex align-items-start">
    <?php if (!empty($item->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 56px; height: 56px; object-fit: contain;">
    <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 56px; height: 56px;">
        <i class="fas fa-university text-muted"></i>
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
        <?php if (!empty($item->instance_count)): ?>
          <span class="small text-muted text-nowrap">
            <i class="fas fa-server me-1"></i><?php echo (int) $item->instance_count; ?> <?php echo __('instance(s)'); ?>
          </span>
        <?php endif; ?>
      </div>

      <div class="mb-1">
        <?php if (!empty($type)): ?>
          <span class="badge <?php echo $typeClass; ?>" style="<?php echo $typeStyle; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if ($isMine): ?>
          <span class="badge bg-info ms-1"><i class="fas fa-user me-1"></i><?php echo __('My Institution'); ?></span>
        <?php endif; ?>
        <?php foreach ($sectors as $sector): ?>
          <span class="badge bg-light text-dark border ms-1"><?php echo htmlspecialchars($sector, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
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
