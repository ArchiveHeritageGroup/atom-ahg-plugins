<?php
$group = $sf_data->getRaw('group');
$classActions = $sf_data->getRaw('classActions') ?: [];
$rootPerms = $sf_data->getRaw('rootPerms') ?: [];
$users = $sf_data->getRaw('users') ?: [];
$members = $group->members ?? [];
$classLabels = [
    'QubitInformationObject' => 'Information objects',
    'QubitActor' => 'Authority records',
    'QubitRepository' => 'Repositories',
    'QubitTerm' => 'Terms',
];
// Render a grant/deny/inherit <select> for one action, pre-selected from any existing root perm.
$permSelect = function ($class, $action) use ($rootPerms) {
    $perm = $rootPerms[$class][$action] ?? null;
    $name = $perm ? ('acl[' . (int) $perm->id . ']') : ('acl[' . $action . '_root]');
    $cur = $perm ? (int) $perm->grantDeny : -1; // -1 = inherit (no row)
    $opts = [1 => 'Grant', 0 => 'Deny', -1 => 'Inherit'];
    $h = '<select name="' . $name . '" class="form-select form-select-sm">';
    foreach ($opts as $v => $lbl) {
        $h .= '<option value="' . $v . '"' . ($cur === $v ? ' selected' : '') . '>' . $lbl . '</option>';
    }

    return $h . '</select>';
};
?>
<div class="container mt-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'aclGroups']); ?>">ACL Groups</a></li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) ($group->name ?? ('Group #' . $group->id))); ?></li>
    </ol>
  </nav>

  <h1 class="h3 mb-3"><i class="fas fa-users-cog me-2"></i><?php echo htmlspecialchars((string) ($group->name ?? ('Group #' . $group->id))); ?></h1>

  <div class="alert alert-info py-2 small"><i class="fas fa-info-circle me-1"></i> Permissions are stored here; live enforcement is not yet enabled.</div>

  <!-- Profile -->
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Profile</h5></div>
    <div class="card-body">
      <form method="post" class="row g-2">
        <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars((string) ($group->name ?? '')); ?>" required></div>
        <div class="col-md-6"><label class="form-label">Description</label><input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars((string) ($group->description ?? '')); ?>"></div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check"><input class="form-check-input" type="checkbox" name="translate" value="1" id="translateFlag" <?php echo !empty($group->translate) ? 'checked' : ''; ?>><label class="form-check-label" for="translateFlag">Translate</label></div>
        </div>
        <div class="col-12"><button class="btn btn-primary btn-sm" name="form_action" value="profile">Save profile</button></div>
      </form>
    </div>
  </div>

  <!-- Members -->
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Members <span class="badge bg-secondary"><?php echo count($members); ?></span></h5></div>
    <div class="card-body">
      <table class="table table-sm">
        <tbody>
          <?php if (empty($members)): ?>
            <tr><td class="text-muted">No members.</td></tr>
          <?php else: foreach ($members as $m): ?>
            <tr>
              <td><?php echo htmlspecialchars((string) ($m->display_name ?: $m->username)); ?> <span class="text-muted small"><?php echo htmlspecialchars((string) $m->username); ?></span></td>
              <td class="text-end">
                <form method="post" class="d-inline">
                  <input type="hidden" name="membership_id" value="<?php echo (int) $m->membership_id; ?>">
                  <button class="btn btn-sm btn-outline-danger" name="form_action" value="remove_member">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <form method="post" class="row g-2">
        <div class="col-md-6">
          <select name="user_id" class="form-select form-select-sm">
            <option value="">— Select user —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>"><?php echo htmlspecialchars((string) ($u->display_name ?: $u->username)); ?> (<?php echo htmlspecialchars((string) $u->username); ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><button class="btn btn-sm btn-outline-primary" name="form_action" value="add_member">Add member</button></div>
      </form>
    </div>
  </div>

  <!-- Permission matrix (root/class-level) per describable class -->
  <?php foreach ($classActions as $class => $actions): ?>
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0"><?php echo htmlspecialchars($classLabels[$class] ?? $class); ?> — class-level permissions</h6></div>
      <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
          <input type="hidden" name="entity_class" value="<?php echo htmlspecialchars($class); ?>">
          <?php foreach ($actions as $action => $label): ?>
            <div class="col-md-3 col-sm-4">
              <label class="form-label small mb-0"><?php echo htmlspecialchars($label); ?></label>
              <?php echo $permSelect($class, $action); ?>
            </div>
          <?php endforeach; ?>
          <div class="col-12 mt-2"><button class="btn btn-sm btn-primary" name="form_action" value="permissions">Save <?php echo htmlspecialchars($classLabels[$class] ?? $class); ?> permissions</button></div>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>
