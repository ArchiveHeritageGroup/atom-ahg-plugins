<?php use_helper('Text'); ?>

<?php
$region = $sf_data->getRaw('region');
$standard = $sf_data->getRaw('standard');
$rules = $sf_data->getRaw('rules');
$isActive = $sf_data->getRaw('isActive');
$countries = is_array($region->countries) ? $region->countries : [];
?>

<h1><i class="fas fa-globe me-2"></i><?php echo htmlspecialchars($region->region_name); ?></h1>

<p class="text-muted mb-4">
  <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regions']); ?>">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Regions'); ?>
  </a>
</p>

<!-- Status Banner -->
<?php if ($isActive): ?>
  <div class="alert alert-primary">
    <i class="fas fa-check-circle me-2"></i>
    <strong><?php echo __('This is the currently active region for compliance checking.'); ?></strong>
  </div>
<?php elseif ($region->is_installed): ?>
  <div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo __('This region is installed and ready to use.'); ?>
    <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionSetActive', 'region' => $region->region_code]); ?>" class="alert-link ms-2">
      <?php echo __('Set as active'); ?> <i class="fas fa-arrow-right"></i>
    </a>
  </div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('This region is not installed.'); ?>
    <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionInstall', 'region' => $region->region_code]); ?>" class="alert-link ms-2">
      <?php echo __('Install now'); ?> <i class="fas fa-arrow-right"></i>
    </a>
  </div>
<?php endif; ?>

<div class="row">
  <!-- Region Details Card -->
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-info-circle me-2"></i><?php echo __('Region Details'); ?>
      </div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tr>
            <th class="w-40"><?php echo __('Code'); ?></th>
            <td><code><?php echo htmlspecialchars($region->region_code); ?></code></td>
          </tr>
          <tr>
            <th><?php echo __('Name'); ?></th>
            <td><?php echo htmlspecialchars($region->region_name); ?></td>
          </tr>
          <tr>
            <th><?php echo __('Default Currency'); ?></th>
            <td><span class="badge bg-primary"><?php echo htmlspecialchars($region->default_currency); ?></span></td>
          </tr>
          <tr>
            <th><?php echo __('Financial Year Start'); ?></th>
            <td><?php echo htmlspecialchars($region->financial_year_start); ?></td>
          </tr>
          <tr>
            <th><?php echo __('Regulatory Body'); ?></th>
            <td><?php echo htmlspecialchars($region->regulatory_body); ?></td>
          </tr>
          <tr>
            <th><?php echo __('Countries'); ?></th>
            <td>
              <?php foreach ($countries as $country): ?>
                <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($country); ?></span>
              <?php endforeach; ?>
            </td>
          </tr>
          <?php if ($region->is_installed && $region->installed_at): ?>
            <tr>
              <th><?php echo __('Installed'); ?></th>
              <td><?php echo htmlspecialchars($region->installed_at); ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <!-- Accounting Standard Card -->
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-balance-scale me-2"></i><?php echo __('Accounting Standard'); ?>
      </div>
      <div class="card-body">
        <?php if ($standard): ?>
          <table class="table table-borderless mb-0">
            <tr>
              <th class="w-40"><?php echo __('Code'); ?></th>
              <td><span class="badge bg-success"><?php echo htmlspecialchars($standard->code); ?></span></td>
            </tr>
            <tr>
              <th><?php echo __('Name'); ?></th>
              <td><?php echo htmlspecialchars($standard->name); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Description'); ?></th>
              <td class="small"><?php echo htmlspecialchars($standard->description); ?></td>
            </tr>
            <tr>
              <th><?php echo __('Capitalisation'); ?></th>
              <td>
                <?php if ($standard->capitalisation_required): ?>
                  <span class="badge bg-warning text-dark">Required</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Optional</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php if ($standard->valuation_methods): ?>
              <?php $methods = json_decode($standard->valuation_methods, true) ?: []; ?>
              <tr>
                <th><?php echo __('Valuation Methods'); ?></th>
                <td>
                  <?php foreach ($methods as $method): ?>
                    <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($method); ?></span>
                  <?php endforeach; ?>
                </td>
              </tr>
            <?php endif; ?>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo __('Standard not installed. Install the region to add the accounting standard.'); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Compliance Rules -->
<?php if ($region->is_installed && count($rules) > 0): ?>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>
        <i class="fas fa-check-square me-2"></i><?php echo __('Compliance Rules'); ?>
        <span class="badge bg-secondary ms-2"><?php echo count($rules); ?></span>
      </span>
      <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $standard->id]); ?>" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-pencil-alt me-1"></i><?php echo __('Manage Rules'); ?>
      </a>
    </div>
    <div class="card-body p-0">
      <?php
        $rulesByCategory = [];
        foreach ($rules as $rule) {
            $cat = $rule->category ?? 'other';
            if (!isset($rulesByCategory[$cat])) {
                $rulesByCategory[$cat] = [];
            }
            $rulesByCategory[$cat][] = $rule;
        }
      ?>

      <div class="accordion accordion-flush" id="rulesAccordion">
        <?php foreach ($rulesByCategory as $category => $categoryRules): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $category; ?>">
                <span class="text-capitalize"><?php echo htmlspecialchars($category); ?></span>
                <span class="badge bg-secondary ms-2"><?php echo count($categoryRules); ?></span>
              </button>
            </h2>
            <div id="collapse-<?php echo $category; ?>" class="accordion-collapse collapse" data-bs-parent="#rulesAccordion">
              <div class="accordion-body p-0">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th><?php echo __('Code'); ?></th>
                      <th><?php echo __('Name'); ?></th>
                      <th><?php echo __('Severity'); ?></th>
                      <th><?php echo __('Reference'); ?></th>
                      <th><?php echo __('Status'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($categoryRules as $rule): ?>
                      <tr>
                        <td><code><?php echo htmlspecialchars($rule->code); ?></code></td>
                        <td><?php echo htmlspecialchars($rule->name); ?></td>
                        <td>
                          <?php
                            $severityClass = match($rule->severity) {
                                'error' => 'danger',
                                'warning' => 'warning',
                                default => 'info'
                            };
                          ?>
                          <span class="badge bg-<?php echo $severityClass; ?>"><?php echo htmlspecialchars($rule->severity); ?></span>
                        </td>
                        <td class="small text-muted"><?php echo htmlspecialchars($rule->reference ?? '-'); ?></td>
                        <td>
                          <?php if ($rule->is_active): ?>
                            <span class="badge bg-success">Active</span>
                          <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Actions -->
<div class="mt-4">
  <?php if ($region->is_installed && !$isActive): ?>
    <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionSetActive', 'region' => $region->region_code]); ?>"
       class="btn btn-primary"
       onclick="return confirm('Set <?php echo htmlspecialchars($region->region_name); ?> as the active region?');">
      <i class="fas fa-check-circle me-1"></i><?php echo __('Set as Active Region'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionUninstall', 'region' => $region->region_code]); ?>"
       class="btn btn-outline-danger"
       onclick="return confirm('Uninstall <?php echo htmlspecialchars($region->region_name); ?>? This will remove the standard and all compliance rules.');">
      <i class="fas fa-trash me-1"></i><?php echo __('Uninstall Region'); ?>
    </a>
  <?php elseif (!$region->is_installed): ?>
    <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionInstall', 'region' => $region->region_code]); ?>"
       class="btn btn-success"
       onclick="return confirm('Install <?php echo htmlspecialchars($region->region_name); ?>?');">
      <i class="fas fa-download me-1"></i><?php echo __('Install Region'); ?>
    </a>
  <?php endif; ?>
</div>
