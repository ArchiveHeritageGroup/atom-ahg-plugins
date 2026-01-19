<?php use_helper('Date'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>">Loans</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1><i class="fa-solid fa-chart-line me-2"></i>Loan Dashboard</h1>
    <p class="text-muted mb-0">Comprehensive overview of loan management activities</p>
  </div>
  <div class="btn-group">
    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'calendar']); ?>" class="btn btn-outline-primary">
      <i class="fa-solid fa-calendar"></i> Calendar
    </a>
    <div class="btn-group" role="group">
      <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fa-solid fa-download"></i> Export
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'loan', 'action' => 'export', 'type' => 'all_loans']); ?>">All Loans (CSV)</a></li>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'loan', 'action' => 'export', 'type' => 'active_loans']); ?>">Active Loans (CSV)</a></li>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'loan', 'action' => 'export', 'type' => 'overdue']); ?>">Overdue Loans (CSV)</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'loan', 'action' => 'annualReport', 'year' => date('Y')]); ?>">Annual Report <?php echo date('Y'); ?></a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card bg-primary text-white h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle mb-2 opacity-75">Active Loans</h6>
            <h2 class="card-title mb-0"><?php echo $summary['active_loans'] ?? 0; ?></h2>
          </div>
          <i class="fa-solid fa-exchange-alt fa-2x opacity-50"></i>
        </div>
        <div class="mt-2 small">
          <span class="me-3"><i class="fa-solid fa-arrow-right"></i> Out: <?php echo $summary['loans_out'] ?? 0; ?></span>
          <span><i class="fa-solid fa-arrow-left"></i> In: <?php echo $summary['loans_in'] ?? 0; ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card bg-danger text-white h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle mb-2 opacity-75">Overdue</h6>
            <h2 class="card-title mb-0"><?php echo $summary['overdue'] ?? 0; ?></h2>
          </div>
          <i class="fa-solid fa-exclamation-triangle fa-2x opacity-50"></i>
        </div>
        <div class="mt-2 small">
          Requires immediate attention
        </div>
      </div>
      <?php if (($summary['overdue'] ?? 0) > 0): ?>
        <div class="card-footer bg-transparent border-0">
          <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index', 'overdue' => 1]); ?>" class="text-white small">View overdue loans &rarr;</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card bg-warning text-dark h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle mb-2 opacity-75">Due This Month</h6>
            <h2 class="card-title mb-0"><?php echo $summary['due_this_month'] ?? 0; ?></h2>
          </div>
          <i class="fa-solid fa-clock fa-2x opacity-50"></i>
        </div>
        <div class="mt-2 small">
          Returns expected soon
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card bg-success text-white h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle mb-2 opacity-75">Insurance Exposure</h6>
            <h2 class="card-title mb-0">R <?php echo number_format($summary['total_insurance_value'] ?? 0, 0); ?></h2>
          </div>
          <i class="fa-solid fa-shield-alt fa-2x opacity-50"></i>
        </div>
        <div class="mt-2 small">
          <?php echo $summary['total_objects_on_loan'] ?? 0; ?> objects on loan
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Overdue & Due Soon -->
  <div class="col-lg-6">
    <?php if (!empty($overdue)): ?>
      <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fa-solid fa-exclamation-circle me-2"></i>Overdue Loans</h5>
          <span class="badge bg-white text-danger"><?php echo count($overdue); ?></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Loan #</th>
                <th>Partner</th>
                <th>Due Date</th>
                <th>Days Overdue</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($overdue, 0, 5) as $loan): ?>
                <tr class="table-danger">
                  <td>
                    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>">
                      <?php echo htmlspecialchars($loan['loan_number']); ?>
                    </a>
                  </td>
                  <td><?php echo htmlspecialchars($loan['partner_institution']); ?></td>
                  <td><?php echo $loan['end_date']; ?></td>
                  <td><span class="badge bg-danger"><?php echo $loan['days_overdue']; ?> days</span></td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>" class="btn btn-sm btn-outline-danger">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (count($overdue) > 5): ?>
          <div class="card-footer text-center">
            <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index', 'overdue' => 1]); ?>">View all <?php echo count($overdue); ?> overdue loans</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($due_soon)): ?>
      <div class="card border-warning">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fa-solid fa-clock me-2"></i>Due Within 30 Days</h5>
          <span class="badge bg-dark"><?php echo count($due_soon); ?></span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Loan #</th>
                <th>Partner</th>
                <th>Due Date</th>
                <th>Days Left</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($due_soon, 0, 5) as $loan): ?>
                <tr>
                  <td>
                    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>">
                      <?php echo htmlspecialchars($loan['loan_number']); ?>
                    </a>
                  </td>
                  <td><?php echo htmlspecialchars($loan['partner_institution']); ?></td>
                  <td><?php echo $loan['end_date']; ?></td>
                  <td>
                    <?php
                      $badgeClass = $loan['days_remaining'] <= 7 ? 'bg-danger' : ($loan['days_remaining'] <= 14 ? 'bg-warning text-dark' : 'bg-info');
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $loan['days_remaining']; ?> days</span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($overdue) && empty($due_soon)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fa-solid fa-check-circle text-success fa-3x mb-3"></i>
          <h5>All loans on track</h5>
          <p class="text-muted">No overdue or upcoming due loans</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Charts & Statistics -->
  <div class="col-lg-6">
    <!-- Loans by Purpose -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-chart-pie me-2"></i>Loans by Purpose</h5>
      </div>
      <div class="card-body">
        <?php if (!empty($by_purpose)): ?>
          <?php
            $purposes = [
              'exhibition' => ['label' => 'Exhibition', 'color' => 'primary'],
              'research' => ['label' => 'Research', 'color' => 'info'],
              'conservation' => ['label' => 'Conservation', 'color' => 'warning'],
              'photography' => ['label' => 'Photography', 'color' => 'success'],
              'education' => ['label' => 'Education', 'color' => 'secondary'],
              'filming' => ['label' => 'Filming', 'color' => 'danger'],
              'long_term' => ['label' => 'Long-term', 'color' => 'dark'],
              'other' => ['label' => 'Other', 'color' => 'light'],
            ];
            $total = array_sum($by_purpose);
          ?>
          <?php foreach ($by_purpose as $purpose => $count): ?>
            <?php $purposeInfo = $purposes[$purpose] ?? ['label' => ucfirst($purpose), 'color' => 'secondary']; ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span><?php echo $purposeInfo['label']; ?></span>
                <span class="text-muted"><?php echo $count; ?> (<?php echo round(($count / $total) * 100); ?>%)</span>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-<?php echo $purposeInfo['color']; ?>" style="width: <?php echo ($count / $total) * 100; ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-center">No loan data available</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Top Partners -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-building me-2"></i>Top Partner Institutions</h5>
      </div>
      <div class="list-group list-group-flush">
        <?php if (!empty($top_partners)): ?>
          <?php foreach (array_slice($top_partners, 0, 5) as $partner): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong><?php echo htmlspecialchars($partner['partner_institution']); ?></strong>
                <?php if ($partner['total_insurance']): ?>
                  <br><small class="text-muted">Insurance: R <?php echo number_format($partner['total_insurance'], 0); ?></small>
                <?php endif; ?>
              </div>
              <span class="badge bg-primary rounded-pill"><?php echo $partner['loan_count']; ?> loans</span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="list-group-item text-muted text-center">No partner data available</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recent Activity -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fa-solid fa-history me-2"></i>Recent Activity</h5>
        <small class="text-muted">Last 30 days</small>
      </div>
      <div class="card-body">
        <?php if (!empty($recent_activity)): ?>
          <div class="timeline">
            <?php foreach (array_slice($recent_activity, 0, 10) as $activity): ?>
              <div class="d-flex mb-3">
                <div class="flex-shrink-0 me-3">
                  <span class="badge bg-<?php echo $activity['color']; ?> rounded-circle p-2">
                    <i class="fa-solid fa-<?php echo $activity['icon']; ?>"></i>
                  </span>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between">
                    <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($activity['date'])); ?></small>
                  </div>
                  <p class="mb-0 text-muted small"><?php echo htmlspecialchars($activity['description']); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted text-center">No recent activity</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Monthly Trend Chart -->
<?php if (!empty($monthly_trend)): ?>
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-chart-bar me-2"></i>Monthly Loan Trend</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Month</th>
                <th class="text-center">Created</th>
                <th class="text-center">Started</th>
                <th class="text-center">Ended</th>
                <th class="text-center">Returned</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monthly_trend as $month): ?>
                <tr>
                  <td><?php echo $month['month']; ?></td>
                  <td class="text-center"><?php echo $month['created']; ?></td>
                  <td class="text-center"><?php echo $month['started']; ?></td>
                  <td class="text-center"><?php echo $month['ended']; ?></td>
                  <td class="text-center"><?php echo $month['returned']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
