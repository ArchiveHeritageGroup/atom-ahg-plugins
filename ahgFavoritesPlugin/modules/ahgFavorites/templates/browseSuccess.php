<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-heart me-2"></i><?php echo __('My Favorites'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span>
        <i class="fas fa-heart me-2"></i>
        <?php echo __('Favorites'); ?>
        <span class="badge bg-light text-primary ms-2"><?php echo $count; ?></span>
      </span>
      <?php if ($count > 0): ?>
        <form action="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'clear']); ?>" method="post" 
              onsubmit="return confirm('<?php echo __('Are you sure you want to clear all favorites?'); ?>');">
          <button type="submit" class="btn btn-sm btn-outline-light">
            <i class="fas fa-trash-alt me-1"></i><?php echo __('Clear All'); ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if (empty($favorites)): ?>
        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('You have no favorites yet. Browse the archive and click the heart icon to add items.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th class="text-center" style="width: 150px;"><?php echo __('Date Added'); ?></th>
                <th class="text-center" style="width: 100px;"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($favorites as $favorite): ?>
                <tr>
                  <td>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>" class="text-decoration-none">
                      <i class="fas fa-file-alt text-muted me-2"></i>
                      <?php echo esc_entities($favorite->title); ?>
                    </a>
                  </td>
                  <td class="text-center text-muted small">
                    <?php echo date('Y-m-d', strtotime($favorite->created_at)); ?>
                  </td>
                  <td class="text-center">
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $favorite->slug]); ?>" 
                       class="btn btn-sm btn-outline-primary me-1" title="<?php echo __('View'); ?>">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'remove', 'id' => $favorite->id]); ?>" 
                       class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>"
                       onclick="return confirm('<?php echo __('Remove from favorites?'); ?>');">
                      <i class="fas fa-heart-broken"></i>
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
</div>

<?php end_slot(); ?>
