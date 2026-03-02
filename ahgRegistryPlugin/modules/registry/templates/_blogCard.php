<?php
  $authorTypeBg = [
    'admin' => 'bg-danger',
    'vendor' => 'bg-primary',
    'institution' => 'bg-success',
    'user_group' => 'bg-purple',
  ];
  $at = $item->author_type ?? '';
  $atClass = $authorTypeBg[$at] ?? 'bg-secondary';
  $atStyle = ('user_group' === $at) ? 'background-color:#6f42c1!important;' : '';
?>
<div class="col">
  <div class="card h-100">
    <?php if (!empty($item->featured_image_path)): ?>
      <img src="<?php echo htmlspecialchars($item->featured_image_path, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="" style="height: 160px; object-fit: cover;">
    <?php else: ?>
      <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 100px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <i class="fas fa-newspaper fa-2x text-white opacity-50"></i>
      </div>
    <?php endif; ?>
    <div class="card-body">
      <div class="mb-2">
        <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item->category ?? 'news')), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <h6 class="card-title">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'blogView', 'slug' => $item->slug ?? '']); ?>" class="text-decoration-none stretched-link">
          <?php echo htmlspecialchars($item->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </a>
      </h6>
      <?php
        $excerpt = $item->excerpt ?? '';
        if (!empty($excerpt)):
      ?>
      <p class="card-text small text-muted">
        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($excerpt), 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent border-0 pt-0">
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">
          <?php echo htmlspecialchars($item->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($at)): ?>
            <span class="badge <?php echo $atClass; ?>" style="<?php echo $atStyle; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $at)), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </small>
        <small class="text-muted">
          <?php echo date('M j, Y', strtotime($item->published_at ?? $item->created_at ?? 'now')); ?>
        </small>
      </div>
      <div class="d-flex gap-2">
        <?php if (!empty($item->view_count)): ?>
        <small class="text-muted">
          <i class="fas fa-eye me-1"></i><?php echo number_format((int) $item->view_count); ?>
        </small>
        <?php endif; ?>
        <?php if (isset($item->comment_count) && (int) $item->comment_count > 0): ?>
        <small class="text-muted">
          <i class="fas fa-comments me-1"></i><?php echo (int) $item->comment_count; ?>
        </small>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
