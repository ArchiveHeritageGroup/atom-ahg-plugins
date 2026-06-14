<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => 'dmps', 'unreadNotifications' => 0]) ?>
<?php end_slot() ?>
<?php
$dmps = $sf_data->getRaw('dmps') ?: [];
$svc = $sf_data->getRaw('svc');
$statusTone = ['draft' => 'secondary', 'active' => 'primary', 'final' => 'success'];
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
    <li class="breadcrumb-item active"><?php echo __('Data Management Plans'); ?></li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h2"><i class="fas fa-clipboard-list text-primary me-2"></i><?php echo __('Data Management Plans'); ?></h1>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDmpModal">
    <i class="fas fa-plus me-1"></i> <?php echo __('New DMP'); ?>
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th><?php echo __('Title'); ?></th><th><?php echo __('Funder'); ?></th><th><?php echo __('Project'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Completeness'); ?></th><th><?php echo __('Updated'); ?></th><th></th></tr></thead>
      <tbody>
      <?php if (empty($dmps)): ?>
        <tr><td colspan="7" class="text-muted p-3"><?php echo __('No data management plans yet. Create one to document how research data will be collected, stored, shared and preserved.'); ?></td></tr>
      <?php else: foreach ($dmps as $d): $pct = $svc ? $svc->completeness($d) : 0; ?>
        <tr>
          <td><a href="<?php echo url_for(['module' => 'research', 'action' => 'dmpView', 'id' => $d->id]); ?>"><strong><?php echo htmlspecialchars((string) $d->title); ?></strong></a> <span class="text-muted small">v<?php echo htmlspecialchars((string) $d->version); ?></span></td>
          <td><?php echo htmlspecialchars((string) ($d->funder ?? '')); ?></td>
          <td><?php echo htmlspecialchars((string) ($d->project_title ?? '—')); ?></td>
          <td><span class="badge bg-<?php echo $statusTone[$d->status] ?? 'secondary'; ?>"><?php echo __(ucfirst((string) $d->status)); ?></span></td>
          <td style="min-width:120px">
            <div class="progress" style="height:14px"><div class="progress-bar<?php echo $pct < 100 ? ' bg-warning' : ' bg-success'; ?>" style="width:<?php echo (int) $pct; ?>%"><?php echo (int) $pct; ?>%</div></div>
          </td>
          <td class="small text-muted"><?php echo htmlspecialchars((string) $d->updated_at); ?></td>
          <td class="text-end">
            <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'research', 'action' => 'dmpEdit', 'id' => $d->id]); ?>"><i class="fas fa-pen"></i></a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="createDmpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'dmps']); ?>">
      <input type="hidden" name="form_action" value="create">
      <div class="modal-header"><h5 class="modal-title"><?php echo __('New Data Management Plan'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label"><?php echo __('Title'); ?> *</label>
          <input class="form-control" name="title" required></div>
        <div class="mb-3"><label class="form-label"><?php echo __('Funder'); ?></label>
          <input class="form-control" name="funder"></div>
        <div class="mb-3"><label class="form-label"><?php echo __('Grant number'); ?></label>
          <input class="form-control" name="grant_number"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button class="btn btn-primary"><?php echo __('Create'); ?></button>
      </div>
    </form>
  </div></div>
</div>
