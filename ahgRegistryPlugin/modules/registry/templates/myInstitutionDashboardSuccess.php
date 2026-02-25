<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('My Institution Dashboard'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution')],
]]); ?>

<?php $sfUser = sfContext::getInstance()->getUser(); ?>
<?php if ($sfUser->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sfUser->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sfUser->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sfUser->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-0"><?php echo __('My Institution Dashboard'); ?></h1>
    <?php if ($sfUser->hasCredential('administrator')): ?>
      <?php $allInst = \Illuminate\Database\Capsule\Manager::table('registry_institution')->where('is_active', 1)->orderBy('name')->get(); ?>
      <?php if (count($allInst) > 1): ?>
      <form method="get" action="" class="mt-1 d-inline-flex align-items-center gap-2">
        <select name="inst" class="form-select form-select-sm" style="max-width: 300px;" onchange="this.form.submit()">
          <?php foreach ($allInst as $opt): ?>
            <option value="<?php echo (int) $opt->id; ?>"<?php echo ($opt->id == $institution->id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($opt->name, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted"><?php echo __('Admin: switch institution'); ?></small>
      </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionEdit']); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-edit me-1"></i> <?php echo __('Edit Profile'); ?>
  </a>
</div>

<!-- Profile summary -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start">
      <?php if (!empty($institution->logo_path)): ?>
        <img src="<?php echo htmlspecialchars($institution->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: contain;">
      <?php else: ?>
        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
          <i class="fas fa-university fa-2x text-muted"></i>
        </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <h2 class="h4 mb-1">
          <?php echo htmlspecialchars($institution->name, ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($institution->is_verified)): ?>
            <span class="badge bg-success ms-2"><i class="fas fa-check-circle me-1"></i><?php echo __('Verified'); ?></span>
          <?php else: ?>
            <span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock me-1"></i><?php echo __('Pending Verification'); ?></span>
          <?php endif; ?>
        </h2>
        <?php if (!empty($institution->institution_type)): ?>
          <span class="badge bg-primary me-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $institution->institution_type)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if (!empty($institution->city) || !empty($institution->country)): ?>
          <small class="text-muted ms-2">
            <i class="fas fa-map-marker-alt me-1"></i>
            <?php echo htmlspecialchars(implode(', ', array_filter([$institution->city ?? '', $institution->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
          </small>
        <?php endif; ?>
        <?php if (!empty($institution->short_description)): ?>
          <p class="text-muted mt-2 mb-0"><?php echo htmlspecialchars($institution->short_description, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-primary"><?php echo count($instances ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Instances'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-success"><?php echo count($contacts ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Contacts'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-info"><?php echo count($vendors ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Vendors'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-warning"><?php echo count($software ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Software'); ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Quick links -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionContacts']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-address-book fa-2x text-primary mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Manage Contacts'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-server fa-2x text-success mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Manage Instances'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionSoftware']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-laptop-code fa-2x text-info mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Software Used'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionVendors']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-handshake fa-2x text-warning mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Vendor Relationships'); ?></h6>
      </div>
    </a>
  </div>
</div>

<!-- Recent instances -->
<?php if (!empty($instances)): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?php echo __('System Instances'); ?></span>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Manage'); ?></a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('URL'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Software'); ?></th>
          <th><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $instCount = 0; foreach ($instances as $inst): if ($instCount >= 5) break; $instCount++; ?>
        <tr>
          <td><?php echo htmlspecialchars($inst->name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if (!empty($inst->url)): ?>
              <a href="<?php echo htmlspecialchars($inst->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $inst->url), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($inst->instance_type ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><?php echo htmlspecialchars(($inst->software ?? '') . ($inst->software_version ? ' ' . $inst->software_version : ''), ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php
              $status = $inst->status ?? 'unknown';
              $statusClass = 'online' === $status ? 'success' : ('offline' === $status ? 'danger' : 'warning');
            ?>
            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Recent vendor relationships -->
<?php if (!empty($vendors)): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?php echo __('Vendor Relationships'); ?></span>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionVendors']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
  </div>
  <ul class="list-group list-group-flush">
    <?php $vendorCount = 0; foreach ($vendors as $rel): if ($vendorCount >= 5) break; $vendorCount++; ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <span><?php echo htmlspecialchars($rel->vendor_name ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $rel->relationship_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php end_slot(); ?>
