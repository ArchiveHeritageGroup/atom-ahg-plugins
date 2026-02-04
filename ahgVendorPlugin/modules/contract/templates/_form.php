<div class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0"><?php echo $isNew ? __('New Contract') : __('Edit Contract') ?></h1>
    <a href="<?php echo url_for(['module' => 'contract', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to List') ?>
    </a>
  </div>

  <form method="post" enctype="multipart/form-data">
    <!-- Basic Information -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Contract Details') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Contract Type') ?> <span class="text-danger">*</span></label>
            <select name="contract[contract_type_id]" class="form-select" required>
              <option value=""><?php echo __('Select type...') ?></option>
              <?php foreach ($types as $type): ?>
              <option value="<?php echo $type->id ?>" <?php echo ($contract->contract_type_id ?? '') == $type->id ? 'selected' : '' ?>>
                <?php echo esc_entities($type->name) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Contract Number') ?></label>
            <input type="text" name="contract[contract_number]" class="form-control" value="<?php echo esc_entities($contract->contract_number ?? '') ?>" placeholder="<?php echo __('Auto-generated if blank') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Status') ?></label>
            <select name="contract[status]" class="form-select">
              <?php foreach ($statuses as $key => $label): ?>
              <option value="<?php echo $key ?>" <?php echo ($contract->status ?? 'draft') == $key ? 'selected' : '' ?>><?php echo __($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Title') ?> <span class="text-danger">*</span></label>
            <input type="text" name="contract[title]" class="form-control" value="<?php echo esc_entities($contract->title ?? '') ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Description') ?></label>
            <textarea name="contract[description]" class="form-control" rows="3"><?php echo esc_entities($contract->description ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Contract Logo') ?></label>
            <?php if (!empty($contract->logo_path)): ?>
            <div class="mb-2">
              <img src="/uploads<?php echo esc_entities($contract->logo_path) ?>" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
              <div class="form-check mt-1">
                <input type="checkbox" name="remove_logo" id="remove_logo" class="form-check-input" value="1">
                <label class="form-check-label text-danger" for="remove_logo"><?php echo __('Remove logo') ?></label>
              </div>
            </div>
            <?php endif; ?>
            <input type="file" name="contract_logo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
            <small class="text-muted"><?php echo __('Upload organization logo for contract header (JPG, PNG, GIF, WebP)') ?></small>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Risk Level') ?></label>
            <select name="contract[risk_level]" class="form-select">
              <option value="low" <?php echo ($contract->risk_level ?? 'low') == 'low' ? 'selected' : '' ?>><?php echo __('Low') ?></option>
              <option value="medium" <?php echo ($contract->risk_level ?? '') == 'medium' ? 'selected' : '' ?>><?php echo __('Medium') ?></option>
              <option value="high" <?php echo ($contract->risk_level ?? '') == 'high' ? 'selected' : '' ?>><?php echo __('High') ?></option>
              <option value="critical" <?php echo ($contract->risk_level ?? '') == 'critical' ? 'selected' : '' ?>><?php echo __('Critical') ?></option>
            </select>
          </div>
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input type="checkbox" name="contract[is_template]" id="is_template" class="form-check-input" value="1" <?php echo !empty($contract->is_template) ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_template"><?php echo __('Save as Template') ?></label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Counterparty Information -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Counterparty') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Counterparty Name') ?> <span class="text-danger">*</span></label>
            <input type="text" name="contract[counterparty_name]" class="form-control" value="<?php echo esc_entities($contract->counterparty_name ?? ($vendor->name ?? '')) ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Type') ?></label>
            <select name="contract[counterparty_type]" class="form-select">
              <option value="vendor" <?php echo ($contract->counterparty_type ?? 'vendor') == 'vendor' ? 'selected' : '' ?>><?php echo __('Vendor/Supplier') ?></option>
              <option value="institution" <?php echo ($contract->counterparty_type ?? '') == 'institution' ? 'selected' : '' ?>><?php echo __('Institution') ?></option>
              <option value="individual" <?php echo ($contract->counterparty_type ?? '') == 'individual' ? 'selected' : '' ?>><?php echo __('Individual') ?></option>
              <option value="government" <?php echo ($contract->counterparty_type ?? '') == 'government' ? 'selected' : '' ?>><?php echo __('Government') ?></option>
              <option value="other" <?php echo ($contract->counterparty_type ?? '') == 'other' ? 'selected' : '' ?>><?php echo __('Other') ?></option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Link to Vendor') ?></label>
            <select name="contract[vendor_id]" class="form-select">
              <option value=""><?php echo __('None') ?></option>
              <?php foreach ($vendors as $v): ?>
              <option value="<?php echo $v->id ?>" <?php echo ($contract->vendor_id ?? ($vendor->id ?? '')) == $v->id ? 'selected' : '' ?>><?php echo esc_entities($v->name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Contact Information') ?></label>
            <textarea name="contract[counterparty_contact]" class="form-control" rows="2" placeholder="<?php echo __('Address, phone, email...') ?>"><?php echo esc_entities($contract->counterparty_contact ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Representative Name') ?></label>
            <input type="text" name="contract[counterparty_representative]" class="form-control" value="<?php echo esc_entities($contract->counterparty_representative ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Representative Title') ?></label>
            <input type="text" name="contract[counterparty_representative_title]" class="form-control" value="<?php echo esc_entities($contract->counterparty_representative_title ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Our Details -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Our Organization') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Our Representative') ?></label>
            <input type="text" name="contract[our_representative]" class="form-control" value="<?php echo esc_entities($contract->our_representative ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Title/Position') ?></label>
            <input type="text" name="contract[our_representative_title]" class="form-control" value="<?php echo esc_entities($contract->our_representative_title ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Dates -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Dates') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Effective Date') ?></label>
            <input type="date" name="contract[effective_date]" class="form-control" value="<?php echo $contract->effective_date ?? '' ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Expiry Date') ?></label>
            <input type="date" name="contract[expiry_date]" class="form-control" value="<?php echo $contract->expiry_date ?? '' ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Review Date') ?></label>
            <input type="date" name="contract[review_date]" class="form-control" value="<?php echo $contract->review_date ?? '' ?>">
          </div>
          <div class="col-md-3">
            <div class="form-check mt-4">
              <input type="checkbox" name="contract[auto_renew]" id="auto_renew" class="form-check-input" value="1" <?php echo !empty($contract->auto_renew) ? 'checked' : '' ?>>
              <label class="form-check-label" for="auto_renew"><?php echo __('Auto-renew') ?></label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Financial -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Financial Terms') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-2">
            <div class="form-check">
              <input type="checkbox" name="contract[has_financial_terms]" id="has_financial" class="form-check-input" value="1" <?php echo !empty($contract->has_financial_terms) ? 'checked' : '' ?>>
              <label class="form-check-label" for="has_financial"><?php echo __('Has financial terms') ?></label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Contract Value') ?></label>
            <input type="number" step="0.01" name="contract[contract_value]" class="form-control" value="<?php echo $contract->contract_value ?? '' ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label"><?php echo __('Currency') ?></label>
            <select name="contract[currency]" class="form-select">
              <option value="ZAR" <?php echo ($contract->currency ?? 'ZAR') == 'ZAR' ? 'selected' : '' ?>>ZAR</option>
              <option value="USD" <?php echo ($contract->currency ?? '') == 'USD' ? 'selected' : '' ?>>USD</option>
              <option value="EUR" <?php echo ($contract->currency ?? '') == 'EUR' ? 'selected' : '' ?>>EUR</option>
              <option value="GBP" <?php echo ($contract->currency ?? '') == 'GBP' ? 'selected' : '' ?>>GBP</option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label"><?php echo __('Payment Terms') ?></label>
            <input type="text" name="contract[payment_terms]" class="form-control" value="<?php echo esc_entities($contract->payment_terms ?? '') ?>" placeholder="<?php echo __('e.g., 30 days from invoice') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Terms & Conditions -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Terms & Conditions') ?></h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label"><?php echo __('Scope of Work') ?></label>
            <textarea name="contract[scope_of_work]" class="form-control" rows="4"><?php echo esc_entities($contract->scope_of_work ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Deliverables') ?></label>
            <textarea name="contract[deliverables]" class="form-control" rows="3"><?php echo esc_entities($contract->deliverables ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('General Terms') ?></label>
            <textarea name="contract[general_terms]" class="form-control" rows="4"><?php echo esc_entities($contract->general_terms ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Special Conditions') ?></label>
            <textarea name="contract[special_conditions]" class="form-control" rows="3"><?php echo esc_entities($contract->special_conditions ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('IP Terms') ?></label>
            <textarea name="contract[ip_terms]" class="form-control" rows="3"><?php echo esc_entities($contract->ip_terms ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Confidentiality Terms') ?></label>
            <textarea name="contract[confidentiality_terms]" class="form-control" rows="3"><?php echo esc_entities($contract->confidentiality_terms ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Governing Law') ?></label>
            <input type="text" name="contract[governing_law]" class="form-control" value="<?php echo esc_entities($contract->governing_law ?? 'South Africa') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Notes -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Internal Notes') ?></h5>
      </div>
      <div class="card-body">
        <textarea name="contract[internal_notes]" class="form-control" rows="3"><?php echo esc_entities($contract->internal_notes ?? '') ?></textarea>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-between">
      <a href="<?php echo url_for(['module' => 'contract', 'action' => 'browse']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> <?php echo $isNew ? __('Create Contract') : __('Save Changes') ?>
      </button>
    </div>
  </form>
</div>
