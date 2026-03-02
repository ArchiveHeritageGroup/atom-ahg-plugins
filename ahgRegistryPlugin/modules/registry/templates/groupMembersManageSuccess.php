<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $group = sfOutputEscaper::unescape($group); ?>
<?php $members = sfOutputEscaper::unescape($members ?? []); ?>
<?php slot('title'); ?><?php echo __('Manage Members'); ?> - <?php echo htmlspecialchars($group->name ?? '', ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'myGroups'])],
  ['label' => htmlspecialchars($group->name ?? '', ENT_QUOTES, 'UTF-8')],
  ['label' => __('Members')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo __('Manage Members'); ?></h1>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($group->name ?? '', ENT_QUOTES, 'UTF-8'); ?> &mdash; <?php echo count($members ?? []); ?> <?php echo __('members'); ?></p>
  </div>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myGroups']); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
  </a>
</div>

<?php if (!empty($members) && count($members) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('Role'); ?></th>
          <th><?php echo __('Joined'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($m->name ?? $m->email ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
          <td>
            <?php if (!empty($m->email)): ?>
              <a href="mailto:<?php echo htmlspecialchars($m->email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($m->email, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($m->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php
              $role = $m->role ?? 'member';
              $roleColors = ['organizer' => 'warning', 'moderator' => 'info', 'member' => 'secondary'];
              $rColor = $roleColors[$role] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $rColor; ?><?php echo 'warning' === $rColor ? ' text-dark' : ''; ?>"><?php echo htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td>
            <?php echo !empty($m->joined_at) ? date('M j, Y', strtotime($m->joined_at)) : (!empty($m->created_at) ? date('M j, Y', strtotime($m->created_at)) : '-'); ?>
          </td>
          <td class="text-end">
            <div class="d-flex gap-1 justify-content-end">
              <!-- Update role -->
              <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'groupMembersManage', 'id' => $group->id]); ?>" class="d-inline">
                <input type="hidden" name="form_action" value="update_role">
                <input type="hidden" name="member_email" value="<?php echo htmlspecialchars($m->email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <select class="form-select form-select-sm d-inline-block" name="member_role" style="width: auto;" onchange="this.form.submit()">
                  <?php $allRoles = ['organizer' => __('Organizer'), 'co_organizer' => __('Co-organizer'), 'speaker' => __('Speaker'), 'sponsor' => __('Sponsor'), 'member' => __('Member')];
                    foreach ($allRoles as $rv => $rl): ?>
                    <option value="<?php echo $rv; ?>"<?php echo $rv === $role ? ' selected' : ''; ?>><?php echo $rl; ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <!-- Remove -->
              <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'groupMembersManage', 'id' => $group->id]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this member?'); ?>');">
                <input type="hidden" name="form_action" value="remove">
                <input type="hidden" name="member_email" value="<?php echo htmlspecialchars($m->email ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>">
                  <i class="fas fa-times"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-users fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No members yet'); ?></h5>
  <p class="text-muted"><?php echo __('Share your group link to invite people to join.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>
