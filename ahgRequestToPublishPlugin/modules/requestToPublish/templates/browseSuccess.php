<?php use_helper('Date') ?>
<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-paper-plane fa-2x text-primary me-3"></i>
  <div>
    <h1 class="h3 mb-0"><?php echo __('Request to Publish') ?></h1>
    <p class="text-muted mb-0"><?php echo __('Manage image publication requests') ?></p>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('notice') && $sf_user->getFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error') && $sf_user->getFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Status Tabs -->
<div class="card shadow-sm mb-4">
  <div class="card-header bg-white p-0">
    <ul class="nav nav-tabs card-header-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link <?php echo ($filter === 'all') ? 'active' : '' ?>" 
           href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => 'all']) ?>">
          <i class="fas fa-list me-1"></i><?php echo __('All') ?>
          <span class="badge bg-secondary ms-1"><?php echo $statusCounts['all'] ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ($filter === 'pending') ? 'active' : '' ?>" 
           href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => 'pending']) ?>">
          <i class="fas fa-clock me-1"></i><?php echo __('Pending') ?>
          <span class="badge bg-warning text-dark ms-1"><?php echo $statusCounts['pending'] ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ($filter === 'approved') ? 'active' : '' ?>" 
           href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => 'approved']) ?>">
          <i class="fas fa-check me-1"></i><?php echo __('Approved') ?>
          <span class="badge bg-success ms-1"><?php echo $statusCounts['approved'] ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo ($filter === 'rejected') ? 'active' : '' ?>" 
           href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => 'rejected']) ?>">
          <i class="fas fa-times me-1"></i><?php echo __('Rejected') ?>
          <span class="badge bg-danger ms-1"><?php echo $statusCounts['rejected'] ?></span>
        </a>
      </li>
    </ul>
  </div>

  <div class="card-body p-0">
    <?php if (count($requests) > 0): ?>
    <div class="table-responsive">
      <table class="table table-hover table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 100px;"><?php echo __('Status') ?></th>
            <th><?php echo __('Archival Item') ?></th>
            <th><?php echo __('Requester') ?></th>
            <th><?php echo __('Institution') ?></th>
            <th><?php echo __('Planned Use') ?></th>
            <th><?php echo __('Need By') ?></th>
            <th><?php echo __('Submitted') ?></th>
            <th style="width: 80px;"><?php echo __('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $item): ?>
          <tr>
            <td>
              <span class="badge <?php echo $repository->getStatusBadgeClass($item->status_id) ?>">
                <?php echo __($repository->getStatusLabel($item->status_id)) ?>
              </span>
            </td>
            <td>
              <?php if ($item->object_title): ?>
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->object_slug]) ?>">
                  <i class="fas fa-file-alt me-1 text-muted"></i>
                  <?php echo esc_entities($item->object_title) ?>
                </a>
                <?php if ($item->object_identifier): ?>
                  <br><small class="text-muted"><?php echo esc_entities($item->object_identifier) ?></small>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted"><?php echo __('Object not found') ?></span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?php echo esc_entities(($item->rtp_name ?? '') . ' ' . ($item->rtp_surname ?? '')) ?></strong>
              <?php if (!empty($item->rtp_email)): ?>
                <br><small class="text-muted">
                  <i class="fas fa-envelope me-1"></i><?php echo esc_entities($item->rtp_email) ?>
                </small>
              <?php endif; ?>
              <?php if (!empty($item->rtp_phone)): ?>
                <br><small class="text-muted">
                  <i class="fas fa-phone me-1"></i><?php echo esc_entities($item->rtp_phone) ?>
                </small>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($item->rtp_institution)): ?>
                <i class="fas fa-building me-1 text-muted"></i><?php echo esc_entities($item->rtp_institution) ?>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($item->rtp_planned_use)): ?>
                <div class="text-truncate" style="max-width: 150px;" title="<?php echo esc_entities($item->rtp_planned_use) ?>">
                  <?php echo esc_entities($item->rtp_planned_use) ?>
                </div>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($item->rtp_need_image_by)): ?>
                <span class="badge bg-info text-dark">
                  <i class="fas fa-calendar me-1"></i><?php echo date('d M Y', strtotime($item->rtp_need_image_by)) ?>
                </span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <small><?php echo date('d M Y', strtotime($item->created_at)) ?></small>
              <?php if (!empty($item->completed_at)): ?>
                <br><small class="text-success">
                  <i class="fas fa-check me-1"></i><?php echo date('d M Y', strtotime($item->completed_at)) ?>
                </small>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'edit', 'slug' => $item->slug]) ?>" 
                 class="btn btn-sm btn-outline-primary" title="<?php echo __('Review') ?>">
                <i class="fas fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
      <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
      <h5 class="text-muted"><?php echo __('No requests found') ?></h5>
      <p class="text-muted mb-0"><?php echo __('There are no publication requests matching your filter.') ?></p>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($pages > 1): ?>
  <div class="card-footer bg-white">
    <nav aria-label="Page navigation">
      <ul class="pagination pagination-sm mb-0 justify-content-center">
        <?php if ($page > 1): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => $filter, 'page' => $page - 1]) ?>">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
          <li class="page-item <?php echo ($i === $page) ? 'active' : '' ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => $filter, 'page' => $i]) ?>">
              <?php echo $i ?>
            </a>
          </li>
        <?php endfor; ?>
        
        <?php if ($page < $pages): ?>
          <li class="page-item">
            <a class="page-link" href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'browse', 'filter' => $filter, 'page' => $page + 1]) ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
    <div class="text-center mt-2">
      <small class="text-muted"><?php echo __('Showing %1% of %2% requests', ['%1%' => count($requests), '%2%' => $total]) ?></small>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php end_slot() ?>
