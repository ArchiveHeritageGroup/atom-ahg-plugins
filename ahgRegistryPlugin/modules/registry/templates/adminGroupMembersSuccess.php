<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Group Members'); ?> - <?php echo __('Admin'); ?><?php end_slot(); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'adminGroups'])],
  ['label' => htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8'), 'url' => '/registry/admin/groups/' . (int) $group->id . '/edit'],
  ['label' => __('Members')],
]]); ?>

<?php $gid = (int) $group->id; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo __('Members'); ?>: <?php echo htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8'); ?></h1>
    <span class="text-muted"><?php echo number_format($members['total'] ?? 0); ?> <?php echo __('members'); ?></span>
  </div>
  <div class="btn-group btn-group-sm">
    <a href="/registry/admin/groups/<?php echo $gid; ?>/edit" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i><?php echo __('Edit Group'); ?></a>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal"><i class="fas fa-user-plus me-1"></i><?php echo __('Add Member'); ?></button>
    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#emailModal"><i class="fas fa-envelope me-1"></i><?php echo __('Email All'); ?></button>
  </div>
</div>

<?php
  $flash = sfContext::getInstance()->getUser();
  $flashSuccess = $flash->getFlash('success', '');
  $flashError = $flash->getFlash('error', '');
?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-1"></i><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($flashSuccess)): ?>
  <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-1"></i><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Search / Filter -->
<div class="row g-2 mb-3">
  <div class="col-md-6">
    <form method="get" action="/registry/admin/groups/<?php echo $gid; ?>/members">
      <div class="input-group input-group-sm">
        <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($sf_request->getParameter('q', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Search by name or email...'); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
      </div>
    </form>
  </div>
  <div class="col-md-3">
    <select class="form-select form-select-sm" onchange="window.location='/registry/admin/groups/<?php echo $gid; ?>/members?status='+this.value+'&q=<?php echo urlencode($sf_request->getParameter('q', '')); ?>'">
      <option value=""><?php echo __('All statuses'); ?></option>
      <option value="1"<?php echo '1' === $sf_request->getParameter('status') ? ' selected' : ''; ?>><?php echo __('Active'); ?></option>
      <option value="0"<?php echo '0' === $sf_request->getParameter('status') ? ' selected' : ''; ?>><?php echo __('Inactive'); ?></option>
    </select>
  </div>
</div>

<?php if (!empty($members['items'])): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Email'); ?></th>
        <th class="text-center"><?php echo __('Role'); ?></th>
        <th class="text-center"><?php echo __('Status'); ?></th>
        <th class="text-center"><?php echo __('Email Notify'); ?></th>
        <th><?php echo __('Joined'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members['items'] as $m): ?>
      <tr<?php echo empty($m->is_active) ? ' class="table-secondary"' : ''; ?>>
        <td><?php echo htmlspecialchars($m->name ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><a href="mailto:<?php echo htmlspecialchars($m->email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($m->email, ENT_QUOTES, 'UTF-8'); ?></a></td>
        <td class="text-center">
          <form method="post" action="/registry/admin/groups/<?php echo $gid; ?>/members" class="d-inline">
            <input type="hidden" name="form_action" value="update_role">
            <input type="hidden" name="member_id" value="<?php echo (int) $m->id; ?>">
            <select name="member_role" class="form-select form-select-sm" style="width:auto;display:inline-block;" onchange="this.form.submit()">
              <?php $roles = ['organizer' => 'Organizer', 'co_organizer' => 'Co-organizer', 'speaker' => 'Speaker', 'sponsor' => 'Sponsor', 'member' => 'Member'];
                foreach ($roles as $rv => $rl): ?>
                  <option value="<?php echo $rv; ?>"<?php echo ($m->role ?? 'member') === $rv ? ' selected' : ''; ?>><?php echo __($rl); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td class="text-center">
          <?php if (!empty($m->is_active)): ?>
            <span class="badge bg-success"><?php echo __('Active'); ?></span>
          <?php else: ?>
            <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!empty($m->email_notifications)): ?>
            <span class="badge bg-success"><i class="fas fa-bell"></i></span>
          <?php else: ?>
            <span class="badge bg-light text-muted border"><i class="fas fa-bell-slash"></i></span>
          <?php endif; ?>
        </td>
        <td><small class="text-muted"><?php echo !empty($m->joined_at) ? date('j M Y', strtotime($m->joined_at)) : '—'; ?></small></td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <form method="post" action="/registry/admin/groups/<?php echo $gid; ?>/members" class="d-inline">
              <input type="hidden" name="form_action" value="toggle_active">
              <input type="hidden" name="member_id" value="<?php echo (int) $m->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-<?php echo !empty($m->is_active) ? 'warning' : 'success'; ?>" title="<?php echo !empty($m->is_active) ? __('Deactivate') : __('Activate'); ?>">
                <i class="fas fa-<?php echo !empty($m->is_active) ? 'pause' : 'play'; ?>"></i>
              </button>
            </form>
            <form method="post" action="/registry/admin/groups/<?php echo $gid; ?>/members" class="d-inline" onsubmit="return confirm('Remove this member?');">
              <input type="hidden" name="form_action" value="remove">
              <input type="hidden" name="member_id" value="<?php echo (int) $m->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php $page = (int) ($members['page'] ?? 1); $total = (int) ($members['total'] ?? 0); $limit = 50; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav class="mt-3">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="/registry/admin/groups/<?php echo $gid; ?>/members?page=<?php echo $i; ?>&q=<?php echo urlencode($sf_request->getParameter('q', '')); ?>&status=<?php echo urlencode($sf_request->getParameter('status', '')); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-users fa-3x text-muted mb-3"></i>
  <p class="text-muted"><?php echo __('No members found.'); ?></p>
</div>
<?php endif; ?>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="/registry/admin/groups/<?php echo $gid; ?>/members">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i><?php echo __('Add Member'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="form_action" value="add">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Email'); ?> <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="new_email" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Name'); ?></label>
            <input type="text" class="form-control" name="new_name">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Role'); ?></label>
            <select class="form-select" name="new_role">
              <option value="member"><?php echo __('Member'); ?></option>
              <option value="co_organizer"><?php echo __('Co-organizer'); ?></option>
              <option value="organizer"><?php echo __('Organizer'); ?></option>
              <option value="speaker"><?php echo __('Speaker'); ?></option>
              <option value="sponsor"><?php echo __('Sponsor'); ?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i><?php echo __('Add'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Batch Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" action="/registry/admin/groups/<?php echo $gid; ?>/email">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-envelope me-2"></i><?php echo __('Email All Active Members'); ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info small">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('This will send an email to all active members of this group.'); ?>
            <strong><?php echo (int) ($group->member_count ?? 0); ?> <?php echo __('recipients'); ?></strong>.
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Subject'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="email_subject" required placeholder="<?php echo __('e.g., Next meeting reminder...'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Message'); ?> <span class="text-danger">*</span></label>
            <textarea class="form-control" name="email_body" rows="8" required placeholder="<?php echo __('Type your message here. Plain text — line breaks are preserved.'); ?>"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-success" onclick="return confirm('Send email to all active members?');"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send Email'); ?></button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php end_slot(); ?>
