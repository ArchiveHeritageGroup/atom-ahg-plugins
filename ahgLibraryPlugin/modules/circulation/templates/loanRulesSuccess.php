<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Loan Rules'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($notice)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $notice; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="<?php echo url_for(['module' => 'circulation', 'action' => 'index']); ?>" class="btn btn-outline-primary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to checkout station'); ?>
    </a>
  </div>
</div>

<div class="row">
  <!-- Left: existing rules -->
  <div class="col-lg-8 mb-4">
    <div class="card shadow-sm">
      <div class="card-header">
        <i class="fas fa-list me-2"></i><?php echo __('Current Loan Rules'); ?>
      </div>
      <div class="card-body p-0">
        <?php $rawRules = $sf_data->getRaw('loanRules'); ?>
        <?php if (empty($rawRules)): ?>
          <div class="p-3 text-muted">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo __('No loan rules configured yet. Use the form to create one.'); ?>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Material type'); ?></th>
                  <th><?php echo __('Patron type'); ?></th>
                  <th><?php echo __('Loan period'); ?></th>
                  <th><?php echo __('Max renewals'); ?></th>
                  <th><?php echo __('Max checkouts'); ?></th>
                  <th><?php echo __('Fine/day'); ?></th>
                  <th><?php echo __('Renewable'); ?></th>
                  <th><?php echo __('Actions'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rawRules as $rule): ?>
                  <tr>
                    <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst($rule->material_type ?? '-')); ?></span></td>
                    <td><?php echo esc_entities(ucfirst($rule->patron_type ?? __('All'))); ?></td>
                    <td><?php echo (int) ($rule->loan_period_days ?? 0); ?> <?php echo __('days'); ?></td>
                    <td><?php echo (int) ($rule->max_renewals ?? 0); ?></td>
                    <td><?php echo (int) ($rule->max_checkouts ?? 0); ?></td>
                    <td><?php echo number_format((float) ($rule->fine_per_day ?? 0), 2); ?></td>
                    <td>
                      <?php if (!empty($rule->is_renewable)): ?>
                        <i class="fas fa-check text-success"></i>
                      <?php else: ?>
                        <i class="fas fa-times text-danger"></i>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-sm btn-outline-primary edit-rule-btn"
                              data-id="<?php echo (int) $rule->id; ?>"
                              data-material-type="<?php echo esc_entities($rule->material_type ?? ''); ?>"
                              data-patron-type="<?php echo esc_entities($rule->patron_type ?? ''); ?>"
                              data-loan-period="<?php echo (int) ($rule->loan_period_days ?? 14); ?>"
                              data-max-renewals="<?php echo (int) ($rule->max_renewals ?? 2); ?>"
                              data-max-checkouts="<?php echo (int) ($rule->max_checkouts ?? 5); ?>"
                              data-fine-per-day="<?php echo (float) ($rule->fine_per_day ?? 0); ?>"
                              data-renewable="<?php echo !empty($rule->is_renewable) ? '1' : '0'; ?>"
                              title="<?php echo __('Edit'); ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: add/edit form -->
  <div class="col-lg-4 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-plus-circle me-2"></i><?php echo __('Add / Edit Loan Rule'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'circulation', 'action' => 'loanRules']); ?>" id="loan-rule-form">
          <input type="hidden" name="rule_id" id="rule_id" value="">

          <div class="mb-3">
            <label for="material_type" class="form-label"><?php echo __('Material type'); ?> <span class="text-danger">*</span></label>
            <select class="form-select" name="material_type" id="material_type" required>
              <option value=""><?php echo __('— Select —'); ?></option>
              <?php $rawTypes = $sf_data->getRaw('materialTypes'); ?>
              <?php foreach ($rawTypes as $type): ?>
                <option value="<?php echo esc_entities($type); ?>"><?php echo esc_entities(ucfirst($type)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="patron_type" class="form-label"><?php echo __('Patron type'); ?></label>
            <select class="form-select" name="patron_type" id="patron_type">
              <option value=""><?php echo __('All patron types'); ?></option>
              <?php $rawPatronTypes = $sf_data->getRaw('patronTypes'); ?>
              <?php foreach ($rawPatronTypes as $pt): ?>
                <option value="<?php echo esc_entities($pt); ?>"><?php echo esc_entities(ucfirst($pt)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="loan_period_days" class="form-label"><?php echo __('Loan period (days)'); ?></label>
            <input type="number" class="form-control" name="loan_period_days" id="loan_period_days" value="14" min="1" max="365">
          </div>

          <div class="mb-3">
            <label for="max_renewals" class="form-label"><?php echo __('Max renewals'); ?></label>
            <input type="number" class="form-control" name="max_renewals" id="max_renewals" value="2" min="0" max="99">
          </div>

          <div class="mb-3">
            <label for="max_checkouts" class="form-label"><?php echo __('Max checkouts per patron'); ?></label>
            <input type="number" class="form-control" name="max_checkouts" id="max_checkouts" value="5" min="1" max="999">
          </div>

          <div class="mb-3">
            <label for="fine_per_day" class="form-label"><?php echo __('Fine per day overdue'); ?></label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" class="form-control" name="fine_per_day" id="fine_per_day" value="0.00" min="0" step="0.01">
            </div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_renewable" id="is_renewable" value="1" checked>
            <label class="form-check-label" for="is_renewable"><?php echo __('Renewable'); ?></label>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i><?php echo __('Save loan rule'); ?>
            </button>
            <button type="button" class="btn btn-outline-secondary" id="reset-form-btn">
              <i class="fas fa-eraser me-1"></i><?php echo __('Reset form'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  document.addEventListener('DOMContentLoaded', function() {
    // Edit rule buttons — populate form with existing data
    document.querySelectorAll('.edit-rule-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        document.getElementById('rule_id').value = this.dataset.id || '';
        document.getElementById('material_type').value = this.dataset.materialType || '';
        document.getElementById('patron_type').value = this.dataset.patronType || '';
        document.getElementById('loan_period_days').value = this.dataset.loanPeriod || 14;
        document.getElementById('max_renewals').value = this.dataset.maxRenewals || 2;
        document.getElementById('max_checkouts').value = this.dataset.maxCheckouts || 5;
        document.getElementById('fine_per_day').value = this.dataset.finePerDay || 0;
        document.getElementById('is_renewable').checked = this.dataset.renewable === '1';
        document.getElementById('loan-rule-form').scrollIntoView({ behavior: 'smooth' });
      });
    });

    // Reset form
    document.getElementById('reset-form-btn').addEventListener('click', function() {
      document.getElementById('rule_id').value = '';
      document.getElementById('loan-rule-form').reset();
    });
  });
</script>
