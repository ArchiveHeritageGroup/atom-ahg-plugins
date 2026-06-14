<?php
require_once dirname(__DIR__, 3) . '/lib/Services/AiActGovernanceService.php';
$r = $sf_data->getRaw('record');
$systemOptions = $sf_data->getRaw('systemOptions') ?: [];
$val = function ($f, $d = '') use ($r) { return htmlspecialchars((string) ($r->$f ?? $d)); };
$sel = function ($f, $opt) use ($r) { return ((string) ($r->$f ?? '') === (string) $opt) ? ' selected' : ''; };
$score = function ($f, $opt, $def) use ($r) { $cur = $r->$f ?? $def; return ((int) $cur === (int) $opt) ? ' selected' : ''; };
$postUrl = url_for(['module' => 'aiActGovernance', 'action' => 'riskEdit']);
$scaleL = [1 => 'Rare', 2 => 'Unlikely', 3 => 'Possible', 4 => 'Likely', 5 => 'Almost certain'];
$scaleS = [1 => 'Negligible', 2 => 'Minor', 3 => 'Moderate', 4 => 'Major', 5 => 'Catastrophic'];
?>
<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-triangle-exclamation me-2"></i><?php echo $r ? __('Edit Risk') : __('New Risk'); ?></h1>
  <form method="post" action="<?php echo $postUrl; ?>">
    <?php if ($r): ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
    <div class="row">
      <div class="col-md-8 mb-3"><label class="form-label"><?php echo __('Title'); ?> *</label>
        <input class="form-control" name="title" required value="<?php echo $val('title'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Category'); ?></label>
        <select class="form-select" name="category">
          <?php foreach (AiActGovernanceService::RISK_CATEGORIES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('category', $o); ?>><?php echo __(ucwords(str_replace('_', ' ', $o))); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Description'); ?></label>
      <textarea class="form-control" name="description" rows="2"><?php echo $val('description'); ?></textarea></div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('AI system'); ?></label>
        <select class="form-select" name="system_id"><option value=""><?php echo __('— none —'); ?></option>
          <?php foreach ($systemOptions as $id => $name): ?><option value="<?php echo (int) $id; ?>"<?php echo $sel('system_id', $id); ?>><?php echo htmlspecialchars((string) $name); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Status'); ?></label>
        <select class="form-select" name="status">
          <?php foreach (AiActGovernanceService::RISK_STATUSES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('status', $o); ?>><?php echo __(ucfirst($o)); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <h6 class="text-muted"><?php echo __('Inherent risk'); ?></h6>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Likelihood'); ?></label>
        <select class="form-select" name="likelihood">
          <?php foreach ($scaleL as $n => $lab): ?><option value="<?php echo $n; ?>"<?php echo $score('likelihood', $n, 3); ?>><?php echo $n; ?> — <?php echo __($lab); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Severity'); ?></label>
        <select class="form-select" name="severity">
          <?php foreach ($scaleS as $n => $lab): ?><option value="<?php echo $n; ?>"<?php echo $score('severity', $n, 3); ?>><?php echo $n; ?> — <?php echo __($lab); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Mitigation measures (Art. 9)'); ?></label>
      <textarea class="form-control" name="mitigation" rows="2"><?php echo $val('mitigation'); ?></textarea></div>
    <h6 class="text-muted"><?php echo __('Residual risk (after mitigation, optional)'); ?></h6>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Residual likelihood'); ?></label>
        <select class="form-select" name="residual_likelihood"><option value=""><?php echo __('— n/a —'); ?></option>
          <?php foreach ($scaleL as $n => $lab): ?><option value="<?php echo $n; ?>"<?php echo ((string) ($r->residual_likelihood ?? '') === (string) $n) ? ' selected' : ''; ?>><?php echo $n; ?> — <?php echo __($lab); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Residual severity'); ?></label>
        <select class="form-select" name="residual_severity"><option value=""><?php echo __('— n/a —'); ?></option>
          <?php foreach ($scaleS as $n => $lab): ?><option value="<?php echo $n; ?>"<?php echo ((string) ($r->residual_severity ?? '') === (string) $n) ? ' selected' : ''; ?>><?php echo $n; ?> — <?php echo __($lab); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Owner'); ?></label>
        <input class="form-control" name="owner" value="<?php echo $val('owner'); ?>"></div>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Review date'); ?></label>
        <input type="date" class="form-control" name="review_date" value="<?php echo $val('review_date'); ?>"></div>
    </div>
    <div class="mt-2">
      <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
      <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'risks']); ?>"><?php echo __('Cancel'); ?></a>
    </div>
  </form>
</main>
