<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>"><?php echo __('Home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'donor', 'action' => 'dashboard']) ?>"><?php echo __('Donor Dashboard') ?></a></li>
          <li class="breadcrumb-item active"><?php echo __('Agreements') ?></li>
        </ol>
      </nav>
      <h1 class="h3 mb-0">
        <i class="fas fa-file-contract text-primary me-2"></i>
        <?php echo __('Donor Agreements') ?>
        <span class="badge bg-secondary ms-2"><?php echo number_format($result['total'] ?? 0) ?></span>
      </h1>
    </div>
    <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'add']) ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i> <?php echo __('New Agreement') ?>
    </a>
  </div>

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Search') ?></label>
            <input type="text" name="q" class="form-control" placeholder="<?php echo __('Agreement #, title...') ?>" value="<?php echo esc_entities($filters['search'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label"><?php echo __('Status') ?></label>
            <select name="status" class="form-select">
              <option value=""><?php echo __('All') ?></option>
              <?php foreach ($statuses as $key => $label): ?>
                <option value="<?php echo $key ?>" <?php echo ($filters['status'] ?? '') === $key ? 'selected' : '' ?>><?php echo esc_entities($label) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label"><?php echo __('Type') ?></label>
            <select name="type" class="form-select">
              <option value=""><?php echo __('All') ?></option>
              <?php foreach ($types as $type): ?>
                <option value="<?php echo $type->id ?>" <?php echo ($filters['type'] ?? '') == $type->id ? 'selected' : '' ?>><?php echo esc_entities($type->name) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label"><?php echo __('Expiring') ?></label>
            <select name="expiring" class="form-select">
              <option value=""><?php echo __('Any') ?></option>
              <option value="7" <?php echo ($filters['expiring'] ?? '') == '7' ? 'selected' : '' ?>><?php echo __('Within 7 days') ?></option>
              <option value="30" <?php echo ($filters['expiring'] ?? '') == '30' ? 'selected' : '' ?>><?php echo __('Within 30 days') ?></option>
              <option value="90" <?php echo ($filters['expiring'] ?? '') == '90' ? 'selected' : '' ?>><?php echo __('Within 90 days') ?></option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> <?php echo __('Filter') ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Results -->
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Agreement #') ?></th>
              <th><?php echo __('Title') ?></th>
              <th><?php echo __('Donor') ?></th>
              <th><?php echo __('Type') ?></th>
              <th><?php echo __('Status') ?></th>
              <th><?php echo __('Agreement Date') ?></th>
              <th><?php echo __('Expiry') ?></th>
              <th class="text-end"><?php echo __('Actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($agreements)): ?>
              <?php foreach ($agreements as $agreement): ?>
              <tr>
                <td>
                  <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement->id]) ?>" class="fw-bold">
                    <?php echo esc_entities($agreement->agreement_number) ?>
                  </a>
                </td>
                <td><?php echo esc_entities($agreement->title ?? '—') ?></td>
                <td>
                  <?php if ($agreement->donor_name): ?>
                    <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $agreement->donor_slug]) ?>"><?php echo esc_entities($agreement->donor_name) ?></a>
                  <?php else: ?>—<?php endif ?>
                </td>
                <td><small><?php echo esc_entities($agreement->type_name ?? '—') ?></small></td>
                <td>
                  <?php
                  $statusColors = ['draft' => 'secondary', 'active' => 'success', 'expired' => 'danger', 'terminated' => 'dark', 'pending_approval' => 'warning'];
                  $color = $statusColors[$agreement->status] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?php echo $color ?>"><?php echo ucfirst(str_replace('_', ' ', $agreement->status)) ?></span>
                </td>
                <td><?php echo $agreement->agreement_date ? format_date($agreement->agreement_date, 'd') : '—' ?></td>
                <td>
                  <?php if ($agreement->expiry_date): ?>
                    <?php
                    $daysLeft = (strtotime($agreement->expiry_date) - time()) / 86400;
                    $textClass = $daysLeft < 30 ? 'text-danger fw-bold' : ($daysLeft < 90 ? 'text-warning' : '');
                    ?>
                    <span class="<?php echo $textClass ?>"><?php echo format_date($agreement->expiry_date, 'd') ?></span>
                  <?php else: ?>—<?php endif ?>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreement->id]) ?>" class="btn btn-outline-primary"><i class="fas fa-eye"></i></a>
                    <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $agreement->id]) ?>" class="btn btn-outline-secondary"><i class="fas fa-edit"></i></a>
                  </div>
                </td>
              </tr>
              <?php endforeach ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center py-5"><i class="fas fa-file-contract fa-3x text-muted mb-3"></i><p class="text-muted mb-0"><?php echo __('No agreements found') ?></p></td></tr>
            <?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
