<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Users — %1%', ['%1%' => htmlspecialchars($institution->name ?? '', ENT_QUOTES, 'UTF-8')]); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Institutions'), 'url' => url_for(['module' => 'registry', 'action' => 'adminInstitutions'])],
  ['label' => __('Users')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-10">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">
          <i class="fas fa-users me-2"></i>
          <?php echo htmlspecialchars($institution->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="text-muted mb-0"><?php echo __('Manage users linked to this institution'); ?></p>
      </div>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminInstitutions']); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
      </a>
    </div>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $err): ?>
          <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Link new user -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-user-plus me-2 text-success"></i><?php echo __('Link User to Institution'); ?></div>
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="form_action" value="link">
          <div class="col-md-5">
            <label class="form-label small fw-semibold"><?php echo __('User email'); ?></label>
            <input type="email" name="user_email" class="form-control" placeholder="user@example.com" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold"><?php echo __('Role'); ?></label>
            <select name="role" class="form-select">
              <option value="owner"><?php echo __('Owner'); ?></option>
              <option value="manager" selected><?php echo __('Manager'); ?></option>
              <option value="editor"><?php echo __('Editor'); ?></option>
              <option value="viewer"><?php echo __('Viewer'); ?></option>
            </select>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-success w-100"><i class="fas fa-link me-1"></i> <?php echo __('Link User'); ?></button>
          </div>
        </form>
      </div>
    </div>

    <!-- Linked users table -->
    <div class="card">
      <div class="card-header fw-semibold"><i class="fas fa-users me-2 text-primary"></i><?php echo __('Linked Users'); ?> <span class="badge bg-secondary ms-1"><?php echo count($linkedUsers); ?></span></div>
      <?php if (!empty($linkedUsers)): ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Email'); ?></th>
              <th><?php echo __('Username'); ?></th>
              <th><?php echo __('Role'); ?></th>
              <th class="text-center"><?php echo __('Primary'); ?></th>
              <th><?php echo __('Linked'); ?></th>
              <th class="text-end"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($linkedUsers as $lu): ?>
            <tr>
              <td><?php echo htmlspecialchars($lu->email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($lu->username ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="form_action" value="update-role">
                  <input type="hidden" name="link_id" value="<?php echo (int) $lu->link_id; ?>">
                  <select name="role" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                    <?php foreach (['owner', 'manager', 'editor', 'viewer'] as $r): ?>
                      <option value="<?php echo $r; ?>"<?php echo ($lu->role ?? '') === $r ? ' selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td class="text-center">
                <?php if (!empty($lu->is_primary)): ?>
                  <span class="badge bg-success"><i class="fas fa-star"></i> <?php echo __('Primary'); ?></span>
                <?php else: ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="form_action" value="set-primary">
                    <input type="hidden" name="link_id" value="<?php echo (int) $lu->link_id; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Set as primary'); ?>">
                      <i class="far fa-star"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
              <td><small class="text-muted"><?php echo !empty($lu->created_at) ? date('Y-m-d', strtotime($lu->created_at)) : '-'; ?></small></td>
              <td class="text-end">
                <form method="post" class="d-inline" onsubmit="return confirm('Delink this user from the institution?');">
                  <input type="hidden" name="form_action" value="delink">
                  <input type="hidden" name="link_id" value="<?php echo (int) $lu->link_id; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delink'); ?>">
                    <i class="fas fa-unlink"></i> <?php echo __('Delink'); ?>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="card-body text-center py-4">
        <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
        <p class="text-muted mb-0"><?php echo __('No users linked to this institution yet.'); ?></p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php end_slot(); ?>
