<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $groupDetail = $group['group']; ?>

<?php slot('title'); ?><?php echo __('Members'); ?> - <?php echo htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'groupBrowse'])],
  ['label' => htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $groupDetail->slug])],
  ['label' => __('Members')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo __('Members'); ?></h1>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'); ?> &mdash; <?php echo __('%1% members', ['%1%' => count($members)]); ?></p>
  </div>
  <div class="btn-group btn-group-sm">
    <?php if ($sf_user->isAuthenticated() && $sf_user->hasCredential('administrator')): ?>
    <a href="/registry/admin/groups/<?php echo (int) $groupDetail->id; ?>/members" class="btn btn-primary">
      <i class="fas fa-cog me-1"></i> <?php echo __('Manage Members'); ?>
    </a>
    <?php endif; ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $groupDetail->slug]); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Group'); ?>
    </a>
  </div>
</div>

<!-- Role filter -->
<div class="mb-3">
  <div class="btn-group btn-group-sm flex-wrap" role="group">
    <?php
      $roles = ['' => __('All'), 'organizer' => __('Organizer'), 'co_organizer' => __('Co-Organizer'), 'member' => __('Member'), 'speaker' => __('Speaker'), 'sponsor' => __('Sponsor')];
      $currentRole = $sf_request->getParameter('role', '');
    ?>
    <?php foreach ($roles as $val => $label): ?>
      <button type="button" class="btn btn-outline-secondary role-filter-btn<?php echo $currentRole === $val ? ' active' : ''; ?>" data-role="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo $label; ?>
      </button>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!empty($members)): ?>
<div class="table-responsive">
  <table class="table table-hover align-middle" id="members-table">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Role'); ?></th>
        <th><?php echo __('Institution'); ?></th>
        <th><?php echo __('Joined'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($members as $m): ?>
      <tr data-role="<?php echo htmlspecialchars($m->role ?? 'member', ENT_QUOTES, 'UTF-8'); ?>">
        <td>
          <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; min-width: 36px;">
              <i class="fas fa-user text-muted small"></i>
            </div>
            <div>
              <strong><?php echo htmlspecialchars($m->name ?? $m->email ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </div>
        </td>
        <td>
          <?php
            $roleColors = ['organizer' => 'danger', 'co_organizer' => 'warning', 'speaker' => 'info', 'sponsor' => 'success', 'member' => 'secondary'];
            $rColor = $roleColors[$m->role ?? 'member'] ?? 'secondary';
          ?>
          <span class="badge bg-<?php echo $rColor; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $m->role ?? 'member')), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td>
          <?php if (!empty($m->institution_name)): ?>
            <?php if (!empty($m->institution_slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $m->institution_slug]); ?>"><?php echo htmlspecialchars($m->institution_name, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
              <?php echo htmlspecialchars($m->institution_name, ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td>
          <small class="text-muted"><?php echo date('M j, Y', strtotime($m->joined_at)); ?></small>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-users fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No members yet'); ?></h5>
</div>
<?php endif; ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var buttons = document.querySelectorAll('.role-filter-btn');
  var rows = document.querySelectorAll('#members-table tbody tr');
  buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      buttons.forEach(function(b) { b.classList.remove('active'); });
      this.classList.add('active');
      var role = this.getAttribute('data-role');
      rows.forEach(function(row) {
        if (role === '' || row.getAttribute('data-role') === role) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });
});
</script>

<?php end_slot(); ?>
