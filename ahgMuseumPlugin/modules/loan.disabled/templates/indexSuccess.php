<?php use_helper('Date'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
    <li class="breadcrumb-item active">Loans</li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-9">
    <h1><i class="fa-solid fa-exchange-alt me-2"></i>Loan Management</h1>
    <p class="text-muted">Manage incoming and outgoing loans across your GLAM institution</p>

    <div class="card mb-4">
      <div class="card-header">
        <form method="get" action="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>" class="row g-2 align-items-center">
          <div class="col-auto">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search loans..." value="<?php echo $sf_request->getParameter('search'); ?>">
          </div>
          <div class="col-auto">
            <select name="type" class="form-select form-select-sm">
              <option value="">All Types</option>
              <option value="out" <?php echo $sf_request->getParameter('type') == 'out' ? 'selected' : ''; ?>>Loans Out</option>
              <option value="in" <?php echo $sf_request->getParameter('type') == 'in' ? 'selected' : ''; ?>>Loans In</option>
            </select>
          </div>
          <div class="col-auto">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Statuses</option>
              <option value="pending" <?php echo $sf_request->getParameter('status') == 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="approved" <?php echo $sf_request->getParameter('status') == 'approved' ? 'selected' : ''; ?>>Approved</option>
              <option value="active" <?php echo $sf_request->getParameter('status') == 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="returned" <?php echo $sf_request->getParameter('status') == 'returned' ? 'selected' : ''; ?>>Returned</option>
            </select>
          </div>
          <div class="col-auto">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="overdue" value="1" id="overdueCheck" <?php echo $sf_request->getParameter('overdue') ? 'checked' : ''; ?>>
              <label class="form-check-label" for="overdueCheck">Overdue only</label>
            </div>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
          </div>
          <div class="col-auto ms-auto">
            <div class="btn-group">
              <a href="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => 'out']); ?>" class="btn btn-success btn-sm">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> New Loan Out
              </a>
              <a href="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => 'in']); ?>" class="btn btn-info btn-sm">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> New Loan In
              </a>
            </div>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        <?php if (empty($loans)): ?>
          <div class="p-4 text-center text-muted">
            <i class="fa-solid fa-exchange-alt fa-3x mb-3"></i>
            <p>No loans found</p>
            <p class="small">Create a new loan to get started</p>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Loan #</th>
                <th>Type</th>
                <th>Partner Institution</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>End Date</th>
                <th>Objects</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($loans as $loan): ?>
                <?php
                  $isOverdue = !empty($loan['end_date']) && strtotime($loan['end_date']) < time() && empty($loan['return_date']) && empty($loan['workflow_complete']);
                  $rowClass = $isOverdue ? 'table-danger' : '';
                ?>
                <tr class="<?php echo $rowClass; ?>">
                  <td>
                    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>">
                      <strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong>
                    </a>
                    <?php if (!empty($loan['title'])): ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($loan['title']); ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($loan['loan_type'] === 'out'): ?>
                      <span class="badge bg-warning text-dark"><i class="fa-solid fa-arrow-right-from-bracket"></i> Out</span>
                    <?php else: ?>
                      <span class="badge bg-info"><i class="fa-solid fa-arrow-right-to-bracket"></i> In</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($loan['partner_institution'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($purposes[$loan['purpose']] ?? $loan['purpose']); ?></td>
                  <td>
                    <?php
                      $statusColors = [
                        'pending' => 'secondary',
                        'submitted' => 'info',
                        'approved' => 'primary',
                        'active' => 'success',
                        'returned' => 'dark',
                        'cancelled' => 'danger',
                      ];
                      $statusColor = $statusColors[$loan['current_state'] ?? 'pending'] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $statusColor; ?>">
                      <?php echo ucfirst($loan['current_state'] ?? 'Pending'); ?>
                    </span>
                    <?php if ($isOverdue): ?>
                      <span class="badge bg-danger">Overdue</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($loan['end_date'])): ?>
                      <?php echo date('Y-m-d', strtotime($loan['end_date'])); ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-secondary"><?php echo $loan['object_count'] ?? 0; ?></span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>" class="btn btn-outline-primary" title="View">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                      <a href="<?php echo url_for(['module' => 'loan', 'action' => 'edit', 'id' => $loan['id']]); ?>" class="btn btn-outline-secondary" title="Edit">
                        <i class="fa-solid fa-edit"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <?php if ($pages > 1): ?>
        <div class="card-footer">
          <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo;</a>
                </li>
              <?php endif; ?>
              <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($page < $pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page + 1; ?>">&raquo;</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-3">
    <!-- Statistics -->
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0"><i class="fa-solid fa-chart-pie me-2"></i>Statistics</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="d-flex justify-content-between mb-2">
            <span>Total Loans</span>
            <strong><?php echo $stats['total_loans'] ?? 0; ?></strong>
          </li>
          <li class="d-flex justify-content-between mb-2">
            <span>Active Loans Out</span>
            <strong class="text-warning"><?php echo $stats['active_loans_out'] ?? 0; ?></strong>
          </li>
          <li class="d-flex justify-content-between mb-2">
            <span>Active Loans In</span>
            <strong class="text-info"><?php echo $stats['active_loans_in'] ?? 0; ?></strong>
          </li>
          <li class="d-flex justify-content-between mb-2">
            <span>Overdue</span>
            <strong class="text-danger"><?php echo $stats['overdue'] ?? 0; ?></strong>
          </li>
          <li class="d-flex justify-content-between">
            <span>Due This Month</span>
            <strong><?php echo $stats['due_this_month'] ?? 0; ?></strong>
          </li>
        </ul>
        <?php if (!empty($stats['total_insurance_value'])): ?>
          <hr>
          <div class="d-flex justify-content-between">
            <span>Insurance Value</span>
            <strong>R <?php echo number_format($stats['total_insurance_value'], 2); ?></strong>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Overdue Loans Alert -->
    <?php if (!empty($overdue)): ?>
      <div class="card mb-3 border-danger">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0"><i class="fa-solid fa-exclamation-triangle me-2"></i>Overdue Loans</h5>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach (array_slice($overdue, 0, 5) as $loan): ?>
            <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>" class="list-group-item list-group-item-action list-group-item-danger">
              <div class="d-flex justify-content-between">
                <strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong>
                <small><?php echo date('Y-m-d', strtotime($loan['end_date'])); ?></small>
              </div>
              <small><?php echo htmlspecialchars($loan['partner_institution']); ?></small>
            </a>
          <?php endforeach; ?>
          <?php if (count($overdue) > 5): ?>
            <div class="list-group-item text-center">
              <a href="?overdue=1">View all <?php echo count($overdue); ?> overdue</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Due Soon -->
    <?php if (!empty($dueSoon)): ?>
      <div class="card mb-3 border-warning">
        <div class="card-header bg-warning text-dark">
          <h5 class="mb-0"><i class="fa-solid fa-clock me-2"></i>Due Within 30 Days</h5>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach (array_slice($dueSoon, 0, 5) as $loan): ?>
            <a href="<?php echo url_for(['module' => 'loan', 'action' => 'show', 'id' => $loan['id']]); ?>" class="list-group-item list-group-item-action">
              <div class="d-flex justify-content-between">
                <strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong>
                <small><?php echo date('Y-m-d', strtotime($loan['end_date'])); ?></small>
              </div>
              <small><?php echo htmlspecialchars($loan['partner_institution']); ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => 'out']); ?>" class="list-group-item list-group-item-action">
          <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> New Loan Out
        </a>
        <a href="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => 'in']); ?>" class="list-group-item list-group-item-action">
          <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> New Loan In
        </a>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>" class="list-group-item list-group-item-action">
          <i class="fa-solid fa-image me-2"></i> View Exhibitions
        </a>
      </div>
    </div>
  </div>
</div>
