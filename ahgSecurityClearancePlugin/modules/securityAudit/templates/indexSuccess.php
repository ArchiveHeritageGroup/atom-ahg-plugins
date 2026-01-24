<?php
$logs = $sf_data->getRaw('logs');
$filters = $sf_data->getRaw('filters');
$actions = $sf_data->getRaw('actions');
$categories = $sf_data->getRaw('categories');
$total = $sf_data->getRaw('total');
$page = $sf_data->getRaw('page');
$totalPages = $sf_data->getRaw('totalPages');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="fas fa-clipboard-list me-2"></i><?php echo __('Security Audit Logs') ?></h1>
        <p class="text-muted mb-0">Showing <?php echo count($logs); ?> of <?php echo number_format($total); ?> records</p>
    </div>
    <div>
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'dashboard']) ?>" class="btn btn-primary">
            <i class="fas fa-chart-line me-1"></i>Dashboard
        </a>
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'export']) ?>" class="btn btn-success ms-1">
            <i class="fas fa-download me-1"></i>Export
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="/index.php/arSecurityAudit/index" class="row g-3">
            <div class="col-md-2">
                <label class="form-label small">User</label>
                <input type="text" name="user" class="form-control form-control-sm" 
                       value="<?php echo esc_entities($filters['user_name'] ?? '') ?>" placeholder="Username...">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Action</label>
                <select name="log_action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ((array)$actions as $act): ?>
                    <option value="<?php echo esc_entities($act) ?>" <?php echo ($filters['log_action'] ?? '') === $act ? 'selected' : '' ?>><?php echo esc_entities($act) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ((array)$categories as $cat): ?>
                    <option value="<?php echo esc_entities($cat) ?>" <?php echo ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>><?php echo esc_entities($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $filters['date_from'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $filters['date_to'] ?? '' ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="/index.php/arSecurityAudit/index" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:140px;">Date/Time</th>
                    <th style="width:120px;">User</th>
                    <th style="width:90px;">Action</th>
                    <th>Object</th>
                    <th style="width:100px;">Category</th>
                    <th style="width:120px;">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No logs found</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><small><?php echo date('M j, Y H:i', strtotime($log->created_at)) ?></small></td>
                    <td>
                        <?php if ($log->user_name): ?>
                        <a href="/index.php/arSecurityAudit/index?user=<?php echo urlencode($log->user_name) ?>"><?php echo esc_entities($log->user_name) ?></a>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php
                        $badgeClass = 'bg-secondary';
                        if (strpos($log->action, 'create') !== false) $badgeClass = 'bg-success';
                        elseif (strpos($log->action, 'update') !== false) $badgeClass = 'bg-warning text-dark';
                        elseif (strpos($log->action, 'delete') !== false) $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge <?php echo $badgeClass ?>"><?php echo esc_entities($log->action) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($log->object_slug)): ?>
                        <a href="/index.php/<?php echo $log->object_slug ?>"><?php echo esc_entities(mb_substr($log->object_title ?: 'Untitled', 0, 40)) ?></a>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif ?>
                    </td>
                    <td><small class="text-muted"><?php echo esc_entities($log->action_category ?? '-') ?></small></td>
                    <td><small class="text-muted font-monospace"><?php echo $log->ip_address ?? '-' ?></small></td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="/index.php/arSecurityAudit/index?page=<?php echo $page - 1 ?>&user=<?php echo urlencode($filters['user_name'] ?? '') ?>&log_action=<?php echo urlencode($filters['log_action'] ?? '') ?>&category=<?php echo urlencode($filters['category'] ?? '') ?>&date_from=<?php echo $filters['date_from'] ?? '' ?>&date_to=<?php echo $filters['date_to'] ?? '' ?>">&laquo;</a></li>
        <?php endif ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i === $page ? 'active' : '' ?>"><a class="page-link" href="/index.php/arSecurityAudit/index?page=<?php echo $i ?>&user=<?php echo urlencode($filters['user_name'] ?? '') ?>&log_action=<?php echo urlencode($filters['log_action'] ?? '') ?>&category=<?php echo urlencode($filters['category'] ?? '') ?>&date_from=<?php echo $filters['date_from'] ?? '' ?>&date_to=<?php echo $filters['date_to'] ?? '' ?>"><?php echo $i ?></a></li>
        <?php endfor ?>
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="/index.php/arSecurityAudit/index?page=<?php echo $page + 1 ?>&user=<?php echo urlencode($filters['user_name'] ?? '') ?>&log_action=<?php echo urlencode($filters['log_action'] ?? '') ?>&category=<?php echo urlencode($filters['category'] ?? '') ?>&date_from=<?php echo $filters['date_from'] ?? '' ?>&date_to=<?php echo $filters['date_to'] ?? '' ?>">&raquo;</a></li>
        <?php endif ?>
    </ul>
</nav>
<?php endif ?>
