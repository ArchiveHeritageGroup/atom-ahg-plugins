<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-bell text-primary me-2"></i><?php echo __('Notifications'); ?></h1>
  <form method="post" class="d-inline">
    <input type="hidden" name="form_action" value="mark_all_read">
    <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fas fa-check-double me-1"></i><?php echo __('Mark All Read'); ?></button>
  </form>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$notifications = isset($notifications) && is_array($notifications) ? $notifications : (isset($notifications) && method_exists($notifications, 'getRawValue') ? $notifications->getRawValue() : (isset($notifications) && is_iterable($notifications) ? iterator_to_array($notifications) : []));
$preferences = isset($preferences) && is_array($preferences) ? $preferences : (isset($preferences) && method_exists($preferences, 'getRawValue') ? $preferences->getRawValue() : (isset($preferences) && is_iterable($preferences) ? iterator_to_array($preferences) : []));
$currentTab = $sf_request->getParameter('tab', 'all');
$currentType = $sf_request->getParameter('type', '');
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Notifications'); ?></li>
  </ol>
</nav>

<!-- Main Tabs -->
<ul class="nav nav-tabs mb-4" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?php echo ($currentTab === 'all' && empty($currentType)) ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'research', 'action' => 'notifications', 'tab' => 'all']); ?>"><?php echo __('All'); ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo ($currentTab === 'unread') ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'research', 'action' => 'notifications', 'tab' => 'unread']); ?>">
      <?php echo __('Unread'); ?>
      <?php if (!empty($unreadCount)): ?>
        <span class="badge bg-danger ms-1"><?php echo $unreadCount; ?></span>
      <?php endif; ?>
    </a>
  </li>
  <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle <?php echo !empty($currentType) ? 'active' : ''; ?>" data-bs-toggle="dropdown" href="#" role="button"><?php echo __('By Type'); ?></a>
    <ul class="dropdown-menu">
      <?php foreach (['alert' => 'Alerts', 'invitation' => 'Invitations', 'comment' => 'Comments', 'reply' => 'Replies', 'system' => 'System', 'reminder' => 'Reminders', 'collaboration' => 'Collaboration'] as $typeKey => $typeLabel): ?>
        <li><a class="dropdown-item <?php echo ($currentType === $typeKey) ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'research', 'action' => 'notifications', 'tab' => 'all', 'type' => $typeKey]); ?>"><i class="<?php echo match($typeKey) {
          'alert' => 'fas fa-exclamation-triangle text-warning',
          'invitation' => 'fas fa-user-plus text-primary',
          'comment' => 'fas fa-comment text-info',
          'reply' => 'fas fa-reply text-secondary',
          'system' => 'fas fa-cog text-dark',
          'reminder' => 'fas fa-clock text-success',
          'collaboration' => 'fas fa-users text-purple',
          default => 'fas fa-bell'
        }; ?> me-2"></i><?php echo __($typeLabel); ?></a></li>
      <?php endforeach; ?>
    </ul>
  </li>
  <li class="nav-item ms-auto">
    <a class="nav-link <?php echo ($currentTab === 'preferences') ? 'active' : ''; ?>" href="<?php echo url_for(['module' => 'research', 'action' => 'notifications', 'tab' => 'preferences']); ?>"><i class="fas fa-cog me-1"></i><?php echo __('Preferences'); ?></a>
  </li>
</ul>

<?php if ($currentTab === 'preferences'): ?>
  <!-- Preferences Tab -->
  <div class="card">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i><?php echo __('Notification Preferences'); ?></h5></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="form_action" value="update_preferences">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Notification Type'); ?></th>
                <th class="text-center"><?php echo __('In-App'); ?></th>
                <th class="text-center"><?php echo __('Email'); ?></th>
                <th><?php echo __('Digest Frequency'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php
              $typeLabels = [
                'alert' => 'Alerts',
                'invitation' => 'Invitations',
                'comment' => 'Comments',
                'reply' => 'Replies',
                'system' => 'System',
                'reminder' => 'Reminders',
                'collaboration' => 'Collaboration',
              ];
              foreach ($typeLabels as $typeKey => $typeLabel):
                $pref = isset($preferences[$typeKey]) ? $preferences[$typeKey] : (object) ['email_enabled' => 1, 'in_app_enabled' => 1, 'digest_frequency' => 'immediate'];
                if (is_array($pref)) $pref = (object) $pref;
              ?>
              <tr>
                <td>
                  <i class="<?php echo match($typeKey) {
                    'alert' => 'fas fa-exclamation-triangle text-warning',
                    'invitation' => 'fas fa-user-plus text-primary',
                    'comment' => 'fas fa-comment text-info',
                    'reply' => 'fas fa-reply text-secondary',
                    'system' => 'fas fa-cog text-dark',
                    'reminder' => 'fas fa-clock text-success',
                    'collaboration' => 'fas fa-users text-purple',
                    default => 'fas fa-bell'
                  }; ?> me-2"></i><?php echo __($typeLabel); ?>
                </td>
                <td class="text-center">
                  <div class="form-check d-flex justify-content-center">
                    <input type="checkbox" name="prefs[<?php echo $typeKey; ?>][in_app_enabled]" value="1" class="form-check-input" <?php echo ($pref->in_app_enabled ?? 1) ? 'checked' : ''; ?>>
                  </div>
                </td>
                <td class="text-center">
                  <div class="form-check d-flex justify-content-center">
                    <input type="checkbox" name="prefs[<?php echo $typeKey; ?>][email_enabled]" value="1" class="form-check-input" <?php echo ($pref->email_enabled ?? 1) ? 'checked' : ''; ?>>
                  </div>
                </td>
                <td>
                  <select name="prefs[<?php echo $typeKey; ?>][digest_frequency]" class="form-select form-select-sm" style="width: 150px;">
                    <?php foreach (['immediate' => 'Immediate', 'hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'never' => 'Never'] as $fKey => $fLabel): ?>
                      <option value="<?php echo $fKey; ?>" <?php echo (($pref->digest_frequency ?? 'immediate') === $fKey) ? 'selected' : ''; ?>><?php echo __($fLabel); ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save Preferences'); ?></button>
      </form>
    </div>
  </div>

<?php else: ?>
  <!-- Notification List -->
  <?php if (!empty($notifications)): ?>
    <div class="list-group">
      <?php foreach ($notifications as $notification): ?>
      <div class="list-group-item list-group-item-action d-flex align-items-start gap-3 <?php echo empty($notification->is_read) ? 'bg-light border-start border-primary border-3' : ''; ?>">
        <div class="flex-shrink-0 mt-1">
          <i class="<?php echo match($notification->type ?? 'system') {
            'alert' => 'fas fa-exclamation-triangle text-warning fa-lg',
            'invitation' => 'fas fa-user-plus text-primary fa-lg',
            'comment' => 'fas fa-comment text-info fa-lg',
            'reply' => 'fas fa-reply text-secondary fa-lg',
            'system' => 'fas fa-cog text-dark fa-lg',
            'reminder' => 'fas fa-clock text-success fa-lg',
            'collaboration' => 'fas fa-users text-purple fa-lg',
            default => 'fas fa-bell text-muted fa-lg'
          }; ?>"></i>
        </div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="mb-0 <?php echo empty($notification->is_read) ? 'fw-bold' : 'fw-normal'; ?>">
                <?php if (!empty($notification->link)): ?>
                  <a href="<?php echo htmlspecialchars($notification->link); ?>" class="text-decoration-none"><?php echo htmlspecialchars($notification->title); ?></a>
                <?php else: ?>
                  <?php echo htmlspecialchars($notification->title); ?>
                <?php endif; ?>
              </h6>
              <?php if (!empty($notification->message)): ?>
                <p class="text-muted small mb-0 mt-1"><?php echo htmlspecialchars(mb_substr($notification->message, 0, 150)); ?><?php echo mb_strlen($notification->message ?? '') > 150 ? '...' : ''; ?></p>
              <?php endif; ?>
            </div>
            <div class="text-end ms-3 flex-shrink-0">
              <small class="text-muted"><?php echo date('M j, H:i', strtotime($notification->created_at)); ?></small>
              <?php if (empty($notification->is_read)): ?>
                <form method="post" class="d-inline ms-1">
                  <input type="hidden" name="form_action" value="mark_read">
                  <input type="hidden" name="notification_id" value="<?php echo $notification->id; ?>">
                  <button type="submit" class="btn btn-link btn-sm p-0" title="<?php echo __('Mark as read'); ?>"><i class="fas fa-check text-muted"></i></button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-5">
      <i class="fas fa-bell-slash fa-4x text-muted mb-3 opacity-50"></i>
      <h4 class="text-muted"><?php echo __('No notifications'); ?></h4>
      <p class="text-muted"><?php echo ($currentTab === 'unread') ? __('You have no unread notifications.') : __('You have no notifications yet.'); ?></p>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php end_slot() ?>
