<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<h1>Edit Rights: <?php echo htmlspecialchars($resource->title ?? 'Untitled'); ?></h1>
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'index']); ?>">Extended Rights</a></li>
    <li class="breadcrumb-item active">Edit</li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post">
  <div class="row">
    <div class="col-md-6">
      <!-- Rights Statement -->
      <div class="card mb-4">
        <div class="card-header"><strong>Rights Statement</strong></div>
        <div class="card-body">
          <select name="rights_statement_id" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($rightsStatements as $rs): ?>
              <option value="<?php echo $rs->id; ?>"
                <?php echo (isset($currentRights->rights_statement) && $currentRights->rights_statement->rights_statement_id == $rs->id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($rs->name ?? $rs->code); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Creative Commons -->
      <div class="card mb-4">
        <div class="card-header"><strong>Creative Commons License</strong></div>
        <div class="card-body">
          <select name="cc_license_id" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($ccLicenses as $cc): ?>
              <option value="<?php echo $cc->id; ?>"
                <?php echo (isset($currentRights->creative_commons) && $currentRights->creative_commons->creative_commons_license_id == $cc->id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cc->name ?? $cc->code); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Rights Holder (Donor) -->
      <div class="card mb-4">
        <div class="card-header"><strong>Rights Holder (Donor)</strong></div>
        <div class="card-body">
          <select name="rights_holder_id" id="rights_holder_id" class="form-select" placeholder="Type to search...">
            <option value="">-- None --</option>
            <?php if (isset($donors) && count($donors) > 0): ?>
              <?php 
              $currentHolderId = isset($currentRights->rights_holder->donor_id) ? $currentRights->rights_holder->donor_id : null;
              foreach ($donors as $donor): ?>
                <option value="<?php echo $donor->id; ?>"
                  <?php echo ($currentHolderId == $donor->id) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($donor->name ?? 'Unknown'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
          <small class="text-muted">Select the donor who holds the rights to this material.</small>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <!-- TK Labels -->
      <div class="card mb-4">
        <div class="card-header"><strong>TK Labels</strong></div>
        <div class="card-body">
          <?php 
          $selectedTkLabels = $currentRights->tk_labels ?? []; 
          if ($selectedTkLabels instanceof sfOutputEscaperArrayDecorator) { 
              $selectedTkLabels = $selectedTkLabels->getRawValue(); 
          } 
          if (!is_array($selectedTkLabels)) { 
              $selectedTkLabels = []; 
          } 
          ?>
          <?php foreach ($tkLabels as $tk): ?>
            <div class="form-check">
              <input type="checkbox" name="tk_label_ids[]" value="<?php echo $tk->id; ?>"
                     class="form-check-input" id="tk_<?php echo $tk->id; ?>"
                     <?php echo in_array($tk->id, $selectedTkLabels) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="tk_<?php echo $tk->id; ?>">
                <?php echo htmlspecialchars($tk->name ?? $tk->code); ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="mb-4">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> Save Rights
    </button>
    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>" class="btn btn-secondary">
      Cancel
    </a>
  </div>
</form>

<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#rights_holder_id', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: 'Type to search for donors...'
    });
});
</script>
