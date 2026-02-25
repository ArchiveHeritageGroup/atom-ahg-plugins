<?php
  $topicIcons = [
    'discussion' => 'fas fa-comments text-primary',
    'question' => 'fas fa-question-circle text-success',
    'announcement' => 'fas fa-bullhorn text-warning',
    'event' => 'fas fa-calendar-alt text-info',
    'showcase' => 'fas fa-star text-purple',
    'help' => 'fas fa-life-ring text-danger',
  ];
  // Support both $item and $disc variable names
  $disc = isset($item) ? $item : (isset($disc) ? $disc : null);
  if (!$disc) { return; }

  $isPinned = !empty($disc->is_pinned);
  $isLocked = !empty($disc->is_locked);
  $isResolved = !empty($disc->is_resolved);
  $tt = $disc->topic_type ?? 'discussion';
  $tIcon = $topicIcons[$tt] ?? 'fas fa-comments text-muted';

  $gSlug = $disc->group_slug ?? ($groupSlug ?? '');
  $discUrl = $gSlug
    ? url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $gSlug, 'id' => (int) $disc->id])
    : '#';
?>
<a href="<?php echo $discUrl; ?>" class="list-group-item list-group-item-action<?php echo $isPinned ? ' list-group-item-warning' : ''; ?>">
  <div class="d-flex align-items-start">
    <div class="me-3 text-center flex-shrink-0" style="min-width: 30px;">
      <i class="<?php echo $tIcon; ?>" title="<?php echo htmlspecialchars(ucfirst($tt), ENT_QUOTES, 'UTF-8'); ?>"></i>
    </div>
    <div class="flex-grow-1 min-width-0">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h6 class="mb-1">
            <?php if ($isPinned): ?><i class="fas fa-thumbtack text-warning me-1 small"></i><?php endif; ?>
            <?php if ($isLocked): ?><i class="fas fa-lock text-secondary me-1 small"></i><?php endif; ?>
            <?php echo htmlspecialchars($disc->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($isResolved): ?>
              <span class="badge bg-success ms-1"><?php echo __('Resolved'); ?></span>
            <?php endif; ?>
          </h6>
          <small class="text-muted">
            <?php echo htmlspecialchars($disc->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            &middot; <?php echo !empty($disc->created_at) ? date('M j, Y', strtotime($disc->created_at)) : ''; ?>
            <?php if (!empty($disc->last_reply_at) && ($disc->last_reply_at ?? '') !== ($disc->created_at ?? '')): ?>
              &middot; <?php echo __('Last reply'); ?> <?php echo date('M j', strtotime($disc->last_reply_at)); ?>
            <?php endif; ?>
          </small>
        </div>
        <div class="text-end text-nowrap ms-2 flex-shrink-0">
          <span class="badge bg-primary" title="<?php echo __('Replies'); ?>"><?php echo (int) ($disc->reply_count ?? 0); ?></span>
          <br>
          <small class="text-muted"><i class="fas fa-eye"></i> <?php echo (int) ($disc->view_count ?? 0); ?></small>
          <?php
            $actTime = strtotime($disc->last_activity_at ?? $disc->last_reply_at ?? $disc->created_at ?? 'now');
            $diff = time() - $actTime;
            if ($diff < 60) {
              $ago = __('just now');
            } elseif ($diff < 3600) {
              $ago = sprintf(__('%d min ago'), (int) floor($diff / 60));
            } elseif ($diff < 86400) {
              $ago = sprintf(__('%d hours ago'), (int) floor($diff / 3600));
            } else {
              $ago = sprintf(__('%d days ago'), (int) floor($diff / 86400));
            }
          ?>
          <br><small class="text-muted"><?php echo $ago; ?></small>
        </div>
      </div>
    </div>
  </div>
</a>
