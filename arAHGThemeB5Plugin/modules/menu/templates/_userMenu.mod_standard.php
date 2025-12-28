<?php
/**
 * User menu with conditional plugin checks
 */
$userId = $sf_user->getUserID();
$isAdmin = $sf_user->isAdministrator();
$isAuthenticated = $sf_user->isAuthenticated();

// Check which plugins have routes registered (not just directory exists)
$routing = sfContext::getInstance()->getRouting();
$hasAccessRequest = $routing->hasRouteName('access_request_my');
$hasResearch = $routing->hasRouteName('research_workspace');
$hasSecurity = is_dir(sfConfig::get('sf_plugins_dir') . '/arSecurityClearancePlugin');

// Get pending counts only if plugins exist
$pendingCount = 0;
$pendingResearcherCount = 0;

if ($isAuthenticated && $hasAccessRequest) {
    try {
        if ($isAdmin || \AtomExtensions\Services\AccessRequestService::isApprover($userId)) {
            $pendingCount = \Illuminate\Database\Capsule\Manager::table('access_request')
                ->where('status', 'pending')
                ->count();
        }
    } catch (Exception $e) {
        // Table may not exist
    }
}

if ($isAuthenticated && $hasResearch && $isAdmin) {
    try {
        $pendingResearcherCount = \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('status', 'pending')
            ->count();
    } catch (Exception $e) {
        // Table may not exist
    }
}
?>

<?php if ($isAuthenticated): ?>
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" id="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-user-circle me-1"></i>
    <?php echo $sf_user->getAttribute('username', 'User'); ?>
  </a>
  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="user-menu">
    
    <!-- Profile Section -->
    <li><h6 class="dropdown-header"><i class="fas fa-user me-1"></i><?php echo __('Profile'); ?></h6></li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for(array($sf_user->user, 'module' => 'user')); ?>">
        <i class="fas fa-id-card me-2"></i><?php echo __('My Profile'); ?>
      </a>
    </li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for(['module' => 'user', 'action' => 'passwordEdit']); ?>">
        <i class="fas fa-key me-2"></i><?php echo __('Change Password'); ?>
      </a>
    </li>
    
    <?php if ($hasResearch): ?>
    <!-- Research Section -->
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-book-reader me-1"></i><?php echo __('Research'); ?></h6></li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for('@research_workspace'); ?>">
        <i class="fas fa-folder-open me-2"></i><?php echo __('My Workspace'); ?>
      </a>
    </li>
    <?php endif; ?>
    
    <?php if ($hasAccessRequest): ?>
    <!-- Security Section -->
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-shield-alt me-1"></i><?php echo __('Security'); ?></h6></li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for('@access_request_my'); ?>">
        <i class="fas fa-key me-2"></i><?php echo __('My Access Requests'); ?>
      </a>
    </li>
    <?php if ($isAdmin || \AtomExtensions\Services\AccessRequestService::isApprover($userId)): ?>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for('@access_request_pending'); ?>">
        <span><i class="fas fa-clock me-2"></i><?php echo __('Pending Requests'); ?></span>
        <?php if ($pendingCount > 0): ?>
        <span class="badge bg-warning text-dark"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
      </a>
    </li>
    <?php endif; ?>
    <?php if ($isAdmin): ?>
    <li>
      <a class="dropdown-item" href="<?php echo url_for('@access_request_approvers'); ?>">
        <i class="fas fa-user-shield me-2"></i><?php echo __('Manage Approvers'); ?>
      </a>
    </li>
    <?php endif; ?>
    <?php endif; ?>
    
    <?php if ($isAdmin && $hasResearch && $pendingResearcherCount > 0): ?>
    <!-- Admin Notifications -->
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-bell me-1"></i><?php echo __('Notifications'); ?></h6></li>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for('@researcher_admin'); ?>">
        <span><i class="fas fa-user-clock me-2"></i><?php echo __('Pending Researchers'); ?></span>
        <span class="badge bg-warning text-dark"><?php echo $pendingResearcherCount; ?></span>
      </a>
    </li>
    <?php endif; ?>
    
    <!-- Logout -->
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item text-danger" href="<?php echo url_for(['module' => 'user', 'action' => 'logout']); ?>">
        <i class="fas fa-sign-out-alt me-2"></i><?php echo __('Logout'); ?>
      </a>
    </li>
    
  </ul>
</li>
<?php else: ?>
<li class="nav-item">
  <a class="nav-link" href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>">
    <i class="fas fa-sign-in-alt me-1"></i><?php echo __('Log in'); ?>
  </a>
</li>
<?php endif; ?>
