<?php slot('title') ?>
  <?php echo __('Donor Management') ?>
<?php end_slot() ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-md-8">
      <h1 class="h3">
        <i class="fas fa-user-friends text-success me-2"></i>
        <?php echo __('Donor Management') ?>
      </h1>
      <p class="text-muted mb-0"><?php echo __('Overview of donors, agreements, and compliance') ?></p>
    </div>
    <div class="col-md-4">
        <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse']) ?>" class="btn btn-outline-success">
          <i class="fas fa-users me-1"></i><?php echo __('Browse Donors') ?>
        </a>
        <a href="<?php echo url_for(['module' => 'donor', 'action' => 'add']) ?>" class="btn btn-success">
          <i class="fas fa-plus me-1"></i><?php echo __('Add Donor') ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Statistics Cards Row 1 -->
  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card border-warning h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="text-muted mb-1"><?php echo __('TOTAL DONORS') ?></h6>
              <h2 class="mb-1"><?php echo number_format($statistics['total_donors']) ?></h2>
              <small class="text-success"><i class="fas fa-check-circle me-1"></i><?php echo $statistics['active_donors'] ?> active</small>
            </div>
            <div class="text-warning opacity-50">
              <i class="fas fa-users fa-3x"></i>
            </div>
          </div>
          <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse']) ?>" class="btn btn-warning w-100 mt-3">
            <?php echo __('View All Donors') ?>
          </a>
        </div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="card border-success h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="text-muted mb-1"><?php echo __('ACTIVE AGREEMENTS') ?></h6>
              <h2 class="mb-1"><?php echo number_format($statistics['active_agreements']) ?></h2>
              <small class="text-muted">of <?php echo $statistics['total_agreements'] ?> total</small>
            </div>
            <div class="text-success opacity-50">
              <i class="fas fa-file-contract fa-3x"></i>
            </div>
          </div>
          <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>" class="btn btn-success w-100 mt-3">
            <?php echo __('View Agreements') ?>
          </a>
        </div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="card border-danger h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="text-muted mb-1"><?php echo __('EXPIRING SOON') ?></h6>
              <h2 class="mb-1"><?php echo number_format($statistics['expiring_soon']) ?></h2>
              <small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>within 30 days</small>
            </div>
            <div class="text-danger opacity-50">
              <i class="fas fa-hourglass-half fa-3x"></i>
            </div>
          </div>
          <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse', 'expiring' => '30']) ?>" class="btn btn-danger w-100 mt-3">
            <?php echo __('View Expiring') ?>
          </a>
        </div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="card border-info h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>
              <h6 class="text-muted mb-1"><?php echo __('PENDING REMINDERS') ?></h6>
              <h2 class="mb-1"><?php echo number_format($alerts['pending_reminders']) ?></h2>
              <small class="text-info"><i class="fas fa-bell me-1"></i>action required</small>
            </div>
            <div class="text-info opacity-50">
              <i class="fas fa-bell fa-3x"></i>
            </div>
          </div>
          <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'reminders']) ?>" class="btn btn-info w-100 mt-3">
            <?php echo __('View Reminders') ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards Row 2 -->
  <div class="row mb-4">
    <div class="col mb-2">
      <div class="card text-center py-2 border-secondary">
        <div class="text-secondary fw-bold fs-4"><?php echo $statistics['draft_agreements'] ?></div>
        <small class="text-muted"><?php echo __('Drafts') ?></small>
      </div>
    </div>
    <div class="col mb-2">
      <div class="card text-center py-2 bg-info text-white">
        <div class="fw-bold fs-4"><?php echo $statistics['review_due'] ?></div>
        <small><?php echo __('Review Due') ?></small>
      </div>
    </div>
    <div class="col mb-2">
      <div class="card text-center py-2 bg-danger text-white">
        <div class="fw-bold fs-4"><?php echo $statistics['expired'] ?></div>
        <small><?php echo __('Expired') ?></small>
      </div>
    </div>
    <div class="col mb-2">
      <div class="card text-center py-2 border-dark">
        <div class="text-dark fw-bold fs-4"><?php echo $statistics['terminated'] ?></div>
        <small class="text-muted"><?php echo __('Terminated') ?></small>
      </div>
    </div>
    <div class="col mb-2">
      <div class="card text-center py-2 bg-warning">
        <div class="fw-bold fs-4"><?php echo $alerts['active_restrictions'] ?></div>
        <small><?php echo __('Active Restrictions') ?></small>
      </div>
    </div>
    <div class="col mb-2">
      <div class="card text-center py-2 border-secondary">
        <div class="text-secondary fw-bold fs-4"><?php echo $statistics['inactive_donors'] ?></div>
        <small class="text-muted"><?php echo __('Inactive Donors') ?></small>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
      
      <!-- Recent Agreements -->
      <div class="card mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i><?php echo __('Recent Agreements') ?></h5>
          <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>" class="btn btn-sm btn-light">
            <?php echo __('View All') ?>
          </a>
        </div>
        <div class="card-body p-0">
          <?php if (!empty($recentAgreements)): ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?php echo __('Agreement') ?></th>
                    <th><?php echo __('Type') ?></th>
                    <th><?php echo __('Donor') ?></th>
                    <th><?php echo __('Status') ?></th>
                    <th><?php echo __('Created') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentAgreements as $agreement): ?>
                    <tr>
                      <td>
                        <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement->id]) ?>">
                          <strong><?php echo esc_entities($agreement->agreement_number ?: $agreement->title) ?></strong>
                        </a>
                        <?php if ($agreement->title && $agreement->title != $agreement->agreement_number): ?>
                          <br><small class="text-muted"><?php echo esc_entities($agreement->title) ?></small>
                        <?php endif ?>
                      </td>
                      <td>
                        <?php if ($agreement->agreement_type_color): ?>
                          <span class="badge" style="background-color: <?php echo $agreement->agreement_type_color ?>">
                            <?php echo esc_entities($agreement->agreement_type_name) ?>
                          </span>
                        <?php else: ?>
                          <?php echo esc_entities($agreement->agreement_type_name) ?>
                        <?php endif ?>
                      </td>
                      <td><?php echo esc_entities($agreement->donor_name ?: '-') ?></td>
                      <td>
                        <?php
                        $statusClasses = [
                            'draft' => 'secondary',
                            'pending_review' => 'warning',
                            'pending_signature' => 'info',
                            'active' => 'success',
                            'expired' => 'danger',
                            'terminated' => 'dark'
                        ];
                        $statusClass = $statusClasses[$agreement->status] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $statusClass ?>">
                          <?php echo esc_entities(ucfirst(str_replace('_', ' ', $agreement->status))) ?>
                        </span>
                      </td>
                      <td><small><?php echo date('d M Y', strtotime($agreement->created_at)) ?></small></td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center text-muted py-4">
              <i class="fas fa-file-contract fa-3x mb-3 opacity-25"></i>
              <p><?php echo __('No agreements yet.') ?></p>
              <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'add']) ?>" class="btn btn-success">
                <i class="fas fa-plus me-1"></i><?php echo __('Create First Agreement') ?>
              </a>
            </div>
          <?php endif ?>
        </div>
      </div>
      
      <!-- Agreement Trends Chart -->
      <div class="card mb-4">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('Agreement Trends (12 Months)') ?></h5>
        </div>
        <div class="card-body">
          <canvas id="trendChart" height="100"></canvas>
        </div>
      </div>

      <!-- Expiring Soon & Pending Reminders -->
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-hourglass-half me-2"></i><?php echo __('Expiring Soon') ?></h5>
              <span class="badge bg-light text-danger"><?php echo count($expiringSoon) ?></span>
            </div>
            <div class="card-body p-0">
              <?php if (!empty($expiringSoon)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($expiringSoon as $agreement): ?>
                    <li class="list-group-item">
                      <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement->id]) ?>">
                        <?php echo esc_entities($agreement->agreement_number) ?>
                      </a>
                      <br>
                      <small class="text-muted"><?php echo esc_entities($agreement->donor_name) ?></small>
                      <span class="float-end badge bg-danger"><?php echo date('d M', strtotime($agreement->expiry_date)) ?></span>
                    </li>
                  <?php endforeach ?>
                </ul>
              <?php else: ?>
                <div class="text-center text-muted py-4"><?php echo __('No agreements expiring soon') ?></div>
              <?php endif ?>
            </div>
            <div class="card-footer">
              <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse', 'expiring' => '30']) ?>" class="btn btn-outline-danger w-100">
                <?php echo __('View All Expiring') ?>
              </a>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Pending Reminders') ?></h5>
              <span class="badge bg-dark"><?php echo count($pendingReminders) ?></span>
            </div>
            <div class="card-body p-0">
              <?php if (!empty($pendingReminders)): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($pendingReminders as $reminder): ?>
                    <li class="list-group-item">
                      <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $reminder->donor_agreement_id]) ?>">
                        <?php echo esc_entities($reminder->title) ?>
                      </a>
                      <br>
                      <small class="text-muted"><?php echo esc_entities($reminder->agreement_number) ?></small>
                      <span class="float-end badge bg-warning text-dark"><?php echo date('d M', strtotime($reminder->reminder_date)) ?></span>
                    </li>
                  <?php endforeach ?>
                </ul>
              <?php else: ?>
                <div class="text-center text-muted py-4"><?php echo __('No pending reminders') ?></div>
              <?php endif ?>
            </div>
            <div class="card-footer">
              <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'reminders']) ?>" class="btn btn-outline-warning w-100">
                <?php echo __('View All Reminders') ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-lg-4">
      
      <!-- Quick Actions -->
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="fas fa-bolt me-2"></i><?php echo __('Quick Actions') ?></h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="<?php echo url_for(['module' => 'donor', 'action' => 'add']) ?>" class="btn btn-outline-success">
              <i class="fas fa-user-plus me-2"></i><?php echo __('Add New Donor') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'add']) ?>" class="btn btn-outline-success">
              <i class="fas fa-file-medical me-2"></i><?php echo __('Create Agreement') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse']) ?>" class="btn btn-outline-success">
              <i class="fas fa-users me-2"></i><?php echo __('Browse Donors') ?>
            </a>
            <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse', 'status' => 'draft']) ?>" class="btn btn-outline-success">
              <i class="fas fa-edit me-2"></i><?php echo __('View Drafts') ?>
            </a>
          </div>
        </div>
      </div>
      
      <!-- Agreements by Type -->
      <div class="card mb-4">
        <div class="card-header bg-dark text-white">
          <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Agreements by Type') ?></h5>
        </div>
        <div class="card-body">
          <canvas id="typeChart" height="200"></canvas>
        </div>
      </div>
      
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="/plugins/ahgThemeB5Plugin/js/chart.min.js@3.9.1/dist/chart.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Trend Chart
    var trendData = <?php echo $trendChartData ?>;
    if (document.getElementById('trendChart')) {
        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [{
                    label: '<?php echo __('Agreements Created') ?>',
                    data: trendData.values,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
    
    // Type Chart
    var typeData = <?php echo $typeChartData ?>;
    if (document.getElementById('typeChart') && typeData.labels.length > 0) {
        new Chart(document.getElementById('typeChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: typeData.labels,
                datasets: [{
                    data: typeData.values,
                    backgroundColor: ['#198754', '#0d6efd', '#ffc107', '#dc3545', '#6c757d', '#17a2b8', '#6f42c1', '#fd7e14']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
