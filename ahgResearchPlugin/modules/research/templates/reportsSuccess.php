<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-file-alt text-primary me-2"></i><?php echo __('Research Reports'); ?></h1>
  <a href="<?php echo url_for(['module' => 'research', 'action' => 'newReport']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i><?php echo __('New Report'); ?>
  </a>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$reports = isset($reports) && is_array($reports) ? $reports : (isset($reports) && method_exists($reports, 'getRawValue') ? $reports->getRawValue() : (isset($reports) && is_iterable($reports) ? iterator_to_array($reports) : []));
$currentStatus = $sf_request->getParameter('status', '');
?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Reports'); ?></li>
  </ol>
</nav>

<!-- Status Filter Tabs -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item">
    <a class="nav-link <?php echo empty($currentStatus) ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'research', 'action' => 'reports']); ?>"><?php echo __('All'); ?></a>
  </li>
  <?php foreach (['draft' => 'Draft', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'] as $statusKey => $statusLabel): ?>
  <li class="nav-item">
    <a class="nav-link <?php echo ($currentStatus === $statusKey) ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'research', 'action' => 'reports', 'status' => $statusKey]); ?>"><?php echo __($statusLabel); ?></a>
  </li>
  <?php endforeach; ?>
</ul>

<?php if (!empty($reports)): ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Title'); ?></th>
          <th><?php echo __('Template'); ?></th>
          <th><?php echo __('Project'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th class="text-center"><?php echo __('Sections'); ?></th>
          <th><?php echo __('Last Updated'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $report): ?>
        <tr>
          <td>
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewReport', 'id' => $report->id]); ?>" class="text-decoration-none fw-semibold">
              <?php echo htmlspecialchars($report->title); ?>
            </a>
            <?php if (!empty($report->description)): ?>
              <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($report->description, 0, 60)); ?><?php echo mb_strlen($report->description ?? '') > 60 ? '...' : ''; ?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?php echo match($report->template_type ?? 'custom') {
              'research_summary' => 'primary', 'genealogical' => 'success', 'historical' => 'info',
              'source_analysis' => 'warning', 'finding_aid' => 'secondary', default => 'dark'
            }; ?>"><?php echo ucwords(str_replace('_', ' ', $report->template_type ?? 'custom')); ?></span>
          </td>
          <td>
            <?php if (!empty($report->project_title)): ?>
              <small><?php echo htmlspecialchars($report->project_title); ?></small>
            <?php else: ?>
              <small class="text-muted"><?php echo __('None'); ?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge rounded-pill bg-<?php echo match($report->status ?? 'draft') {
              'draft' => 'secondary', 'in_progress' => 'primary', 'review' => 'warning', 'completed' => 'success', default => 'dark'
            }; ?>"><?php echo ucwords(str_replace('_', ' ', $report->status ?? 'draft')); ?></span>
          </td>
          <td class="text-center">
            <span class="badge bg-light text-dark"><?php echo (int) ($report->section_count ?? 0); ?></span>
          </td>
          <td>
            <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($report->updated_at)); ?></small>
          </td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewReport', 'id' => $report->id]); ?>" class="btn btn-outline-primary" title="<?php echo __('View'); ?>"><i class="fas fa-eye"></i></a>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportReport', 'id' => $report->id, 'format' => 'pdf']); ?>" class="btn btn-outline-secondary" title="<?php echo __('Export PDF'); ?>"><i class="fas fa-file-pdf"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="text-center py-5">
    <i class="fas fa-file-alt fa-4x text-muted mb-3 opacity-50"></i>
    <h4 class="text-muted"><?php echo __('No reports yet'); ?></h4>
    <p class="text-muted"><?php echo __('Create a report to document your research findings.'); ?></p>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'newReport']); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('Create First Report'); ?>
    </a>
  </div>
<?php endif; ?>
<?php end_slot() ?>
