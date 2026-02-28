<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>
    <?php echo __('Appraisal'); ?>
    <small class="text-muted">&mdash; <?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></small>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
  $flash = $sf_user->getFlash('notice', '');
  $accId = $accession['id'] ?? 0;
  $accTitle = $accession['title'] ?? '';

  $typeLabels = [
      'archival' => __('Archival'),
      'monetary' => __('Monetary'),
      'insurance' => __('Insurance'),
      'historical' => __('Historical'),
      'research' => __('Research'),
  ];
  $significanceLabels = [
      'low' => __('Low'),
      'medium' => __('Medium'),
      'high' => __('High'),
      'exceptional' => __('Exceptional'),
      'national_significance' => __('National Significance'),
  ];
  $recommendationLabels = [
      'pending' => __('Pending'),
      'accept' => __('Accept'),
      'reject' => __('Reject'),
      'partial' => __('Partial'),
      'defer' => __('Defer'),
  ];
  $recommendationColors = [
      'pending' => 'warning',
      'accept' => 'success',
      'reject' => 'danger',
      'partial' => 'info',
      'defer' => 'secondary',
  ];
?>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  <?php echo htmlspecialchars($flash); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'accessionManage', 'action' => 'dashboard']); ?>"><?php echo __('Accessions'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')); ?>"><?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Appraisal'); ?></li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h4 mb-0"><?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></h2>
    <?php if (!empty($accTitle)): ?>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($accTitle); ?></p>
    <?php endif; ?>
  </div>
  <div class="btn-group">
    <a href="<?php echo url_for('@accession_valuation_view?id=' . $accId); ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-coins me-1"></i><?php echo __('Valuation History'); ?>
    </a>
    <a href="<?php echo url_for('@accession_appraisal_templates'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-file-alt me-1"></i><?php echo __('Templates'); ?>
    </a>
  </div>
</div>

<?php if ($currentAppraisal): ?>
<?php
  $ap = $currentAppraisal['appraisal'];
  $criteria = $currentAppraisal['criteria'] ?? [];
  $appraiserName = $currentAppraisal['appraiser_name'] ?? __('Unknown');
  $weightedScore = $currentAppraisal['weighted_score'];
?>

<form method="post" action="<?php echo url_for('@accession_appraisal_save?id=' . $accId); ?>">
  <input type="hidden" name="appraisal_id" value="<?php echo htmlspecialchars($ap->id); ?>">

  <div class="row">
    <div class="col-lg-8">
      <!-- Appraisal Info Card -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="fas fa-clipboard-check me-2"></i><?php echo __('Appraisal Detail'); ?></span>
          <span class="badge bg-<?php echo $recommendationColors[$ap->recommendation] ?? 'secondary'; ?>">
            <?php echo $recommendationLabels[$ap->recommendation] ?? ucfirst($ap->recommendation); ?>
          </span>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold"><?php echo __('Type'); ?></label>
              <select name="appraisal_type" class="form-select">
                <?php foreach ($typeLabels as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $ap->appraisal_type === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold"><?php echo __('Significance'); ?></label>
              <select name="significance" class="form-select">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php foreach ($significanceLabels as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($ap->significance ?? '') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold"><?php echo __('Recommendation'); ?></label>
              <select name="recommendation" class="form-select">
                <?php foreach ($recommendationLabels as $val => $label): ?>
                <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $ap->recommendation === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label fw-bold"><?php echo __('Monetary Value'); ?></label>
              <div class="input-group">
                <select name="currency" class="form-select" style="max-width: 80px;">
                  <?php foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $cur): ?>
                  <option value="<?php echo $cur; ?>" <?php echo ($ap->currency ?? 'ZAR') === $cur ? 'selected' : ''; ?>><?php echo $cur; ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="number" name="monetary_value" class="form-control" step="0.01" value="<?php echo htmlspecialchars($ap->monetary_value ?? ''); ?>" placeholder="0.00">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold"><?php echo __('Appraiser'); ?></label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars($appraiserName); ?>" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold"><?php echo __('Appraised Date'); ?></label>
              <input type="datetime-local" name="appraised_at" class="form-control" value="<?php echo $ap->appraised_at ? date('Y-m-d\TH:i', strtotime($ap->appraised_at)) : ''; ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold"><?php echo __('Summary'); ?></label>
            <textarea name="summary" class="form-control" rows="3"><?php echo htmlspecialchars($ap->summary ?? ''); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold"><?php echo __('Detailed Notes'); ?></label>
            <textarea name="detailed_notes" class="form-control" rows="4"><?php echo htmlspecialchars($ap->detailed_notes ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Criteria Scoring Grid -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-star-half-alt me-2"></i><?php echo __('Criteria Scoring'); ?>
        </div>
        <div class="card-body p-0">
          <?php if (count($criteria) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Criterion'); ?></th>
                  <th class="text-center" style="width:60px;"><?php echo __('Weight'); ?></th>
                  <th class="text-center" style="width:280px;"><?php echo __('Score (1-5)'); ?></th>
                  <th><?php echo __('Notes'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($criteria as $c): ?>
                <tr data-criterion-id="<?php echo htmlspecialchars($c->id); ?>">
                  <td>
                    <strong><?php echo htmlspecialchars($c->criterion_name); ?></strong>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-secondary"><?php echo number_format($c->weight, 2); ?></span>
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                      <input type="radio" class="btn-check score-radio" name="scores[<?php echo $c->id; ?>]" id="score_<?php echo $c->id; ?>_<?php echo $s; ?>" value="<?php echo $s; ?>" autocomplete="off" <?php echo ((int)($c->score ?? 0)) === $s ? 'checked' : ''; ?> data-criterion-id="<?php echo htmlspecialchars($c->id); ?>">
                      <label class="btn btn-outline-primary btn-sm" for="score_<?php echo $c->id; ?>_<?php echo $s; ?>"><?php echo $s; ?></label>
                      <?php endfor; ?>
                    </div>
                  </td>
                  <td>
                    <span class="text-muted small"><?php echo htmlspecialchars($c->notes ?? ''); ?></span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="text-center py-4 text-muted">
            <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
            <?php echo __('No criteria defined. Apply a template when creating the appraisal to populate criteria.'); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Weighted Score Card -->
      <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-calculator me-2"></i><?php echo __('Weighted Score'); ?>
        </div>
        <div class="card-body text-center">
          <div class="display-4 fw-bold" id="weightedScoreDisplay">
            <?php if ($weightedScore !== null): ?>
            <?php echo number_format($weightedScore, 2); ?>
            <?php else: ?>
            <span class="text-muted">&mdash;</span>
            <?php endif; ?>
          </div>
          <p class="text-muted mb-0"><?php echo __('out of 5.00'); ?></p>
          <?php if ($weightedScore !== null): ?>
          <div class="progress mt-3" style="height: 8px;">
            <div class="progress-bar <?php echo $weightedScore >= 4 ? 'bg-success' : ($weightedScore >= 3 ? 'bg-info' : ($weightedScore >= 2 ? 'bg-warning' : 'bg-danger')); ?>" style="width: <?php echo ($weightedScore / 5) * 100; ?>%"></div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="card mb-4">
        <div class="card-body">
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-save me-1"></i><?php echo __('Save Appraisal'); ?>
          </button>
          <a href="<?php echo url_for('@accession_appraisal_form?id=' . $accId); ?>" class="btn btn-outline-secondary w-100">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to List'); ?>
          </a>
        </div>
      </div>

      <!-- Record Info -->
      <div class="card">
        <div class="card-header">
          <i class="fas fa-info-circle me-2"></i><?php echo __('Record Info'); ?>
        </div>
        <div class="card-body">
          <small class="text-muted">
            <?php echo __('Created'); ?>: <?php echo date('d M Y H:i', strtotime($ap->created_at)); ?><br>
            <?php echo __('Updated'); ?>: <?php echo date('d M Y H:i', strtotime($ap->updated_at)); ?>
          </small>
        </div>
      </div>
    </div>
  </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var radios = document.querySelectorAll('.score-radio');
  radios.forEach(function(radio) {
    radio.addEventListener('change', function() {
      var criterionId = this.getAttribute('data-criterion-id');
      var score = this.value;

      fetch('<?php echo url_for("@accession_api_appraisal_score?id=0"); ?>'.replace('/0/', '/' + criterionId + '/'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + criterionId + '&score=' + score
      })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.weighted_score !== null && data.weighted_score !== undefined) {
          var display = document.getElementById('weightedScoreDisplay');
          display.textContent = parseFloat(data.weighted_score).toFixed(2);
        }
      })
      .catch(function(err) {
        console.error('Score update failed:', err);
      });
    });
  });
});
</script>

<?php else: ?>

<div class="row">
  <div class="col-lg-8">
    <!-- Existing Appraisals -->
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-list me-2"></i><?php echo __('Appraisals'); ?>
      </div>
      <div class="card-body p-0">
        <?php if (is_array($appraisals) && count($appraisals) > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Significance'); ?></th>
                <th><?php echo __('Recommendation'); ?></th>
                <th><?php echo __('Value'); ?></th>
                <th><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appraisals as $a): ?>
              <tr>
                <td><?php echo $a->appraised_at ? date('d M Y', strtotime($a->appraised_at)) : date('d M Y', strtotime($a->created_at)); ?></td>
                <td>
                  <span class="badge bg-info"><?php echo $typeLabels[$a->appraisal_type] ?? ucfirst($a->appraisal_type); ?></span>
                </td>
                <td><?php echo $significanceLabels[$a->significance ?? ''] ?? ($a->significance ? ucfirst(str_replace('_', ' ', $a->significance)) : '&mdash;'); ?></td>
                <td>
                  <span class="badge bg-<?php echo $recommendationColors[$a->recommendation] ?? 'secondary'; ?>">
                    <?php echo $recommendationLabels[$a->recommendation] ?? ucfirst($a->recommendation); ?>
                  </span>
                </td>
                <td>
                  <?php if ($a->monetary_value): ?>
                  <?php echo htmlspecialchars($a->currency ?? 'ZAR'); ?> <?php echo number_format($a->monetary_value, 2); ?>
                  <?php else: ?>
                  <span class="text-muted">&mdash;</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo url_for('@accession_appraisal_form?id=' . $accId . '&appraisal_id=' . $a->id); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View / Edit'); ?>">
                    <i class="fas fa-pen"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fas fa-clipboard fa-2x mb-2 d-block"></i>
          <?php echo __('No appraisals recorded yet. Create one below.'); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- New Appraisal Form -->
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <i class="fas fa-plus-circle me-2"></i><?php echo __('New Appraisal'); ?>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for('@accession_appraisal_save?id=' . $accId); ?>">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Template'); ?></label>
            <select name="template_id" class="form-select">
              <option value=""><?php echo __('-- No Template --'); ?></option>
              <?php if (is_array($templates)): ?>
              <?php foreach ($templates as $t): ?>
              <option value="<?php echo htmlspecialchars($t->id); ?>"><?php echo htmlspecialchars($t->name); ?><?php echo $t->sector ? ' (' . ucfirst($t->sector) . ')' : ''; ?><?php echo $t->is_default ? ' *' : ''; ?></option>
              <?php endforeach; ?>
              <?php endif; ?>
            </select>
            <div class="form-text"><?php echo __('Selecting a template populates the criteria scoring grid.'); ?></div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Type'); ?></label>
            <select name="appraisal_type" class="form-select">
              <?php foreach ($typeLabels as $val => $label): ?>
              <option value="<?php echo htmlspecialchars($val); ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Significance'); ?></label>
            <select name="significance" class="form-select">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($significanceLabels as $val => $label): ?>
              <option value="<?php echo htmlspecialchars($val); ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Recommendation'); ?></label>
            <select name="recommendation" class="form-select">
              <?php foreach ($recommendationLabels as $val => $label): ?>
              <option value="<?php echo htmlspecialchars($val); ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Monetary Value'); ?></label>
            <div class="input-group">
              <select name="currency" class="form-select" style="max-width: 80px;">
                <?php foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $cur): ?>
                <option value="<?php echo $cur; ?>"><?php echo $cur; ?></option>
                <?php endforeach; ?>
              </select>
              <input type="number" name="monetary_value" class="form-control" step="0.01" placeholder="0.00">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Summary'); ?></label>
            <textarea name="summary" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Detailed Notes'); ?></label>
            <textarea name="detailed_notes" class="form-control" rows="3"></textarea>
          </div>

          <button type="submit" class="btn btn-success w-100">
            <i class="fas fa-plus me-1"></i><?php echo __('Create Appraisal'); ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>
<?php end_slot(); ?>
