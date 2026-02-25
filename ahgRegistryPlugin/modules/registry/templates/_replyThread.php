<?php
  // Variables: $reply (single reply) OR $replies (collection), $level (default 0), $discussionId, $groupSlug
  // Can be called recursively for nested replies
  $maxDepth = 4;
  $currentLevel = isset($level) ? (int) $level : (isset($depth) ? (int) $depth : 0);

  // Support both single reply and collection modes
  $replyList = [];
  if (isset($replies) && is_iterable($replies)) {
    $replyList = $replies;
  } elseif (isset($reply)) {
    $replyList = [$reply];
  }
?>
<?php if (!empty($replyList)): ?>
<?php foreach ($replyList as $r): ?>
<div class="card mb-2<?php echo $currentLevel > 0 ? ' ms-' . min($currentLevel * 3, 12) : ''; ?>" id="reply-<?php echo (int) ($r->id ?? 0); ?>">
  <div class="card-body py-2">
    <div class="d-flex align-items-start">
      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 32px; height: 32px;">
        <i class="fas fa-user text-muted small"></i>
      </div>
      <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <strong class="small"><?php echo htmlspecialchars($r->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <small class="text-muted ms-2">
              <?php
                if (!empty($r->created_at)):
                  $rTime = strtotime($r->created_at);
                  $rDiff = time() - $rTime;
                  if ($rDiff < 60) {
                    $rAgo = __('just now');
                  } elseif ($rDiff < 3600) {
                    $rAgo = sprintf(__('%d min ago'), (int) floor($rDiff / 60));
                  } elseif ($rDiff < 86400) {
                    $rAgo = sprintf(__('%d hours ago'), (int) floor($rDiff / 3600));
                  } elseif ($rDiff < 604800) {
                    $rAgo = sprintf(__('%d days ago'), (int) floor($rDiff / 86400));
                  } else {
                    $rAgo = date('M j, Y g:i A', $rTime);
                  }
                  echo $rAgo;
                endif;
              ?>
            </small>
          </div>
          <?php if (!empty($r->is_accepted_answer)): ?>
            <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Accepted Answer'); ?></span>
          <?php endif; ?>
        </div>

        <!-- Reply content -->
        <div class="small mt-1">
          <?php
            $rawReplyContent = sfOutputEscaper::unescape($r->content ?? '');
            $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr>';
            if (preg_match('/<[a-z][\s\S]*>/i', $rawReplyContent)) {
              echo strip_tags($rawReplyContent, $allowedTags);
            } else {
              echo nl2br(htmlspecialchars($rawReplyContent, ENT_QUOTES, 'UTF-8'));
            }
          ?>
        </div>

        <!-- Reply button -->
        <?php if ($currentLevel < $maxDepth && !empty($discussionId)): ?>
        <div class="mt-2">
          <button type="button" class="btn btn-sm btn-outline-secondary reply-toggle-btn" data-reply-id="<?php echo (int) ($r->id ?? 0); ?>">
            <i class="fas fa-reply me-1"></i><?php echo __('Reply'); ?>
          </button>
          <div class="reply-form mt-2" id="reply-form-<?php echo (int) ($r->id ?? 0); ?>" style="display: none;">
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'discussionReply', 'slug' => $groupSlug ?? '', 'id' => (int) ($discussionId ?? 0)]); ?>">
              <input type="hidden" name="parent_reply_id" value="<?php echo (int) ($r->id ?? 0); ?>">
              <textarea class="form-control form-control-sm mb-2" name="content" rows="2" placeholder="<?php echo __('Write a reply...'); ?>" required></textarea>
              <button type="submit" class="btn btn-sm btn-primary"><?php echo __('Submit'); ?></button>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php if (!empty($r->children) && $currentLevel < $maxDepth): ?>
  <?php include_partial('registry/replyThread', [
    'replies' => $r->children,
    'groupSlug' => $groupSlug ?? '',
    'discussionId' => $discussionId ?? 0,
    'level' => $currentLevel + 1,
    'depth' => $currentLevel + 1,
  ]); ?>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
