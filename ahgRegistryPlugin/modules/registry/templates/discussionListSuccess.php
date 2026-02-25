<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $groupDetail = $group['group']; ?>

<?php slot('title'); ?><?php echo __('Discussions'); ?> - <?php echo htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'groupBrowse'])],
  ['label' => htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $groupDetail->slug])],
  ['label' => __('Discussions')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo __('Discussions'); ?></h1>
    <p class="text-muted mb-0"><?php echo htmlspecialchars($groupDetail->name, ENT_QUOTES, 'UTF-8'); ?></p>
  </div>
  <?php if (!empty($currentUserEmail)): ?>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionNew', 'slug' => $groupDetail->slug]); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> <?php echo __('New Discussion'); ?>
  </a>
  <?php endif; ?>
</div>

<!-- Topic type filter -->
<div class="mb-3">
  <div class="btn-group btn-group-sm flex-wrap" role="group">
    <?php
      $topicTypes = ['' => __('All'), 'discussion' => __('Discussion'), 'question' => __('Question'), 'announcement' => __('Announcement'), 'event' => __('Event'), 'showcase' => __('Showcase'), 'help' => __('Help')];
      $currentType = $sf_request->getParameter('topic_type', '');
    ?>
    <?php foreach ($topicTypes as $val => $label): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $groupDetail->slug, 'topic_type' => $val]); ?>" class="btn btn-outline-secondary<?php echo $currentType === $val ? ' active' : ''; ?>">
        <?php echo $label; ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!empty($result['items'])): ?>
<div class="list-group">
  <?php foreach ($result['items'] as $disc): ?>
  <?php include_partial('registry/discussionRow', ['disc' => $disc, 'groupSlug' => $groupDetail->slug]); ?>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php $page = (int) ($result['page'] ?? 1); $total = (int) ($result['total'] ?? 0); $limit = 20; ?>
<?php if ($total > $limit): ?>
  <?php $totalPages = (int) ceil($total / $limit); ?>
  <nav aria-label="<?php echo __('Page navigation'); ?>" class="mt-4">
    <ul class="pagination justify-content-center">
      <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $groupDetail->slug, 'page' => $page - 1, 'topic_type' => $currentType]); ?>">&laquo;</a>
      </li>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $groupDetail->slug, 'page' => $i, 'topic_type' => $currentType]); ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
        <a class="page-link" href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $groupDetail->slug, 'page' => $page + 1, 'topic_type' => $currentType]); ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-comments fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No discussions yet'); ?></h5>
  <p class="text-muted"><?php echo __('Be the first to start a discussion in this group.'); ?></p>
  <?php if (!empty($currentUserEmail)): ?>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionNew', 'slug' => $groupDetail->slug]); ?>" class="btn btn-primary">
    <?php echo __('Start a Discussion'); ?>
  </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php end_slot(); ?>
