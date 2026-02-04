<?php
/**
 * Create User Dashboard
 */
?>

<div class="container py-4" style="max-width: 600px;">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardList']) ?>">
          My Dashboards
        </a>
      </li>
      <li class="breadcrumb-item active">Create New</li>
    </ol>
  </nav>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create Personal Dashboard</h5>
    </div>
    <div class="card-body">
      <?php if (isset($error)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle"></i> <?php echo esc_entities($error) ?>
        </div>
      <?php endif ?>

      <?php if (!$hasDashboards): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          This will be your first dashboard. It will be set as your default.
        </div>
      <?php endif ?>

      <form method="post" action="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardCreate']) ?>">
        <div class="mb-3">
          <label class="form-label" for="name">Dashboard Name <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control" required
                 value="<?php echo esc_entities($sf_request->getParameter('name', 'My Dashboard')) ?>"
                 placeholder="e.g., My Dashboard, Research View">
          <div class="form-text">Give your dashboard a name</div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="description">Description</label>
          <textarea name="description" id="description" class="form-control" rows="2"
                    placeholder="Optional description"><?php echo esc_entities($sf_request->getParameter('description', '')) ?></textarea>
        </div>

        <?php if ($hasDashboards): ?>
        <div class="mb-4">
          <div class="form-check">
            <input type="checkbox" name="is_default" id="is_default" class="form-check-input" value="1"
                   <?php echo $sf_request->getParameter('is_default') ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_default">
              Set as my default dashboard
            </label>
          </div>
        </div>
        <?php else: ?>
        <input type="hidden" name="is_default" value="1">
        <?php endif ?>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> Create Dashboard
          </button>
          <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardList']) ?>"
             class="btn btn-outline-secondary">
            Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
