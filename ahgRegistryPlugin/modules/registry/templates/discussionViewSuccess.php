<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $groupDetail = $group['group']; ?>
<?php $disc = $discussion['discussion']; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($disc->title, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'groupBrowse'])],
  ['label' => htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $groupDetail->slug])],
  ['label' => __('Discussions'), 'url' => url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $groupDetail->slug])],
  ['label' => htmlspecialchars(mb_substr($disc->title, 0, 50), ENT_QUOTES, 'UTF-8') . (mb_strlen($disc->title) > 50 ? '...' : '')],
]]); ?>

<!-- Original post -->
<div class="card mb-4">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1 class="h4 mb-1"><?php echo htmlspecialchars($disc->title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div>
          <?php if (!empty($disc->topic_type)): ?>
          <?php
            $topicColors = ['discussion' => 'primary', 'question' => 'success', 'announcement' => 'warning', 'event' => 'info', 'showcase' => 'purple', 'help' => 'danger'];
            $tColor = $topicColors[$disc->topic_type] ?? 'secondary';
          ?>
          <span class="badge bg-<?php echo $tColor; ?>"><?php echo htmlspecialchars(ucfirst($disc->topic_type), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <?php if (!empty($disc->is_pinned)): ?>
            <span class="badge bg-warning text-dark"><i class="fas fa-thumbtack"></i> <?php echo __('Pinned'); ?></span>
          <?php endif; ?>
          <?php if (!empty($disc->is_locked)): ?>
            <span class="badge bg-secondary"><i class="fas fa-lock"></i> <?php echo __('Locked'); ?></span>
          <?php endif; ?>
          <?php if (!empty($disc->is_resolved)): ?>
            <span class="badge bg-success"><i class="fas fa-check"></i> <?php echo __('Resolved'); ?></span>
          <?php endif; ?>
        </div>
      </div>
      <small class="text-muted text-nowrap ms-2">
        <i class="fas fa-eye"></i> <?php echo (int) ($disc->view_count ?? 0); ?>
      </small>
    </div>
  </div>
  <div class="card-body">
    <div class="d-flex mb-3">
      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; min-width: 48px;">
        <i class="fas fa-user text-muted"></i>
      </div>
      <div>
        <strong><?php echo htmlspecialchars($disc->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
        <br><small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($disc->created_at)); ?></small>
      </div>
    </div>
    <div class="mb-3"><?php
      $rawContent = sfOutputEscaper::unescape($disc->content);
      $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr><img><table><thead><tbody><tr><th><td><span><div><dl><dt><dd><sub><sup>';
      if (preg_match('/<[a-z][\s\S]*>/i', $rawContent)) {
        echo strip_tags($rawContent, $allowedTags);
      } else {
        echo nl2br(htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8'));
      }
    ?></div>

    <!-- Tags -->
    <?php if (!empty($disc->tags)): ?>
    <div class="mb-2">
      <?php
        $rawTags = sfOutputEscaper::unescape($disc->tags);
        $tags = is_string($rawTags) ? json_decode($rawTags, true) : (array) $rawTags;
        if (is_array($tags)):
          foreach ($tags as $tag): ?>
            <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>

    <!-- Attachments -->
    <?php if (!empty($discussion['attachments'])): ?>
    <div class="mt-3">
      <strong class="small"><?php echo __('Attachments'); ?>:</strong>
      <div class="mt-1">
        <?php foreach ($discussion['attachments'] as $att): ?>
        <a href="<?php echo htmlspecialchars($att->file_path, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary me-1 mb-1" target="_blank" rel="noopener">
          <i class="fas fa-paperclip me-1"></i> <?php echo htmlspecialchars($att->file_name, ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($att->file_size_bytes)): ?>
            <small class="text-muted">(<?php echo number_format($att->file_size_bytes / 1024, 0); ?> KB)</small>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Replies -->
<?php if (!empty($discussion['replies'])): ?>
<h2 class="h5 mb-3"><?php echo __('%1% Replies', ['%1%' => (int) $disc->reply_count]); ?></h2>
<?php include_partial('registry/replyThread', ['replies' => $discussion['replies'], 'groupSlug' => $groupDetail->slug, 'discussionId' => (int) $disc->id, 'depth' => 0]); ?>
<?php endif; ?>

<!-- Reply form -->
<?php if ($isMember && empty($disc->is_locked)): ?>
<div class="card mt-4" id="reply-form">
  <div class="card-header fw-semibold"><?php echo __('Post a Reply'); ?></div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'discussionReply', 'slug' => $groupDetail->slug, 'id' => (int) $disc->id]); ?>">
      <div class="mb-3">
        <textarea class="form-control" name="content" rows="4" required placeholder="<?php echo __('Write your reply...'); ?>"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-reply me-1"></i> <?php echo __('Post Reply'); ?>
      </button>
    </form>
  </div>
</div>
<?php elseif (!empty($disc->is_locked)): ?>
<div class="alert alert-secondary mt-4">
  <i class="fas fa-lock me-1"></i> <?php echo __('This discussion is locked. No new replies can be posted.'); ?>
</div>
<?php elseif (!$isMember && $sf_user->isAuthenticated()): ?>
<div class="alert alert-info mt-4">
  <i class="fas fa-info-circle me-1"></i>
  <?php echo __('Join this group to participate in discussions.'); ?>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupJoin', 'slug' => $groupDetail->slug]); ?>" class="btn btn-sm btn-primary ms-2"><?php echo __('Join Group'); ?></a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
