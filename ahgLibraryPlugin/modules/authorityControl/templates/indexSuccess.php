<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Subject Authority Control'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <span class="text-muted"><?php echo __('%1% authority records', ['%1%' => $total]); ?></span>
  </div>
  <div>
    <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'edit']); ?>" class="btn btn-success">
      <i class="fas fa-plus me-2"></i><?php echo __('Add Authority Record'); ?>
    </a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'authorityControl', 'action' => 'index']); ?>">
      <div class="row g-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label"><?php echo __('Search'); ?></label>
          <input type="text" name="search" class="form-control" placeholder="<?php echo __('Heading...'); ?>"
                 value="<?php echo esc_entities($search); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo __('Subject Type'); ?></label>
          <select name="subject_type" class="form-select">
            <option value=""><?php echo __('All types'); ?></option>
            <?php foreach (['topic' => 'Topical', 'name' => 'Name', 'geographic' => 'Geographic', 'temporal' => 'Temporal', 'genre' => 'Genre', 'title' => 'Title'] as $key => $label): ?>
              <option value="<?php echo $key; ?>" <?php echo $subjectType === $key ? 'selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label"><?php echo __('Source'); ?></label>
          <input type="text" name="source" class="form-control" placeholder="lcsh, mesh, local..."
                 value="<?php echo esc_entities($source); ?>">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (empty($sf_data->getRaw('authorities'))): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No authority records found. Click "Add Authority Record" to create one.'); ?>
  </div>
<?php else: ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Heading'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Source'); ?></th>
            <th class="text-center"><?php echo __('Linked'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sf_data->getRaw('authorities') as $auth): ?>
            <tr>
              <td>
                <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'view', 'id' => $auth->id]); ?>">
                  <?php echo esc_entities($auth->heading ?? ''); ?>
                </a>
              </td>
              <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst($auth->subject_type ?? 'topic')); ?></span></td>
              <td><?php echo esc_entities(strtoupper($auth->source ?? '')); ?></td>
              <td class="text-center"><?php echo (int) ($auth->linked_count ?? 0); ?></td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'view', 'id' => $auth->id]); ?>" class="btn btn-outline-primary" title="<?php echo __('View'); ?>">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'edit', 'id' => $auth->id]); ?>" class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                    <i class="fas fa-edit"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'index', 'page' => $i, 'search' => $search, 'subject_type' => $subjectType, 'source' => $source]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>

<?php endif; ?>
