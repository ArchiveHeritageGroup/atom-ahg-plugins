<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Reply to Discussion'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  $groupDetail = isset($group['group']) ? $group['group'] : null;
  $disc = isset($discussion['discussion']) ? $discussion['discussion'] : $discussion;
?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Community'), 'url' => url_for(['module' => 'registry', 'action' => 'community'])],
  ['label' => $groupDetail ? htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8') : '', 'url' => $groupDetail ? url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $groupDetail->slug]) : ''],
  ['label' => __('Reply')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <h1 class="h3 mb-3"><?php echo __('Reply to Discussion'); ?></h1>

    <!-- Original discussion summary -->
    <?php if ($disc): ?>
    <div class="card mb-4">
      <div class="card-body">
        <h5><?php echo htmlspecialchars($disc->title ?? '', ENT_QUOTES, 'UTF-8'); ?></h5>
        <small class="text-muted">
          <?php echo htmlspecialchars($disc->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          &middot; <?php echo !empty($disc->created_at) ? date('M j, Y', strtotime($disc->created_at)) : ''; ?>
        </small>
        <?php if (!empty($disc->content)): ?>
          <div class="mt-2 text-muted"><?php echo nl2br(htmlspecialchars(mb_strimwidth($disc->content, 0, 300, '...'), ENT_QUOTES, 'UTF-8')); ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'discussionReply', 'slug' => $groupDetail ? $groupDetail->slug : '', 'id' => $disc ? (int) $disc->id : 0]); ?>">

      <?php $parentReplyId = $sf_request->getParameter('parent_reply_id', ''); ?>
      <?php if ($parentReplyId): ?>
        <input type="hidden" name="parent_reply_id" value="<?php echo (int) $parentReplyId; ?>">
        <div class="alert alert-info small mb-3">
          <i class="fas fa-reply me-1"></i> <?php echo __('Replying to a specific comment. This will be nested under that reply.'); ?>
        </div>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-body">
          <label for="dr-content" class="form-label"><?php echo __('Your Reply'); ?> <span class="text-danger">*</span></label>
          <textarea class="form-control" id="dr-content" name="content" rows="6" required placeholder="<?php echo __('Write your reply...'); ?>"><?php echo htmlspecialchars($sf_request->getParameter('content', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="form-text"><?php echo __('Plain text. Paragraphs separated by blank lines.'); ?></div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <?php if ($groupDetail && $disc): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $groupDetail->slug, 'id' => (int) $disc->id]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <?php else: ?>
          <a href="javascript:history.back();" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary"><i class="fas fa-reply me-1"></i> <?php echo __('Post Reply'); ?></button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>
