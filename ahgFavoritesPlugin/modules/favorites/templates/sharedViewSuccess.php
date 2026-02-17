<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-share-alt me-2"></i><?php echo __('Shared Favorites'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span>
        <i class="fas fa-folder-open me-2"></i>
        <?php echo esc_entities($folder->name); ?>
        <span class="badge bg-light text-primary ms-2"><?php echo count($items); ?></span>
      </span>
      <?php if ($isAuthenticated): ?>
        <form method="post" action="/favorites/shared/<?php echo esc_entities($token); ?>" class="d-inline">
          <input type="hidden" name="copy_to_favorites" value="1">
          <button type="submit" class="btn btn-sm btn-outline-light"
                  onclick="return confirm('<?php echo __('Copy all items to your favorites?'); ?>');">
            <i class="fas fa-heart me-1"></i><?php echo __('Copy to My Favorites'); ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($folder->description): ?>
      <div class="card-body border-bottom py-2">
        <p class="text-muted mb-0"><?php echo esc_entities($folder->description); ?></p>
      </div>
    <?php endif; ?>

    <div class="card-body border-bottom py-2">
      <small class="text-muted">
        <?php if ($ownerName): ?>
          <?php echo __('Shared by %1%', ['%1%' => esc_entities($ownerName)]); ?>
        <?php else: ?>
          <?php echo __('Shared collection'); ?>
        <?php endif; ?>
        &mdash; <?php echo count($items); ?> <?php echo __('items'); ?>
      </small>
    </div>

    <div class="card-body">
      <?php if (empty($items)): ?>
        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('This shared folder has no items.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th style="width: 140px;"><?php echo __('Reference Code'); ?></th>
                <th class="text-center" style="width: 110px;"><?php echo __('Date Added'); ?></th>
                <th class="text-center" style="width: 80px;"><?php echo __('View'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" class="text-decoration-none">
                      <i class="fas fa-file-alt text-muted me-2"></i>
                      <?php echo esc_entities($item->title); ?>
                    </a>
                  </td>
                  <td class="small text-muted">
                    <?php echo esc_entities($item->reference_code ?? ''); ?>
                  </td>
                  <td class="text-center text-muted small">
                    <?php echo date('Y-m-d', strtotime($item->created_at)); ?>
                  </td>
                  <td class="text-center">
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>"
                       class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$isAuthenticated): ?>
    <div class="alert alert-secondary mt-3">
      <i class="fas fa-user-lock me-2"></i>
      <?php echo __('Log in to copy these items to your own favorites.'); ?>
      <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="alert-link"><?php echo __('Log in'); ?></a>
    </div>
  <?php endif; ?>
</div>

<?php end_slot(); ?>
