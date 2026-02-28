<?php
$entries = $sf_data->getRaw('entries') ?: [];
$filterOutcome = $sf_data->getRaw('filterOutcome');
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-book me-2"></i><?php echo __('Verification Ledger'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
  </div>

  <div class="alert alert-info py-2">
    <i class="fas fa-info-circle me-1"></i>
    <?php echo __('The integrity ledger is append-only. Entries are never updated or deleted.'); ?>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th><?php echo __('Run'); ?></th><th><?php echo __('Object'); ?></th>
              <th><?php echo __('Outcome'); ?></th><th><?php echo __('File'); ?></th>
              <th><?php echo __('Hash Match'); ?></th><th><?php echo __('Verified'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($entries)): ?>
              <tr><td colspan="7" class="text-muted text-center py-3"><?php echo __('No entries found.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($entries as $e): ?>
                <tr class="<?php echo $e->outcome !== 'pass' ? 'table-danger' : ''; ?>">
                  <td><?php echo $e->id; ?></td>
                  <td><?php echo $e->run_id ?? '—'; ?></td>
                  <td><?php echo $e->digital_object_id; ?></td>
                  <td><span class="badge <?php echo $e->outcome === 'pass' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $e->outcome; ?></span></td>
                  <td><?php echo htmlspecialchars(basename($e->file_path ?? '—')); ?></td>
                  <td>
                    <?php if ($e->hash_match === null): ?>—
                    <?php elseif ($e->hash_match): ?><i class="fas fa-check text-success"></i>
                    <?php else: ?><i class="fas fa-times text-danger"></i>
                    <?php endif; ?>
                  </td>
                  <td><?php echo $e->verified_at; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
