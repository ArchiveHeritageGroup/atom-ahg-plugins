<?php
/**
 * Multi-Tenant Settings Template
 *
 * Settings for the ahgMultiTenantPlugin repository-based multi-tenancy.
 */
?>

<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0"><i class="fas fa-building me-2"></i> Multi-Tenancy Configuration</h5>
  </div>
  <div class="card-body">
    <p class="text-muted mb-4">
      Configure repository-based multi-tenancy. Each repository acts as a tenant with isolated user access and custom branding.
    </p>

    <div class="row">
      <div class="col-md-6">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="tenant_enabled" name="settings[tenant_enabled]" value="true" <?php echo ($settings['tenant_enabled'] ?? '') === 'true' ? 'checked' : '' ?>>
            <label class="form-check-label" for="tenant_enabled">
              <strong>Enable Multi-Tenancy</strong>
            </label>
          </div>
          <small class="text-muted">Enable repository-based access control and filtering.</small>
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="tenant_enforce_filter" name="settings[tenant_enforce_filter]" value="true" <?php echo ($settings['tenant_enforce_filter'] ?? '') === 'true' ? 'checked' : '' ?>>
            <label class="form-check-label" for="tenant_enforce_filter">
              <strong>Enforce Repository Filtering</strong>
            </label>
          </div>
          <small class="text-muted">Automatically filter browse/search results by current tenant.</small>
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="tenant_show_switcher" name="settings[tenant_show_switcher]" value="true" <?php echo ($settings['tenant_show_switcher'] ?? 'true') === 'true' ? 'checked' : '' ?>>
            <label class="form-check-label" for="tenant_show_switcher">
              <strong>Show Tenant Switcher</strong>
            </label>
          </div>
          <small class="text-muted">Display the repository switcher dropdown in the navigation bar.</small>
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" id="tenant_allow_branding" name="settings[tenant_allow_branding]" value="true" <?php echo ($settings['tenant_allow_branding'] ?? 'true') === 'true' ? 'checked' : '' ?>>
            <label class="form-check-label" for="tenant_allow_branding">
              <strong>Allow Per-Tenant Branding</strong>
            </label>
          </div>
          <small class="text-muted">Allow super users to customize colors and logos for their repositories.</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i> User Hierarchy</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Role</th>
            <th>Description</th>
            <th>Permissions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><span class="badge bg-danger">Administrator</span></td>
            <td>AtoM administrator group member</td>
            <td>
              <ul class="mb-0 small">
                <li>Sees ALL repositories</li>
                <li>Can assign Super Users to any repository</li>
                <li>Can toggle "View All" mode</li>
              </ul>
            </td>
          </tr>
          <tr>
            <td><span class="badge bg-warning text-dark">Super User</span></td>
            <td>Assigned to specific repositories by Admin</td>
            <td>
              <ul class="mb-0 small">
                <li>Sees ONLY assigned repositories</li>
                <li>Can assign Users to their repositories</li>
                <li>Can manage branding for their repositories</li>
              </ul>
            </td>
          </tr>
          <tr>
            <td><span class="badge bg-info">User</span></td>
            <td>Assigned to specific repositories</td>
            <td>
              <ul class="mb-0 small">
                <li>Sees ONLY assigned repositories</li>
                <li>Standard editor/contributor/viewer permissions</li>
              </ul>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-cog me-2"></i> Quick Actions</h5>
  </div>
  <div class="card-body">
    <a href="<?php echo url_for('tenant_admin') ?>" class="btn btn-primary me-2">
      <i class="fas fa-building me-1"></i> Manage Tenants
    </a>
    <a href="<?php echo url_for('@homepage') ?>" class="btn btn-outline-secondary">
      <i class="fas fa-home me-1"></i> Back to Home
    </a>
  </div>
</div>

<div class="alert alert-info">
  <h6><i class="fas fa-info-circle me-2"></i> Integration Tips</h6>
  <ul class="mb-0 small">
    <li><strong>Theme Integration:</strong> Add <code>&lt;?php include_component('tenantSwitcher', 'switcher') ?&gt;</code> to your theme's navbar.</li>
    <li><strong>Query Filtering:</strong> Use <code>TenantContext::applyRepositoryFilter($query, 'repository_id')</code> in your code.</li>
    <li><strong>Elasticsearch:</strong> Use <code>TenantQueryFilter::applyElasticsearchFilter($query)</code> for search queries.</li>
  </ul>
</div>
