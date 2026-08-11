<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo __('Valuation') ?></h1>
  <p class="lead mb-0"><?php echo esc_specialchars($resource->title ?: $resource->slug) ?></p>
<?php end_slot() ?>

<p>
  <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resource->slug, 'procedure_type' => 'valuation']) ?>">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Valuation procedure') ?>
  </a>
</p>

<?php if ($valuations): ?>
  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th><?php echo __('Date') ?></th>
        <th><?php echo __('Amount') ?></th>
        <th><?php echo __('Basis') ?></th>
        <th><?php echo __('Valuer') ?></th>
        <th><?php echo __('Renewal') ?></th>
        <th><?php echo __('Current') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($valuations as $v): ?>
      <tr>
        <td><?php echo esc_specialchars((string) $v->valuation_date) ?></td>
        <td>
          <?php echo esc_specialchars((string) ($v->valuation_currency ?: $v->currency ?: '')) ?>
          <?php echo esc_specialchars(number_format((float) $v->valuation_amount, 2)) ?>
        </td>
        <td><?php echo esc_specialchars(ucwords(str_replace('_', ' ', (string) $v->valuation_type))) ?></td>
        <td>
          <?php echo esc_specialchars((string) ($v->valuer_name ?: $v->valuer ?: '')) ?>
          <?php if ($v->valuer_organization): ?>
            <div class="small text-muted"><?php echo esc_specialchars((string) $v->valuer_organization) ?></div>
          <?php endif ?>
        </td>
        <td class="small text-muted"><?php echo esc_specialchars((string) $v->renewal_date) ?></td>
        <td>
          <?php if ($v->is_current): ?>
            <span class="badge bg-success"><?php echo __('Current') ?></span>
          <?php endif ?>
        </td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
<?php else: ?>
  <div class="alert alert-info">
    <?php echo __('No valuation has been recorded. The procedure can still be worked through, but there will be nothing for it to propose to the accounts.') ?>
  </div>
<?php endif ?>

<h2 class="h5 mt-4"><?php echo __('Record a valuation') ?></h2>

<form method="post" class="row g-3">
  <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES) ?>">

  <div class="col-md-3">
    <label class="form-label" for="valuation_date"><?php echo __('Date') ?></label>
    <input type="date" class="form-control" id="valuation_date" name="valuation_date" required>
  </div>
  <div class="col-md-3">
    <label class="form-label" for="valuation_amount"><?php echo __('Amount') ?></label>
    <input type="text" class="form-control" id="valuation_amount" name="valuation_amount" inputmode="decimal" required>
  </div>
  <div class="col-md-2">
    <label class="form-label" for="valuation_currency"><?php echo __('Currency') ?></label>
    <input type="text" class="form-control" id="valuation_currency" name="valuation_currency" value="ZAR" maxlength="3">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="valuation_type"><?php echo __('Basis') ?></label>
    <select class="form-select" id="valuation_type" name="valuation_type">
      <?php foreach ($types as $k => $label): ?>
      <option value="<?php echo esc_specialchars($k) ?>"><?php echo esc_specialchars($label) ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label" for="valuer_name"><?php echo __('Valuer') ?></label>
    <input type="text" class="form-control" id="valuer_name" name="valuer_name">
  </div>
  <div class="col-md-4">
    <label class="form-label" for="valuer_organization"><?php echo __('Organisation') ?></label>
    <input type="text" class="form-control" id="valuer_organization" name="valuer_organization">
  </div>
  <div class="col-md-2">
    <label class="form-label" for="valuation_reference"><?php echo __('Report reference') ?></label>
    <input type="text" class="form-control" id="valuation_reference" name="valuation_reference">
  </div>
  <div class="col-md-2">
    <label class="form-label" for="renewal_date"><?php echo __('Renew by') ?></label>
    <input type="date" class="form-control" id="renewal_date" name="renewal_date">
  </div>

  <div class="col-12">
    <label class="form-label" for="valuation_note"><?php echo __('Note') ?></label>
    <textarea class="form-control" id="valuation_note" name="valuation_note" rows="2"></textarea>
  </div>

  <div class="col-12 form-check ms-2">
    <input class="form-check-input" type="checkbox" id="is_current" name="is_current" value="1" checked>
    <label class="form-check-label" for="is_current">
      <?php echo __('This is the current valuation') ?>
      <span class="text-muted small"><?php echo __('- any earlier one stops being current') ?></span>
    </label>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary"><?php echo __('Record valuation') ?></button>
  </div>
</form>
