<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Compare Snapshots</li>
    </ol>
</nav>

<h1 class="h2 mb-4">Compare Snapshots</h1>

<div class="row mb-4">
    <div class="col-md-6"><div class="card"><div class="card-header bg-primary text-white"><h6 class="mb-0"><?php echo htmlspecialchars($snapshotA->title); ?></h6></div><div class="card-body"><small>Items: <?php echo (int) $snapshotA->item_count; ?> | Hash: <code><?php echo substr($snapshotA->hash_sha256 ?? '', 0, 12); ?></code></small></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header bg-info text-white"><h6 class="mb-0"><?php echo htmlspecialchars($snapshotB->title); ?></h6></div><div class="card-body"><small>Items: <?php echo (int) $snapshotB->item_count; ?> | Hash: <code><?php echo substr($snapshotB->hash_sha256 ?? '', 0, 12); ?></code></small></div></div></div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card border-success mb-3"><div class="card-header bg-success text-white">Added (<?php echo count($diff['added'] ?? []); ?>)</div>
            <ul class="list-group list-group-flush">
                <?php foreach (($diff['added'] ?? []) as $item): ?><li class="list-group-item small"><?php echo htmlspecialchars($item->object_type . ':' . $item->object_id); ?></li><?php endforeach; ?>
                <?php if (empty($diff['added'])): ?><li class="list-group-item text-muted small">None</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger mb-3"><div class="card-header bg-danger text-white">Removed (<?php echo count($diff['removed'] ?? []); ?>)</div>
            <ul class="list-group list-group-flush">
                <?php foreach (($diff['removed'] ?? []) as $item): ?><li class="list-group-item small"><?php echo htmlspecialchars($item->object_type . ':' . $item->object_id); ?></li><?php endforeach; ?>
                <?php if (empty($diff['removed'])): ?><li class="list-group-item text-muted small">None</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning mb-3"><div class="card-header bg-warning">Changed (<?php echo count($diff['changed'] ?? []); ?>)</div>
            <ul class="list-group list-group-flush">
                <?php foreach (($diff['changed'] ?? []) as $item): ?><li class="list-group-item small"><?php echo htmlspecialchars($item->object_type . ':' . $item->object_id); ?></li><?php endforeach; ?>
                <?php if (empty($diff['changed'])): ?><li class="list-group-item text-muted small">None</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>
