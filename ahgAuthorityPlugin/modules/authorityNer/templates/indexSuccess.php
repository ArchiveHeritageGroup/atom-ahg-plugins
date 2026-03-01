<?php decorate_with('layout_1col'); ?>

<?php
  $rawStats    = $sf_data->getRaw('stats');
  $rawStubs    = $sf_data->getRaw('stubs');
  $rawPending  = $sf_data->getRaw('pendingEntities');
  $rawFilters  = $sf_data->getRaw('filters');

  $stats   = is_array($rawStats) ? $rawStats : (array) $rawStats;
  $stubs   = is_array($rawStubs) ? $rawStubs : (array) $rawStubs;
  $pending = is_array($rawPending) ? $rawPending : (array) $rawPending;
  $filters = is_array($rawFilters) ? $rawFilters : [];

  $stubItems    = $stubs['data'] ?? [];
  $pendingItems = $pending['data'] ?? [];
  $byStatus     = $stats['by_status'] ?? [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-robot me-2"></i><?php echo __('NER-to-Authority Pipeline'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('NER Pipeline'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center border-info">
        <div class="card-body">
          <h3><?php echo number_format($stats['pending_entities'] ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('Pending Entities'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-warning">
        <div class="card-body">
          <h3><?php echo isset($byStatus['stub']) ? $byStatus['stub']->count : 0; ?></h3>
          <small class="text-muted"><?php echo __('Stubs'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-success">
        <div class="card-body">
          <h3><?php echo isset($byStatus['promoted']) ? $byStatus['promoted']->count : 0; ?></h3>
          <small class="text-muted"><?php echo __('Promoted'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-danger">
        <div class="card-body">
          <h3><?php echo isset($byStatus['rejected']) ? $byStatus['rejected']->count : 0; ?></h3>
          <small class="text-muted"><?php echo __('Rejected'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending NER Entities -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-magic me-1"></i><?php echo __('Pending NER Entities (not yet stubbed)'); ?>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Entity Value'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Confidence'); ?></th>
            <th><?php echo __('Source'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pendingItems)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3"><?php echo __('No pending NER entities. Requires ahgAIPlugin with NER extraction.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($pendingItems as $entity): ?>
              <?php $entity = (object) $entity; ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($entity->entity_value); ?></strong></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($entity->entity_type); ?></span></td>
                <td><?php echo number_format(($entity->confidence ?? 0) * 100, 1); ?>%</td>
                <td><small><?php echo htmlspecialchars($entity->source_title ?? ''); ?></small></td>
                <td>
                  <button class="btn btn-sm btn-success btn-create-stub" data-id="<?php echo $entity->id; ?>">
                    <i class="fas fa-user-plus me-1"></i><?php echo __('Create Stub'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Existing Stubs -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-user-clock me-1"></i><?php echo __('Authority Stubs'); ?>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Entity'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Actor'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($stubItems)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3"><?php echo __('No stubs created yet.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($stubItems as $stub): ?>
              <?php $stub = (object) $stub; ?>
              <?php
                $statusColors = ['stub' => 'warning', 'promoted' => 'success', 'rejected' => 'danger'];
              ?>
              <tr>
                <td><?php echo htmlspecialchars($stub->entity_value); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($stub->entity_type); ?></span></td>
                <td>
                  <?php if ($stub->slug): ?>
                    <a href="/<?php echo $stub->slug; ?>"><?php echo htmlspecialchars($stub->actor_name ?? ''); ?></a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($stub->actor_name ?? 'Actor #' . $stub->actor_id); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-<?php echo $statusColors[$stub->status] ?? 'secondary'; ?>">
                    <?php echo ucfirst($stub->status); ?>
                  </span>
                </td>
                <td>
                  <?php if ($stub->status === 'stub'): ?>
                    <button class="btn btn-sm btn-outline-success btn-promote" data-id="<?php echo $stub->id; ?>">
                      <i class="fas fa-arrow-up"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-reject" data-id="<?php echo $stub->id; ?>">
                      <i class="fas fa-ban"></i>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.querySelectorAll('.btn-create-stub').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var data = new FormData();
    data.append('ner_entity_id', this.dataset.id);
    fetch('<?php echo url_for('@ahg_authority_ner_create_stub'); ?>', { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); else alert(d.error || 'Failed'); });
  });
});

document.querySelectorAll('.btn-promote').forEach(function(btn) {
  btn.addEventListener('click', function() {
    fetch('/api/authority/ner/' + this.dataset.id + '/promote', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
  });
});

document.querySelectorAll('.btn-reject').forEach(function(btn) {
  btn.addEventListener('click', function() {
    fetch('/api/authority/ner/' + this.dataset.id + '/reject', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
  });
});
</script>
