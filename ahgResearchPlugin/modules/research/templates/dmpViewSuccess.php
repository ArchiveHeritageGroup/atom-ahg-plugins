<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => 'dmps', 'unreadNotifications' => 0]) ?>
<?php end_slot() ?>
<?php
require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/DmpService.php';
$d = $sf_data->getRaw('dmp');
$datasets = $sf_data->getRaw('datasets') ?: [];
$pct = (int) $sf_data->getRaw('completeness');
$statusTone = ['draft' => 'secondary', 'active' => 'primary', 'final' => 'success'];
$sensTone = ['open' => 'success', 'restricted' => 'warning', 'sensitive' => 'danger'];
$viewUrl = url_for(['module' => 'research', 'action' => 'dmpView', 'id' => $d->id]);
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dmps']); ?>"><?php echo __('Data Management Plans'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) $d->title); ?></li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h1 class="h2"><i class="fas fa-clipboard-list text-primary me-2"></i><?php echo htmlspecialchars((string) $d->title); ?></h1>
    <p class="text-muted mb-0">
      <span class="badge bg-<?php echo $statusTone[$d->status] ?? 'secondary'; ?>"><?php echo __(ucfirst((string) $d->status)); ?></span>
      v<?php echo htmlspecialchars((string) $d->version); ?>
      <?php if ($d->funder): ?> · <?php echo htmlspecialchars((string) $d->funder); ?><?php endif; ?>
      <?php if ($d->grant_number): ?> · <?php echo htmlspecialchars((string) $d->grant_number); ?><?php endif; ?>
    </p>
  </div>
  <div class="text-end">
    <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'research', 'action' => 'dmpEdit', 'id' => $d->id]); ?>"><i class="fas fa-pen me-1"></i><?php echo __('Edit'); ?></a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'research', 'action' => 'dmpExport', 'id' => $d->id]); ?>"><i class="fas fa-download me-1"></i>MD</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for(['module' => 'research', 'action' => 'dmpExport', 'id' => $d->id, 'format' => 'json']); ?>">JSON</a>
  </div>
</div>

<div class="progress mb-4" style="height:18px">
  <div class="progress-bar<?php echo $pct < 100 ? ' bg-warning' : ' bg-success'; ?>" style="width:<?php echo $pct; ?>%"><?php echo $pct; ?>% <?php echo __('complete'); ?></div>
</div>

<?php foreach (DmpService::SECTIONS as $col => $label): $text = trim((string) ($d->$col ?? '')); ?>
  <div class="card mb-3"><div class="card-body">
    <h5><?php echo __($label); ?></h5>
    <?php if ('' !== $text): ?>
      <p class="mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars($text); ?></p>
    <?php else: ?>
      <p class="text-muted fst-italic mb-0"><?php echo __('Not yet documented.'); ?></p>
    <?php endif; ?>
  </div></div>
<?php endforeach; ?>

<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-database me-2"></i><?php echo __('Datasets'); ?> <span class="badge bg-secondary"><?php echo count($datasets); ?></span></h5>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDatasetModal"><i class="fas fa-plus me-1"></i><?php echo __('Add dataset'); ?></button>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0 align-middle">
      <thead><tr><th><?php echo __('Name'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Volume'); ?></th><th><?php echo __('Sensitivity'); ?></th><th><?php echo __('License'); ?></th><th><?php echo __('Repository'); ?></th><th></th></tr></thead>
      <tbody>
      <?php if (empty($datasets)): ?>
        <tr><td colspan="7" class="text-muted p-3"><?php echo __('No datasets described yet.'); ?></td></tr>
      <?php else: foreach ($datasets as $ds): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars((string) $ds->name); ?></strong><?php if ($ds->personal_data): ?> <span class="badge bg-danger"><?php echo __('personal data'); ?></span><?php endif; ?></td>
          <td><?php echo htmlspecialchars((string) ($ds->data_type ?? '')); ?></td>
          <td><?php echo htmlspecialchars((string) ($ds->est_volume ?? '')); ?></td>
          <td><span class="badge bg-<?php echo $sensTone[$ds->sensitivity] ?? 'secondary'; ?>"><?php echo __(ucfirst((string) $ds->sensitivity)); ?></span></td>
          <td><?php echo htmlspecialchars((string) ($ds->license ?? '')); ?></td>
          <td><?php echo htmlspecialchars((string) ($ds->repository ?? '')); ?></td>
          <td class="text-end">
            <form method="post" action="<?php echo $viewUrl; ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this dataset?'); ?>');">
              <input type="hidden" name="form_action" value="delete_dataset">
              <input type="hidden" name="dataset_id" value="<?php echo (int) $ds->id; ?>">
              <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="post" action="<?php echo $viewUrl; ?>" class="mb-5" onsubmit="return confirm('<?php echo __('Delete this entire DMP?'); ?>');">
  <input type="hidden" name="form_action" value="delete_dmp">
  <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i><?php echo __('Delete DMP'); ?></button>
</form>

<div class="modal fade" id="addDatasetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="post" action="<?php echo $viewUrl; ?>">
      <input type="hidden" name="form_action" value="add_dataset">
      <div class="modal-header"><h5 class="modal-title"><?php echo __('Add dataset'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Name'); ?> *</label><input class="form-control" name="name" required></div>
          <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Data type'); ?></label><input class="form-control" name="data_type" placeholder="images, interviews, …"></div>
        </div>
        <div class="mb-3"><label class="form-label"><?php echo __('Description'); ?></label><textarea class="form-control" name="description" rows="2"></textarea></div>
        <div class="row">
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Formats'); ?></label><input class="form-control" name="formats"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Estimated volume'); ?></label><input class="form-control" name="est_volume" placeholder="20 GB"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Sensitivity'); ?></label>
            <select class="form-select" name="sensitivity">
              <?php foreach (DmpService::SENSITIVITIES as $s): ?><option value="<?php echo $s; ?>"><?php echo __(ucfirst($s)); ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('License'); ?></label><input class="form-control" name="license"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Repository'); ?></label><input class="form-control" name="repository"></div>
          <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Retention period'); ?></label><input class="form-control" name="retention_period"></div>
        </div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="personal_data" value="1" id="pd"><label class="form-check-label" for="pd"><?php echo __('Contains personal data'); ?></label></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button class="btn btn-primary"><?php echo __('Add dataset'); ?></button>
      </div>
    </form>
  </div></div>
</div>
