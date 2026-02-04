<?php use_helper('Text') ?>

<div class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><?php echo __('Contract Management') ?></h1>
    <a href="<?php echo url_for(['module' => 'contract', 'action' => 'add']) ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i> <?php echo __('New Contract') ?>
    </a>
  </div>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="get" class="row g-3">
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Search') ?></label>
          <input type="text" name="sq" class="form-control" value="<?php echo esc_entities($sf_request->getParameter('sq')) ?>" placeholder="<?php echo __('Contract #, title, counterparty...') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Type') ?></label>
          <select name="type" class="form-select">
            <option value=""><?php echo __('All Types') ?></option>
            <?php foreach ($types as $type): ?>
            <option value="<?php echo $type->id ?>" <?php echo $sf_request->getParameter('type') == $type->id ? 'selected' : '' ?>><?php echo esc_entities($type->name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Status') ?></label>
          <select name="status" class="form-select">
            <option value=""><?php echo __('All Statuses') ?></option>
            <?php foreach ($statuses as $key => $label): ?>
            <option value="<?php echo $key ?>" <?php echo $sf_request->getParameter('status') == $key ? 'selected' : '' ?>><?php echo __($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-secondary me-2"><?php echo __('Filter') ?></button>
          <a href="<?php echo url_for(['module' => 'contract', 'action' => 'browse']) ?>" class="btn btn-outline-secondary"><?php echo __('Clear') ?></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Contracts Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Contract #') ?></th>
            <th><?php echo __('Title') ?></th>
            <th><?php echo __('Type') ?></th>
            <th><?php echo __('Counterparty') ?></th>
            <th><?php echo __('Status') ?></th>
            <th><?php echo __('Effective') ?></th>
            <th><?php echo __('Expiry') ?></th>
            <th class="text-end"><?php echo __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($contracts)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4"><?php echo __('No contracts found') ?></td>
          </tr>
          <?php else: ?>
          <?php foreach ($contracts as $contract): ?>
          <tr>
            <td>
              <a href="<?php echo url_for(['module' => 'contract', 'action' => 'view', 'id' => $contract->id]) ?>" class="fw-medium">
                <?php echo esc_entities($contract->contract_number) ?>
              </a>
            </td>
            <td><?php echo esc_entities(truncate_text($contract->title, 50)) ?></td>
            <td>
              <span class="badge" style="background-color: <?php echo $contract->type_color ?>">
                <?php echo esc_entities($contract->contract_type_name) ?>
              </span>
            </td>
            <td><?php echo esc_entities($contract->counterparty_name) ?></td>
            <td>
              <?php
              $statusColors = [
                'draft' => 'secondary',
                'pending_review' => 'warning',
                'pending_signature' => 'info',
                'active' => 'success',
                'suspended' => 'warning',
                'expired' => 'danger',
                'terminated' => 'dark',
                'renewed' => 'primary'
              ];
              $color = $statusColors[$contract->status] ?? 'secondary';
              ?>
              <span class="badge bg-<?php echo $color ?>"><?php echo __(ucwords(str_replace('_', ' ', $contract->status))) ?></span>
            </td>
            <td><?php echo $contract->effective_date ? date('d M Y', strtotime($contract->effective_date)) : '-' ?></td>
            <td>
              <?php if ($contract->expiry_date): ?>
                <?php
                $expiry = strtotime($contract->expiry_date);
                $daysUntil = (int)(($expiry - time()) / 86400);
                $expiryClass = $daysUntil < 0 ? 'text-danger' : ($daysUntil < 30 ? 'text-warning' : '');
                ?>
                <span class="<?php echo $expiryClass ?>"><?php echo date('d M Y', $expiry) ?></span>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="<?php echo url_for(['module' => 'contract', 'action' => 'view', 'id' => $contract->id]) ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View') ?>">
                <i class="fas fa-eye"></i>
              </a>
              <a href="<?php echo url_for(['module' => 'contract', 'action' => 'edit', 'id' => $contract->id]) ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit') ?>">
                <i class="fas fa-edit"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Quick Stats -->
  <div class="row mt-4">
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3 class="mb-0"><?php echo count(array_filter($contracts, fn($c) => $c->status === 'active')) ?></h3>
          <small class="text-muted"><?php echo __('Active Contracts') ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3 class="mb-0"><?php echo count(array_filter($contracts, fn($c) => $c->status === 'pending_signature')) ?></h3>
          <small class="text-muted"><?php echo __('Pending Signature') ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <?php
          $expiringCount = count(array_filter($contracts, function($c) {
            if (!$c->expiry_date) return false;
            $days = (strtotime($c->expiry_date) - time()) / 86400;
            return $days > 0 && $days < 30;
          }));
          ?>
          <h3 class="mb-0 text-warning"><?php echo $expiringCount ?></h3>
          <small class="text-muted"><?php echo __('Expiring Soon') ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3 class="mb-0 text-danger"><?php echo count(array_filter($contracts, fn($c) => $c->status === 'expired')) ?></h3>
          <small class="text-muted"><?php echo __('Expired') ?></small>
        </div>
      </div>
    </div>
  </div>
</div>
