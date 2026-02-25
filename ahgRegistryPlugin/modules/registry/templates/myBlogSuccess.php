<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('My Blog Posts'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Blog Posts')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('My Blog Posts'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogNew']); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-pen me-1"></i> <?php echo __('Write New Post'); ?>
  </a>
</div>

<?php if (!empty($posts) && count($posts) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Title'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Category'); ?></th>
          <th><?php echo __('Created'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
        <tr>
          <td>
            <strong>
              <?php if ('published' === ($post->status ?? '')): ?>
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $post->slug]); ?>">
                  <?php echo htmlspecialchars($post->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </a>
              <?php else: ?>
                <?php echo htmlspecialchars($post->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
            </strong>
          </td>
          <td>
            <?php
              $status = $post->status ?? 'draft';
              $statusColors = [
                'draft' => 'secondary', 'pending_review' => 'warning',
                'published' => 'success', 'archived' => 'dark',
              ];
              $sColor = $statusColors[$status] ?? 'secondary';
            ?>
            <span class="badge bg-<?php echo $sColor; ?><?php echo 'warning' === $sColor ? ' text-dark' : ''; ?>">
              <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </td>
          <td>
            <?php if (!empty($post->category)): ?>
              <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst($post->category), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php echo !empty($post->created_at) ? date('M j, Y', strtotime($post->created_at)) : '-'; ?>
          </td>
          <td class="text-end">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogEdit', 'id' => $post->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-blog fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No blog posts yet'); ?></h5>
  <p class="text-muted"><?php echo __('Write your first blog post to share with the community.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogNew']); ?>" class="btn btn-primary">
    <i class="fas fa-pen me-1"></i> <?php echo __('Write New Post'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
