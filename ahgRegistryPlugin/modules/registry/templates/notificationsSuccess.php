<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?>Notifications — AtoM Registry<?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => 'Home', 'url' => '/'],
  ['label' => 'Registry', 'url' => '/registry/'],
  ['label' => 'Notifications', 'url' => null],
]]); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h1 class="h4 mb-0"><i class="fas fa-bell me-2"></i>Notifications</h1>
  <?php if ($unreadCount > 0): ?>
    <form method="post" action="/registry/api/notifications/read-all" id="regNotifReadAllForm">
      <button type="submit" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-check-double me-1"></i>Mark all as read
        <span class="badge bg-primary ms-1"><?php echo (int) $unreadCount; ?></span>
      </button>
    </form>
  <?php endif; ?>
</div>

<?php if (empty($items)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
      <p class="text-muted mb-0">You have no notifications yet.</p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <ul class="list-group list-group-flush">
      <?php foreach ($items as $item): ?>
        <?php
          $isRead = (int) $item->is_read === 1;
          $href = $item->link ?: '/registry/notifications';
          $rowClass = $isRead ? '' : ' bg-light';
        ?>
        <li class="list-group-item<?php echo $rowClass; ?>">
          <div class="d-flex align-items-start">
            <div class="me-3 pt-1">
              <?php if (!$isRead): ?>
                <span class="badge rounded-pill bg-primary">&nbsp;</span>
              <?php else: ?>
                <i class="fas fa-check text-muted"></i>
              <?php endif; ?>
            </div>
            <div class="flex-grow-1">
              <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"
                 class="text-decoration-none reg-notif-link"
                 data-id="<?php echo (int) $item->id; ?>"
                 data-read="<?php echo $isRead ? '1' : '0'; ?>">
                <div class="fw-semibold"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if (!empty($item->message)): ?>
                  <div class="small text-muted mt-1"><?php echo nl2br(htmlspecialchars($item->message, ENT_QUOTES, 'UTF-8')); ?></div>
                <?php endif; ?>
              </a>
              <div class="small text-muted mt-1">
                <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($item->created_at)), ENT_QUOTES, 'UTF-8'); ?>
                <span class="ms-3 badge bg-secondary"><?php echo htmlspecialchars($item->type, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
            <?php if (!$isRead): ?>
              <button type="button" class="btn btn-sm btn-link text-muted reg-notif-mark-read" data-id="<?php echo (int) $item->id; ?>" title="Mark as read">
                <i class="fas fa-check"></i>
              </button>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
      <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item<?php echo $p === $page ? ' active' : ''; ?>">
            <a class="page-link" href="/registry/notifications?page=<?php echo $p; ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>

<?php slot('scripts'); ?>
<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script <?php echo $na; ?>>
(function () {
  document.querySelectorAll('.reg-notif-mark-read').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      var id = btn.getAttribute('data-id');
      if (!id) return;
      var fd = new FormData();
      fetch('/registry/api/notifications/' + encodeURIComponent(id) + '/read', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      }).then(function () { window.location.reload(); });
    });
  });

  // "Mark all" form posts via AJAX so we stay on the page.
  var allForm = document.getElementById('regNotifReadAllForm');
  if (allForm) {
    allForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var fd = new FormData();
      fetch('/registry/api/notifications/read-all', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      }).then(function () { window.location.reload(); });
    });
  }
})();
</script>
<?php end_slot(); ?>
