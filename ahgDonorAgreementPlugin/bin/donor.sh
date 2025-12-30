#!/bin/bash
# Donor Agreement Module
# Server: 192.168.0.112

ARCHIVE_PATH="/usr/share/nginx/archive"
PLUGIN_PATH="/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin"
FRAMEWORK_PATH="/usr/share/nginx/archive/atom-framework"

echo "=== Creating Donor Agreement Module ==="

# =============================================================================
# 1. CREATE DIRECTORIES
# =============================================================================
mkdir -p "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions"
mkdir -p "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates"
mkdir -p "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/config"

# =============================================================================
# 2. MODULE CONFIG
# =============================================================================
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/config/module.yml" << 'YAMLEOF'
all:
  is_internal: false
  view_class: sfPHP
YAMLEOF

cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/config/security.yml" << 'YAMLEOF'
all:
  is_secure: true

add:
  credentials: [[editor, contributor, administrator]]

edit:
  credentials: [[editor, administrator]]

delete:
  credentials: [[administrator]]
YAMLEOF

echo "✓ Created module config"

# =============================================================================
# 3. ACTIONS
# =============================================================================

# Browse Action
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions/browseAction.class.php" << 'PHPEOF'
<?php

class donorAgreementBrowseAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        $filters = [
            'status' => $request->getParameter('status'),
            'type' => $request->getParameter('type'),
            'donor_id' => $request->getParameter('donor'),
            'repository_id' => $request->getParameter('repository'),
            'search' => $request->getParameter('q'),
            'expiring' => $request->getParameter('expiring'),
        ];

        $page = max(1, (int) $request->getParameter('page', 1));

        $this->result = $service->browse(array_filter($filters), $page);
        $this->agreements = $this->result['data'] ?? [];
        $this->filters = $filters;
        $this->statuses = $service->getStatuses();
        $this->types = $service->getTypes();
    }
}
PHPEOF

# View Action
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions/viewAction.class.php" << 'PHPEOF'
<?php

class donorAgreementViewAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();
        $this->agreement = $service->find($id);

        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }

        $this->documents = $service->getDocuments($id);
        $this->reminders = $service->getReminders($id);
        $this->history = $service->getHistory($id);
    }
}
PHPEOF

# Add Action
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions/addAction.class.php" << 'PHPEOF'
<?php

class donorAgreementAddAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        $this->types = $service->getTypes();
        $this->statuses = $service->getStatuses();
        $this->agreement = null;

        // Pre-fill donor if passed
        $this->donorId = $request->getParameter('donor');
        $this->donor = null;
        if ($this->donorId) {
            $this->donor = QubitDonor::getById($this->donorId);
        }

        if ($request->isMethod('POST')) {
            $data = $request->getParameter('agreement', []);
            $data['created_by'] = $this->context->user->getAttribute('user_id');

            try {
                $id = $service->create($data);
                $this->context->user->setFlash('notice', 'Agreement created successfully.');
                $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $id]);
            } catch (\Exception $e) {
                $this->context->user->setFlash('error', 'Error creating agreement: ' . $e->getMessage());
            }
        }
    }
}
PHPEOF

# Edit Action
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions/editAction.class.php" << 'PHPEOF'
<?php

class donorAgreementEditAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        $this->agreement = $service->find($id);
        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }

        $this->types = $service->getTypes();
        $this->statuses = $service->getStatuses();

        if ($request->isMethod('POST')) {
            $data = $request->getParameter('agreement', []);
            $data['updated_by'] = $this->context->user->getAttribute('user_id');

            try {
                $service->update($id, $data);
                $this->context->user->setFlash('notice', 'Agreement updated successfully.');
                $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $id]);
            } catch (\Exception $e) {
                $this->context->user->setFlash('error', 'Error updating agreement: ' . $e->getMessage());
            }
        }
    }
}
PHPEOF

# Delete Action
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions/deleteAction.class.php" << 'PHPEOF'
<?php

class donorAgreementDeleteAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        try {
            $service->delete($id);
            $this->context->user->setFlash('notice', 'Agreement deleted successfully.');
        } catch (\Exception $e) {
            $this->context->user->setFlash('error', 'Error deleting agreement: ' . $e->getMessage());
        }

        $this->redirect(['module' => 'donorAgreement', 'action' => 'browse']);
    }
}
PHPEOF

# Reminders Action
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/actions/remindersAction.class.php" << 'PHPEOF'
<?php

class donorAgreementRemindersAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();
        $this->reminders = $service->getPendingReminders();
    }
}
PHPEOF

echo "✓ Created actions"

# =============================================================================
# 4. TEMPLATES
# =============================================================================

# Browse Template
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates/browseSuccess.php" << 'PHPEOF'
<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>"><?php echo __('Home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgDonor', 'action' => 'dashboard']) ?>"><?php echo __('Donor Dashboard') ?></a></li>
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
                    <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $agreement->donor_id]) ?>"><?php echo esc_entities($agreement->donor_name) ?></a>
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
PHPEOF

# Add Template
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates/addSuccess.php" << 'PHPEOF'
<?php use_helper('Date') ?>
<?php include_partial('donorAgreement/form', ['agreement' => $agreement, 'types' => $types, 'statuses' => $statuses, 'donor' => $donor ?? null, 'donorId' => $donorId ?? null]) ?>
PHPEOF

# Edit Template
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates/editSuccess.php" << 'PHPEOF'
<?php use_helper('Date') ?>
<?php include_partial('donorAgreement/form', ['agreement' => $agreement, 'types' => $types, 'statuses' => $statuses, 'donor' => null, 'donorId' => null]) ?>
PHPEOF

# Form Partial
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates/_form.php" << 'PHPEOF'
<?php
$isEdit = !empty($agreement);
$title = $isEdit ? __('Edit Agreement') : __('New Agreement');
$action = $isEdit 
    ? url_for(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $agreement->id])
    : url_for(['module' => 'donorAgreement', 'action' => 'add']);
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>"><?php echo __('Home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgDonor', 'action' => 'dashboard']) ?>"><?php echo __('Donor Dashboard') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>"><?php echo __('Agreements') ?></a></li>
          <li class="breadcrumb-item active"><?php echo $title ?></li>
        </ol>
      </nav>
      <h1 class="h3 mb-0">
        <i class="fas fa-file-contract text-primary me-2"></i>
        <?php echo $title ?>
      </h1>
    </div>
  </div>

  <form method="post" action="<?php echo $action ?>" enctype="multipart/form-data">
    <div class="row">
      <div class="col-lg-8">
        <!-- Main Details -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i><?php echo __('Agreement Details') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Agreement Number') ?> <span class="text-danger">*</span></label>
                <input type="text" name="agreement[agreement_number]" class="form-control" required
                       value="<?php echo esc_entities($agreement->agreement_number ?? '') ?>"
                       placeholder="<?php echo __('Auto-generated if blank') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label"><?php echo __('Agreement Type') ?> <span class="text-danger">*</span></label>
                <select name="agreement[agreement_type_id]" class="form-select" required>
                  <option value=""><?php echo __('Select type...') ?></option>
                  <?php foreach ($types as $type): ?>
                    <option value="<?php echo $type->id ?>" <?php echo ($agreement->agreement_type_id ?? '') == $type->id ? 'selected' : '' ?>>
                      <?php echo esc_entities($type->name) ?>
                    </option>
                  <?php endforeach ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label"><?php echo __('Title') ?></label>
                <input type="text" name="agreement[title]" class="form-control" value="<?php echo esc_entities($agreement->title ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label"><?php echo __('Description') ?></label>
                <textarea name="agreement[description]" class="form-control" rows="3"><?php echo esc_entities($agreement->description ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Donor Selection -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user text-success me-2"></i><?php echo __('Donor') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label"><?php echo __('Select Donor') ?></label>
                <?php if ($donor): ?>
                  <input type="hidden" name="agreement[donor_id]" value="<?php echo $donorId ?>">
                  <div class="form-control bg-light">
                    <i class="fas fa-user me-2"></i><?php echo esc_entities($donor->authorizedFormOfName) ?>
                    <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'add']) ?>" class="btn btn-sm btn-link float-end"><?php echo __('Change') ?></a>
                  </div>
                <?php else: ?>
                  <div class="input-group">
                    <input type="text" id="donorSearch" class="form-control" placeholder="<?php echo __('Search for donor...') ?>" autocomplete="off">
                    <input type="hidden" name="agreement[donor_id]" id="donorId" value="<?php echo esc_entities($agreement->donor_id ?? '') ?>">
                  </div>
                  <div id="donorResults" class="list-group mt-1" style="display:none; position:absolute; z-index:1000; width:calc(100% - 24px);"></div>
                <?php endif ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Dates -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar text-info me-2"></i><?php echo __('Dates') ?></h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Agreement Date') ?></label>
                <input type="date" name="agreement[agreement_date]" class="form-control" value="<?php echo $agreement->agreement_date ?? date('Y-m-d') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Effective Date') ?></label>
                <input type="date" name="agreement[effective_date]" class="form-control" value="<?php echo $agreement->effective_date ?? '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Expiry Date') ?></label>
                <input type="date" name="agreement[expiry_date]" class="form-control" value="<?php echo $agreement->expiry_date ?? '' ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label"><?php echo __('Review Date') ?></label>
                <input type="date" name="agreement[review_date]" class="form-control" value="<?php echo $agreement->review_date ?? '' ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-sticky-note text-warning me-2"></i><?php echo __('Notes') ?></h5>
          </div>
          <div class="card-body">
            <textarea name="agreement[notes]" class="form-control" rows="4" placeholder="<?php echo __('Internal notes...') ?>"><?php echo esc_entities($agreement->notes ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <!-- Status -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-flag text-primary me-2"></i><?php echo __('Status') ?></h5>
          </div>
          <div class="card-body">
            <select name="agreement[status]" class="form-select">
              <?php foreach ($statuses as $key => $label): ?>
                <option value="<?php echo $key ?>" <?php echo ($agreement->status ?? 'draft') === $key ? 'selected' : '' ?>>
                  <?php echo esc_entities($label) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <!-- Actions -->
        <div class="card">
          <div class="card-body">
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i><?php echo $isEdit ? __('Update Agreement') : __('Create Agreement') ?>
              </button>
              <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i><?php echo __('Cancel') ?>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('donorSearch');
    const donorIdInput = document.getElementById('donorId');
    const resultsDiv = document.getElementById('donorResults');
    
    if (!searchInput) return;
    
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }
        
        timeout = setTimeout(function() {
            fetch('/donor/autocomplete?query=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">No donors found</div>';
                    } else {
                        data.forEach(function(donor) {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.textContent = donor.name;
                            item.onclick = function(e) {
                                e.preventDefault();
                                searchInput.value = donor.name;
                                donorIdInput.value = donor.id;
                                resultsDiv.style.display = 'none';
                            };
                            resultsDiv.appendChild(item);
                        });
                    }
                    resultsDiv.style.display = 'block';
                });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>
PHPEOF

# View Template
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates/viewSuccess.php" << 'PHPEOF'
<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>"><?php echo __('Home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgDonor', 'action' => 'dashboard']) ?>"><?php echo __('Donor Dashboard') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'browse']) ?>"><?php echo __('Agreements') ?></a></li>
          <li class="breadcrumb-item active"><?php echo esc_entities($agreement->agreement_number) ?></li>
        </ol>
      </nav>
      <h1 class="h3 mb-0">
        <i class="fas fa-file-contract text-primary me-2"></i>
        <?php echo esc_entities($agreement->agreement_number) ?>
        <?php
        $statusColors = ['draft' => 'secondary', 'active' => 'success', 'expired' => 'danger', 'terminated' => 'dark', 'pending_approval' => 'warning'];
        $color = $statusColors[$agreement->status] ?? 'secondary';
        ?>
        <span class="badge bg-<?php echo $color ?> ms-2"><?php echo ucfirst(str_replace('_', ' ', $agreement->status)) ?></span>
      </h1>
    </div>
    <div class="btn-group">
      <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $agreement->id]) ?>" class="btn btn-outline-primary">
        <i class="fas fa-edit me-1"></i> <?php echo __('Edit') ?>
      </a>
      <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
        <i class="fas fa-trash me-1"></i> <?php echo __('Delete') ?>
      </button>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <!-- Agreement Details -->
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle text-primary me-2"></i><?php echo __('Agreement Details') ?></h5></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-3"><?php echo __('Agreement Number') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($agreement->agreement_number) ?></dd>

            <?php if ($agreement->title): ?>
            <dt class="col-sm-3"><?php echo __('Title') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($agreement->title) ?></dd>
            <?php endif ?>

            <dt class="col-sm-3"><?php echo __('Type') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($agreement->type_name ?? '—') ?></dd>

            <dt class="col-sm-3"><?php echo __('Donor') ?></dt>
            <dd class="col-sm-9">
              <?php if ($agreement->donor_id): ?>
                <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $agreement->donor_id]) ?>">
                  <i class="fas fa-user me-1"></i><?php echo esc_entities($agreement->donor_name ?? 'Unknown') ?>
                </a>
              <?php else: ?>—<?php endif ?>
            </dd>

            <?php if ($agreement->description): ?>
            <dt class="col-sm-3"><?php echo __('Description') ?></dt>
            <dd class="col-sm-9"><?php echo nl2br(esc_entities($agreement->description)) ?></dd>
            <?php endif ?>
          </dl>
        </div>
      </div>

      <!-- Dates -->
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-calendar text-info me-2"></i><?php echo __('Dates') ?></h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 text-center">
              <div class="text-muted small"><?php echo __('Agreement Date') ?></div>
              <div class="fw-bold"><?php echo $agreement->agreement_date ? format_date($agreement->agreement_date, 'd') : '—' ?></div>
            </div>
            <div class="col-md-3 text-center">
              <div class="text-muted small"><?php echo __('Effective Date') ?></div>
              <div class="fw-bold"><?php echo $agreement->effective_date ? format_date($agreement->effective_date, 'd') : '—' ?></div>
            </div>
            <div class="col-md-3 text-center">
              <div class="text-muted small"><?php echo __('Expiry Date') ?></div>
              <div class="fw-bold <?php echo $agreement->expiry_date && strtotime($agreement->expiry_date) < strtotime('+30 days') ? 'text-danger' : '' ?>">
                <?php echo $agreement->expiry_date ? format_date($agreement->expiry_date, 'd') : '—' ?>
              </div>
            </div>
            <div class="col-md-3 text-center">
              <div class="text-muted small"><?php echo __('Review Date') ?></div>
              <div class="fw-bold"><?php echo $agreement->review_date ? format_date($agreement->review_date, 'd') : '—' ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <?php if ($agreement->notes): ?>
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-sticky-note text-warning me-2"></i><?php echo __('Notes') ?></h5></div>
        <div class="card-body"><?php echo nl2br(esc_entities($agreement->notes)) ?></div>
      </div>
      <?php endif ?>
    </div>

    <div class="col-lg-4">
      <!-- Quick Actions -->
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i><?php echo __('Actions') ?></h5></div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $agreement->id]) ?>" class="btn btn-outline-primary">
              <i class="fas fa-edit me-2"></i><?php echo __('Edit Agreement') ?>
            </a>
            <?php if ($agreement->donor_id): ?>
            <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $agreement->donor_id]) ?>" class="btn btn-outline-secondary">
              <i class="fas fa-user me-2"></i><?php echo __('View Donor') ?>
            </a>
            <?php endif ?>
          </div>
        </div>
      </div>

      <!-- Control -->
      <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-cog text-secondary me-2"></i><?php echo __('Control') ?></h5></div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-sm-5"><?php echo __('Record ID') ?></dt>
            <dd class="col-sm-7"><?php echo $agreement->id ?></dd>
            <dt class="col-sm-5"><?php echo __('Created') ?></dt>
            <dd class="col-sm-7"><?php echo format_date($agreement->created_at, 'f') ?></dd>
            <dt class="col-sm-5"><?php echo __('Updated') ?></dt>
            <dd class="col-sm-7"><?php echo format_date($agreement->updated_at, 'f') ?></dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Confirm Delete') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><?php echo __('Are you sure you want to delete this agreement?') ?></p>
        <p class="text-danger fw-bold"><?php echo esc_entities($agreement->agreement_number) ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
        <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'delete', 'id' => $agreement->id]) ?>" class="btn btn-danger">
          <i class="fas fa-trash me-1"></i> <?php echo __('Delete') ?>
        </a>
      </div>
    </div>
  </div>
</div>
PHPEOF

# Reminders Template
cat > "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement/templates/remindersSuccess.php" << 'PHPEOF'
<?php use_helper('Date') ?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0">
        <i class="fas fa-bell text-warning me-2"></i>
        <?php echo __('Pending Reminders') ?>
        <span class="badge bg-warning text-dark ms-2"><?php echo count($reminders) ?></span>
      </h1>
    </div>
    <a href="<?php echo url_for(['module' => 'ahgDonor', 'action' => 'dashboard']) ?>" class="btn btn-outline-primary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Dashboard') ?>
    </a>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (!empty($reminders)): ?>
      <div class="list-group list-group-flush">
        <?php foreach ($reminders as $reminder): ?>
        <div class="list-group-item">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="mb-1">
                <a href="<?php echo url_for(['module' => 'donorAgreement', 'action' => 'view', 'id' => $reminder->agreement_id]) ?>">
                  <?php echo esc_entities($reminder->agreement_number) ?>
                </a>
              </h6>
              <p class="mb-1"><?php echo esc_entities($reminder->message ?? ucfirst(str_replace('_', ' ', $reminder->reminder_type))) ?></p>
              <small class="text-muted">Due: <?php echo format_date($reminder->reminder_date, 'd') ?></small>
            </div>
            <span class="badge bg-<?php echo $reminder->priority === 'urgent' ? 'danger' : ($reminder->priority === 'high' ? 'warning' : 'secondary') ?>">
              <?php echo ucfirst($reminder->priority) ?>
            </span>
          </div>
        </div>
        <?php endforeach ?>
      </div>
      <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
        <p class="text-muted"><?php echo __('No pending reminders') ?></p>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>
PHPEOF

echo "✓ Created templates"

# =============================================================================
# 5. ROUTING
# =============================================================================
cat >> "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/config/routing.yml" << 'YAMLEOF'

# Donor Agreement Routes
donor_agreement_browse:
  url: /donor-agreement
  param: { module: donorAgreement, action: browse }

donor_agreement_add:
  url: /donor-agreement/add
  param: { module: donorAgreement, action: add }

donor_agreement_view:
  url: /donor-agreement/:id
  param: { module: donorAgreement, action: view }
  requirements: { id: \d+ }

donor_agreement_edit:
  url: /donor-agreement/:id/edit
  param: { module: donorAgreement, action: edit }
  requirements: { id: \d+ }

donor_agreement_delete:
  url: /donor-agreement/:id/delete
  param: { module: donorAgreement, action: delete }
  requirements: { id: \d+ }

donor_agreement_reminders:
  url: /donor-agreement/reminders
  param: { module: donorAgreement, action: reminders }
YAMLEOF

echo "✓ Added routing"

# =============================================================================
# 6. SET PERMISSIONS
# =============================================================================
chown -R www-data:www-data "/usr/share/nginx/archive/plugins/arAHGThemeB5Plugin/modules/donorAgreement"

echo ""
echo "=== Donor Agreement Module Complete ==="
echo ""
echo "URLs:"
echo "  Browse:    https://psis.theahg.co.za/donor-agreement"
echo "  Add:       https://psis.theahg.co.za/donor-agreement/add"
echo "  View:      https://psis.theahg.co.za/donor-agreement/{id}"
echo "  Edit:      https://psis.theahg.co.za/donor-agreement/{id}/edit"
echo "  Reminders: https://psis.theahg.co.za/donor-agreement/reminders"
echo ""
echo "Clear cache: php symfony cc"