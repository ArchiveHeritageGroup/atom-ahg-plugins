<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>">Workspace</a></li>
        <li class="breadcrumb-item active">Saved Searches</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-search text-primary me-2"></i>Saved Searches</h1>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<div class="card">
    <?php if (!empty($savedSearches)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Query</th><th>Alerts</th><th>Created</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($savedSearches as $s): ?>
                        <tr>
                            <td><strong><?php echo $s->name; ?></strong><?php if ($s->description): ?><br><small class="text-muted"><?php echo $s->description; ?></small><?php endif; ?></td>
                            <td><code><?php echo htmlspecialchars($s->search_query); ?></code></td>
                            <td><?php echo $s->alert_enabled ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($s->created_at)); ?></td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?sq0=' . urlencode($s->search_query); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></a>
                                <form method="post" class="d-inline"><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?php echo $s->id; ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-body text-center text-muted"><i class="fas fa-search fa-3x mb-3"></i><p>No saved searches. Use the search feature and save searches for quick access.</p></div>
    <?php endif; ?>
</div>
