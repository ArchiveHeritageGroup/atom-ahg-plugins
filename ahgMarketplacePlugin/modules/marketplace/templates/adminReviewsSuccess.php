<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Reviews'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Reviews'); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Manage Reviews'); ?></h1>

<!-- Filter row -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReviews']); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" name="flagged" value="1" id="filterFlagged"<?php echo ($filters['flagged'] ?? '') ? ' checked' : ''; ?>>
          <label class="form-check-label" for="filterFlagged"><?php echo __('Flagged Only'); ?></label>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Visibility'); ?></label>
        <select name="is_visible" class="form-select form-select-sm">
          <option value=""><?php echo __('All'); ?></option>
          <option value="1"<?php echo ($filters['is_visible'] ?? '') === '1' ? ' selected' : ''; ?>><?php echo __('Visible'); ?></option>
          <option value="0"<?php echo ($filters['is_visible'] ?? '') === '0' ? ' selected' : ''; ?>><?php echo __('Hidden'); ?></option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-filter me-1"></i> <?php echo __('Filter'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Reviews table -->
<?php if (empty($reviews)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-star fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No reviews found'); ?></h5>
      <p class="text-muted"><?php echo __('Try adjusting your filters.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;"><?php echo __('ID'); ?></th>
            <th><?php echo __('Seller'); ?></th>
            <th><?php echo __('Reviewer'); ?></th>
            <th><?php echo __('Rating'); ?></th>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Comment'); ?></th>
            <th><?php echo __('Flagged'); ?></th>
            <th><?php echo __('Visible'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviews as $review): ?>
            <tr<?php echo ($review->is_flagged ?? 0) ? ' class="table-warning"' : ''; ?>>
              <td class="small text-muted"><?php echo (int) $review->id; ?></td>
              <td class="small"><?php echo esc_entities($review->seller_name ?? '-'); ?></td>
              <td class="small"><?php echo esc_entities($review->reviewer_name ?? '-'); ?></td>
              <td class="text-nowrap">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="fa<?php echo $s <= (int) $review->rating ? 's' : 'r'; ?> fa-star text-warning small"></i>
                <?php endfor; ?>
              </td>
              <td class="small"><?php echo esc_entities($review->title ?? '-'); ?></td>
              <td class="small" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo esc_entities(mb_substr($review->comment ?? '', 0, 80)); ?>
                <?php if (mb_strlen($review->comment ?? '') > 80): ?>...<?php endif; ?>
              </td>
              <td>
                <?php if ($review->is_flagged ?? 0): ?>
                  <span class="badge bg-danger"><?php echo __('Flagged'); ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($review->is_visible ?? 1): ?>
                  <span class="badge bg-success"><?php echo __('Visible'); ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo __('Hidden'); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <!-- Toggle visibility -->
                <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReviews']); ?>" class="d-inline">
                  <input type="hidden" name="form_action" value="moderate">
                  <input type="hidden" name="review_id" value="<?php echo (int) $review->id; ?>">
                  <?php if ($review->is_visible ?? 1): ?>
                    <input type="hidden" name="is_visible" value="0">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Hide'); ?>">
                      <i class="fas fa-eye-slash"></i>
                    </button>
                  <?php else: ?>
                    <input type="hidden" name="is_visible" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Show'); ?>">
                      <i class="fas fa-eye"></i>
                    </button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $limit = 30;
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReviews', 'flagged' => $filters['flagged'] ?? '', 'is_visible' => $filters['is_visible'] ?? '', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReviews', 'flagged' => $filters['flagged'] ?? '', 'is_visible' => $filters['is_visible'] ?? '', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReviews', 'flagged' => $filters['flagged'] ?? '', 'is_visible' => $filters['is_visible'] ?? '', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>
