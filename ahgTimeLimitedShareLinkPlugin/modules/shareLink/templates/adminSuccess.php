<?php
/** @var array $filters */
/** @var int $page */
/** @var int $pageSize */
/** @var int $totalCount */
/** @var int $totalPages */
/** @var \Illuminate\Support\Collection $tokens */
/** @var array $issuers */

$now = time();
$statusOptions = [
    'active'    => __('Active'),
    'expired'   => __('Expired'),
    'revoked'   => __('Revoked'),
    'exhausted' => __('Exhausted'),
    'all'       => __('All'),
];

$badgeFor = function (object $row) use ($now): array {
    if (!empty($row->revoked_at)) {
        return ['bg-secondary', __('Revoked')];
    }
    if (strtotime((string) $row->expires_at) <= $now) {
        return ['bg-warning text-dark', __('Expired')];
    }
    if ($row->max_access !== null && (int) $row->access_count >= (int) $row->max_access) {
        return ['bg-info text-dark', __('Exhausted')];
    }
    return ['bg-success', __('Active')];
};
?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  .sl-admin .badge { font-weight: 500; }
  .sl-admin td.tok code { font-size: .85rem; }
  .sl-admin .meta { font-size: .85rem; color: #6c757d; }
</style>

<h1>
  <i class="fas fa-share-alt me-1"></i><?php echo __('Share links') ?>
  <small class="text-muted"><?php echo sprintf(__('%d total'), $totalCount) ?></small>
</h1>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success"><?php echo esc_entities($sf_user->getFlash('success')) ?></div>
<?php endif ?>
<?php if ($sf_user->hasFlash('info')): ?>
  <div class="alert alert-info"><?php echo esc_entities($sf_user->getFlash('info')) ?></div>
<?php endif ?>

<form method="get" action="<?php echo url_for(['module' => 'shareLink', 'action' => 'admin']) ?>" class="row g-2 mb-3 sl-admin">
  <div class="col-auto">
    <label class="form-label small mb-0"><?php echo __('Status') ?></label>
    <select name="status" class="form-select form-select-sm">
      <?php foreach ($statusOptions as $val => $label): ?>
        <option value="<?php echo esc_entities($val) ?>"<?php echo $filters['status'] === $val ? ' selected' : '' ?>><?php echo esc_entities($label) ?></option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="col-auto">
    <label class="form-label small mb-0"><?php echo __('Issuer') ?></label>
    <select name="issuer" class="form-select form-select-sm">
      <option value=""><?php echo __('Any user') ?></option>
      <?php foreach ($issuers as $u): ?>
        <option value="<?php echo (int) $u->issued_by ?>"<?php echo (int) $filters['issuer'] === (int) $u->issued_by ? ' selected' : '' ?>>
          <?php echo esc_entities($u->username ?? ('#' . $u->issued_by)) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>
  <div class="col-auto flex-grow-1">
    <label class="form-label small mb-0"><?php echo __('Search') ?></label>
    <input type="text" name="q" value="<?php echo esc_entities($filters['q']) ?>" class="form-control form-control-sm" placeholder="<?php echo esc_entities(__('Token, email, or record title')) ?>">
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary btn-sm">
      <i class="fas fa-filter me-1"></i><?php echo __('Filter') ?>
    </button>
    <a href="<?php echo url_for(['module' => 'shareLink', 'action' => 'admin']) ?>" class="btn btn-link btn-sm">
      <?php echo __('Reset') ?>
    </a>
  </div>
</form>

<?php if ($totalCount === 0): ?>
  <div class="alert alert-info">
    <?php echo __('No share links match the current filter.') ?>
  </div>
<?php else: ?>
<table class="table table-sm table-hover sl-admin">
  <thead>
    <tr>
      <th><?php echo __('Status') ?></th>
      <th><?php echo __('Record') ?></th>
      <th><?php echo __('Issuer') ?></th>
      <th><?php echo __('Recipient') ?></th>
      <th><?php echo __('Issued') ?></th>
      <th><?php echo __('Expires') ?></th>
      <th><?php echo __('Visits') ?></th>
      <th class="tok"><?php echo __('Token') ?></th>
      <th class="text-end"><?php echo __('Actions') ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($tokens as $t): ?>
      <?php [$badgeCls, $badgeLabel] = $badgeFor($t); ?>
      <tr>
        <td><span class="badge <?php echo $badgeCls ?>"><?php echo esc_entities($badgeLabel) ?></span></td>
        <td>
          <?php echo esc_entities($t->io_title ?? ('#' . $t->information_object_id)) ?>
          <div class="meta">#<?php echo (int) $t->information_object_id ?></div>
        </td>
        <td><?php echo esc_entities($t->issuer_username ?? ('#' . $t->issued_by)) ?></td>
        <td>
          <?php echo $t->recipient_email ? esc_entities($t->recipient_email) : '<span class="text-muted">—</span>' ?>
        </td>
        <td><span class="meta"><?php echo esc_entities($t->created_at) ?></span></td>
        <td><span class="meta"><?php echo esc_entities($t->expires_at) ?></span></td>
        <td>
          <?php echo (int) $t->access_count ?><?php if ($t->max_access !== null) echo ' / ' . (int) $t->max_access ?>
        </td>
        <td class="tok"><code><?php echo esc_entities(substr($t->token, 0, 12)) ?>…</code></td>
        <td class="text-end">
          <a href="<?php echo url_for(['module' => 'shareLink', 'action' => 'adminShow', 'id' => $t->id]) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-eye me-1"></i><?php echo __('View') ?>
          </a>
          <?php if (empty($t->revoked_at) && strtotime((string) $t->expires_at) > time()): ?>
            <form action="<?php echo url_for(['module' => 'shareLink', 'action' => 'revoke', 'id' => $t->id]) ?>" method="post" class="d-inline ms-1"
                  onsubmit="return confirm('<?php echo esc_entities(__('Revoke this share link? Recipients will no longer be able to view the record.')) ?>');">
              <input type="hidden" name="back" value="<?php echo esc_entities($_SERVER['REQUEST_URI']) ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-ban me-1"></i><?php echo __('Revoke') ?>
              </button>
            </form>
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?php if ($totalPages > 1): ?>
<nav>
  <ul class="pagination pagination-sm">
    <?php
    $linkFor = function ($p) use ($filters) {
      return url_for([
        'module' => 'shareLink', 'action' => 'admin',
        'page' => $p, 'status' => $filters['status'],
        'q' => $filters['q'], 'issuer' => $filters['issuer'],
      ]);
    };
    $start = max(1, $page - 4);
    $end   = min($totalPages, $page + 4);
    ?>
    <li class="page-item <?php echo $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?php echo esc_entities($linkFor(max(1, $page - 1))) ?>">«</a>
    </li>
    <?php for ($p = $start; $p <= $end; $p++): ?>
      <li class="page-item <?php echo $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="<?php echo esc_entities($linkFor($p)) ?>"><?php echo $p ?></a>
      </li>
    <?php endfor ?>
    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : '' ?>">
      <a class="page-link" href="<?php echo esc_entities($linkFor(min($totalPages, $page + 1))) ?>">»</a>
    </li>
  </ul>
</nav>
<?php endif ?>
<?php endif ?>
