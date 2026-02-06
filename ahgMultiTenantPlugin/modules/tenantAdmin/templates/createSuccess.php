<?php echo get_component('default', 'updateCheck') ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12 col-lg-8 offset-lg-2">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">
          <i class="fas fa-plus-circle me-2"></i>
          Create Tenant
        </h1>
        <a href="<?php echo url_for('tenant_admin') ?>" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
      </div>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form action="<?php echo url_for('tenant_admin_store') ?>" method="post">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Tenant Information</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="name" class="form-label">Tenant Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required autofocus>
                <small class="form-text text-muted">Display name for the tenant</small>
              </div>
              <div class="col-md-6 mb-3">
                <label for="code" class="form-label">Code</label>
                <input type="text" class="form-control" id="code" name="code" pattern="[a-z0-9-]+" maxlength="50">
                <small class="form-text text-muted">Unique identifier (auto-generated if empty). Lowercase letters, numbers, and hyphens only.</small>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="domain" class="form-label">Domain</label>
                <input type="text" class="form-control" id="domain" name="domain" placeholder="example.com">
                <small class="form-text text-muted">Custom domain for the tenant (optional)</small>
              </div>
              <div class="col-md-6 mb-3">
                <label for="subdomain" class="form-label">Subdomain</label>
                <input type="text" class="form-control" id="subdomain" name="subdomain" placeholder="client1">
                <small class="form-text text-muted">Subdomain prefix (optional)</small>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="repository_id" class="form-label">Link to Repository</label>
                <select class="form-select" id="repository_id" name="repository_id">
                  <option value="">-- None --</option>
                  <?php foreach ($repositories as $repo): ?>
                    <option value="<?php echo $repo->id ?>">
                      <?php echo esc_specialchars($repo->name ?: $repo->identifier ?: "Repository #{$repo->id}") ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Link tenant to an existing AtoM repository</small>
              </div>
              <div class="col-md-6 mb-3">
                <label for="status" class="form-label">Initial Status</label>
                <select class="form-select" id="status" name="status">
                  <option value="trial" selected>Trial</option>
                  <option value="active">Active</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>
            </div>

            <div class="row" id="trialDaysRow">
              <div class="col-md-6 mb-3">
                <label for="trial_days" class="form-label">Trial Period (Days)</label>
                <input type="number" class="form-control" id="trial_days" name="trial_days" value="14" min="1" max="365">
                <small class="form-text text-muted">Number of days for the trial period</small>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="contact_name" class="form-label">Contact Name</label>
                <input type="text" class="form-control" id="contact_name" name="contact_name">
              </div>
              <div class="col-md-6 mb-3">
                <label for="contact_email" class="form-label">Contact Email</label>
                <input type="email" class="form-control" id="contact_email" name="contact_email">
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Owner Assignment</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="owner_user_id" class="form-label">Assign Owner</label>
              <select class="form-select" id="owner_user_id" name="owner_user_id">
                <option value="">-- Select User (Optional) --</option>
                <?php
                $users = \Illuminate\Database\Capsule\Manager::table('user as u')
                    ->leftJoin('actor_i18n as ai', function ($join) {
                        $join->on('u.id', '=', 'ai.id')
                            ->where('ai.culture', '=', 'en');
                    })
                    ->where('u.active', 1)
                    ->select('u.id', 'u.username', 'u.email', 'ai.authorized_form_of_name as name')
                    ->orderBy('ai.authorized_form_of_name')
                    ->get();
                foreach ($users as $user): ?>
                  <option value="<?php echo $user->id ?>">
                    <?php echo esc_specialchars($user->name ?: $user->username) ?> (<?php echo esc_specialchars($user->email) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="form-text text-muted">Assign an owner who will have full control over this tenant</small>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="<?php echo url_for('tenant_admin') ?>" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Create Tenant
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var statusSelect = document.getElementById('status');
  var trialDaysRow = document.getElementById('trialDaysRow');

  function toggleTrialDays() {
    trialDaysRow.style.display = statusSelect.value === 'trial' ? 'flex' : 'none';
  }

  statusSelect.addEventListener('change', toggleTrialDays);
  toggleTrialDays();

  // Auto-generate code from name
  var nameInput = document.getElementById('name');
  var codeInput = document.getElementById('code');

  nameInput.addEventListener('blur', function() {
    if (!codeInput.value && nameInput.value) {
      codeInput.value = nameInput.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
        .substring(0, 50);
    }
  });
});
</script>
