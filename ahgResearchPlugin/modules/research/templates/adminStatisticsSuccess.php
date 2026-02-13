<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Statistics</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-chart-bar text-primary me-2"></i>Research Statistics</h1>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
            </div>
            <div class="col-md-4 text-end">
                <a href="?date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary btn-sm">This Month</a>
                <a href="?date_from=<?php echo date('Y-01-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-secondary btn-sm">This Year</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['total_researchers'] ?? 0); ?></h3>
                        <small>Total Researchers</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['total_bookings'] ?? 0); ?></h3>
                        <small>Bookings</small>
                    </div>
                    <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
                        <small>Item Views</small>
                    </div>
                    <i class="fas fa-eye fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['total_citations'] ?? 0); ?></h3>
                        <small>Citations</small>
                    </div>
                    <i class="fas fa-quote-right fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Visualizations -->
<div class="row mb-4">
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('Registrations Over Time'); ?></h5></div>
      <div class="card-body"><canvas id="registrationsChart" height="250"></canvas></div>
    </div>
  </div>
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i><?php echo __('Bookings by Room'); ?></h5></div>
      <div class="card-body"><canvas id="bookingsChart" height="250"></canvas></div>
    </div>
  </div>
</div>

<div class="row">
    <!-- Most Viewed Items -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-eye me-2"></i>Most Viewed Items</h5></div>
            <div class="card-body p-0">
                <?php if (!empty($mostViewed)): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th class="text-end">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mostViewed as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($item->title ?? 'Untitled', 0, 40)); ?></td>
                                    <td class="text-end"><span class="badge bg-primary"><?php echo number_format($item->view_count); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center text-muted py-4">No data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Most Cited Items -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-quote-right me-2"></i>Most Cited Items</h5></div>
            <div class="card-body p-0">
                <?php if (!empty($mostCited)): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th class="text-end">Citations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mostCited as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($item->title ?? 'Untitled', 0, 40)); ?></td>
                                    <td class="text-end"><span class="badge bg-warning text-dark"><?php echo number_format($item->citation_count); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center text-muted py-4">No data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Active Researchers -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-users me-2"></i>Most Active Researchers</h5></div>
            <div class="card-body p-0">
                <?php if (!empty($activeResearchers)): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Researcher</th>
                                <th>Institution</th>
                                <th class="text-center">Views</th>
                                <th class="text-center">Citations</th>
                                <th class="text-center">Bookings</th>
                                <th class="text-center">Collections</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeResearchers as $r): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewResearcher', 'id' => $r->id]); ?>">
                                            <?php echo htmlspecialchars($r->first_name . ' ' . $r->last_name); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($r->institution ?? '-'); ?></td>
                                    <td class="text-center"><?php echo number_format($r->view_count ?? 0); ?></td>
                                    <td class="text-center"><?php echo number_format($r->citation_count ?? 0); ?></td>
                                    <td class="text-center"><?php echo number_format($r->booking_count ?? 0); ?></td>
                                    <td class="text-center"><?php echo number_format($r->collection_count ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center text-muted py-4">No data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Researcher Types</h6></div>
            <ul class="list-group list-group-flush">
                <?php if (!empty($stats['by_type'])): ?>
                    <?php foreach ($stats['by_type'] as $type): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?php echo htmlspecialchars($type->name ?? 'Unspecified'); ?>
                            <span class="badge bg-secondary"><?php echo $type->count; ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item text-muted">No data</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Projects by Status</h6></div>
            <ul class="list-group list-group-flush">
                <?php if (!empty($stats['projects_by_status'])): ?>
                    <?php foreach ($stats['projects_by_status'] as $status): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?php echo ucfirst($status->status); ?>
                            <span class="badge bg-secondary"><?php echo $status->count; ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item text-muted">No data</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Reproduction Requests</h6></div>
            <ul class="list-group list-group-flush">
                <?php if (!empty($stats['reproductions_by_status'])): ?>
                    <?php foreach ($stats['reproductions_by_status'] as $status): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <?php echo ucfirst(str_replace('_', ' ', $status->status)); ?>
                            <span class="badge bg-secondary"><?php echo $status->count; ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item text-muted">No data</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  <?php
  // Prepare chart data from visualization endpoint
  $vizData = isset($vizData) && is_array($vizData) ? $vizData : (isset($vizData) && method_exists($vizData, 'getRawValue') ? $vizData->getRawValue() : []);
  $regLabels = [];
  $regData = [];
  if (!empty($vizData['registrations'])) {
    foreach ($vizData['registrations'] as $r) { $regLabels[] = $r->period ?? ''; $regData[] = (int)($r->count ?? 0); }
  }
  $roomLabels = [];
  $roomData = [];
  if (!empty($vizData['bookings_by_room'])) {
    foreach ($vizData['bookings_by_room'] as $r) { $roomLabels[] = $r->room_name ?? ''; $roomData[] = (int)($r->count ?? 0); }
  }
  ?>

  // Registrations Over Time
  var regCtx = document.getElementById('registrationsChart');
  if (regCtx) {
    new Chart(regCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($regLabels); ?>,
        datasets: [{
          label: '<?php echo __("Registrations"); ?>',
          data: <?php echo json_encode($regData); ?>,
          borderColor: '#0d6efd',
          backgroundColor: 'rgba(13,110,253,0.1)',
          fill: true,
          tension: 0.3
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
  }

  // Bookings by Room
  var roomCtx = document.getElementById('bookingsChart');
  if (roomCtx) {
    new Chart(roomCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($roomLabels); ?>,
        datasets: [{
          label: '<?php echo __("Bookings"); ?>',
          data: <?php echo json_encode($roomData); ?>,
          backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14']
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
  }
});
</script>
