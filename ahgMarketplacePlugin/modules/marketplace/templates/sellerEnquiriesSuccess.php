<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Enquiries'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Enquiries'); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Enquiries'); ?></h1>

<?php if (empty($enquiries)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-envelope fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No enquiries yet'); ?></h5>
      <p class="text-muted"><?php echo __('Enquiries from potential buyers will appear here.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Listing'); ?></th>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Email'); ?></th>
            <th><?php echo __('Subject'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($enquiries as $enq): ?>
            <tr>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($enq->created_at)); ?></td>
              <td>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $enq->listing_slug ?? '']); ?>" class="text-decoration-none">
                  <?php echo esc_entities($enq->listing_title ?? '-'); ?>
                </a>
              </td>
              <td class="small"><?php echo esc_entities($enq->name ?? '-'); ?></td>
              <td class="small"><?php echo esc_entities($enq->email); ?></td>
              <td class="small"><?php echo esc_entities($enq->subject ?? '-'); ?></td>
              <td>
                <?php
                  $statusClass = match($enq->status) {
                      'new' => 'primary',
                      'read' => 'info',
                      'replied' => 'success',
                      'closed' => 'secondary',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst($enq->status)); ?></span>
              </td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#enquiry-<?php echo (int) $enq->id; ?>">
                  <i class="fas fa-eye me-1"></i><?php echo __('View'); ?>
                </button>
              </td>
            </tr>
            <tr class="collapse" id="enquiry-<?php echo (int) $enq->id; ?>">
              <td colspan="7">
                <div class="p-3 bg-light rounded">
                  <p class="mb-2"><strong><?php echo __('Message:'); ?></strong></p>
                  <p class="mb-3"><?php echo nl2br(esc_entities($enq->message)); ?></p>

                  <?php if ($enq->reply): ?>
                    <div class="border-start border-3 border-success ps-3 mb-3">
                      <p class="mb-1"><strong class="text-success"><?php echo __('Your Reply:'); ?></strong></p>
                      <p class="mb-0"><?php echo nl2br(esc_entities($enq->reply)); ?></p>
                      <?php if ($enq->replied_at): ?>
                        <small class="text-muted"><?php echo date('d M Y H:i', strtotime($enq->replied_at)); ?></small>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <?php if ($enq->status !== 'replied' && $enq->status !== 'closed'): ?>
                    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerEnquiries']); ?>">
                      <input type="hidden" name="form_action" value="reply">
                      <input type="hidden" name="enquiry_id" value="<?php echo (int) $enq->id; ?>">
                      <div class="mb-2">
                        <label for="reply-<?php echo (int) $enq->id; ?>" class="form-label"><?php echo __('Reply'); ?></label>
                        <textarea class="form-control" id="reply-<?php echo (int) $enq->id; ?>" name="reply" rows="3" required placeholder="<?php echo __('Type your reply...'); ?>"></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-reply me-1"></i><?php echo __('Send Reply'); ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerEnquiries', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerEnquiries', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerEnquiries', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>
