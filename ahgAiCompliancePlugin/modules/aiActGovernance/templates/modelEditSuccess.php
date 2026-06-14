<?php
require_once dirname(__DIR__, 3) . '/lib/Services/AiActGovernanceService.php';
$r = $sf_data->getRaw('record');
$systemOptions = $sf_data->getRaw('systemOptions') ?: [];
$val = function ($f, $d = '') use ($r) { return htmlspecialchars((string) ($r->$f ?? $d)); };
$sel = function ($f, $opt) use ($r) { return ((string) ($r->$f ?? '') === (string) $opt) ? ' selected' : ''; };
$postUrl = url_for(['module' => 'aiActGovernance', 'action' => 'modelEdit']);
?>
<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-cubes me-2"></i><?php echo $r ? __('Edit Model') : __('New Model'); ?></h1>
  <form method="post" action="<?php echo $postUrl; ?>">
    <?php if ($r): ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
    <div class="row">
      <div class="col-md-5 mb-3"><label class="form-label"><?php echo __('Model ID'); ?> *</label>
        <input class="form-control" name="model_id" required value="<?php echo $val('model_id'); ?>" placeholder="nomic-embed-text"></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Version'); ?></label>
        <input class="form-control" name="version" value="<?php echo $val('version'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Modality'); ?></label>
        <select class="form-select" name="modality">
          <?php foreach (AiActGovernanceService::MODALITIES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('modality', $o); ?>><?php echo __(ucfirst($o)); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Provider'); ?></label>
        <input class="form-control" name="provider" value="<?php echo $val('provider'); ?>"></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('AI system'); ?></label>
        <select class="form-select" name="system_id"><option value=""><?php echo __('— none —'); ?></option>
          <?php foreach ($systemOptions as $id => $name): ?><option value="<?php echo (int) $id; ?>"<?php echo $sel('system_id', $id); ?>><?php echo htmlspecialchars((string) $name); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Intended purpose'); ?></label>
      <textarea class="form-control" name="intended_purpose" rows="2"><?php echo $val('intended_purpose'); ?></textarea></div>
    <div class="mb-3"><label class="form-label"><?php echo __('Training data summary (Art. 10)'); ?></label>
      <textarea class="form-control" name="training_data_summary" rows="2"><?php echo $val('training_data_summary'); ?></textarea></div>
    <div class="mb-3"><label class="form-label"><?php echo __('Known limitations / accuracy / bias'); ?></label>
      <textarea class="form-control" name="limitations" rows="2"><?php echo $val('limitations'); ?></textarea></div>
    <div class="mb-3"><label class="form-label"><?php echo __('Evaluation summary'); ?></label>
      <textarea class="form-control" name="evaluation_summary" rows="2"><?php echo $val('evaluation_summary'); ?></textarea></div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('License'); ?></label>
        <input class="form-control" name="license" value="<?php echo $val('license'); ?>"></div>
      <div class="col-md-4 mb-3 d-flex align-items-end"><div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act"<?php echo (!$r || $r->is_active) ? ' checked' : ''; ?>>
        <label class="form-check-label" for="act"><?php echo __('Active'); ?></label></div></div>
    </div>
    <div class="mt-2">
      <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
      <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'models']); ?>"><?php echo __('Cancel'); ?></a>
    </div>
  </form>
</main>
