<?php
/** @var string $entityType */
/** @var int    $entityId */
/** @var string $entityTitle */
/** @var string $viewUrl */
/** @var int    $totalCount */
/** @var int    $page */
/** @var int    $pageSize */
/** @var int    $totalPages */
/** @var \Illuminate\Support\Collection $versions */
?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  .vc-list .badge-restore { background:#fff3cd; color:#856404; border:1px solid #ffeeba; }
  .vc-list .badge-no-change { background:#e9ecef; color:#495057; }
  .vc-list .badge-changes { background:#d4edda; color:#155724; }
  .vc-list code.fields { font-size: 0.85rem; color:#495057; }
  .vc-list tbody tr:hover { background:#f8f9fa; }
  .vc-list .restored-from { font-size: 0.85rem; color:#856404; }
</style>

<h1>
    <?php echo __('Version history') ?>
    <small class="text-muted"><?php echo esc_entities($entityTitle) ?></small>
</h1>

<p>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_entities($viewUrl) ?>">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to record') ?>
    </a>
    <span class="text-muted ms-2"><?php echo sprintf(__('%d version(s)'), $totalCount) ?></span>
</p>

<?php if ($totalCount === 0): ?>
    <div class="alert alert-info">
        <?php echo __('No versions have been captured for this record yet. A version is written automatically the next time the record is saved.') ?>
    </div>
<?php else: ?>

<form id="vc-diff-form" method="get" action="<?php echo url_for(['module' => 'versionControl', 'action' => 'diff', 'entity' => $entityType, 'id' => $entityId]) ?>" class="mb-2">
    <div class="d-flex align-items-center gap-2">
        <button type="submit" class="btn btn-outline-primary btn-sm" disabled id="vc-compare-btn">
            <i class="fas fa-code-compare me-1"></i><?php echo __('Compare selected') ?>
        </button>
        <small class="text-muted"><?php echo __('Tick two versions to compare them') ?></small>
    </div>
</form>

<table class="table table-sm vc-list">
    <thead>
        <tr>
            <th style="width:36px"></th>
            <th style="width:84px"><?php echo __('Version') ?></th>
            <th style="width:170px"><?php echo __('Date') ?></th>
            <th style="width:140px"><?php echo __('User') ?></th>
            <th><?php echo __('Summary') ?></th>
            <th style="width:170px"><?php echo __('Changes') ?></th>
            <th style="width:60px"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($versions as $v): ?>
        <?php
            $detailUrl = url_for([
                'module' => 'versionControl',
                'action' => 'show',
                'entity' => $entityType,
                'id'     => $entityId,
                'number' => (int) $v->version_number,
            ]);
            $changed = is_string($v->changed_fields) ? (json_decode($v->changed_fields, true) ?? []) : [];
            $changedCount = is_array($changed) ? count($changed) : 0;
        ?>
        <tr>
            <td>
                <input type="checkbox" form="vc-diff-form" class="vc-pick" name="vc-pick"
                       value="<?php echo (int) $v->version_number ?>">
            </td>
            <td>
                <a href="<?php echo $detailUrl ?>"><strong>v<?php echo (int) $v->version_number ?></strong></a>
                <?php if ((int) $v->is_restore === 1): ?>
                    <span class="badge badge-restore"><?php echo __('restore') ?></span>
                <?php endif ?>
            </td>
            <td><?php echo esc_entities($v->created_at) ?></td>
            <td><?php echo esc_entities($v->created_by_username ?? '—') ?></td>
            <td>
                <?php echo esc_entities($v->change_summary ?: '—') ?>
                <?php if ((int) $v->is_restore === 1 && $v->restored_from_version): ?>
                    <div class="restored-from">↩ <?php echo sprintf(__('Restored from v%d'), (int) $v->restored_from_version) ?></div>
                <?php endif ?>
            </td>
            <td>
                <?php if ($changedCount === 0 && $v->changed_fields !== null): ?>
                    <span class="badge badge-no-change"><?php echo __('no archival metadata changes') ?></span>
                <?php elseif ($changedCount > 0): ?>
                    <span class="badge badge-changes"><?php echo sprintf(__('%d field(s)'), $changedCount) ?></span>
                    <div><code class="fields"><?php echo esc_entities(implode(', ', array_slice((array) $changed, 0, 3))) ?><?php echo $changedCount > 3 ? '…' : '' ?></code></div>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif ?>
            </td>
            <td><a class="btn btn-sm btn-outline-secondary" href="<?php echo $detailUrl ?>"><?php echo __('View') ?></a></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?php echo $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?php echo url_for([
                    'module' => 'versionControl', 'action' => 'list',
                    'entity' => $entityType, 'id' => $entityId, 'page' => $p,
                ]) ?>"><?php echo $p ?></a>
            </li>
        <?php endfor ?>
    </ul>
</nav>
<?php endif ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function () {
    var picks = document.querySelectorAll('.vc-pick');
    var compareBtn = document.getElementById('vc-compare-btn');
    var form = document.getElementById('vc-diff-form');
    function refresh() {
        var checked = Array.from(picks).filter(function (c) { return c.checked; });
        compareBtn.disabled = checked.length !== 2;
        // Enforce max of 2 selections by disabling the rest when 2 are picked
        picks.forEach(function (c) { if (!c.checked) c.disabled = checked.length >= 2; });
    }
    picks.forEach(function (c) { c.addEventListener('change', refresh); });
    form.addEventListener('submit', function (e) {
        var checked = Array.from(picks).filter(function (c) { return c.checked; })
            .map(function (c) { return parseInt(c.value, 10); })
            .sort(function (a, b) { return a - b; });
        if (checked.length !== 2) { e.preventDefault(); return; }
        // Remove the raw vc-pick fields; rewrite as v1/v2 query params
        Array.from(form.querySelectorAll('input[name="vc-pick"]')).forEach(function (n) { n.remove(); });
        var v1 = document.createElement('input'); v1.type='hidden'; v1.name='v1'; v1.value=String(checked[0]); form.appendChild(v1);
        var v2 = document.createElement('input'); v2.type='hidden'; v2.name='v2'; v2.value=String(checked[1]); form.appendChild(v2);
    });
})();
</script>
<?php endif ?>
