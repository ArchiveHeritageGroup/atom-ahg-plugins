<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Currencies'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Currencies'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Manage Currencies'); ?></h1>

<!-- Add currency form -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0"><?php echo __('Add Currency'); ?></h5>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCurrencies']); ?>" class="row g-2 align-items-end">
      <input type="hidden" name="form_action" value="add">
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('Code'); ?></label>
        <input type="text" name="code" class="form-control form-control-sm" required placeholder="USD" maxlength="3" style="text-transform: uppercase;">
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Name'); ?></label>
        <input type="text" name="name" class="form-control form-control-sm" required placeholder="<?php echo __('US Dollar'); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('Symbol'); ?></label>
        <input type="text" name="symbol" class="form-control form-control-sm" placeholder="$" maxlength="5">
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Exchange Rate to ZAR'); ?></label>
        <input type="number" name="exchange_rate_to_zar" class="form-control form-control-sm" value="1.000000" step="0.000001" min="0.000001" required>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-plus me-1"></i> <?php echo __('Add'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Currencies table -->
<?php if (empty($currencies)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-money-bill fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No currencies configured'); ?></h5>
      <p class="text-muted"><?php echo __('Add your first currency above.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Code'); ?></th>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Symbol'); ?></th>
            <th class="text-end"><?php echo __('Rate to ZAR'); ?></th>
            <th><?php echo __('Active'); ?></th>
            <th><?php echo __('Updated'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($currencies as $currency): ?>
            <tr>
              <td class="fw-semibold"><?php echo esc_entities($currency->code); ?></td>
              <td><?php echo esc_entities($currency->name); ?></td>
              <td><?php echo esc_entities($currency->symbol ?? '-'); ?></td>
              <td class="text-end">
                <!-- Inline rate edit form -->
                <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCurrencies']); ?>" class="d-inline-flex align-items-center">
                  <input type="hidden" name="form_action" value="update">
                  <input type="hidden" name="code" value="<?php echo esc_entities($currency->code); ?>">
                  <input type="number" name="exchange_rate_to_zar" class="form-control form-control-sm me-1" value="<?php echo number_format((float) $currency->exchange_rate_to_zar, 6, '.', ''); ?>" step="0.000001" min="0.000001" style="width: 130px;">
                  <button type="submit" class="btn btn-sm btn-outline-primary" title="<?php echo __('Update Rate'); ?>">
                    <i class="fas fa-save"></i>
                  </button>
                </form>
              </td>
              <td>
                <?php if ($currency->is_active ?? 1): ?>
                  <span class="badge bg-success"><?php echo __('Active'); ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
                <?php endif; ?>
              </td>
              <td class="small text-muted">
                <?php echo ($currency->updated_at ?? null) ? date('d M Y', strtotime($currency->updated_at)) : '-'; ?>
              </td>
              <td class="text-end">
                <!-- Toggle active -->
                <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCurrencies']); ?>" class="d-inline">
                  <input type="hidden" name="form_action" value="toggle">
                  <input type="hidden" name="code" value="<?php echo esc_entities($currency->code); ?>">
                  <?php if ($currency->is_active ?? 1): ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Deactivate'); ?>">
                      <i class="fas fa-toggle-on"></i>
                    </button>
                  <?php else: ?>
                    <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Activate'); ?>">
                      <i class="fas fa-toggle-off"></i>
                    </button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php end_slot(); ?>
