<?php use_helper('Text') ?>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-cloud-upload text-primary me-2"></i>AHG Extended Import</h4>
    <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'map']) ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> Back to Mapping
    </a>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <i class="bi bi-info-circle me-2"></i>Import Summary
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr>
              <th width="40%">Source File</th>
              <td><?php echo esc_specialchars($filename) ?></td>
            </tr>
            <tr>
              <th>Records to Import</th>
              <td><strong class="text-primary"><?php echo number_format($rowCount) ?></strong></td>
            </tr>
            <tr>
              <th>Import Mode</th>
              <td><span class="badge bg-info">AHG Extended Import</span></td>
            </tr>
          </table>
        </div>
      </div>

      <?php if (!empty($hasAhgFields)): ?>
      <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">
          <i class="bi bi-puzzle me-2"></i>AHG Extended Fields Detected
        </div>
        <div class="card-body">
          <p class="small text-muted mb-2">The following AHG plugin fields will be imported:</p>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($hasAhgFields as $field): ?>
              <span class="badge bg-secondary"><?php echo esc_specialchars($field) ?></span>
            <?php endforeach ?>
          </div>
          <hr>
          <div class="row small">
            <div class="col-md-4">
              <i class="bi bi-clock-history text-info me-1"></i>
              <strong>Provenance</strong>: Will create provenance records
            </div>
            <div class="col-md-4">
              <i class="bi bi-shield-check text-info me-1"></i>
              <strong>Rights</strong>: Will create rights statements
            </div>
            <div class="col-md-4">
              <i class="bi bi-lock text-info me-1"></i>
              <strong>Security</strong>: Will set classifications
            </div>
          </div>
        </div>
      </div>
      <?php endif ?>

      <!-- Import Form -->
      <form method="post" action="<?php echo url_for(['module' => 'dataMigration', 'action' => 'executeAhgImport']) ?>">
        <input type="hidden" name="confirm" value="yes">

        <div class="card">
          <div class="card-header">
            <i class="bi bi-gear me-2"></i>Import Options
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Repository</label>
                <select name="repository_id" class="form-select">
                  <option value="">-- No Repository --</option>
                  <?php 
                  $repos = \Illuminate\Database\Capsule\Manager::table('repository')
                      ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
                      ->where('actor_i18n.culture', 'en')
                      ->select('repository.id', 'actor_i18n.authorized_form_of_name')
                      ->get();
                  foreach ($repos as $repo): ?>
                    <option value="<?php echo $repo->id ?>"><?php echo esc_specialchars($repo->authorized_form_of_name) ?></option>
                  <?php endforeach ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Parent Record ID (optional)</label>
                <input type="text" name="parent_id" class="form-control" placeholder="Leave empty for top-level">
              </div>

              <div class="col-md-6">
                <label class="form-label">Culture / Language</label>
                <select name="culture" class="form-select">
                  <option value="en" selected>English</option>
                  <option value="af">Afrikaans</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                  <option value="nl">Dutch</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">&nbsp;</label>
                <div class="form-check mt-2">
                  <input type="checkbox" name="update_existing" value="1" class="form-check-input" id="updateExisting">
                  <label class="form-check-label" for="updateExisting">Update existing records (match by Legacy ID)</label>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-play-fill me-1"></i> Start Import
            </button>
            <a href="<?php echo url_for(['module' => 'dataMigration', 'action' => 'map']) ?>" class="btn btn-outline-secondary ms-2">
              Cancel
            </a>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-4">
      <div class="card bg-light">
        <div class="card-body">
          <h6><i class="bi bi-lightbulb me-2"></i>About AHG Extended Import</h6>
          <p class="small text-muted">
            This import mode creates records with full AHG plugin integration:
          </p>
          <ul class="small text-muted">
            <li><strong>Provenance Plugin</strong>: Creates provenance records and events from ahgProvenanceHistory fields</li>
            <li><strong>Extended Rights Plugin</strong>: Creates rights statements from ahgRightsStatement fields</li>
            <li><strong>Security Clearance Plugin</strong>: Sets security classifications from ahgSecurityClassification</li>
          </ul>
          <hr>
          <p class="small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Standard ISAD fields are imported normally. AHG fields are processed through their respective plugins.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
