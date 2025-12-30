<h1>Embargo Management</h1>

<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'index']); ?>">Extended Rights</a></li>
    <li class="breadcrumb-item active">Embargoes</li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Active Embargoes</h5>
  </div>
  <div class="card-body">
    <?php if (!empty($embargoes) && count($embargoes) > 0): ?>
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($embargoes as $embargo): ?>
            <tr>
              <td>
                <?php if (!empty($embargo->slug)): ?>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $embargo->slug]); ?>">
                    <?php echo htmlspecialchars($embargo->title ?? 'Untitled'); ?>
                  </a>
                <?php else: ?>
                  <?php echo htmlspecialchars($embargo->title ?? 'Untitled'); ?>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-<?php echo $embargo->embargo_type === 'full' ? 'danger' : 'warning'; ?>">
                  <?php echo htmlspecialchars(ucfirst($embargo->embargo_type ?? 'full')); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($embargo->start_date ?? '-'); ?></td>
              <td>
                <?php if (!empty($embargo->end_date)): ?>
                  <?php 
                  $endDate = new DateTime($embargo->end_date);
                  $now = new DateTime();
                  $isExpiringSoon = $endDate <= $now->modify('+30 days');
                  ?>
                  <span class="<?php echo $isExpiringSoon ? 'text-warning fw-bold' : ''; ?>">
                    <?php echo htmlspecialchars($embargo->end_date); ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">Indefinite</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'liftEmbargo', 'id' => $embargo->id]); ?>" 
                   class="btn btn-sm btn-success"
                   onclick="return confirm('Are you sure you want to lift this embargo?');">
                  <i class="fas fa-unlock"></i> Lift
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No active embargoes found.
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="mt-3">
  <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'index']); ?>" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back to Extended Rights
  </a>
</div>
