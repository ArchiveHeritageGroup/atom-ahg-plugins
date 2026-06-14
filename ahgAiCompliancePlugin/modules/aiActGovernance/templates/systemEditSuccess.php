<?php
require_once dirname(__DIR__, 3) . '/lib/Services/AiActGovernanceService.php';
$r = $sf_data->getRaw('record');
$val = function ($f, $d = '') use ($r) { return htmlspecialchars((string) ($r->$f ?? $d)); };
$sel = function ($f, $opt) use ($r) { return ((string) ($r->$f ?? '') === (string) $opt) ? ' selected' : ''; };
$postUrl = url_for(['module' => 'aiActGovernance', 'action' => 'systemEdit']);
?>
<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-microchip me-2"></i><?php echo $r ? __('Edit AI System') : __('New AI System'); ?></h1>
  <form method="post" action="<?php echo $postUrl; ?>">
    <?php if ($r): ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
    <div class="row">
      <div class="col-md-8 mb-3"><label class="form-label"><?php echo __('Name'); ?> *</label>
        <input class="form-control" name="name" required value="<?php echo $val('name'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Owner'); ?></label>
        <input class="form-control" name="owner" value="<?php echo $val('owner'); ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Description'); ?></label>
      <textarea class="form-control" name="description" rows="2"><?php echo $val('description'); ?></textarea></div>
    <div class="mb-3"><label class="form-label"><?php echo __('Intended purpose (Art. 6)'); ?></label>
      <textarea class="form-control" name="purpose" rows="2"><?php echo $val('purpose'); ?></textarea></div>
    <div class="row">
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Risk classification'); ?></label>
        <select class="form-select" name="risk_classification">
          <?php foreach (AiActGovernanceService::RISK_CLASSIFICATIONS as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('risk_classification', $o); ?>><?php echo __(ucfirst($o)); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Lifecycle status'); ?></label>
        <select class="form-select" name="lifecycle_status">
          <?php foreach (AiActGovernanceService::LIFECYCLE_STATUSES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('lifecycle_status', $o); ?>><?php echo __(ucfirst($o)); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Role'); ?></label>
        <select class="form-select" name="role">
          <?php foreach (AiActGovernanceService::SYSTEM_ROLES as $o): ?><option value="<?php echo $o; ?>"<?php echo $sel('role', $o); ?>><?php echo __(ucfirst($o)); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Provider'); ?></label>
        <input class="form-control" name="provider" value="<?php echo $val('provider'); ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label"><?php echo __('Deployment context'); ?></label>
      <textarea class="form-control" name="deployment_context" rows="2"><?php echo $val('deployment_context'); ?></textarea></div>
    <div class="mb-3"><label class="form-label"><?php echo __('Human oversight measures (Art. 14)'); ?></label>
      <textarea class="form-control" name="human_oversight" rows="2"><?php echo $val('human_oversight'); ?></textarea></div>
    <div class="row">
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Last review'); ?></label>
        <input type="date" class="form-control" name="last_review_date" value="<?php echo $val('last_review_date'); ?>"></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Next review'); ?></label>
        <input type="date" class="form-control" name="next_review_date" value="<?php echo $val('next_review_date'); ?>"></div>
      <div class="col-md-3 mb-3 d-flex align-items-end"><div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act"<?php echo (!$r || $r->is_active) ? ' checked' : ''; ?>>
        <label class="form-check-label" for="act"><?php echo __('Active'); ?></label></div></div>
    </div>
    <div class="mt-2">
      <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
      <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'systems']); ?>"><?php echo __('Cancel'); ?></a>
    </div>
  </form>
</main>
