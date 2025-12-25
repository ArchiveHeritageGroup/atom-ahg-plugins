<?php if ($showLogin) { ?>
  <div class="dropdown my-2">
    <button class="btn btn-sm atom-btn-secondary dropdown-toggle" type="button" id="user-menu" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
      <?php echo $menuLabels['login']; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-lg-end mt-2" aria-labelledby="user-menu" style="min-width: 280px;">
      <div>
        <h6 class="dropdown-header">
          <?php echo __('Have an account?'); ?>
        </h6>
      </div>
      <?php echo $form->renderFormTag(url_for(['module' => 'user', 'action' => 'login']), ['class' => 'mx-3 my-2']); ?>
        <?php echo $form->renderHiddenFields(); ?>
        <?php echo render_field($form->email, null, ['class' => 'form-control-sm']); ?>
        <?php echo render_field($form->password, null, ['class' => 'form-control-sm', 'autocomplete' => 'off']); ?>
        <button class="btn btn-sm atom-btn-secondary" type="submit">
          <?php echo $menuLabels['login']; ?>
        </button>
      </form>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item small" href="<?php echo url_for(['module' => 'research', 'action' => 'passwordResetRequest']); ?>">
        <i class="fas fa-key me-2 text-muted"></i><?php echo __('Forgot password?'); ?>
      </a>
      <div class="dropdown-divider"></div>
      <div class="px-3 py-2">
        <small class="text-muted"><?php echo __('New researcher?'); ?></small>
        <a class="btn btn-sm btn-outline-primary w-100 mt-1" href="<?php echo url_for(['module' => 'research', 'action' => 'publicRegister']); ?>">
          <i class="fas fa-user-plus me-1"></i><?php echo __('Register'); ?>
        </a>
      </div>
    </div>
  </div>
<?php } elseif ($sf_user->isAuthenticated()) { ?>
  <?php
  // Check for pending access requests (for approvers)
  $pendingCount = 0;
  $pendingResearcherCount = 0;
  $isApprover = false;
  $isResearcher = false;
  $researcherStatus = null;
  $isAdmin = $sf_user->isAdministrator();
  try {
    require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
    require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/AccessRequestService.php';
    $userId = $sf_user->getAttribute('user_id');
    $isApprover = \AtomExtensions\Services\AccessRequestService::isApprover($userId);
    if ($isApprover || $isAdmin) {
      $pendingCount = \Illuminate\Database\Capsule\Manager::table('access_request')
        ->where('status', 'pending')
        ->count();
    }
    if ($isAdmin) {
      $pendingResearcherCount = \Illuminate\Database\Capsule\Manager::table('research_researcher')
        ->where('status', 'pending')
        ->count();
    }
    // Check researcher status
    $researcher = \Illuminate\Database\Capsule\Manager::table('research_researcher')
      ->where('user_id', $userId)
      ->first();
    if ($researcher) {
      $isResearcher = true;
      $researcherStatus = $researcher->status;
    }
  } catch (Exception $e) {
    // Silently fail
  }
  ?>
  <style>
  #user-menu + .dropdown-menu { font-size: 0.85rem; max-height: 80vh; overflow-y: auto; }
  #user-menu + .dropdown-menu .dropdown-item { padding: 0.3rem 1rem; }
  #user-menu + .dropdown-menu .dropdown-header { padding: 0.3rem 1rem; font-size: 0.75rem; }
  #user-menu + .dropdown-menu .dropdown-divider { margin: 0.3rem 0; }
  </style>
  <div class="dropdown my-2">
    <button class="btn btn-sm atom-btn-secondary dropdown-toggle" type="button" id="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
      <?php echo $sf_user->user->username; ?>
      <?php if ($pendingCount > 0 || $pendingResearcherCount > 0): ?>
        <span class="badge bg-danger ms-1"><?php echo $pendingCount + $pendingResearcherCount; ?></span>
      <?php endif; ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-lg-end mt-2" aria-labelledby="user-menu">
      <li>
        <h6 class="dropdown-header">
          <?php echo image_tag($gravatar, ['alt' => '']); ?>&nbsp;
          <?php echo __('Hi, %1%', ['%1%' => $sf_user->user->username]); ?>
        </h6>
      </li>
      <li><?php echo link_to($menuLabels['myProfile'], [$sf_user->user, 'module' => 'user'], ['class' => 'dropdown-item']); ?></li>

      <!-- Research Section -->
      <li><hr class="dropdown-divider"></li>
      <li><h6 class="dropdown-header"><i class="fas fa-book-reader me-1"></i><?php echo __('Research'); ?></h6></li>
      
      <?php if ($isResearcher && $researcherStatus === 'approved'): ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>">
            <i class="fas fa-briefcase me-2"></i><?php echo __('My Workspace'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'profile']); ?>">
            <i class="fas fa-user-graduate me-2"></i><?php echo __('My Profile'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'book']); ?>">
            <i class="fas fa-calendar-plus me-2"></i><?php echo __('Book Reading Room'); ?>
          </a>
        </li>
      <?php elseif ($isResearcher && $researcherStatus === 'pending'): ?>
        <li>
          <a class="dropdown-item text-warning" href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">
            <i class="fas fa-clock me-2"></i><?php echo __('Registration Pending'); ?>
          </a>
        </li>
      <?php else: ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'register']); ?>">
            <i class="fas fa-user-plus me-2"></i><?php echo __('Register as Researcher'); ?>
          </a>
        </li>
      <?php endif; ?>
      
      <?php if ($isAdmin || $isApprover): ?>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header"><i class="fas fa-user-cog me-1"></i><?php echo __('Research Admin'); ?></h6></li>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">
            <i class="fas fa-tachometer-alt me-2"></i><?php echo __('Dashboard'); ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'researchers']); ?>">
            <i class="fas fa-users me-2"></i><?php echo __('Manage Researchers'); ?>
            <?php if ($pendingResearcherCount > 0): ?>
              <span class="badge bg-warning ms-1"><?php echo $pendingResearcherCount; ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'bookings']); ?>">
            <i class="fas fa-calendar-alt me-2"></i><?php echo __('Manage Bookings'); ?>
          </a>
        </li>
        <?php if ($isAdmin): ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for(['module' => 'research', 'action' => 'rooms']); ?>">
            <i class="fas fa-door-open me-2"></i><?php echo __('Reading Rooms'); ?>
          </a>
        </li>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Security Section -->
      <li><hr class="dropdown-divider"></li>
      <li><h6 class="dropdown-header"><i class="fas fa-shield-alt me-1"></i><?php echo __('Security'); ?></h6></li>
      <li>
        <a class="dropdown-item" href="<?php echo url_for('@access_request_my'); ?>">
          <i class="fas fa-key me-2"></i><?php echo __('My Access Requests'); ?>
        </a>
      </li>
      <?php if ($isApprover || $isAdmin): ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for('@access_request_pending'); ?>">
            <i class="fas fa-clipboard-check me-2"></i><?php echo __('Review Requests'); ?>
            <?php if ($pendingCount > 0): ?>
              <span class="badge bg-danger ms-1"><?php echo $pendingCount; ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
        <li>
          <a class="dropdown-item" href="<?php echo url_for('@access_request_approvers'); ?>">
            <i class="fas fa-users-cog me-2"></i><?php echo __('Manage Approvers'); ?>
          </a>
        </li>
      <?php endif; ?>

      <li><hr class="dropdown-divider"></li>
      <li><?php echo link_to($menuLabels['logout'], ['module' => 'user', 'action' => 'logout'], ['class' => 'dropdown-item']); ?></li>
    </ul>
  </div>
<?php } ?>
