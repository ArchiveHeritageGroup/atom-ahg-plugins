<?php
/**
 * Admin/Approver notification bar - shows pending items requiring action
 */
$isAdmin = $sf_user->isAdministrator();
$userId = $sf_user->getUserID();
$isAuthenticated = $sf_user->isAuthenticated();

// Check if user is an approver
$isApprover = false;
if ($isAuthenticated && !$isAdmin) {
    try {
        $isApprover = \AtomExtensions\Services\AccessRequestService::isApprover($userId);
    } catch (Exception $e) {
        // Service may not exist
    }
}

// Check if Spectrum plugin is available
$routing = sfContext::getInstance()->getRouting();
$hasSpectrum = $routing->hasRouteName('spectrum_my_tasks');

// Get Spectrum task count for any authenticated user (exclude final states)
$spectrumTaskCount = 0;
if ($isAuthenticated && $hasSpectrum) {
    try {
        // Collect all final states from all procedures
        $allFinalStates = [];
        $configs = \Illuminate\Database\Capsule\Manager::table('spectrum_workflow_config')
            ->where('is_active', 1)
            ->get();
        foreach ($configs as $config) {
            $finalStates = ahgSpectrumWorkflowService::getFinalStates($config->procedure_type);
            $allFinalStates = array_merge($allFinalStates, $finalStates);
        }
        $allFinalStates = array_unique($allFinalStates);

        $query = \Illuminate\Database\Capsule\Manager::table('spectrum_workflow_state')
            ->where('assigned_to', $userId);

        if (!empty($allFinalStates)) {
            $query->whereNotIn('current_state', $allFinalStates);
        }

        $spectrumTaskCount = $query->count();
    } catch (Exception $e) {
        // Table may not exist
    }
}

// Only show to admin, approvers, or users with spectrum tasks
if (!$isAdmin && !$isApprover && $spectrumTaskCount === 0) {
    return;
}

$notifications = [];

try {
    // Check for pending bookings (admin only for now)
    if ($isAdmin) {
        $pendingBookings = (int) \Illuminate\Database\Capsule\Manager::table('research_booking')
            ->where('status', 'pending')
            ->count();
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
        $pendingResearchers = (int) \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('status', 'pending')
            ->count();
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
        $pendingAccess = (int) \Illuminate\Database\Capsule\Manager::table('access_request')
            ->where('status', 'pending')
            ->count();
        if ($pendingAccess > 0) {
            $notifications[] = [
                'type' => 'secondary',
                'icon' => 'fa-shield-alt',
                'text' => sprintf(__('%d access request(s) awaiting approval'), $pendingAccess),
                'url' => url_for(['module' => 'accessRequest', 'action' => 'pending']),
                'action' => __('Review')
            ];
        }
    }
} catch (Exception $e) {
    // Tables may not exist - silently fail
}

// Add Spectrum task notifications for any authenticated user
if ($spectrumTaskCount > 0) {
    $notifications[] = [
        'type' => 'primary',
        'icon' => 'fa-clipboard-list',
        'text' => sprintf(__('You have %d task(s) assigned to you'), $spectrumTaskCount),
        'url' => url_for('@spectrum_my_tasks'),
        'action' => __('View Tasks')
    ];
}

if (empty($notifications)) {
    return;
}
?>
<div class="ahg-admin-notifications">
  <?php foreach ($notifications as $n): ?>
  <div class="alert alert-<?php echo $n['type']; ?> alert-dismissible fade show d-flex align-items-center justify-content-center py-2 mb-0 rounded-0 border-0" role="alert">
    <div class="container-xxl d-flex align-items-center">
      <i class="fas <?php echo $n['icon']; ?> me-2"></i>
      <span class="flex-grow-1"><?php echo $n['text']; ?></span>
      <a href="<?php echo $n['url']; ?>" class="btn btn-sm btn-light ms-2">
        <?php echo $n['action']; ?>
      </a>
      <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>
  <?php endforeach; ?>
</div>
