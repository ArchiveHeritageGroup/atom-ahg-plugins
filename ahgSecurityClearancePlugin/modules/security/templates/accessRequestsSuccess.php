<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
<h1><?php echo __('Access Requests'); ?></h1>
<?php end_slot(); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Access Requests'); ?></li>
  </ol>
</nav>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center" style="background-color: #f0ad4e; color: white;">
            <div class="card-body">
                <h2><?php echo $pendingCount; ?></h2>
                <small><?php echo __('Pending'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center" style="background-color: #5cb85c; color: white;">
            <div class="card-body">
                <h2><?php echo $approvedTodayCount; ?></h2>
                <small><?php echo __('Approved Today'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center" style="background-color: #d9534f; color: white;">
            <div class="card-body">
                <h2><?php echo $deniedTodayCount; ?></h2>
                <small><?php echo __('Denied Today'); ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center" style="background-color: #5bc0de; color: white;">
            <div class="card-body">
                <h2><?php echo $thisMonthCount; ?></h2>
                <small><?php echo __('This Month'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Pending Requests Table -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-check-square me-2"></i><?php echo __('Pending Access Requests'); ?></h5>
    </div>
    <div class="card-body">
        <?php echo "DEBUG: " . count($pendingRequests) . " requests"; if (empty($pendingRequests)): ?>
        <p class="text-muted text-center mb-0"><?php echo __('No pending access requests.'); ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('User'); ?></th>
                        <th><?php echo __('Request Type'); ?></th>
                        <th><?php echo __('Object'); ?></th>
                        <th><?php echo __('Priority'); ?></th>
                        <th><?php echo __('Justification'); ?></th>
                        <th><?php echo __('Submitted'); ?></th>
                        <th><?php echo __('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $req): ?>
                    <tr>
                        <td><?php echo esc_entities($req->user_name ?? $req->username ?? 'Unknown'); ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo ucwords(str_replace('_', ' ', $req->request_type)); ?></span>
                        </td>
                        <td>
                            <?php if ($req->object_id && $req->slug): ?>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $req->slug]); ?>">
                                <?php echo esc_entities($req->object_title ?? $req->slug); ?>
                            </a>
                            <?php else: ?>
                            <em class="text-muted"><?php echo __('N/A'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $priorityClass = match($req->priority ?? 'normal') {
                                'immediate' => 'bg-danger',
                                'urgent' => 'bg-warning',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $priorityClass; ?>"><?php echo ucfirst($req->priority ?? 'Normal'); ?></span>
                        </td>
                        <td><small><?php echo esc_entities(substr($req->justification ?? '', 0, 100)); ?><?php echo strlen($req->justification ?? '') > 100 ? '...' : ''; ?></small></td>
                        <td><small><?php echo $req->created_at ? date('Y-m-d H:i', strtotime($req->created_at)) : ''; ?></small></td>
                        <td>
                            <form method="post" action="<?php echo url_for(['module' => 'security', 'action' => 'approveRequest']); ?>" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $req->id; ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="<?php echo __('Approve'); ?>">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="post" action="<?php echo url_for(['module' => 'security', 'action' => 'denyRequest']); ?>" class="d-inline">
                                <input type="hidden" name="id" value="<?php echo $req->id; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('Deny'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <a href="<?php echo url_for(['module' => 'security', 'action' => 'viewRequest', 'id' => $req->id]); ?>" class="btn btn-sm btn-info" title="<?php echo __('View Details'); ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
