<?php $groups = $sf_data->getRaw('groups') ?: []; ?>
<div class="container mt-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
      <li class="breadcrumb-item active">ACL Groups</li>
    </ol>
  </nav>

  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="fas fa-users-cog me-2"></i>ACL Groups</h1>
  </div>

  <div class="alert alert-info py-2 small">
    <i class="fas fa-info-circle me-1"></i> Group permissions are <strong>managed and stored here</strong>; live enforcement is not yet enabled (it will be wired in a reviewed follow-up).
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Groups</h5></div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead><tr><th>Name</th><th>Description</th><th>Members</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($groups)): ?>
            <tr><td colspan="4" class="text-muted p-3">No ACL groups yet.</td></tr>
          <?php else: foreach ($groups as $g): ?>
            <tr>
              <td><a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'aclGroupEdit', 'id' => $g->id]); ?>"><?php echo htmlspecialchars((string) ($g->name ?? ('Group #' . $g->id))); ?></a></td>
              <td class="text-muted small"><?php echo htmlspecialchars((string) ($g->description ?? '')); ?></td>
              <td><span class="badge bg-secondary"><?php echo (int) $g->member_count; ?></span></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'aclGroupEdit', 'id' => $g->id]); ?>">Edit</a>
                <form method="post" class="d-inline">
                  <input type="hidden" name="group_id" value="<?php echo (int) $g->id; ?>">
                  <button class="btn btn-sm btn-outline-danger" name="form_action" value="delete">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h5 class="mb-0">Create group</h5></div>
    <div class="card-body">
      <form method="post" class="row g-2">
        <div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" name="form_action" value="create">Create</button></div>
      </form>
    </div>
  </div>
</div>
