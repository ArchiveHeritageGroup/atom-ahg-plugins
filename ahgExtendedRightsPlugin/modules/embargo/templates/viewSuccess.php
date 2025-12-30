<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
  <div class="sidebar-widget">
    <h3><?php echo __('Actions'); ?></h3>
    <ul class="nav nav-pills nav-stacked">
      <?php if ($embargo['status'] === 'active'): ?>
        <li>
          <a href="#" data-bs-toggle="modal" data-bs-target="#liftEmbargoModal" class="text-success">
            <i class="fas fa-unlock"></i> <?php echo __('Lift Embargo'); ?>
          </a>
        </li>
      <?php endif; ?>
      <li>
        <a href="<?php echo url_for(['module' => 'embargo', 'action' => 'edit', 'id' => $embargo['id']]); ?>">
          <i class="fas fa-edit"></i> <?php echo __('Edit'); ?>
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-widget">
    <h3><?php echo __('Status'); ?></h3>
    <?php 
    $statusColors = ['active' => 'danger', 'expired' => 'secondary', 'lifted' => 'success', 'pending' => 'warning'];
    $status = $embargo['status'];
    ?>
    <span class="badge bg-<?php echo $statusColors[$status] ?? 'secondary'; ?> fs-6">
      <?php echo ucfirst($status); ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Embargo Details'); ?></h1>
<?php end_slot(); ?>

<div class="card mb-4">
  <div class="card-header">
    <h4 class="mb-0"><?php echo __('Embargo Information'); ?></h4>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <dl>
          <dt><?php echo __('Object'); ?></dt>
          <dd><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'id' => $embargo['object_id']]); ?>">#<?php echo $embargo['object_id']; ?></a></dd>
          
          <dt><?php echo __('Type'); ?></dt>
          <dd><?php echo ucfirst(str_replace('_', ' ', $embargo['embargo_type'])); ?></dd>
          
          <dt><?php echo __('Start Date'); ?></dt>
          <dd><?php echo $embargo['start_date']; ?></dd>
          
          <dt><?php echo __('End Date'); ?></dt>
          <dd>
            <?php if ($embargo['is_perpetual']): ?>
              <span class="text-danger"><?php echo __('Perpetual'); ?></span>
            <?php elseif ($embargo['end_date']): ?>
              <?php echo $embargo['end_date']; ?>
            <?php else: ?>
              -
            <?php endif; ?>
          </dd>
        </dl>
      </div>
      <div class="col-md-6">
        <dl>
          <?php if (!empty($embargo['translations'][0]['reason'])): ?>
            <dt><?php echo __('Reason'); ?></dt>
            <dd><?php echo esc_entities($embargo['translations'][0]['reason']); ?></dd>
          <?php endif; ?>
          
          <?php if (!empty($embargo['translations'][0]['public_message'])): ?>
            <dt><?php echo __('Public Message'); ?></dt>
            <dd><?php echo esc_entities($embargo['translations'][0]['public_message']); ?></dd>
          <?php endif; ?>
          
          <?php if (!empty($embargo['translations'][0]['notes'])): ?>
            <dt><?php echo __('Internal Notes'); ?></dt>
            <dd><?php echo nl2br(esc_entities($embargo['translations'][0]['notes'])); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
    
    <?php if ($embargo['status'] === 'lifted'): ?>
      <div class="alert alert-success">
        <strong><?php echo __('This embargo was lifted'); ?></strong>
        <?php if ($embargo['lifted_at']): ?>
          <?php echo __('on %1%', ['%1%' => date('Y-m-d H:i', strtotime($embargo['lifted_at']))]); ?>
        <?php endif; ?>
        <?php if ($embargo['lift_reason']): ?>
          <br><?php echo __('Reason: %1%', ['%1%' => esc_entities($embargo['lift_reason'])]); ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Exceptions -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0"><?php echo __('Exceptions'); ?></h4>
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addExceptionModal">
      <i class="fas fa-plus"></i> <?php echo __('Add Exception'); ?>
    </button>
  </div>
  <div class="card-body">
    <?php if (!empty($embargo['exceptions'])): ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Details'); ?></th>
            <th><?php echo __('Valid Period'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($embargo['exceptions'] as $exception): ?>
            <tr>
              <td><?php echo ucfirst($exception['exception_type']); ?></td>
              <td>
                <?php if ($exception['exception_type'] === 'ip_range'): ?>
                  <?php echo esc_entities($exception['ip_range_start']); ?> - <?php echo esc_entities($exception['ip_range_end']); ?>
                <?php elseif ($exception['exception_id']): ?>
                  #<?php echo $exception['exception_id']; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($exception['valid_from'] || $exception['valid_until']): ?>
                  <?php echo $exception['valid_from'] ?? '...'; ?> - <?php echo $exception['valid_until'] ?? '...'; ?>
                <?php else: ?>
                  <?php echo __('Always'); ?>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" action="<?php echo url_for(['module' => 'embargo', 'action' => 'removeException', 'id' => $exception['id'], 'embargo_id' => $embargo['id']]); ?>" style="display:inline;">
                  <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo __('Are you sure?'); ?>');">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted"><?php echo __('No exceptions defined.'); ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- Audit Log -->
<div class="card">
  <div class="card-header">
    <h4 class="mb-0"><?php echo __('Audit Log'); ?></h4>
  </div>
  <div class="card-body">
    <?php if (!empty($embargo['audit_log'])): ?>
      <table class="table table-sm">
        <thead>
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Action'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('IP Address'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($embargo['audit_log'] as $log): ?>
            <tr>
              <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
              <td><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></td>
              <td><?php echo $log['user_id'] ? '#' . $log['user_id'] : '-'; ?></td>
              <td><?php echo $log['ip_address'] ?? '-'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted"><?php echo __('No audit log entries.'); ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- Lift Embargo Modal -->
<div class="modal fade" id="liftEmbargoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for(['module' => 'embargo', 'action' => 'lift', 'id' => $embargo['id']]); ?>">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Lift Embargo'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><?php echo __('Are you sure you want to lift this embargo? This action will be logged.'); ?></p>
          <div class="mb-3">
            <label for="lift_reason" class="form-label"><?php echo __('Reason for lifting'); ?></label>
            <textarea name="lift_reason" id="lift_reason" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-success"><?php echo __('Lift Embargo'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Exception Modal -->
<div class="modal fade" id="addExceptionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for(['module' => 'embargo', 'action' => 'addException', 'embargo_id' => $embargo['id']]); ?>">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Add Exception'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="exception_type" class="form-label"><?php echo __('Exception Type'); ?></label>
            <select name="exception_type" id="exception_type" class="form-select" required>
              <option value="user"><?php echo __('User'); ?></option>
              <option value="group"><?php echo __('Group'); ?></option>
              <option value="ip_range"><?php echo __('IP Range'); ?></option>
              <option value="repository"><?php echo __('Repository'); ?></option>
            </select>
          </div>
          
          <div class="mb-3" id="exception_id_field">
            <label for="exception_id" class="form-label"><?php echo __('User/Group/Repository ID'); ?></label>
            <input type="number" name="exception_id" id="exception_id" class="form-control">
          </div>
          
          <div id="ip_range_fields" style="display:none;">
            <div class="mb-3">
              <label for="ip_range_start" class="form-label"><?php echo __('IP Range Start'); ?></label>
              <input type="text" name="ip_range_start" id="ip_range_start" class="form-control" placeholder="192.168.1.1">
            </div>
            <div class="mb-3">
              <label for="ip_range_end" class="form-label"><?php echo __('IP Range End'); ?></label>
              <input type="text" name="ip_range_end" id="ip_range_end" class="form-control" placeholder="192.168.1.255">
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="valid_from" class="form-label"><?php echo __('Valid From'); ?></label>
              <input type="date" name="valid_from" id="valid_from" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label for="valid_until" class="form-label"><?php echo __('Valid Until'); ?></label>
              <input type="date" name="valid_until" id="valid_until" class="form-control">
            </div>
          </div>
          
          <div class="mb-3">
            <label for="exception_notes" class="form-label"><?php echo __('Notes'); ?></label>
            <textarea name="notes" id="exception_notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo __('Add Exception'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.getElementById('exception_type').addEventListener('change', function() {
  var isIpRange = this.value === 'ip_range';
  document.getElementById('exception_id_field').style.display = isIpRange ? 'none' : 'block';
  document.getElementById('ip_range_fields').style.display = isIpRange ? 'block' : 'none';
});
</script>
