<?php
$members = $sf_data->getRaw('members') ?: [];
$counts = $sf_data->getRaw('counts') ?: [];
$result = $sf_data->getRaw('result') ?: ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $sf_data->getRaw('filters') ?: [];
$unionUrl = url_for(['module' => 'federation', 'action' => 'union']);
?>
<div class="container-fluid py-3 federation-union">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-network-wired me-2"></i><?php echo __('Union catalogue') ?></h1>
    <a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Federation') ?></a>
  </div>

  <div class="row mb-4">
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Total holdings') ?></div><div class="display-6"><?php echo (int) ($counts['total'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Local') ?></div><div class="display-6"><?php echo (int) ($counts['local'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Harvested') ?></div><div class="display-6"><?php echo (int) ($counts['harvested'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Members') ?></div><div class="display-6"><?php echo (int) ($counts['members'] ?? 0) ?></div></div></div></div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Federation members') ?></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Member') ?></th><th><?php echo __('Type') ?></th><th><?php echo __('Records') ?></th><th><?php echo __('Last harvest') ?></th><th></th></tr></thead>
        <tbody>
        <?php if (empty($members)): ?>
          <tr><td colspan="5" class="text-muted p-3"><?php echo __('No federation members yet.') ?></td></tr>
        <?php else: foreach ($members as $m): ?>
          <tr>
            <td><?php echo esc_entities($m->name) ?><?php if (!$m->is_active): ?> <span class="badge bg-secondary"><?php echo __('inactive') ?></span><?php endif ?></td>
            <td><?php echo esc_entities((string) ($m->peer_type ?? '')) ?></td>
            <td><a href="<?php echo $unionUrl . '?peer_id=' . (int) $m->id ?>"><?php echo (int) $m->records ?></a></td>
            <td class="small text-muted"><?php echo esc_entities((string) ($m->last_harvest_at ?? '—')) ?> <?php echo $m->last_harvest_status ? '(' . esc_entities($m->last_harvest_status) . ')' : '' ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'federation', 'action' => 'harvest', 'peerId' => $m->id]) ?>"><?php echo __('Harvest') ?></a></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <form method="get" action="<?php echo $unionUrl ?>" class="row g-2 mb-3">
    <div class="col-md-5"><input type="text" class="form-control" name="q" value="<?php echo esc_entities((string) ($filters['q'] ?? '')) ?>" placeholder="<?php echo __('Search title…') ?>"></div>
    <div class="col-md-3">
      <select class="form-select" name="source">
        <option value=""><?php echo __('All sources') ?></option>
        <option value="local" <?php echo ($filters['source'] ?? '') === 'local' ? 'selected' : '' ?>><?php echo __('Local only') ?></option>
        <option value="harvested" <?php echo ($filters['source'] ?? '') === 'harvested' ? 'selected' : '' ?>><?php echo __('Harvested only') ?></option>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i><?php echo __('Search') ?></button></div>
    <div class="col-md-2 d-flex align-items-center"><span class="text-muted small"><?php echo (int) $result['total'] ?> <?php echo __('records') ?></span></div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover table-sm mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Title') ?></th><th><?php echo __('Source') ?></th><th></th></tr></thead>
        <tbody>
        <?php if (empty($result['items'])): ?>
          <tr><td colspan="3" class="text-muted p-3"><?php echo __('No records.') ?></td></tr>
        <?php else: foreach ($result['items'] as $r): ?>
          <tr>
            <td><?php echo esc_entities((string) ($r->title ?: ('#' . $r->id))) ?></td>
            <td><?php if ($r->source_peer): ?><span class="badge bg-info text-dark"><?php echo esc_entities($r->source_peer) ?></span><?php else: ?><span class="badge bg-light text-dark border"><?php echo __('Local') ?></span><?php endif ?></td>
            <td class="text-end"><?php if (!empty($r->slug)): ?><a class="btn btn-sm btn-outline-primary" href="/<?php echo esc_entities($r->slug) ?>" target="_blank"><?php echo __('Open') ?></a><?php endif ?></td>
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
          <a class="page-link" href="<?php echo $unionUrl . '?page=' . $i . '&q=' . urlencode((string) ($filters['q'] ?? '')) . '&source=' . urlencode((string) ($filters['source'] ?? '')) ?>"><?php echo $i ?></a>
        </li>
      <?php endfor ?>
    </ul></nav>
  <?php endif ?>
</div>
