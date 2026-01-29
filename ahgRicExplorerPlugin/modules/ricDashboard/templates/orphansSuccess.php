<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-unlink"></i> <?php echo __('Orphaned Triples Management'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Orphaned Triples'); ?></li>
  </ol>
</nav>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?php echo $currentStatus === 'all' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'orphans', 'status' => 'all']); ?>">
      <?php echo __('All'); ?> <span class="badge bg-secondary"><?php echo array_sum($statusCounts); ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $currentStatus === 'detected' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'orphans', 'status' => 'detected']); ?>">
      <?php echo __('Detected'); ?> <span class="badge bg-warning text-dark"><?php echo $statusCounts['detected'] ?? 0; ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $currentStatus === 'reviewed' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'orphans', 'status' => 'reviewed']); ?>">
      <?php echo __('Reviewed'); ?> <span class="badge bg-info"><?php echo $statusCounts['reviewed'] ?? 0; ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $currentStatus === 'cleaned' ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'orphans', 'status' => 'cleaned']); ?>">
      <?php echo __('Cleaned'); ?> <span class="badge bg-success"><?php echo $statusCounts['cleaned'] ?? 0; ?></span>
    </a>
  </li>
</ul>

<div class="card">
  <div class="card-body">
    <?php if (empty($orphans)): ?>
      <div class="alert alert-success mb-0"><i class="fa fa-check-circle"></i> <?php echo __('No orphaned triples found.'); ?></div>
    <?php else: ?>
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th><?php echo __('RIC URI'); ?></th>
            <th><?php echo __('Expected Entity'); ?></th>
            <th><?php echo __('Detected'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orphans as $orphan): ?>
            <?php $key = $orphan->expected_entity_type . '/' . $orphan->expected_entity_id; ?>
            <tr>
              <td><code class="small"><?php echo htmlspecialchars(substr($orphan->ric_uri, -50)); ?></code></td>
              <td>
                <strong><?php echo htmlspecialchars($orphanNames[$key] ?? '(deleted)'); ?></strong>
                <br><small class="text-muted"><?php echo $orphan->expected_entity_type; ?> #<?php echo $orphan->expected_entity_id; ?></small>
              </td>
              <td class="small text-muted"><?php echo date('Y-m-d H:i', strtotime($orphan->detected_at)); ?></td>
              <td>
                <?php
                  $statusColors = ['detected' => 'warning', 'reviewed' => 'info', 'retained' => 'secondary', 'cleaned' => 'success'];
                ?>
                <span class="badge bg-<?php echo $statusColors[$orphan->status] ?? 'secondary'; ?>"><?php echo $orphan->status; ?></span>
              </td>
              <td>
                <?php if ($orphan->status === 'detected'): ?>
                  <button class="btn btn-sm btn-outline-info" onclick="updateOrphan(<?php echo $orphan->id; ?>, 'reviewed')"><i class="fa fa-eye"></i></button>
                  <button class="btn btn-sm btn-outline-secondary" onclick="updateOrphan(<?php echo $orphan->id; ?>, 'retained')"><i class="fa fa-lock"></i></button>
                  <button class="btn btn-sm btn-outline-danger" onclick="updateOrphan(<?php echo $orphan->id; ?>, 'cleaned')"><i class="fa fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
        <nav><ul class="pagination justify-content-center mb-0">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?php echo $p === $currentPage ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'orphans', 'status' => $currentStatus, 'page' => $p]); ?>"><?php echo $p; ?></a>
            </li>
          <?php endfor; ?>
        </ul></nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function updateOrphan(id, status) {
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxUpdateOrphan']); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + id + '&orphan_status=' + status
  }).then(r => r.json()).then(data => {
    if (data.success) location.reload();
    else alert('Error: ' + data.error);
  });
}
</script>
<?php end_slot(); ?>
