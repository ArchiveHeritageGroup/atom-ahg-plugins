<?php
  $catBg = [
    'descriptive' => 'bg-primary',
    'preservation' => 'bg-success',
    'rights' => 'bg-warning text-dark',
    'accounting' => 'bg-info text-dark',
    'compliance' => 'bg-danger',
    'metadata' => 'bg-secondary',
    'interchange' => 'bg-dark',
    'sector' => 'bg-primary',
  ];
  $cat = $item->category ?? '';
  $catClass = $catBg[strtolower($cat)] ?? 'bg-secondary';
  $acronym = $item->acronym ?? '';
  $name = $item->name ?? '';
  $slug = $item->slug ?? '';
  $shortDesc = $item->short_description ?? '';
  $issuingBody = $item->issuing_body ?? '';
  $version = $item->current_version ?? '';
  $pubYear = $item->publication_year ?? '';
  $isFeatured = !empty($item->is_featured);
  $extensionCount = (int) ($item->extension_count ?? 0);

  $rawSectors = sfOutputEscaper::unescape($item->sector_applicability ?? '');
  $sectors = is_string($rawSectors) ? json_decode($rawSectors, true) : (is_array($rawSectors) ? $rawSectors : []);
  if (!is_array($sectors)) { $sectors = []; }
?>
<a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardView', 'slug' => $slug]); ?>"
   class="list-group-item list-group-item-action">
  <div class="d-flex align-items-start">
    <div class="me-3 flex-shrink-0 text-center" style="min-width: 64px;">
      <?php if (!empty($acronym)): ?>
        <div class="badge bg-secondary fs-6 px-2 py-1"><?php echo htmlspecialchars($acronym, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php else: ?>
        <i class="fas fa-balance-scale fa-2x text-muted"></i>
      <?php endif; ?>
    </div>

    <div class="flex-grow-1 min-width-0">
      <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-2">
        <h6 class="mb-0 text-body">
          <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($isFeatured): ?>
            <i class="fas fa-award text-warning ms-1" title="<?php echo __('Featured'); ?>"></i>
          <?php endif; ?>
        </h6>
        <div class="text-nowrap small">
          <?php if (!empty($version)): ?>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($pubYear)): ?>
            <span class="text-muted ms-1"><?php echo (int) $pubYear; ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="mb-1">
        <?php if ('' !== $cat): ?>
          <span class="badge <?php echo $catClass; ?>"><?php echo htmlspecialchars(ucfirst($cat), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if ($extensionCount > 0): ?>
          <span class="badge bg-success"><i class="fas fa-puzzle-piece me-1"></i>Heratio +<?php echo $extensionCount; ?></span>
        <?php endif; ?>
        <?php foreach ($sectors as $s): ?>
          <span class="badge bg-light text-dark border ms-1" style="font-size: 0.75em;"><?php echo htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($issuingBody)): ?>
        <div class="small text-muted mb-1">
          <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($issuingBody, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($shortDesc)): ?>
        <p class="mb-0 small text-muted">
          <?php echo htmlspecialchars(mb_strimwidth(strip_tags($shortDesc), 0, 220, '...'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
      <?php endif; ?>
    </div>
  </div>
</a>
