<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-share-alt text-primary me-2"></i><?php echo __('Share Project'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$shares = isset($shares) && is_array($shares) ? $shares : (isset($shares) && method_exists($shares, 'getRawValue') ? $shares->getRawValue() : (isset($shares) && is_iterable($shares) ? iterator_to_array($shares) : []));
$institutions = isset($institutions) && is_array($institutions) ? $institutions : (isset($institutions) && method_exists($institutions, 'getRawValue') ? $institutions->getRawValue() : (isset($institutions) && is_iterable($institutions) ? iterator_to_array($institutions) : []));
$externalCollaborators = isset($externalCollaborators) && is_array($externalCollaborators) ? $externalCollaborators : (isset($externalCollaborators) && method_exists($externalCollaborators, 'getRawValue') ? $externalCollaborators->getRawValue() : (isset($externalCollaborators) && is_iterable($externalCollaborators) ? iterator_to_array($externalCollaborators) : []));
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>"><?php echo __('Projects'); ?></a></li>
    <?php if (isset($project)): ?>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
    <?php endif; ?>
    <li class="breadcrumb-item active"><?php echo __('Share'); ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-5">
    <!-- Generate Share Link -->
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Generate Share Link'); ?></h5></div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="form_action" value="create_share">

          <div class="mb-3">
            <label class="form-label"><?php echo __('Share Type'); ?> *</label>
            <select name="share_type" class="form-select" required>
              <option value="view"><?php echo __('View Only'); ?> - <?php echo __('Can view project details and resources'); ?></option>
              <option value="contribute"><?php echo __('Contribute'); ?> - <?php echo __('Can add notes and resources'); ?></option>
              <option value="full"><?php echo __('Full Access'); ?> - <?php echo __('Can edit all project content'); ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Institution'); ?> <small class="text-muted">(<?php echo __('optional'); ?>)</small></label>
            <select name="institution_id" class="form-select">
              <option value=""><?php echo __('No specific institution'); ?></option>
              <?php foreach ($institutions as $inst): ?>
                <option value="<?php echo $inst->id; ?>"><?php echo htmlspecialchars($inst->name); ?> (<?php echo htmlspecialchars($inst->code ?? ''); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Message'); ?></label>
            <textarea name="message" class="form-control" rows="2" placeholder="<?php echo __('Optional message for the recipient...'); ?>"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Expiry Date'); ?></label>
            <input type="date" name="expires_at" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            <div class="form-text"><?php echo __('Leave empty for no expiry.'); ?></div>
          </div>

          <button type="submit" class="btn btn-primary w-100"><i class="fas fa-link me-1"></i><?php echo __('Generate Share Link'); ?></button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <!-- Existing Shares -->
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="fas fa-share-nodes me-2"></i><?php echo __('Existing Shares'); ?></h5></div>
      <?php if (!empty($shares)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Token'); ?></th>
                <th><?php echo __('Type'); ?></th>
                <th><?php echo __('Institution'); ?></th>
                <th><?php echo __('Status'); ?></th>
                <th><?php echo __('Created'); ?></th>
                <th class="text-end"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shares as $share): ?>
              <tr>
                <td>
                  <code class="small"><?php echo htmlspecialchars(substr($share->share_token ?? '', 0, 12)); ?>...</code>
                  <button type="button" class="btn btn-link btn-sm p-0 ms-1 copy-token-btn" data-token="<?php echo htmlspecialchars($share->share_token ?? ''); ?>" title="<?php echo __('Copy full token'); ?>">
                    <i class="fas fa-copy"></i>
                  </button>
                </td>
                <td>
                  <span class="badge bg-<?php echo match($share->share_type ?? 'view') {
                    'view' => 'info', 'contribute' => 'warning', 'full' => 'danger', default => 'secondary'
                  }; ?>"><?php echo ucfirst($share->share_type ?? 'view'); ?></span>
                </td>
                <td>
                  <?php echo !empty($share->institution_name) ? htmlspecialchars($share->institution_name) : '<span class="text-muted">-</span>'; ?>
                </td>
                <td>
                  <span class="badge rounded-pill bg-<?php echo match($share->status ?? 'pending') {
                    'pending' => 'warning', 'active' => 'success', 'revoked' => 'danger', 'expired' => 'secondary', default => 'dark'
                  }; ?>"><?php echo ucfirst($share->status ?? 'pending'); ?></span>
                </td>
                <td><small class="text-muted"><?php echo date('M j, Y', strtotime($share->created_at)); ?></small></td>
                <td class="text-end">
                  <?php if (($share->status ?? 'pending') !== 'revoked' && ($share->status ?? 'pending') !== 'expired'): ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('<?php echo __('Revoke this share link?'); ?>');">
                    <input type="hidden" name="form_action" value="revoke_share">
                    <input type="hidden" name="share_id" value="<?php echo $share->id; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Revoke'); ?>"><i class="fas fa-ban"></i></button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- External Collaborators for this share -->
              <?php
              $shareCollabs = [];
              foreach ($externalCollaborators as $ec) {
                if (($ec->share_id ?? null) == $share->id) {
                  $shareCollabs[] = $ec;
                }
              }
              if (!empty($shareCollabs)):
              ?>
              <tr>
                <td colspan="6" class="bg-light">
                  <small class="text-muted fw-bold"><i class="fas fa-users me-1"></i><?php echo __('External Collaborators:'); ?></small>
                  <div class="ms-3 mt-1">
                    <?php foreach ($shareCollabs as $collab): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div>
                        <span class="small"><?php echo htmlspecialchars($collab->name ?? ''); ?></span>
                        <small class="text-muted">(<?php echo htmlspecialchars($collab->email ?? ''); ?>)</small>
                        <?php if (!empty($collab->institution)): ?>
                          <small class="text-muted">- <?php echo htmlspecialchars($collab->institution); ?></small>
                        <?php endif; ?>
                      </div>
                      <span class="badge bg-light text-dark"><?php echo ucfirst($collab->role ?? 'viewer'); ?></span>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
              <?php endif; ?>

              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="card-body text-center text-muted py-4">
          <i class="fas fa-share-alt fa-2x mb-2 opacity-50"></i>
          <p class="mb-0"><?php echo __('No shares yet. Generate a share link to collaborate with external researchers.'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.copy-token-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var token = this.dataset.token;
      var shareUrl = window.location.origin + '<?php echo url_for(['module' => 'research', 'action' => 'externalAccess']); ?>?token=' + token;
      navigator.clipboard.writeText(shareUrl).then(function() {
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        setTimeout(function() { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
      });
    });
  });
});
</script>
<?php end_slot() ?>
