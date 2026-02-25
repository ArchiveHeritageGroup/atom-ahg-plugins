<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Newsletters'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Newsletters')],
]]); ?>

<?php $flash = sfContext::getInstance()->getUser()->getFlash('success'); ?>
<?php if ($flash): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php $flashErr = sfContext::getInstance()->getUser()->getFlash('error'); ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Stats bar -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card bg-primary text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($subscriberStats['active'] ?? 0); ?></div>
        <small><?php echo __('Active Subscribers'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-secondary text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($subscriberStats['unsubscribed'] ?? 0); ?></div>
        <small><?php echo __('Unsubscribed'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-info text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($subscriberStats['total'] ?? 0); ?></div>
        <small><?php echo __('Total Subscribers'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-success text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($newsletters['total'] ?? 0); ?></div>
        <small><?php echo __('Newsletters'); ?></small>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="fas fa-newspaper me-2"></i><?php echo __('Newsletters'); ?></h1>
  <div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-users me-1"></i> <?php echo __('Subscribers'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletterForm']); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i> <?php echo __('New Newsletter'); ?>
    </a>
  </div>
</div>

<?php if (!empty($newsletters['items'])): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Subject'); ?></th>
        <th><?php echo __('Author'); ?></th>
        <th class="text-center"><?php echo __('Status'); ?></th>
        <th class="text-center"><?php echo __('Recipients'); ?></th>
        <th class="text-center"><?php echo __('Sent'); ?></th>
        <th><?php echo __('Created'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($newsletters['items'] as $nl): ?>
      <tr>
        <td>
          <span class="fw-semibold"><?php echo htmlspecialchars($nl->subject ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($nl->excerpt)): ?>
            <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($nl->excerpt, 0, 80, '...'), ENT_QUOTES, 'UTF-8'); ?></small>
          <?php endif; ?>
        </td>
        <td><small><?php echo htmlspecialchars($nl->author_name ?? '—', ENT_QUOTES, 'UTF-8'); ?></small></td>
        <td class="text-center">
          <?php
            $stBg = ['draft' => 'bg-secondary', 'sent' => 'bg-success', 'scheduled' => 'bg-warning text-dark'];
            $st = $nl->status ?? 'draft';
          ?>
          <span class="badge <?php echo $stBg[$st] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td class="text-center">
          <?php if ('sent' === $st): ?>
            <span class="text-success fw-semibold"><?php echo (int) ($nl->sent_count ?? 0); ?></span>
            <span class="text-muted">/<?php echo (int) ($nl->recipient_count ?? 0); ?></span>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <small class="text-muted">
            <?php echo !empty($nl->sent_at) ? date('M j, Y H:i', strtotime($nl->sent_at)) : '—'; ?>
          </small>
        </td>
        <td>
          <small class="text-muted"><?php echo date('M j, Y', strtotime($nl->created_at)); ?></small>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <?php if ('sent' !== $st): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletterForm', 'id' => $nl->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
                <i class="fas fa-edit"></i>
              </a>
              <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletterSend', 'id' => $nl->id]); ?>" class="d-inline" onsubmit="return confirm('Send this newsletter to all active subscribers?');">
                <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Send Now'); ?>">
                  <i class="fas fa-paper-plane"></i>
                </button>
              </form>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" disabled title="<?php echo __('Already Sent'); ?>">
                <i class="fas fa-check"></i>
              </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php $page = (int) ($newsletters['page'] ?? 1); $total = (int) ($newsletters['total'] ?? 0); $limit = 20; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-3">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletters', 'page' => $page - 1]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletters', 'page' => $i]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletters', 'page' => $page + 1]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No newsletters yet'); ?></h5>
  <p class="text-muted"><?php echo __('Create your first newsletter to send to subscribers.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletterForm']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Create Newsletter'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
