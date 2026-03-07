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
  if (!is_array($sectors)) {
    $sectors = [];
  }
?>
<div class="col">
  <div class="card h-100">
    <div class="card-body">
      <div class="mb-2">
        <?php if (!empty($acronym)): ?>
          <h5 class="card-title mb-0">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardView', 'slug' => $slug]); ?>" class="text-decoration-none stretched-link">
              <?php echo htmlspecialchars($acronym, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h5>
          <small class="text-muted"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></small>
        <?php else: ?>
          <h6 class="card-title mb-0">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardView', 'slug' => $slug]); ?>" class="text-decoration-none stretched-link">
              <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h6>
        <?php endif; ?>
      </div>

      <div class="mb-2">
        <span class="badge <?php echo $catClass; ?>"><?php echo htmlspecialchars(ucfirst($cat), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($extensionCount > 0): ?>
          <span class="badge bg-success"><i class="fas fa-puzzle-piece me-1"></i>Heratio +<?php echo $extensionCount; ?></span>
        <?php endif; ?>
        <?php if ($isFeatured): ?>
          <span class="badge bg-warning text-dark"><i class="fas fa-award me-1"></i><?php echo __('Featured'); ?></span>
        <?php endif; ?>
      </div>

      <?php if (!empty($issuingBody)): ?>
      <div class="small text-muted mb-2">
        <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($issuingBody, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($shortDesc)): ?>
      <p class="card-text small text-muted mb-2">
        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($shortDesc), 0, 140, '...'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php endif; ?>

      <?php if (!empty($sectors)): ?>
      <div class="mb-2">
        <?php foreach ($sectors as $s): ?>
          <span class="badge bg-light text-dark border me-1" style="font-size: 0.7em;"><?php echo htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($version) || !empty($pubYear)): ?>
      <div class="small text-muted">
        <?php if (!empty($version)): ?>
          <span class="badge bg-secondary"><?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if (!empty($pubYear)): ?>
          <span class="ms-1"><?php echo (int) $pubYear; ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
