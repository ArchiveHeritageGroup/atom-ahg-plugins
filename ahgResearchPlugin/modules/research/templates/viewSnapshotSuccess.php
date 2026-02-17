<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Snapshot: <?php echo htmlspecialchars($snapshot->title); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2"><?php echo htmlspecialchars($snapshot->title); ?></h1>
        <span class="badge bg-<?php echo $snapshot->status === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($snapshot->status); ?></span>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Details</h5></div>
            <div class="card-body">
                <?php if ($snapshot->description): ?><p><?php echo nl2br(htmlspecialchars($snapshot->description)); ?></p><?php endif; ?>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Items</dt><dd class="col-sm-8"><?php echo (int) $snapshot->item_count; ?></dd>
                    <dt class="col-sm-4">SHA-256</dt><dd class="col-sm-8"><code class="small"><?php echo htmlspecialchars($snapshot->hash_sha256 ?? 'N/A'); ?></code></dd>
                    <dt class="col-sm-4">Created</dt><dd class="col-sm-8"><?php echo $snapshot->created_at; ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Snapshot Items</h5></div>
    <div class="card-body">
        <?php if (!empty($items['items'])): ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Object</th><th>Type</th><th>Culture</th></tr></thead>
                <tbody>
                <?php foreach ($items['items'] as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item->slug ?? 'Object #' . $item->object_id); ?></td>
                        <td><?php echo htmlspecialchars($item->object_type ?? 'information_object'); ?></td>
                        <td><?php echo htmlspecialchars($item->culture ?? 'en'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-0">No items in this snapshot.</p>
        <?php endif; ?>
    </div>
</div>
