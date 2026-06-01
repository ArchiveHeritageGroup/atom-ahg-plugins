<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-check-circle"></i> <?php echo __('RiC-O SHACL Validation'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']); ?>"><?php echo __('RIC Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('SHACL Validation'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-info"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <strong><?php echo __('SHACL engine:'); ?></strong>
      <?php if (!empty($engineStatus['available'])): ?>
        <span class="badge bg-success"><?php echo __('Available'); ?></span>
      <?php else: ?>
        <span class="badge bg-warning text-dark"><?php echo __('Unavailable'); ?></span>
        <small class="text-muted ms-2"><?php echo htmlspecialchars($engineStatus['reason'] ?? ''); ?></small>
      <?php endif; ?>
    </div>
    <form method="post" action="<?php echo url_for(['module' => 'ricShacl', 'action' => 'run']); ?>" class="d-flex gap-2">
      <input type="text" name="graph" class="form-control form-control-sm" style="width:320px"
             placeholder="<?php echo __('Optional named graph URI (blank = all)'); ?>">
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa fa-play"></i> <?php echo __('Run validation'); ?>
      </button>
    </form>
  </div>
</div>

<?php if (empty($engineStatus['available'])): ?>
  <div class="alert alert-secondary">
    <?php echo __('The SHACL engine (python3 + pyshacl + rdflib) is not installed on this host. Validation will be recorded as "not verified" until the dependency is available. Install with: pip install pyshacl rdflib --break-system-packages'); ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><?php echo __('Recent validation reports'); ?></div>
  <div class="card-body p-0">
    <table class="table table-striped table-sm mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th><?php echo __('Run at'); ?></th>
          <th><?php echo __('Engine'); ?></th>
          <th><?php echo __('Result'); ?></th>
          <th class="text-end"><?php echo __('Triples'); ?></th>
          <th class="text-end"><?php echo __('Violations'); ?></th>
          <th class="text-end"><?php echo __('Warnings'); ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($reports)): ?>
        <tr><td colspan="8" class="text-center text-muted py-3"><?php echo __('No validation reports yet.'); ?></td></tr>
      <?php else: ?>
        <?php foreach ($reports as $r): ?>
          <?php $r = (array) $r; ?>
          <tr>
            <td><?php echo (int) $r['id']; ?></td>
            <td><?php echo htmlspecialchars((string) ($r['created_at'] ?? '')); ?></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars((string) ($r['engine'] ?? 'none')); ?></span></td>
            <td>
              <?php if (null === ($r['conforms'] ?? null)): ?>
                <span class="badge bg-secondary"><?php echo __('Not verified'); ?></span>
              <?php elseif ((int) $r['conforms'] === 1): ?>
                <span class="badge bg-success"><?php echo __('Conforms'); ?></span>
              <?php else: ?>
                <span class="badge bg-danger"><?php echo __('Issues'); ?></span>
              <?php endif; ?>
            </td>
            <td class="text-end"><?php echo (int) ($r['data_triples'] ?? 0); ?></td>
            <td class="text-end"><?php echo (int) ($r['violation_count'] ?? 0); ?></td>
            <td class="text-end"><?php echo (int) ($r['warning_count'] ?? 0); ?></td>
            <td class="text-end">
              <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'ricShacl', 'action' => 'report', 'id' => (int) $r['id']]); ?>">
                <?php echo __('View'); ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php end_slot(); ?>
