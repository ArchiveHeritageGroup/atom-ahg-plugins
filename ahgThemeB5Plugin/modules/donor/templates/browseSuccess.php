<?php decorate_with('layout_1col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1 class="h3 mb-0"><?php echo __('Browse donors'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

<div class="row mb-4">
  <!-- Search -->
  <div class="col-md-6 mb-3 mb-md-0">
    <form action="<?php echo url_for(['module' => 'donor', 'action' => 'browse']); ?>" method="get" class="d-flex">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="subquery" class="form-control" placeholder="<?php echo __('Search donors...'); ?>" value="<?php echo esc_entities($subquery); ?>">
        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
        <input type="hidden" name="sortDir" value="<?php echo $sortDir; ?>">
        <button type="submit" class="btn btn-primary"><?php echo __('Search'); ?></button>
      </div>
    </form>
  </div>

  <!-- Sort Controls -->
  <div class="col-md-6">
    <div class="d-flex justify-content-md-end gap-2">
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <i class="bi bi-sort-alpha-down me-1"></i><?php echo __('Sort by'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item <?php echo 'alphabetic' === $sort ? 'active' : ''; ?>" 
               href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'sort' => 'alphabetic', 'sortDir' => 'asc', 'subquery' => $subquery]); ?>">
              <i class="bi bi-sort-alpha-down me-2"></i><?php echo __('Name (A-Z)'); ?>
            </a>
          </li>
          <li>
            <a class="dropdown-item <?php echo 'lastUpdated' === $sort ? 'active' : ''; ?>" 
               href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'sort' => 'lastUpdated', 'sortDir' => 'desc', 'subquery' => $subquery]); ?>">
              <i class="bi bi-clock-history me-2"></i><?php echo __('Date modified'); ?>
            </a>
          </li>
          <li>
            <a class="dropdown-item <?php echo 'identifier' === $sort ? 'active' : ''; ?>" 
               href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'sort' => 'identifier', 'sortDir' => 'asc', 'subquery' => $subquery]); ?>">
              <i class="bi bi-hash me-2"></i><?php echo __('Identifier'); ?>
            </a>
          </li>
        </ul>
      </div>

      <?php
      $newDir = ('asc' === $sortDir) ? 'desc' : 'asc';
      $dirIcon = ('asc' === $sortDir) ? 'bi-sort-up' : 'bi-sort-down';
      ?>
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'sort' => $sort, 'sortDir' => $newDir, 'subquery' => $subquery]); ?>" 
         class="btn btn-outline-secondary" title="<?php echo __('Toggle sort direction'); ?>">
        <i class="bi <?php echo $dirIcon; ?>"></i>
      </a>
    </div>
  </div>
</div>

<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if ($total > 0) { ?>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-people me-2"></i><?php echo __('Donors'); ?></span>
      <span class="badge bg-secondary"><?php echo $total; ?> <?php echo __('results'); ?></span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col"><?php echo __('Name'); ?></th>
            <?php if ('alphabetic' !== $sort) { ?>
              <th scope="col" class="text-end"><?php echo __('Updated'); ?></th>
            <?php } ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donors as $item) { ?>
            <tr>
              <td>
                <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $item->slug]); ?>" class="text-decoration-none fw-medium">
                  <?php echo esc_entities($item->authorizedFormOfName) ?: __('Untitled'); ?>
                </a>
              </td>
              <?php if ('alphabetic' !== $sort) { ?>
                <td class="text-end text-muted">
                  <small><?php echo format_date($item->updatedAt, 'f'); ?></small>
                </td>
              <?php } ?>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1) { ?>
    <nav aria-label="<?php echo __('Donor pagination'); ?>" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1) { ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'page' => $page - 1, 'sort' => $sort, 'sortDir' => $sortDir, 'subquery' => $subquery]); ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
        <?php } else { ?>
          <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>
        <?php } ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($pages, $page + 2);
        ?>

        <?php if ($startPage > 1) { ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'page' => 1, 'sort' => $sort, 'sortDir' => $sortDir, 'subquery' => $subquery]); ?>">1</a>
          </li>
          <?php if ($startPage > 2) { ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php } ?>
        <?php } ?>

        <?php for ($i = $startPage; $i <= $endPage; ++$i) { ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'page' => $i, 'sort' => $sort, 'sortDir' => $sortDir, 'subquery' => $subquery]); ?>"><?php echo $i; ?></a>
          </li>
        <?php } ?>

        <?php if ($endPage < $pages) { ?>
          <?php if ($endPage < $pages - 1) { ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
          <?php } ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'page' => $pages, 'sort' => $sort, 'sortDir' => $sortDir, 'subquery' => $subquery]); ?>"><?php echo $pages; ?></a>
          </li>
        <?php } ?>

        <?php if ($page < $pages) { ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'donor', 'action' => 'browse', 'page' => $page + 1, 'sort' => $sort, 'sortDir' => $sortDir, 'subquery' => $subquery]); ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        <?php } else { ?>
          <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>
        <?php } ?>
      </ul>
    </nav>
  <?php } ?>

<?php } else { ?>
  <div class="alert alert-info" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <?php if ($subquery) { ?>
      <?php echo __('No donors found matching "%1%"', ['%1%' => esc_entities($subquery)]); ?>
    <?php } else { ?>
      <?php echo __('No donors have been created yet.'); ?>
    <?php } ?>
  </div>
<?php } ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php if ($canCreate) { ?>
    <div class="d-flex flex-wrap gap-2 mt-4">
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'add']); ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i><?php echo __('Add new'); ?>
      </a>
    </div>
  <?php } ?>
<?php end_slot(); ?>
