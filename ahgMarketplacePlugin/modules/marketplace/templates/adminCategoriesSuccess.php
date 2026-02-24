<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Categories'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Categories'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Manage Categories'); ?></h1>

<!-- Add category form -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0"><?php echo __('Add Category'); ?></h5>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCategories']); ?>" class="row g-2 align-items-end">
      <input type="hidden" name="form_action" value="create">
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('Sector'); ?></label>
        <select name="sector" class="form-select form-select-sm" required>
          <option value=""><?php echo __('Select...'); ?></option>
          <?php foreach ($sectors as $sec): ?>
            <option value="<?php echo esc_entities($sec); ?>"><?php echo esc_entities(ucfirst($sec)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Name'); ?></label>
        <input type="text" name="name" class="form-control form-control-sm" required placeholder="<?php echo __('Category name'); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Description'); ?></label>
        <input type="text" name="description" class="form-control form-control-sm" placeholder="<?php echo __('Optional description'); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small"><?php echo __('Sort Order'); ?></label>
        <input type="number" name="sort_order" class="form-control form-control-sm" value="0" min="0">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-plus me-1"></i> <?php echo __('Add'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Categories table grouped by sector -->
<?php if (empty($categories)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-folder fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No categories yet'); ?></h5>
      <p class="text-muted"><?php echo __('Add your first category above.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <?php
    // Group categories by sector
    $grouped = [];
    foreach ($categories as $cat) {
        $sector = $cat->sector ?? 'other';
        if (!isset($grouped[$sector])) {
            $grouped[$sector] = [];
        }
        $grouped[$sector][] = $cat;
    }
    ksort($grouped);
  ?>

  <?php foreach ($grouped as $sectorName => $sectorCats): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <span class="badge bg-info me-2"><?php echo esc_entities(ucfirst($sectorName)); ?></span>
          <?php echo __('%1% categories', ['%1%' => count($sectorCats)]); ?>
        </h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Name'); ?></th>
              <th><?php echo __('Slug'); ?></th>
              <th><?php echo __('Description'); ?></th>
              <th class="text-end"><?php echo __('Sort Order'); ?></th>
              <th><?php echo __('Active'); ?></th>
              <th class="text-end"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sectorCats as $cat): ?>
              <tr>
                <td class="fw-semibold"><?php echo esc_entities($cat->name); ?></td>
                <td class="small text-muted"><?php echo esc_entities($cat->slug); ?></td>
                <td class="small"><?php echo esc_entities($cat->description ?? '-'); ?></td>
                <td class="text-end small"><?php echo (int) $cat->sort_order; ?></td>
                <td>
                  <?php if ($cat->is_active ?? 1): ?>
                    <span class="badge bg-success"><?php echo __('Active'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <!-- Edit button (triggers modal) -->
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCat<?php echo (int) $cat->id; ?>" title="<?php echo __('Edit'); ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <!-- Delete -->
                  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCategories']); ?>" class="d-inline">
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="category_id" value="<?php echo (int) $cat->id; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>" onclick="return confirm('<?php echo __('Delete this category?'); ?>');">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>

              <!-- Edit modal -->
              <tr style="display: none;"><td colspan="6">
                <div class="modal fade" id="editCat<?php echo (int) $cat->id; ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCategories']); ?>">
                        <input type="hidden" name="form_action" value="update">
                        <input type="hidden" name="category_id" value="<?php echo (int) $cat->id; ?>">
                        <div class="modal-header">
                          <h5 class="modal-title"><?php echo __('Edit Category'); ?></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label"><?php echo __('Sector'); ?></label>
                            <select name="sector" class="form-select" required>
                              <?php foreach ($sectors as $sec): ?>
                                <option value="<?php echo esc_entities($sec); ?>"<?php echo ($cat->sector ?? '') === $sec ? ' selected' : ''; ?>><?php echo esc_entities(ucfirst($sec)); ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="mb-3">
                            <label class="form-label"><?php echo __('Name'); ?></label>
                            <input type="text" name="name" class="form-control" value="<?php echo esc_entities($cat->name); ?>" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <input type="text" name="description" class="form-control" value="<?php echo esc_entities($cat->description ?? ''); ?>">
                          </div>
                          <div class="mb-3">
                            <label class="form-label"><?php echo __('Sort Order'); ?></label>
                            <input type="number" name="sort_order" class="form-control" value="<?php echo (int) $cat->sort_order; ?>" min="0">
                          </div>
                          <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active<?php echo (int) $cat->id; ?>"<?php echo ($cat->is_active ?? 1) ? ' checked' : ''; ?>>
                            <label class="form-check-label" for="active<?php echo (int) $cat->id; ?>"><?php echo __('Active'); ?></label>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                          <button type="submit" class="btn btn-primary"><?php echo __('Save Changes'); ?></button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </td></tr>

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php end_slot(); ?>
