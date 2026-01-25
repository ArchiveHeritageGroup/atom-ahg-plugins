<?php
/**
 * Heritage My Access Requests.
 */

decorate_with('layout_2col');

$requests = $requestData['requests'] ?? [];
$total = $requestData['total'] ?? 0;
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-key me-2"></i>My Access Requests
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<div class="list-group mb-4">
    <a href="?status=" class="list-group-item list-group-item-action <?php echo !$sf_request->getParameter('status') ? 'active' : ''; ?>">
        All Requests
    </a>
    <a href="?status=pending" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('status') === 'pending' ? 'active' : ''; ?>">
        Pending
    </a>
    <a href="?status=approved" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('status') === 'approved' ? 'active' : ''; ?>">
        Approved
    </a>
    <a href="?status=denied" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('status') === 'denied' ? 'active' : ''; ?>">
        Denied
    </a>
</div>
<?php end_slot(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0">Your Access Requests</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fs-1 mb-3 d-block"></i>
            <p>No access requests found.</p>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($requests as $request): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $request->slug]); ?>">
                                <?php echo esc_specialchars($request->object_title ?? $request->slug ?? 'Item'); ?>
                            </a>
                        </h6>
                        <small class="text-muted">
                            Purpose: <?php echo esc_specialchars($request->purpose_name ?? 'Not specified'); ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <?php
                        $statusColors = ['pending' => 'warning', 'approved' => 'success', 'denied' => 'danger', 'expired' => 'secondary', 'withdrawn' => 'secondary'];
                        $color = $statusColors[$request->status] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($request->status); ?></span>
                        <br>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($request->created_at)); ?></small>
                    </div>
                </div>
                <?php if ($request->status === 'approved' && $request->valid_until): ?>
                <div class="mt-2">
                    <small class="text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        Access valid until <?php echo date('M d, Y', strtotime($request->valid_until)); ?>
                    </small>
                </div>
                <?php elseif ($request->status === 'denied' && $request->decision_notes): ?>
                <div class="mt-2">
                    <small class="text-danger">
                        <i class="fas fa-info-circle me-1"></i>
                        <?php echo esc_specialchars($request->decision_notes); ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
