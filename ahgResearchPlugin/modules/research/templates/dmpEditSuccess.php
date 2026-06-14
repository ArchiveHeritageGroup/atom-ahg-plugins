<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => 'dmps', 'unreadNotifications' => 0]) ?>
<?php end_slot() ?>
<?php
require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/DmpService.php';
$d = $sf_data->getRaw('dmp');
$projects = $sf_data->getRaw('projects') ?: [];
$v = function ($f) use ($d) { return htmlspecialchars((string) ($d->$f ?? '')); };
$postUrl = url_for(['module' => 'research', 'action' => 'dmpEdit', 'id' => $d->id]);
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dmps']); ?>"><?php echo __('Data Management Plans'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Edit'); ?></li>
  </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-pen text-primary me-2"></i><?php echo htmlspecialchars((string) $d->title); ?></h1>

<form method="post" action="<?php echo $postUrl; ?>">
  <div class="card mb-3"><div class="card-body">
    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Title'); ?> *</label>
        <input class="form-control" name="title" required value="<?php echo $v('title'); ?>"></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Status'); ?></label>
        <select class="form-select" name="status">
          <?php foreach (DmpService::STATUSES as $s): ?><option value="<?php echo $s; ?>"<?php echo $d->status === $s ? ' selected' : ''; ?>><?php echo __(ucfirst($s)); ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Version'); ?></label>
        <input class="form-control" name="version" value="<?php echo $v('version'); ?>"></div>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Funder'); ?></label>
        <input class="form-control" name="funder" value="<?php echo $v('funder'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Grant number'); ?></label>
        <input class="form-control" name="grant_number" value="<?php echo $v('grant_number'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Linked project'); ?></label>
        <select class="form-select" name="project_id"><option value=""><?php echo __('— none —'); ?></option>
          <?php foreach ($projects as $p): ?><option value="<?php echo (int) $p->id; ?>"<?php echo ((int) ($d->project_id ?? 0) === (int) $p->id) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $p->title); ?></option><?php endforeach; ?>
        </select></div>
    </div>
  </div></div>

  <?php foreach (DmpService::SECTIONS as $col => $label): ?>
    <div class="card mb-3"><div class="card-body">
      <label class="form-label fw-bold"><?php echo __($label); ?></label>
      <textarea class="form-control" name="<?php echo $col; ?>" rows="3"><?php echo $v($col); ?></textarea>
    </div></div>
  <?php endforeach; ?>

  <div class="mb-4">
    <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
    <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'research', 'action' => 'dmpView', 'id' => $d->id]); ?>"><?php echo __('View'); ?></a>
  </div>
</form>
