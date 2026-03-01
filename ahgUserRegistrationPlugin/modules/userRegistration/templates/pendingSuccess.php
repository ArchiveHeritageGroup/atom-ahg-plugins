<?php decorate_with('layout_1col'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Registration Requests'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <ul class="nav nav-pills mb-3">
    <li class="nav-item">
      <a class="nav-link <?php echo empty($statusFilter) ? 'active' : ''; ?>"
         href="<?php echo url_for(['module' => 'userRegistration', 'action' => 'pending']); ?>">
        <?php echo __('All'); ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $statusFilter === 'verified' ? 'active' : ''; ?>"
         href="<?php echo url_for(['module' => 'userRegistration', 'action' => 'pending']); ?>?status=verified">
        <?php echo __('Awaiting Review'); ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>"
         href="<?php echo url_for(['module' => 'userRegistration', 'action' => 'pending']); ?>?status=approved">
        <?php echo __('Approved'); ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>"
         href="<?php echo url_for(['module' => 'userRegistration', 'action' => 'pending']); ?>?status=rejected">
        <?php echo __('Rejected'); ?>
      </a>
    </li>
  </ul>
<?php end_slot(); ?>

<?php if (empty($requests)): ?>
  <div class="alert alert-info">
    <?php echo __('No registration requests found.'); ?>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('Username'); ?></th>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('Reason'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Submitted'); ?></th>
          <th><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
          <tr id="reg-row-<?php echo $req->id; ?>">
            <td>
              <strong><?php echo esc_entities($req->full_name); ?></strong>
              <?php if ($req->research_interest): ?>
                <br><small class="text-muted"><?php echo esc_entities(mb_substr($req->research_interest, 0, 60)); ?></small>
              <?php endif; ?>
            </td>
            <td>
              <small><?php echo esc_entities($req->email); ?></small>
              <?php if ($req->email_verified_at): ?>
                <br><span class="badge bg-success"><?php echo __('Verified'); ?></span>
              <?php else: ?>
                <br><span class="badge bg-warning text-dark"><?php echo __('Unverified'); ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo esc_entities($req->username); ?></td>
            <td><small><?php echo esc_entities($req->institution ?: '-'); ?></small></td>
            <td><small><?php echo esc_entities(mb_substr($req->reason ?? '', 0, 80)); ?></small></td>
            <td>
              <?php
              $statusClass = match($req->status) {
                  'verified' => 'bg-primary',
                  'approved' => 'bg-success',
                  'rejected' => 'bg-danger',
                  'expired' => 'bg-secondary',
                  default => 'bg-warning text-dark',
              };
              ?>
              <span class="badge <?php echo $statusClass; ?>"><?php echo esc_entities($req->status); ?></span>
              <?php if ($req->admin_notes): ?>
                <br><small class="text-muted" title="<?php echo esc_entities($req->admin_notes); ?>">📝</small>
              <?php endif; ?>
            </td>
            <td><small><?php echo date('Y-m-d H:i', strtotime($req->created_at)); ?></small></td>
            <td>
              <?php if ($req->status === 'verified'): ?>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-success btn-approve"
                          data-id="<?php echo $req->id; ?>"
                          data-name="<?php echo esc_entities($req->full_name); ?>"
                          title="<?php echo __('Approve'); ?>">
                    ✓
                  </button>
                  <button type="button" class="btn btn-danger btn-reject"
                          data-id="<?php echo $req->id; ?>"
                          data-name="<?php echo esc_entities($req->full_name); ?>"
                          title="<?php echo __('Reject'); ?>">
                    ✕
                  </button>
                </div>
              <?php elseif ($req->status === 'pending'): ?>
                <small class="text-muted"><?php echo __('Awaiting email verification'); ?></small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Approve Registration'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><?php echo __('Approve registration for:'); ?> <strong id="approve-name"></strong></p>
        <input type="hidden" id="approve-id">

        <div class="mb-3">
          <label for="approve-group" class="form-label"><?php echo __('Assign Group/Role'); ?></label>
          <select id="approve-group" class="form-select">
            <?php foreach ($groups as $group): ?>
              <option value="<?php echo $group->id; ?>"
                <?php echo ($group->id == 102) ? 'selected' : ''; ?>>
                <?php echo esc_entities($group->name ?? "Group {$group->id}"); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="approve-notes" class="form-label"><?php echo __('Admin Notes'); ?></label>
          <textarea id="approve-notes" class="form-control" rows="2"
                    placeholder="<?php echo __('Optional notes'); ?>"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-success" id="confirm-approve"><?php echo __('Approve'); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Reject Registration'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><?php echo __('Reject registration for:'); ?> <strong id="reject-name"></strong></p>
        <input type="hidden" id="reject-id">

        <div class="mb-3">
          <label for="reject-notes" class="form-label"><?php echo __('Reason for Rejection'); ?></label>
          <textarea id="reject-notes" class="form-control" rows="3" required
                    placeholder="<?php echo __('Please provide a reason (sent to applicant via email)'); ?>"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-danger" id="confirm-reject"><?php echo __('Reject'); ?></button>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  var approveUrl = '<?php echo url_for(['module' => 'userRegistration', 'action' => 'approve']); ?>';
  var rejectUrl = '<?php echo url_for(['module' => 'userRegistration', 'action' => 'reject']); ?>';

  // Approve button click
  document.querySelectorAll('.btn-approve').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('approve-id').value = this.dataset.id;
      document.getElementById('approve-name').textContent = this.dataset.name;
      document.getElementById('approve-notes').value = '';
      new bootstrap.Modal(document.getElementById('approveModal')).show();
    });
  });

  // Reject button click
  document.querySelectorAll('.btn-reject').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('reject-id').value = this.dataset.id;
      document.getElementById('reject-name').textContent = this.dataset.name;
      document.getElementById('reject-notes').value = '';
      new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
  });

  // Confirm approve
  document.getElementById('confirm-approve').addEventListener('click', function() {
    var requestId = document.getElementById('approve-id').value;
    var groupId = document.getElementById('approve-group').value;
    var notes = document.getElementById('approve-notes').value;

    var formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('group_id', groupId);
    formData.append('admin_notes', notes);

    fetch(approveUrl, { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(result) {
        if (result.success) {
          var row = document.getElementById('reg-row-' + requestId);
          if (row) {
            row.querySelector('td:nth-child(6)').innerHTML = '<span class="badge bg-success">approved</span>';
            row.querySelector('td:last-child').innerHTML = '<small class="text-muted">Approved</small>';
          }
          bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();
        } else {
          alert('Error: ' + result.error);
        }
      })
      .catch(function(err) { alert('Network error: ' + err.message); });
  });

  // Confirm reject
  document.getElementById('confirm-reject').addEventListener('click', function() {
    var requestId = document.getElementById('reject-id').value;
    var notes = document.getElementById('reject-notes').value;

    if (!notes.trim()) {
      alert('Please provide a reason for rejection.');
      return;
    }

    var formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('admin_notes', notes);

    fetch(rejectUrl, { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(result) {
        if (result.success) {
          var row = document.getElementById('reg-row-' + requestId);
          if (row) {
            row.querySelector('td:nth-child(6)').innerHTML = '<span class="badge bg-danger">rejected</span>';
            row.querySelector('td:last-child').innerHTML = '<small class="text-muted">Rejected</small>';
          }
          bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
        } else {
          alert('Error: ' + result.error);
        }
      })
      .catch(function(err) { alert('Network error: ' + err.message); });
  });
})();
</script>
