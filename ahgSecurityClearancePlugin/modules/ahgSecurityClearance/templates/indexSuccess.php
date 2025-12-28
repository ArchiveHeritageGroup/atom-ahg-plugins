<div class="container mt-4">
  <div class="row">
    <div class="col-12">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'user', 'action' => 'index', 'slug' => $sf_user->user->slug]); ?>">My Profile</a></li>
          <li class="breadcrumb-item active">Security Clearances</li>
        </ol>
      </nav>

      <?php if ($sf_user->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('success'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Stats Cards -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="card bg-primary text-white">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
              <small>Total Users</small>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-success text-white">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['with_clearance']; ?></h2>
              <small>With Clearance</small>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-danger text-white">
            <div class="card-body text-center">
              <h2 class="mb-0"><?php echo $stats['top_secret']; ?></h2>
              <small>Secret+ Level</small>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>User Security Clearances</h5>
          <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bulkGrantModal">
            <i class="fas fa-users me-1"></i> Bulk Grant
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" id="clearanceTable">
              <thead class="table-light">
                <tr>
                  <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                  <th>User</th>
                  <th>Clearance Level</th>
                  <th>Granted By</th>
                  <th>Granted</th>
                  <th>Expires</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                  <?php
                  $levelClass = 'secondary';
                  if ($user->classification_level !== null) {
                      if ($user->classification_level >= 4) $levelClass = 'danger';
                      elseif ($user->classification_level >= 2) $levelClass = 'warning';
                      else $levelClass = 'success';
                  }
                  ?>
                  <tr>
                    <td>
                      <input type="checkbox" class="form-check-input user-select" value="<?php echo $user->id; ?>">
                    </td>
                    <td>
                      <strong><?php echo htmlspecialchars($user->username); ?></strong>
                      <br><small class="text-muted"><?php echo htmlspecialchars($user->email); ?></small>
                      <?php if (!$user->active): ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($user->classification_name): ?>
                        <span class="badge bg-<?php echo $levelClass; ?> fs-6">
                          <?php echo htmlspecialchars($user->classification_name); ?>
                        </span>
                        <br><small class="text-muted">Level <?php echo $user->classification_level; ?></small>
                      <?php else: ?>
                        <span class="badge bg-secondary">No Clearance</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php echo $user->granted_by_name ? htmlspecialchars($user->granted_by_name) : '-'; ?>
                    </td>
                    <td>
                      <?php echo $user->granted_at ? date('M j, Y', strtotime($user->granted_at)) : '-'; ?>
                    </td>
                    <td>
                      <?php if ($user->expires_at): ?>
                        <?php 
                        $isExpired = strtotime($user->expires_at) < time();
                        ?>
                        <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>">
                          <?php echo date('M j, Y', strtotime($user->expires_at)); ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">Never</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <a href="<?php echo url_for('@security_clearance_view?id=' . $user->id); ?>" 
                           class="btn btn-outline-primary" title="View Details">
                          <i class="fas fa-eye"></i>
                        </a>
                        <button class="btn btn-outline-success" 
                                data-bs-toggle="modal" 
                                data-bs-target="#grantModal"
                                data-user-id="<?php echo $user->id; ?>"
                                data-username="<?php echo htmlspecialchars($user->username); ?>"
                                data-current="<?php echo $user->classification_id ?? 0; ?>"
                                title="Grant/Change Clearance">
                          <i class="fas fa-key"></i>
                        </button>
                        <?php if ($user->classification_id): ?>
                          <a href="<?php echo url_for('@security_clearance_revoke?id=' . $user->id); ?>" 
                             class="btn btn-outline-danger"
                             onclick="return confirm('Revoke clearance for <?php echo htmlspecialchars($user->username); ?>?');"
                             title="Revoke Clearance">
                            <i class="fas fa-ban"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Grant Modal -->
<div class="modal fade" id="grantModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@security_clearance_grant'); ?>">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-key me-2"></i>Grant Security Clearance</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="grantUserId">
          <p>Granting clearance to: <strong id="grantUsername"></strong></p>
          
          <div class="mb-3">
            <label for="classification_id" class="form-label">Clearance Level</label>
            <select class="form-select" name="classification_id" id="grantClassification" required>
              <option value="0">-- Revoke Clearance --</option>
              <?php foreach ($classifications as $c): ?>
                <option value="<?php echo $c->id; ?>">
                  <?php echo htmlspecialchars($c->name); ?> (Level <?php echo $c->level; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="expires_at" class="form-label">Expires (optional)</label>
            <input type="date" class="form-control" name="expires_at" id="grantExpires">
          </div>
          
          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Reason for granting clearance..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check me-1"></i> Grant Clearance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bulk Grant Modal -->
<div class="modal fade" id="bulkGrantModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@security_clearance_bulk_grant'); ?>" id="bulkGrantForm">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fas fa-users me-2"></i>Bulk Grant Clearance</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-1"></i>
            Select users in the table, then choose a clearance level to grant to all selected users.
          </div>
          
          <p><strong id="selectedCount">0</strong> users selected</p>
          <div id="selectedUsersContainer"></div>
          
          <div class="mb-3">
            <label for="bulkClassification" class="form-label">Clearance Level</label>
            <select class="form-select" name="classification_id" id="bulkClassification" required>
              <?php foreach ($classifications as $c): ?>
                <option value="<?php echo $c->id; ?>">
                  <?php echo htmlspecialchars($c->name); ?> (Level <?php echo $c->level; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="bulkNotes" class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Reason for bulk grant..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="bulkGrantBtn" disabled>
            <i class="fas fa-check me-1"></i> Grant to Selected
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Grant modal - populate data
  const grantModal = document.getElementById('grantModal');
  grantModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('grantUserId').value = button.dataset.userId;
    document.getElementById('grantUsername').textContent = button.dataset.username;
    document.getElementById('grantClassification').value = button.dataset.current || 0;
  });

  // Select all checkbox
  document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.user-select').forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
  });

  // Individual checkboxes
  document.querySelectorAll('.user-select').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
  });

  function updateSelectedCount() {
    const selected = document.querySelectorAll('.user-select:checked');
    document.getElementById('selectedCount').textContent = selected.length;
    document.getElementById('bulkGrantBtn').disabled = selected.length === 0;
    
    // Update hidden inputs
    const container = document.getElementById('selectedUsersContainer');
    container.innerHTML = '';
    selected.forEach(cb => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'user_ids[]';
      input.value = cb.value;
      container.appendChild(input);
    });
  }
});
</script>
