<?php
// Unescape arrays from Symfony output escaper
$stats = sfOutputEscaper::unescape($stats ?? []);
$expiringEmbargoes = sfOutputEscaper::unescape($expiringEmbargoes ?? []);
$reviewDue = sfOutputEscaper::unescape($reviewDue ?? []);
$formOptions = sfOutputEscaper::unescape($formOptions ?? []);
?>
<?php echo get_partial('header', ['title' => 'Rights Management']); ?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
      <div class="position-sticky pt-3">
        <h6 class="sidebar-heading px-3 mt-1 mb-2 text-muted">Rights Management</h6>
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link active" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'index']); ?>">
              <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoes']); ?>">
              <i class="fas fa-clock me-2"></i> Embargoes
              <?php if ($stats['active_embargoes'] > 0): ?>
                <span class="badge bg-warning ms-1"><?php echo $stats['active_embargoes']; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorks']); ?>">
              <i class="fas fa-search me-2"></i> Orphan Works
              <?php if ($stats['orphan_works_in_progress'] > 0): ?>
                <span class="badge bg-info ms-1"><?php echo $stats['orphan_works_in_progress']; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'tkLabels']); ?>">
              <i class="fas fa-tags me-2"></i> TK Labels
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'statements']); ?>">
              <i class="fas fa-balance-scale me-2"></i> Statements & Licenses
            </a>
          </li>
        </ul>

        <h6 class="sidebar-heading px-3 mt-4 mb-2 text-muted">Reports</h6>
        <ul class="nav flex-column mb-2">
          <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'embargoes']); ?>">
              <i class="fas fa-file-alt me-2"></i> Embargo Report
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'report', 'type' => 'tk_labels']); ?>">
              <i class="fas fa-file-alt me-2"></i> TK Label Report
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-gavel me-2"></i>Rights Management Dashboard</h1>
      </div>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-3 mb-3">
          <div class="card bg-primary text-white h-100">
            <div class="card-body">
              <h6 class="card-title">Total Rights Records</h6>
              <h2 class="mb-0"><?php echo number_format($stats['total_rights_records']); ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card bg-warning text-dark h-100">
            <div class="card-body">
              <h6 class="card-title">Active Embargoes</h6>
              <h2 class="mb-0"><?php echo number_format($stats['active_embargoes']); ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card bg-danger text-white h-100">
            <div class="card-body">
              <h6 class="card-title">Expiring Soon (30 days)</h6>
              <h2 class="mb-0"><?php echo number_format($stats['expiring_soon']); ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card bg-info text-white h-100">
            <div class="card-body">
              <h6 class="card-title">TK Label Assignments</h6>
              <h2 class="mb-0"><?php echo number_format($stats['tk_label_assignments']); ?></h2>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Expiring Embargoes Alert -->
        <?php if (count($expiringEmbargoes) > 0): ?>
        <div class="col-lg-6 mb-4">
          <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
              <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Embargoes Expiring Soon</h5>
            </div>
            <div class="card-body p-0">
              <table class="table table-sm table-hover mb-0">
                <thead>
                  <tr>
                    <th>Object</th>
                    <th>Expires</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($expiringEmbargoes as $embargo): ?>
                  <tr>
                    <td>
                      <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $embargo->slug]); ?>">
                        <?php echo esc_entities($embargo->object_title ?: 'ID: '.$embargo->object_id); ?>
                      </a>
                    </td>
                    <td>
                      <span class="badge bg-warning text-dark">
                        <?php echo date('d M Y', strtotime($embargo->end_date)); ?>
                      </span>
                    </td>
                    <td>
                      <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoEdit', 'id' => $embargo->id]); ?>" 
                         class="btn btn-sm btn-outline-primary">Review</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Review Due -->
        <?php if (count($reviewDue) > 0): ?>
        <div class="col-lg-6 mb-4">
          <div class="card border-info">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Reviews Due</h5>
            </div>
            <div class="card-body p-0">
              <table class="table table-sm table-hover mb-0">
                <thead>
                  <tr>
                    <th>Object</th>
                    <th>Review Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reviewDue as $embargo): ?>
                  <tr>
                    <td>
                      <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $embargo->slug]); ?>">
                        <?php echo esc_entities($embargo->object_title ?: 'ID: '.$embargo->object_id); ?>
                      </a>
                    </td>
                    <td><?php echo date('d M Y', strtotime($embargo->review_date)); ?></td>
                    <td>
                      <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoEdit', 'id' => $embargo->id]); ?>" 
                         class="btn btn-sm btn-outline-info">Review</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Rights by Basis -->
      <div class="row">
        <div class="col-lg-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">Rights by Basis</h5>
            </div>
            <div class="card-body">
              <?php if (!empty($stats['by_basis'])): ?>
                <canvas id="basisChart" height="200"></canvas>
              <?php else: ?>
                <p class="text-muted">No rights records yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">Rights Statements Used</h5>
            </div>
            <div class="card-body">
              <?php if (!empty($stats['by_rights_statement'])): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($stats['by_rights_statement'] as $code => $count): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php echo esc_entities($code); ?>
                    <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                  </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-muted">No rights statements assigned yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Quick Actions</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-2">
              <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoEdit']); ?>" 
                 class="btn btn-outline-primary w-100">
                <i class="fas fa-plus me-2"></i>Create New Embargo
              </a>
            </div>
            <div class="col-md-4 mb-2">
              <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorkEdit']); ?>" 
                 class="btn btn-outline-info w-100">
                <i class="fas fa-search me-2"></i>Start Orphan Work Search
              </a>
            </div>
            <div class="col-md-4 mb-2">
              <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'processExpired']); ?>" 
                 class="btn btn-outline-warning w-100"
                 onclick="return confirm('Process all expired embargoes?');">
                <i class="fas fa-clock me-2"></i>Process Expired Embargoes
              </a>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<?php if (!empty($stats['by_basis'])): ?>
<script src="/plugins/ahgCorePlugin/js/vendor/chart.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('basisChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats['by_basis'])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats['by_basis'])); ?>,
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
<?php endif; ?>
