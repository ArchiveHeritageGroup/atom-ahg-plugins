<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .themeb-min-width-280px-1f44 { min-width: 280px; }
</style>
<?php
/**
 * User menu with conditional plugin checks
 */
$userId = $sf_user->getUserID();
$isAdmin = $sf_user->isAdministrator();
$isAuthenticated = $sf_user->isAuthenticated();

// Check which plugins have routes registered
$routing = sfContext::getInstance()->getRouting();
$hasAccessRequest = $routing->hasRouteName('access_request_my');
$hasResearch = $routing->hasRouteName('research_workspace');
$hasSpectrum = $routing->hasRouteName('spectrum_my_tasks');
$hasResearcher = $routing->hasRouteName('researcher_dashboard');
$hasRegistration = $routing->hasRouteName('user_register');

// Get pending counts only if plugins exist
$pendingCount = 0;
$pendingResearcherCount = 0;
$pendingBookingCount = 0;
$spectrumTaskCount = 0;

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
        $pendingBookingCount = \Illuminate\Database\Capsule\Manager::table('research_booking')
            ->where('status', 'pending')
            ->count();
    } catch (Exception $e) {
        // Table may not exist
    }
}

// Get open task count for current user. Each procedure declares its own final
// state (a location movement ends at 'verified', a loan-in at 'returned'), so the
// count must be judged per procedure against the workflow config - a hardcoded
// list here counted finished work as outstanding.
if ($isAuthenticated && $hasSpectrum) {
    try {
        $spectrumTaskCount = ahgSpectrumWorkflowService::countOpenTasksForUser($userId);
    } catch (Exception $e) {
        // Plugin or table may not be present.
    }
}

// SAHRA / NHRA heritage permits - actions awaiting this user.
$hasSahra = $routing->hasRouteName('sahra_my');
$sahraEndorseCount = 0; // pending endorsements where the user is the nominated supervisor
$sahraReviewCount = 0;  // applications lodged and awaiting a SAHRA decision (reviewers)
if ($isAuthenticated && $hasSahra) {
    try {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgSAHRAPlugin/lib/Services/SahraPermitService.php';
        $sahraSvc = new \AhgSAHRA\Services\SahraPermitService();
        if ($sahraSvc->isFeatureEnabled()) {
            $sahraEndorseCount = \Illuminate\Database\Capsule\Manager::table('sahra_permit')
                ->where('status', 'pending_supervisor')
                ->where('supervisor_user_id', $userId)
                ->count();
            if ($isAdmin || $sahraSvc->isSahraReviewer($userId)) {
                $sahraReviewCount = \Illuminate\Database\Capsule\Manager::table('sahra_permit')
                    ->where('status', 'submitted_to_sahra')
                    ->count();
            }
        }
    } catch (Exception $e) {
        // Plugin/table may not be present.
    }
}
$sahraTotal = $sahraEndorseCount + $sahraReviewCount;
?>

<?php if ($showLogin): ?>
<!-- Login dropdown for unauthenticated users -->
<div class="dropdown my-2">
  <button class="btn btn-sm atom-btn-secondary dropdown-toggle" type="button" id="user-menu" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
    <i class="fas fa-sign-in-alt me-1"></i><?php echo $menuLabels['login'] ?? __('Log in'); ?>
  </button>
  <div class="dropdown-menu dropdown-menu-lg-end mt-2 p-3" aria-labelledby="user-menu" class="themeb-min-width-280px-1f44">
    <h6 class="dropdown-header px-0"><?php echo __('Have an account?'); ?></h6>
    <?php echo $form->renderFormTag(url_for(['module' => 'user', 'action' => 'login']), ['class' => 'mt-2']); ?>
      <?php echo $form->renderHiddenFields(); ?>
      <?php echo render_field($form->email, null, ['class' => 'form-control-sm']); ?>
      <?php echo render_field($form->password, null, ['class' => 'form-control-sm', 'autocomplete' => 'off']); ?>
      <button class="btn btn-sm atom-btn-secondary w-100 mt-2" type="submit">
        <?php echo $menuLabels['login'] ?? __('Log in'); ?>
      </button>
    </form>

    <div class="text-center mt-2">
      <a href="<?php echo url_for(['module' => 'user', 'action' => 'passwordReset']); ?>" class="small text-muted">
        <i class="fas fa-key me-1"></i><?php echo __('Forgot password?'); ?>
      </a>
    </div>

    <?php if ($hasRegistration || $hasResearch): ?>
    <hr class="my-3">
    <div class="text-center">
      <?php if ($hasRegistration): ?>
      <a href="<?php echo url_for('@user_register'); ?>" class="btn btn-sm btn-primary w-100 mb-2">
        <i class="fas fa-user-plus me-1"></i><?php echo __('Register'); ?>
      </a>
      <?php endif; ?>
      <?php if ($hasResearch): ?>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'publicRegister']); ?>" class="btn btn-sm btn-success w-100 mb-1">
        <i class="fas fa-user-graduate me-1"></i><?php echo __('Register as Researcher'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>" class="small text-muted d-block mt-1">
        <i class="fas fa-book-reader me-1"></i><?php echo __('View Research Services'); ?>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($isAuthenticated): ?>
<!-- User menu for authenticated users -->
<div class="dropdown my-2">
  <button class="btn btn-sm atom-btn-secondary dropdown-toggle position-relative" type="button" id="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-user-circle me-1"></i><?php echo $sf_user->user->username; ?>
    <?php if (!empty($sahraTotal) && $sahraTotal > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" title="<?php echo __('Heritage permit actions awaiting you'); ?>">
        <?php echo $sahraTotal; ?><span class="visually-hidden"><?php echo __('pending heritage permit actions'); ?></span>
      </span>
    <?php endif; ?>
  </button>
  <ul class="dropdown-menu dropdown-menu-lg-end mt-2" aria-labelledby="user-menu">

    <!-- Profile Section -->
    <li><h6 class="dropdown-header"><i class="fas fa-user me-1"></i><?php echo __('Profile'); ?></h6></li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for('user/' . $sf_user->getAttribute('user_slug')); ?>">
        <i class="fas fa-id-card me-2"></i><?php echo $menuLabels['myProfile'] ?? __('My Profile'); ?>
      </a>
    </li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for(['module' => 'user', 'action' => 'passwordEdit']); ?>">
        <i class="fas fa-key me-2"></i><?php echo __('Change Password'); ?>
      </a>
    </li>

    <?php if ($hasSpectrum): ?>
    <!-- Spectrum Tasks Section -->
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-tasks me-1"></i><?php echo __('Tasks'); ?></h6></li>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for('@spectrum_my_tasks'); ?>">
        <span><i class="fas fa-clipboard-list me-2"></i><?php echo __('My Tasks'); ?></span>
        <?php if ($spectrumTaskCount > 0): ?>
        <span class="badge bg-danger"><?php echo $spectrumTaskCount; ?></span>
        <?php endif; ?>
      </a>
    </li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for('@spectrum_dashboard'); ?>">
        <span><i class="fas fa-tachometer-alt me-2"></i><?php echo __('Workflow Dashboard'); ?></span>
      </a>
    </li>
    <?php endif; ?>

    <?php if ($hasResearch): ?>
    <!-- Research Section -->
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-book-reader me-1"></i><?php echo __('Research'); ?></h6></li>
    <li>
      <a class="dropdown-item" href="<?php echo url_for('@research_dashboard'); ?>">
        <i class="fas fa-folder-open me-2"></i><?php echo __('My Workspace'); ?>
      </a>
    </li>
    <?php endif; ?>

    <?php if ($hasResearcher): ?>
    <!-- Researcher Submissions -->
    <?php if (!$hasResearch): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
    <li>
      <a class="dropdown-item" href="<?php echo url_for(['module' => 'researcher', 'action' => 'dashboard']); ?>">
        <i class="fas fa-cloud-upload-alt me-2"></i><?php echo __('My Submissions'); ?>
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

    <!-- SAHRA heritage permits -->
    <?php if ($hasSahra && $sahraTotal > 0): ?>
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-landmark me-1"></i><?php echo __('Heritage permits'); ?></h6></li>
    <?php if ($sahraEndorseCount > 0): ?>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for('@sahra_approvals'); ?>">
        <span><i class="fas fa-user-check me-2"></i><?php echo __('To endorse'); ?></span>
        <span class="badge bg-warning text-dark"><?php echo $sahraEndorseCount; ?></span>
      </a>
    </li>
    <?php endif; ?>
    <?php if ($sahraReviewCount > 0): ?>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for('@sahra_review'); ?>">
        <span><i class="fas fa-stamp me-2"></i><?php echo __('SAHRA review'); ?></span>
        <span class="badge bg-danger"><?php echo $sahraReviewCount; ?></span>
      </a>
    </li>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($isAdmin && $hasResearch && ($pendingResearcherCount > 0 || $pendingBookingCount > 0)): ?>
    <!-- Admin Notifications -->
    <li><hr class="dropdown-divider"></li>
    <li><h6 class="dropdown-header"><i class="fas fa-bell me-1"></i><?php echo __('Notifications'); ?></h6></li>
    <?php if ($pendingResearcherCount > 0): ?>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for('@research_researchers'); ?>">
        <span><i class="fas fa-user-clock me-2"></i><?php echo __('Pending Researchers'); ?></span>
        <span class="badge bg-warning text-dark"><?php echo $pendingResearcherCount; ?></span>
      </a>
    </li>
    <?php endif; ?>
    <?php if ($pendingBookingCount > 0): ?>
    <li>
      <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>">
        <span><i class="fas fa-calendar-check me-2"></i><?php echo __('Pending Bookings'); ?></span>
        <span class="badge bg-danger"><?php echo $pendingBookingCount; ?></span>
      </a>
    </li>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Logout -->
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item text-danger" href="<?php echo url_for(['module' => 'user', 'action' => 'logout']); ?>">
        <i class="fas fa-sign-out-alt me-2"></i><?php echo $menuLabels['logout'] ?? __('Logout'); ?>
      </a>
    </li>

  </ul>
</div>
<?php endif; ?>
