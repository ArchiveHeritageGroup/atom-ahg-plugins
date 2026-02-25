<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Subscribers'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Newsletters'), 'url' => url_for(['module' => 'registry', 'action' => 'adminNewsletters'])],
  ['label' => __('Subscribers')],
]]); ?>

<!-- Stats bar -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card bg-success text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($stats['active'] ?? 0); ?></div>
        <small><?php echo __('Active'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-secondary text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($stats['unsubscribed'] ?? 0); ?></div>
        <small><?php echo __('Unsubscribed'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card bg-info text-white">
      <div class="card-body py-3 text-center">
        <div class="h4 mb-0"><?php echo number_format($stats['total'] ?? 0); ?></div>
        <small><?php echo __('Total'); ?></small>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i><?php echo __('Subscribers'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletters']); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-newspaper me-1"></i> <?php echo __('Newsletters'); ?>
  </a>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-2">
    <form method="get" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers']); ?>" class="row g-2 align-items-center">
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search by name or email...'); ?>">
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </div>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value=""><?php echo __('All Statuses'); ?></option>
          <option value="active" <?php echo 'active' === $sf_request->getParameter('status') ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
          <option value="unsubscribed" <?php echo 'unsubscribed' === $sf_request->getParameter('status') ? 'selected' : ''; ?>><?php echo __('Unsubscribed'); ?></option>
        </select>
      </div>
      <div class="col-md-2">
        <?php if ($sf_request->getParameter('q') || $sf_request->getParameter('status')): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers']); ?>" class="btn btn-sm btn-outline-secondary w-100"><?php echo __('Clear'); ?></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($subscribers['items'])): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Email'); ?></th>
        <th class="text-center"><?php echo __('Status'); ?></th>
        <th class="text-center"><?php echo __('Confirmed'); ?></th>
        <th><?php echo __('Subscribed'); ?></th>
        <th><?php echo __('Unsubscribed'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($subscribers['items'] as $sub): ?>
      <tr>
        <td><?php echo htmlspecialchars($sub->name ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a href="mailto:<?php echo htmlspecialchars($sub->email, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($sub->email, ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </td>
        <td class="text-center">
          <?php if ('active' === ($sub->status ?? '')): ?>
            <span class="badge bg-success"><?php echo __('Active'); ?></span>
          <?php else: ?>
            <span class="badge bg-secondary"><?php echo __('Unsubscribed'); ?></span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!empty($sub->is_confirmed)): ?>
            <i class="fas fa-check-circle text-success"></i>
          <?php else: ?>
            <i class="fas fa-clock text-warning"></i>
          <?php endif; ?>
        </td>
        <td>
          <small class="text-muted">
            <?php echo !empty($sub->subscribed_at) ? date('M j, Y', strtotime($sub->subscribed_at)) : '—'; ?>
          </small>
        </td>
        <td>
          <small class="text-muted">
            <?php echo !empty($sub->unsubscribed_at) ? date('M j, Y', strtotime($sub->unsubscribed_at)) : '—'; ?>
          </small>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php $page = (int) ($subscribers['page'] ?? 1); $total = (int) ($subscribers['total'] ?? 0); $limit = 50; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-3">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers', 'page' => $page - 1, 'q' => $sf_request->getParameter('q', ''), 'status' => $sf_request->getParameter('status', '')]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers', 'page' => $i, 'q' => $sf_request->getParameter('q', ''), 'status' => $sf_request->getParameter('status', '')]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminSubscribers', 'page' => $page + 1, 'q' => $sf_request->getParameter('q', ''), 'status' => $sf_request->getParameter('status', '')]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-users fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No subscribers found'); ?></h5>
  <p class="text-muted"><?php echo __('Subscribers will appear here when users sign up for the newsletter.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>
