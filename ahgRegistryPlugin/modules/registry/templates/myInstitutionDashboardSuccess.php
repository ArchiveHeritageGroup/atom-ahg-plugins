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

<!-- My Institutions -->
<?php
  $isAdmin = sfContext::getInstance()->getUser()->hasCredential('administrator');
  $hasMultiple = isset($myInstitutions) && count($myInstitutions) > 1;
?>
<div class="card mb-4">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="fas fa-university me-2 text-primary"></i><?php echo __('My Institutions'); ?> <span class="badge bg-secondary ms-1"><?php echo count($myInstitutions ?? []); ?></span></span>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionRegister']); ?>" class="btn btn-sm btn-outline-success">
      <i class="fas fa-plus me-1"></i><?php echo __('Register New'); ?>
    </a>
  </div>
  <?php if (!empty($myInstitutions)): ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Your Role'); ?></th>
          <th class="text-center"><?php echo __('Primary'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($myInstitutions as $mi): ?>
        <tr<?php echo ($mi->id == $institution->id) ? ' class="table-active"' : ''; ?>>
          <td>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $mi->slug ?? $mi->id]); ?>" class="fw-semibold text-decoration-none">
              <?php echo htmlspecialchars($mi->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($mi->city) || !empty($mi->country)): ?>
              <br><small class="text-muted"><?php echo htmlspecialchars(implode(', ', array_filter([$mi->city ?? '', $mi->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $mi->institution_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td>
            <?php
              $role = $mi->role ?? 'owner';
              $roleColors = ['owner' => 'danger', 'manager' => 'warning', 'editor' => 'info', 'viewer' => 'secondary'];
            ?>
            <span class="badge bg-<?php echo $roleColors[$role] ?? 'secondary'; ?>"><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td class="text-center">
            <?php if (!empty($mi->is_primary)): ?>
              <span class="badge bg-success"><i class="fas fa-star"></i></span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php if ($mi->id != $institution->id): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>?inst=<?php echo (int) $mi->id; ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-exchange-alt me-1"></i><?php echo __('Switch'); ?>
              </a>
            <?php else: ?>
              <span class="badge bg-info"><?php echo __('Current'); ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-center text-muted py-4">
    <i class="fas fa-university fa-2x mb-2"></i>
    <p class="mb-0"><?php echo __('No institutions linked to your account yet.'); ?></p>
  </div>
  <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Admin: Manage any institution -->
<div class="card mb-4 border-warning">
  <div class="card-header fw-semibold bg-warning bg-opacity-10">
    <i class="fas fa-shield-alt me-2 text-warning"></i><?php echo __('Admin: Manage Any Institution'); ?>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3"><?php echo __('As an admin, you can switch to any institution to manage it on their behalf.'); ?></p>
    <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="row g-2 align-items-end" id="admin-inst-search-form">
      <div class="col-md-8">
        <label class="form-label small fw-semibold"><?php echo __('Search by name or ID'); ?></label>
        <input type="text" class="form-control" id="admin-inst-search" placeholder="<?php echo __('Start typing an institution name...'); ?>" autocomplete="off">
        <input type="hidden" name="inst" id="admin-inst-id" value="">
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-warning w-100" id="admin-inst-switch-btn" disabled>
          <i class="fas fa-exchange-alt me-1"></i><?php echo __('Switch to Institution'); ?>
        </button>
      </div>
    </form>
    <div id="admin-inst-results" class="list-group mt-2" style="display: none; max-height: 250px; overflow-y: auto;"></div>
  </div>
</div>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var searchInput = document.getElementById('admin-inst-search');
  var hiddenId = document.getElementById('admin-inst-id');
  var switchBtn = document.getElementById('admin-inst-switch-btn');
  var resultsDiv = document.getElementById('admin-inst-results');
  var debounceTimer = null;
  var allInstitutions = <?php
    // Pre-load all institutions for admin (lightweight — id, name, slug, type, city, country)
    $allInst = \Illuminate\Database\Capsule\Manager::table('registry_institution')
      ->where('is_active', 1)
      ->orderBy('name')
      ->select('id', 'name', 'slug', 'institution_type', 'city', 'country')
      ->get()->all();
    echo json_encode($allInst, JSON_UNESCAPED_UNICODE);
  ?>;

  searchInput.addEventListener('input', function() {
    var q = this.value.trim().toLowerCase();
    hiddenId.value = '';
    switchBtn.disabled = true;

    if (q.length < 2) {
      resultsDiv.style.display = 'none';
      return;
    }

    var matches = allInstitutions.filter(function(inst) {
      return inst.name.toLowerCase().indexOf(q) !== -1 || String(inst.id) === q;
    }).slice(0, 15);

    if (matches.length === 0) {
      resultsDiv.innerHTML = '<div class="list-group-item text-muted small">No matches found.</div>';
      resultsDiv.style.display = 'block';
      return;
    }

    resultsDiv.innerHTML = '';
    matches.forEach(function(inst) {
      var item = document.createElement('a');
      item.href = '#';
      item.className = 'list-group-item list-group-item-action py-2';
      var typeLabel = (inst.institution_type || '').replace(/_/g, ' ');
      typeLabel = typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1);
      var loc = [inst.city, inst.country].filter(Boolean).join(', ');
      item.innerHTML = '<div class="d-flex justify-content-between align-items-center">'
        + '<div><strong>' + escapeHtml(inst.name) + '</strong>'
        + (loc ? ' <small class="text-muted">(' + escapeHtml(loc) + ')</small>' : '')
        + '</div>'
        + '<span class="badge bg-secondary">' + escapeHtml(typeLabel) + '</span>'
        + '</div>';
      item.addEventListener('click', function(e) {
        e.preventDefault();
        searchInput.value = inst.name;
        hiddenId.value = inst.id;
        switchBtn.disabled = false;
        resultsDiv.style.display = 'none';
      });
      resultsDiv.appendChild(item);
    });
    resultsDiv.style.display = 'block';
  });

  // Hide results on outside click
  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
      resultsDiv.style.display = 'none';
    }
  });

  function escapeHtml(str) {
    if (!str) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }
});
</script>
<?php endif; ?>

<?php
  // Inst query param for carrying context through sub-pages
  $instParam = '?inst=' . (int) $institution->id;

  // Check if current user is linked to this institution
  $isLinked = false;
  $myRole = null;
  if (!empty($myInstitutions)) {
    foreach ($myInstitutions as $_mi) {
      if ((int) $_mi->id === (int) $institution->id) {
        $isLinked = true;
        $myRole = $_mi->role ?? 'manager';
        break;
      }
    }
  }
?>

<?php if (!$isLinked): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
  <div>
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong><?php echo __('You are not linked to this institution.'); ?></strong>
    <?php echo __('Claim it to add it to your account.'); ?>
  </div>
  <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionClaim']); ?>" class="d-flex gap-2">
    <input type="hidden" name="institution_id" value="<?php echo (int) $institution->id; ?>">
    <?php if ($isAdmin): ?>
    <select name="role" class="form-select form-select-sm" style="width: auto;">
      <option value="owner"><?php echo __('as Owner'); ?></option>
      <option value="manager"><?php echo __('as Manager'); ?></option>
    </select>
    <?php else: ?>
    <input type="hidden" name="role" value="manager">
    <?php endif; ?>
    <button type="submit" class="btn btn-sm btn-warning">
      <i class="fas fa-hand-paper me-1"></i><?php echo __('Claim'); ?>
    </button>
  </form>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo htmlspecialchars($institution->name ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $institution->slug ?? $institution->id]); ?>" class="btn btn-outline-secondary btn-sm me-1">
      <i class="fas fa-eye me-1"></i> <?php echo __('View Public Profile'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionEdit', 'id' => (int) $institution->id]); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-edit me-1"></i> <?php echo __('Edit Profile'); ?>
    </a>
  </div>
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

<!-- Quick links (order + colors mirror the Stats row above) -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']) . $instParam; ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-server fa-2x text-primary mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Manage Instances'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionContacts']) . $instParam; ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-address-book fa-2x text-success mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Manage Contacts'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionVendors']) . $instParam; ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-handshake fa-2x text-info mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Vendor Relationships'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionSoftware']) . $instParam; ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-laptop-code fa-2x text-warning mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Software Used'); ?></h6>
      </div>
    </a>
  </div>
</div>

<!-- Recent instances -->
<?php if (!empty($instances)): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?php echo __('System Instances'); ?></span>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionInstances']) . $instParam; ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Manage'); ?></a>
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
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionVendors']) . $instParam; ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
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
