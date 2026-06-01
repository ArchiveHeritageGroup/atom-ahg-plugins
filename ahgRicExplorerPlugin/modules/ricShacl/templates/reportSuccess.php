<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-check-circle"></i> <?php echo __('SHACL Report'); ?> #<?php echo (int) $report['id']; ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  $stats = $report['statistics'] ?? [];
  $sev = $stats['by_severity'] ?? [];
  $byShape = $stats['by_shape'] ?? [];
  $byType = $stats['by_entity_type'] ?? [];
  $violations = $report['violations'] ?? [];
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricShacl', 'action' => 'index']); ?>"><?php echo __('SHACL Validation'); ?></a></li>
    <li class="breadcrumb-item active">#<?php echo (int) $report['id']; ?></li>
  </ol>
</nav>

<div class="row mb-3">
  <div class="col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="h3 mb-0"><?php echo (int) ($report['data_triples'] ?? 0); ?></div>
      <div class="text-muted small"><?php echo __('Total triples'); ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="h3 mb-0 text-danger"><?php echo (int) ($sev['Violation'] ?? 0); ?></div>
      <div class="text-muted small"><?php echo __('Violations'); ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="h3 mb-0 text-warning"><?php echo (int) ($sev['Warning'] ?? 0); ?></div>
      <div class="text-muted small"><?php echo __('Warnings'); ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card text-center"><div class="card-body">
      <div class="h3 mb-0 text-info"><?php echo (int) ($sev['Info'] ?? 0); ?></div>
      <div class="text-muted small"><?php echo __('Info'); ?></div>
    </div></div>
  </div>
</div>

<div class="card mb-3"><div class="card-body">
  <dl class="row mb-0">
    <dt class="col-sm-3"><?php echo __('Result'); ?></dt>
    <dd class="col-sm-9">
      <?php if (null === ($report['conforms'] ?? null)): ?>
        <span class="badge bg-secondary"><?php echo __('Not verified'); ?></span>
      <?php elseif ((int) $report['conforms'] === 1): ?>
        <span class="badge bg-success"><?php echo __('Conforms'); ?></span>
      <?php else: ?>
        <span class="badge bg-danger"><?php echo __('Does not conform'); ?></span>
      <?php endif; ?>
      <span class="text-muted ms-2"><?php echo htmlspecialchars((string) ($report['reason'] ?? '')); ?></span>
    </dd>
    <dt class="col-sm-3"><?php echo __('Engine'); ?></dt>
    <dd class="col-sm-9"><?php echo htmlspecialchars((string) ($report['engine'] ?? 'none')); ?></dd>
    <dt class="col-sm-3"><?php echo __('Graph'); ?></dt>
    <dd class="col-sm-9"><?php echo htmlspecialchars((string) ($report['graph_uri'] ?? __('all'))); ?></dd>
    <dt class="col-sm-3"><?php echo __('Run window'); ?></dt>
    <dd class="col-sm-9"><?php echo htmlspecialchars((string) ($report['started_at'] ?? '')); ?> &rarr; <?php echo htmlspecialchars((string) ($report['finished_at'] ?? '')); ?></dd>
  </dl>
</div></div>

<?php if (!empty($violations)): ?>
<div class="card mb-3">
  <div class="card-header"><?php echo __('Violations'); ?> (<?php echo count($violations); ?>)</div>
  <div class="card-body p-0">
    <table class="table table-sm table-striped mb-0">
      <thead><tr>
        <th><?php echo __('Entity'); ?></th>
        <th><?php echo __('Severity'); ?></th>
        <th><?php echo __('Shape'); ?></th>
        <th><?php echo __('Path'); ?></th>
        <th><?php echo __('Message'); ?></th>
      </tr></thead>
      <tbody>
        <?php foreach (array_slice($violations, 0, 200) as $v): ?>
          <?php $v = (array) $v; ?>
          <tr>
            <td><code><?php echo htmlspecialchars((string) ($v['focus_node'] ?? '')); ?></code></td>
            <td><?php echo htmlspecialchars((string) ($v['severity'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($v['source_shape'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($v['path'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($v['message'] ?? '')); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
  <div class="alert alert-success"><?php echo __('No violations recorded for this run.'); ?></div>
<?php endif; ?>

<?php if (!empty($byShape)): ?>
<div class="card">
  <div class="card-header"><?php echo __('Issues by validation rule'); ?></div>
  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <thead><tr><th><?php echo __('Rule'); ?></th><th class="text-end"><?php echo __('Count'); ?></th></tr></thead>
      <tbody>
        <?php arsort($byShape); foreach ($byShape as $shape => $count): ?>
          <tr><td><?php echo htmlspecialchars((string) $shape); ?></td><td class="text-end"><?php echo (int) $count; ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php end_slot(); ?>
