<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-shopping-cart me-2"></i><?php echo __('My Cart'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <span>
        <i class="fas fa-shopping-cart me-2"></i>
        <?php echo __('Cart Items'); ?>
        <span class="badge bg-light text-success ms-2"><?php echo $count; ?></span>
      </span>
      <?php if ($count > 0): ?>
        <form action="<?php echo url_for(['module' => 'ahgCart', 'action' => 'clear']); ?>" method="post" 
              onsubmit="return confirm('<?php echo __('Are you sure you want to clear your cart?'); ?>');">
          <button type="submit" class="btn btn-sm btn-outline-light">
            <i class="fas fa-trash-alt me-1"></i><?php echo __('Clear Cart'); ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if (empty($items)): ?>
        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('Your cart is empty. Browse the archive and click "Add to Cart" on items with digital objects.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th class="text-center" style="width: 120px;"><?php echo __('Has Image'); ?></th>
                <th class="text-center" style="width: 150px;"><?php echo __('Date Added'); ?></th>
                <th class="text-center" style="width: 100px;"><?php echo __('Actions'); ?></th>
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
                  <td class="text-center">
                    <?php if ($item->has_digital_object): ?>
                      <span class="badge bg-success"><i class="fas fa-check"></i> Yes</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center text-muted small">
                    <?php echo date('Y-m-d', strtotime($item->created_at)); ?>
                  </td>
                  <td class="text-center">
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" 
                       class="btn btn-sm btn-outline-primary me-1" title="<?php echo __('View'); ?>">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'remove', 'id' => $item->id]); ?>" 
                       class="btn btn-sm btn-outline-danger" title="<?php echo __('Remove'); ?>"
                       onclick="return confirm('<?php echo __('Remove from cart?'); ?>');">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <div class="mt-4 text-end">
          <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'checkout']); ?>" class="btn btn-success">
            <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Request'); ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php end_slot(); ?>
