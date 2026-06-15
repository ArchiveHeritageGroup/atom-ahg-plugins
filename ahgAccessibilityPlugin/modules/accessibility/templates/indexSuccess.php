<?php
$counts = $sf_data->getRaw('counts') ?: ['total' => 0, 'with_alt' => 0, 'missing' => 0, 'percent' => 0];
$result = $sf_data->getRaw('result') ?: ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $sf_data->getRaw('filters') ?: [];
$indexUrl = url_for(['module' => 'accessibility', 'action' => 'index']);
$pct = (float) ($counts['percent'] ?? 0);
?>
<div class="container-fluid py-3 accessibility-altcoverage">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-universal-access me-2"></i><?php echo __('Image alternative text') ?></h1>
    <span class="text-muted small"><?php echo __('WCAG 1.1.1 — Non-text content') ?></span>
  </div>

  <div class="row mb-4">
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Images') ?></div><div class="display-6"><?php echo (int) $counts['total'] ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('With alt text') ?></div><div class="display-6 text-success"><?php echo (int) $counts['with_alt'] ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Missing') ?></div><div class="display-6 text-danger"><?php echo (int) $counts['missing'] ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Coverage') ?></div><div class="display-6"><?php echo $pct ?>%</div></div></div></div>
  </div>

  <div class="progress mb-4" style="height:8px;" role="progressbar" aria-label="<?php echo __('Alt text coverage') ?>" aria-valuenow="<?php echo $pct ?>" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar bg-success" style="width: <?php echo $pct ?>%;"></div>
  </div>

  <form method="get" action="<?php echo $indexUrl ?>" class="row g-2 mb-3">
    <div class="col-md-6"><input type="text" class="form-control" name="q" value="<?php echo esc_entities((string) ($filters['q'] ?? '')) ?>" placeholder="<?php echo __('Search title or filename…') ?>"></div>
    <div class="col-md-3 d-flex align-items-center">
      <div class="form-check"><input class="form-check-input" type="checkbox" name="missing" value="1" id="missingOnly" <?php echo !empty($filters['missing']) ? 'checked' : '' ?>><label class="form-check-label" for="missingOnly"><?php echo __('Missing only') ?></label></div>
    </div>
    <div class="col-md-3"><button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i><?php echo __('Filter') ?></button></div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0 align-middle">
        <thead class="table-light"><tr>
          <th><?php echo __('Record') ?></th>
          <th><?php echo __('Filename') ?></th>
          <th><?php echo __('Alt text (en)') ?></th>
          <th></th>
        </tr></thead>
        <tbody>
        <?php if (empty($result['items'])): ?>
          <tr><td colspan="4" class="text-muted p-3"><?php echo __('No images.') ?></td></tr>
        <?php else: foreach ($result['items'] as $r): ?>
          <tr>
            <td><?php if (!empty($r->slug)): ?><a href="/<?php echo esc_entities($r->slug) ?>" target="_blank"><?php echo esc_entities((string) ($r->title ?: ('#' . $r->object_id))) ?></a><?php else: ?><?php echo esc_entities((string) ($r->title ?: ('#' . $r->object_id))) ?><?php endif ?></td>
            <td class="small text-muted"><?php echo esc_entities((string) $r->name) ?></td>
            <td>
              <?php if ($r->alt_text !== null && $r->alt_text !== ''): ?>
                <span class="text-truncate d-inline-block" style="max-width:380px;" title="<?php echo esc_entities((string) $r->alt_text) ?>"><?php echo esc_entities((string) $r->alt_text) ?></span>
              <?php else: ?>
                <span class="badge bg-danger"><?php echo __('missing') ?></span>
              <?php endif ?>
            </td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'accessibility', 'action' => 'edit', 'id' => $r->id]) ?>"><i class="fas fa-pen me-1"></i><?php echo __('Edit') ?></a></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (($result['pages'] ?? 1) > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm">
      <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
        <li class="page-item <?php echo $i === $result['page'] ? 'active' : '' ?>">
          <a class="page-link" href="<?php echo $indexUrl . '?page=' . $i . '&q=' . urlencode((string) ($filters['q'] ?? '')) . ($filters['missing'] ? '&missing=1' : '') ?>"><?php echo $i ?></a>
        </li>
      <?php endfor ?>
    </ul></nav>
  <?php endif ?>
</div>
