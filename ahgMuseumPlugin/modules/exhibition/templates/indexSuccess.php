<?php use_helper('Date'); ?>

<div class="row">
  <div class="col-md-9">
    <h1>Exhibitions</h1>

    <div class="card mb-4">
      <div class="card-header">
        <form method="get" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>" class="row g-2 align-items-center">
          <div class="col-auto">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo $sf_request->getParameter('search'); ?>">
          </div>
          <div class="col-auto">
            <select name="status" class="form-select form-select-sm">
              <option value="">All Statuses</option>
              <?php foreach ($statuses as $key => $status): ?>
                <option value="<?php echo $key; ?>" <?php echo $sf_request->getParameter('status') == $key ? 'selected' : ''; ?>>
                  <?php echo $status['label']; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <select name="type" class="form-select form-select-sm">
              <option value="">All Types</option>
              <?php foreach ($types as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo $sf_request->getParameter('type') == $key ? 'selected' : ''; ?>>
                  <?php echo $label; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
          </div>
          <div class="col-auto ms-auto">
            <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'add']); ?>" class="btn btn-success btn-sm">
              <i class="fa fa-plus"></i> New Exhibition
            </a>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        <?php if (empty($exhibitions)): ?>
          <div class="p-4 text-center text-muted">
            <i class="fa fa-image fa-3x mb-3"></i>
            <p>No exhibitions found</p>
          </div>
        <?php else: ?>
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
                <th>Opens</th>
                <th>Closes</th>
                <th>Venue</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($exhibitions as $exhibition): ?>
                <tr>
                  <td>
                    <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>">
                      <strong><?php echo $exhibition['title']; ?></strong>
                    </a>
                    <?php if (!empty($exhibition['subtitle'])): ?>
                      <br><small class="text-muted"><?php echo $exhibition['subtitle']; ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?php echo $exhibition['type_label']; ?></td>
                  <td>
                    <span class="badge" style="background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>">
                      <?php echo $exhibition['status_info']['label'] ?? $exhibition['status']; ?>
                    </span>
                  </td>
                  <td><?php echo $exhibition['opening_date'] ?? '-'; ?></td>
                  <td><?php echo $exhibition['closing_date'] ?? '-'; ?></td>
                  <td><?php echo $exhibition['venue_name'] ?? '-'; ?></td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>" class="btn btn-outline-primary" title="View">
                        <i class="fa fa-eye"></i>
                      </a>
                      <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'edit', 'id' => $exhibition['id']]); ?>" class="btn btn-outline-secondary" title="Edit">
                        <i class="fa fa-edit"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <?php if ($pages > 1): ?>
        <div class="card-footer">
          <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo;</a>
                </li>
              <?php endif; ?>

              <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                  <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>

              <?php if ($page < $pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?php echo $page + 1; ?>">&raquo;</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Statistics</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <li class="d-flex justify-content-between mb-2">
            <span>Total Exhibitions</span>
            <strong><?php echo $stats['total_exhibitions']; ?></strong>
          </li>
          <li class="d-flex justify-content-between mb-2">
            <span>Currently Open</span>
            <strong class="text-success"><?php echo $stats['current_exhibitions']; ?></strong>
          </li>
          <li class="d-flex justify-content-between mb-2">
            <span>Upcoming</span>
            <strong class="text-info"><?php echo $stats['upcoming_exhibitions']; ?></strong>
          </li>
          <li class="d-flex justify-content-between">
            <span>Objects on Display</span>
            <strong><?php echo $stats['total_objects_on_display']; ?></strong>
          </li>
        </ul>
      </div>
    </div>

    <?php if (!empty($stats['by_status'])): ?>
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0">By Status</h5>
        </div>
        <div class="card-body">
          <?php foreach ($stats['by_status'] as $status => $count): ?>
            <?php $statusInfo = $statuses[$status] ?? []; ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge" style="background-color: <?php echo $statusInfo['color'] ?? '#999'; ?>">
                <?php echo $statusInfo['label'] ?? $status; ?>
              </span>
              <span><?php echo $count; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'dashboard']); ?>" class="list-group-item list-group-item-action">
          <i class="fa fa-dashboard me-2"></i> Exhibition Dashboard
        </a>
        <a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'add']); ?>" class="list-group-item list-group-item-action">
          <i class="fa fa-plus me-2"></i> Create Exhibition
        </a>
      </div>
    </div>
  </div>
</div>
