<?php
$r = $sf_data->getRaw('report') ?: [];
$missThumb = $sf_data->getRaw('missingThumbnail') ?: [];
$missRef = $sf_data->getRaw('missingReference') ?: [];
$byType = $r['by_media_type'] ?? [];
function cov_bar($pct)
{
    $tone = $pct < 50 ? 'bg-danger' : ($pct < 85 ? 'bg-warning' : 'bg-success');
    echo '<div class="progress" style="height:18px"><div class="progress-bar ' . $tone . '" style="width:' . (int) $pct . '%">' . (int) $pct . '%</div></div>';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="mb-0"><i class="fas fa-images me-2"></i><?php echo __('Derivative coverage'); ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'mediaSettings', 'action' => 'index']); ?>"><i class="fas fa-arrow-left me-1"></i><?php echo __('Media settings'); ?></a>
</div>

<p class="text-muted"><?php echo __('Coverage of reference and thumbnail derivatives across object-linked digital objects. Use the lists below to find objects that need derivative regeneration.'); ?></p>

<div class="row mb-3">
  <div class="col-md-3 mb-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small text-uppercase"><?php echo __('Primary digital objects'); ?></div>
    <div class="display-6"><?php echo (int) ($r['primary_total'] ?? 0); ?></div>
    <?php if (!empty($r['external_uri'])): ?><div class="small text-muted"><?php echo (int) $r['external_uri']; ?> <?php echo __('external URIs'); ?></div><?php endif; ?>
  </div></div></div>
  <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small text-uppercase mb-1"><?php echo __('Reference images'); ?></div>
    <?php cov_bar($r['reference_pct'] ?? 0); ?>
    <div class="small text-muted mt-1"><?php echo (int) ($r['with_reference'] ?? 0); ?> <?php echo __('present'); ?> · <strong class="text-danger"><?php echo (int) ($r['missing_reference'] ?? 0); ?></strong> <?php echo __('missing'); ?></div>
  </div></div></div>
  <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small text-uppercase mb-1"><?php echo __('Thumbnails'); ?></div>
    <?php cov_bar($r['thumbnail_pct'] ?? 0); ?>
    <div class="small text-muted mt-1"><?php echo (int) ($r['with_thumbnail'] ?? 0); ?> <?php echo __('present'); ?> · <strong class="text-danger"><?php echo (int) ($r['missing_thumbnail'] ?? 0); ?></strong> <?php echo __('missing'); ?></div>
  </div></div></div>
</div>

<div class="card mb-4"><div class="card-body">
  <h6><?php echo __('By media type'); ?></h6>
  <?php if (empty($byType)): ?><span class="text-muted">—</span><?php else: foreach ($byType as $name => $count): ?>
    <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars((string) $name); ?>: <strong><?php echo (int) $count; ?></strong></span>
  <?php endforeach; endif; ?>
</div></div>

<div class="row">
  <?php
    $tables = [
        [__('Missing thumbnails'), $missThumb, 'fa-image'],
        [__('Missing reference images'), $missRef, 'fa-photo-film'],
    ];
    foreach ($tables as [$label, $rows, $icon]):
  ?>
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas <?php echo $icon; ?> me-2"></i><?php echo $label; ?> <span class="badge bg-secondary"><?php echo count($rows); ?></span></h5></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr><th><?php echo __('Object'); ?></th><th><?php echo __('File'); ?></th></tr></thead>
          <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="2" class="text-muted p-3"><?php echo __('None — full coverage.'); ?></td></tr>
          <?php else: foreach ($rows as $row): ?>
            <tr>
              <td><?php if (!empty($row['slug'])): ?><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $row['slug']]); ?>" target="_blank"><?php echo htmlspecialchars((string) ($row['title'] ?? $row['slug'])); ?></a><?php else: ?>#<?php echo (int) ($row['object_id'] ?? 0); ?><?php endif; ?></td>
              <td class="small text-muted"><?php echo htmlspecialchars((string) ($row['name'] ?? '')); ?> <?php echo $row['mime_type'] ? '(' . htmlspecialchars((string) $row['mime_type']) . ')' : ''; ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
