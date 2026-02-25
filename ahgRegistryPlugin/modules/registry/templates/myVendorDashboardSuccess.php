<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('My Vendor Dashboard'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('My Vendor Dashboard'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorEdit', 'id' => (int) $vendor->id]); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-edit me-1"></i> <?php echo __('Edit Profile'); ?>
  </a>
</div>

<!-- Profile summary -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start">
      <?php if (!empty($vendor->logo_path)): ?>
        <img src="<?php echo htmlspecialchars($vendor->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: contain;">
      <?php else: ?>
        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
          <i class="fas fa-handshake fa-2x text-muted"></i>
        </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <h2 class="h4 mb-1">
          <?php echo htmlspecialchars($vendor->name, ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($vendor->is_verified)): ?>
            <span class="badge bg-success ms-2"><i class="fas fa-check-circle me-1"></i><?php echo __('Verified'); ?></span>
          <?php else: ?>
            <span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock me-1"></i><?php echo __('Pending Verification'); ?></span>
          <?php endif; ?>
        </h2>
        <?php
          $rawVt = sfOutputEscaper::unescape($vendor->vendor_type ?? '[]');
          $vtArr = is_string($rawVt) ? (json_decode($rawVt, true) ?: []) : (is_array($rawVt) ? $rawVt : []);
          foreach ($vtArr as $vt): ?>
            <span class="badge bg-success me-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $vt)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
        <?php if (!empty($vendor->city) || !empty($vendor->country)): ?>
          <small class="text-muted ms-2">
            <i class="fas fa-map-marker-alt me-1"></i>
            <?php echo htmlspecialchars(implode(', ', array_filter([$vendor->city ?? '', $vendor->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
          </small>
        <?php endif; ?>
        <?php if (!empty($vendor->short_description)): ?>
          <p class="text-muted mt-2 mb-0"><?php echo htmlspecialchars($vendor->short_description, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-primary"><?php echo count($clients ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Clients'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-success"><?php echo count($software ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Software Products'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-info"><?php echo count($contacts ?? []); ?></div>
        <div class="text-muted small"><?php echo __('Contacts'); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card text-center h-100">
      <div class="card-body">
        <div class="display-6 fw-bold text-warning"><?php echo number_format($vendor->avg_rating ?? 0, 1); ?></div>
        <div class="text-muted small"><?php echo __('Avg. Rating'); ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Quick links -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorClients']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-building fa-2x text-primary mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Manage Clients'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftware']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-laptop-code fa-2x text-success mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Software Products'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorContacts']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-address-book fa-2x text-info mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Manage Contacts'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorCallLog']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-phone-alt fa-2x text-warning mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Call & Issue Log'); ?></h6>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-3">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myBlog']); ?>" class="card h-100 text-decoration-none">
      <div class="card-body text-center">
        <i class="fas fa-blog fa-2x text-danger mb-2"></i>
        <h6 class="card-title mb-0"><?php echo __('Blog Posts'); ?></h6>
      </div>
    </a>
  </div>
</div>

<!-- Client list preview -->
<?php if (!empty($clients)): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?php echo __('Client Institutions'); ?></span>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorClients']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Manage'); ?></a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('Relationship'); ?></th>
          <th><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $clientCount = 0; foreach ($clients as $cl): if ($clientCount >= 5) break; $clientCount++; ?>
        <tr>
          <td><?php echo htmlspecialchars($cl->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $cl->relationship_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td>
            <?php if (!empty($cl->is_active)): ?>
              <span class="badge bg-success"><?php echo __('Active'); ?></span>
            <?php else: ?>
              <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Software products preview -->
<?php if (!empty($software)): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><?php echo __('Software Products'); ?></span>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftware']); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('Manage'); ?></a>
  </div>
  <ul class="list-group list-group-flush">
    <?php $swCount = 0; foreach ($software as $sw): if ($swCount >= 5) break; $swCount++; ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
        <?php if (!empty($sw->category)): ?>
          <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars(ucfirst($sw->category), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($sw->latest_version)): ?>
        <span class="badge bg-primary">v<?php echo htmlspecialchars($sw->latest_version, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php end_slot(); ?>
