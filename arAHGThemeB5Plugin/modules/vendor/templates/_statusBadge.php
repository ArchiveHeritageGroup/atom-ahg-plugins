<?php
$statusConfig = [
    'pending_approval' => ['label' => 'Pending Approval', 'color' => 'warning', 'icon' => 'clock'],
    'approved' => ['label' => 'Approved', 'color' => 'info', 'icon' => 'check'],
    'dispatched' => ['label' => 'Dispatched', 'color' => 'primary', 'icon' => 'truck'],
    'received_by_vendor' => ['label' => 'At Vendor', 'color' => 'secondary', 'icon' => 'building'],
    'in_progress' => ['label' => 'In Progress', 'color' => 'info', 'icon' => 'spinner'],
    'completed' => ['label' => 'Completed', 'color' => 'success', 'icon' => 'check-circle'],
    'ready_for_collection' => ['label' => 'Ready', 'color' => 'success', 'icon' => 'box'],
    'returned' => ['label' => 'Returned', 'color' => 'dark', 'icon' => 'undo'],
    'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'icon' => 'times'],
];

$config = $statusConfig[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'color' => 'secondary', 'icon' => 'question'];
?>
<span class="badge bg-<?php echo $config['color']; ?>">
    <i class="fas fa-<?php echo $config['icon']; ?> me-1"></i><?php echo $config['label']; ?>
</span>
