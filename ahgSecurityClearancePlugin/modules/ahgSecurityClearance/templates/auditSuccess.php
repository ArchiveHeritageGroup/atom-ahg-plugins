<?php
/**
 * Security Audit Log Template.
 */
?>

<h1><i class="fas fa-history"></i> <?php echo __('Security Audit Log') ?></h1>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="">
      <div class="row">
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Date From') ?></label>
          <input type="date" name="date_from" class="form-control" value="<?php echo esc_entities($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Date To') ?></label>
          <input type="date" name="date_to" class="form-control" value="<?php echo esc_entities($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Action') ?></label>
          <select name="action" class="form-select">
            <option value=""><?php echo __('All') ?></option>
            <option value="view" <?php echo ('view' === ($filters['action'] ?? '')) ? 'selected' : '' ?>><?php echo __('View') ?></option>
            <option value="download" <?php echo ('download' === ($filters['action'] ?? '')) ? 'selected' : '' ?>><?php echo __('Download') ?></option>
            <option value="print" <?php echo ('print' === ($filters['action'] ?? '')) ? 'selected' : '' ?>><?php echo __('Print') ?></option>
            <option value="classify" <?php echo ('classify' === ($filters['action'] ?? '')) ? 'selected' : '' ?>><?php echo __('Classify') ?></option>
            <option value="access_denied" <?php echo ('access_denied' === ($filters['action'] ?? '')) ? 'selected' : '' ?>><?php echo __('Denied') ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Result') ?></label>
          <select name="access_granted" class="form-select">
            <option value=""><?php echo __('All') ?></option>
            <option value="granted" <?php echo ('granted' === ($filters['access_granted'] ?? '')) ? 'selected' : '' ?>><?php echo __('Granted') ?></option>
            <option value="denied" <?php echo ('denied' === ($filters['access_granted'] ?? '')) ? 'selected' : '' ?>><?php echo __('Denied') ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Classification') ?></label>
          <select name="classification_id" class="form-select">
            <option value=""><?php echo __('All') ?></option>
            <?php foreach ($classifications as $c): ?>
            <option value="<?php echo $c->id ?>" <?php echo ($c->id == ($filters['classification_id'] ?? '')) ? 'selected' : '' ?>>
              <?php echo esc_entities($c->name) ?>
            </option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100"><?php echo __('Filter') ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Export -->
<div class="mb-3">
  <a href="/security/report/export?date_from=<?php echo urlencode($filters['date_from'] ?? '') ?>&date_to=<?php echo urlencode($filters['date_to'] ?? '') ?>" 
     class="btn btn-success">
    <i class="fas fa-download"></i> <?php echo __('Export CSV') ?>
  </a>
</div>

<!-- Log Table -->
<div class="card">
  <div class="card-body">
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th><?php echo __('Date/Time') ?></th>
          <th><?php echo __('User') ?></th>
          <th><?php echo __('Object') ?></th>
          <th><?php echo __('Classification') ?></th>
          <th><?php echo __('Action') ?></th>
          <th><?php echo __('Result') ?></th>
          <th><?php echo __('IP Address') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr class="<?php echo $log->access_granted ? '' : 'table-danger' ?>">
          <td><?php echo date('Y-m-d H:i:s', strtotime($log->created_at)) ?></td>
          <td>
            <a href="/security/audit/user/<?php echo $log->user_id ?>"><?php echo esc_entities($log->username) ?></a>
          </td>
          <td>
            <?php if ($log->object_id): ?>
              <a href="/security/audit/object/<?php echo $log->object_id ?>"><?php echo esc_entities($log->object_title ?? 'ID: '.$log->object_id) ?></a>
            <?php else: ?>
              -
            <?php endif ?>
          </td>
          <td><?php echo esc_entities($log->classification_name ?? '-') ?></td>
          <td>
            <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $log->action)) ?></span>
          </td>
          <td>
            <?php if ($log->access_granted): ?>
              <span class="badge bg-success"><?php echo __('Granted') ?></span>
            <?php else: ?>
              <span class="badge bg-danger"><?php echo __('Denied') ?></span>
              <?php if ($log->denial_reason): ?>
                <br><small class="text-muted"><?php echo esc_entities($log->denial_reason) ?></small>
              <?php endif ?>
            <?php endif ?>
          </td>
          <td><small><?php echo esc_entities($log->ip_address ?? '') ?></small></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>

    <?php if (empty($logs)): ?>
    <p class="text-muted text-center"><?php echo __('No audit entries found.') ?></p>
    <?php endif ?>
  </div>
</div>
