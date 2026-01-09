<?php
/**
 * Admin/Approver notification bar - shows pending items requiring action
 */
$isAdmin = $sf_user->isAdministrator();
$userId = $sf_user->getUserID();

// Check if user is an approver
$isApprover = false;
if ($sf_user->isAuthenticated() && !$isAdmin) {
    try {
        $isApprover = \AtomExtensions\Services\AccessRequestService::isApprover($userId);
    } catch (Exception $e) {
        // Service may not exist
    }
}

// Only show to admin or approvers
if (!$isAdmin && !$isApprover) {
    return;
}

$notifications = [];

try {
    $conn = Propel::getConnection();
    
    // Check for pending bookings (admin only for now)
    if ($isAdmin) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM research_booking WHERE status = 'pending'");
        $stmt->execute();
        $pendingBookings = (int)$stmt->fetchColumn();
        if ($pendingBookings > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fa-calendar-check',
                'text' => sprintf(__('%d booking request(s) awaiting confirmation'), $pendingBookings),
                'url' => url_for(['module' => 'research', 'action' => 'bookings']),
                'action' => __('Review')
            ];
        }
        
        // Check for pending researchers (admin only)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM research_researcher WHERE status = 'pending'");
        $stmt->execute();
        $pendingResearchers = (int)$stmt->fetchColumn();
        if ($pendingResearchers > 0) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fa-user-clock',
                'text' => sprintf(__('%d researcher registration(s) awaiting approval'), $pendingResearchers),
                'url' => url_for(['module' => 'research', 'action' => 'researchers']),
                'action' => __('Review')
            ];
        }
    }
    
    // Check for pending access requests (admin and approvers)
    if ($isAdmin || $isApprover) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM access_request WHERE status = 'pending'");
        $stmt->execute();
        $pendingAccess = (int)$stmt->fetchColumn();
        if ($pendingAccess > 0) {
            $notifications[] = [
                'type' => 'secondary',
                'icon' => 'fa-shield-alt',
                'text' => sprintf(__('%d access request(s) awaiting approval'), $pendingAccess),
                'url' => url_for(['module' => 'ahgAccessRequest', 'action' => 'pending']),
                'action' => __('Review')
            ];
        }
    }
} catch (Exception $e) {
    // Tables may not exist - silently fail
}

if (empty($notifications)) {
    return;
}
?>
<div class="ahg-admin-notifications mb-3">
  <?php foreach ($notifications as $n): ?>
  <div class="alert alert-<?php echo $n['type']; ?> alert-dismissible fade show d-flex align-items-center py-2 mb-2" role="alert">
    <i class="fas <?php echo $n['icon']; ?> me-2"></i>
    <span class="flex-grow-1"><?php echo $n['text']; ?></span>
    <a href="<?php echo $n['url']; ?>" class="btn btn-sm btn-<?php echo $n['type']; ?> ms-2">
      <?php echo $n['action']; ?>
    </a>
    <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  <?php endforeach; ?>
</div>
