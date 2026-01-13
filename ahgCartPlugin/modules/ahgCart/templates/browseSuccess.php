<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-shopping-cart me-2"></i><?php echo __('My Cart'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  
  <?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <span>
        <i class="fas fa-shopping-cart me-2"></i>
        <?php echo __('Cart Items'); ?>
        <span class="badge bg-light text-success ms-2"><?php echo $count; ?></span>
        <?php if ($ecommerceEnabled): ?>
          <span class="badge bg-warning text-dark ms-2"><i class="fas fa-store me-1"></i><?php echo __('E-Commerce'); ?></span>
        <?php endif; ?>
      </span>
      <?php if ($count > 0): ?>
        <form action="<?php echo url_for(['module' => 'ahgCart', 'action' => 'clear']); ?>" method="post"
              onsubmit="return confirm('<?php echo __('Are you sure you want to clear your cart?'); ?>');" class="d-inline">
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
        
        <?php if ($ecommerceEnabled): ?>
          <!-- E-COMMERCE MODE -->
          <form id="cartForm" action="<?php echo url_for(['module' => 'ahgCart', 'action' => 'updateProducts']); ?>" method="post">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 40%;"><?php echo __('Item'); ?></th>
                    <th style="width: 25%;"><?php echo __('Product Type'); ?></th>
                    <th class="text-center" style="width: 10%;"><?php echo __('Qty'); ?></th>
                    <th class="text-end" style="width: 12%;"><?php echo __('Price'); ?></th>
                    <th class="text-end" style="width: 13%;"><?php echo __('Actions'); ?></th>
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
                        <?php if ($item->has_digital_object): ?>
                          <span class="badge bg-success ms-2"><i class="fas fa-image"></i></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <select name="product_type[<?php echo $item->id; ?>]" class="form-select form-select-sm product-select" 
                                data-cart-id="<?php echo $item->id; ?>">
                          <option value=""><?php echo __('-- Select --'); ?></option>
                          <?php foreach ($productTypes as $type): ?>
                            <option value="<?php echo $type->id; ?>" 
                                    data-price="<?php echo $pricing[$type->id]->price ?? 0; ?>"
                                    <?php echo ($item->product_type_id == $type->id) ? 'selected' : ''; ?>>
                              <?php echo esc_entities($type->name); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td class="text-center">
                        <input type="number" name="quantity[<?php echo $item->id; ?>]" 
                               value="<?php echo $item->quantity; ?>" min="1" max="10"
                               class="form-control form-control-sm text-center quantity-input" style="width: 60px;">
                      </td>
                      <td class="text-end">
                        <span class="item-price" data-cart-id="<?php echo $item->id; ?>">
                          <?php if ($item->unit_price !== null): ?>
                            <?php echo $settings->currency ?? 'ZAR'; ?> <?php echo number_format($item->line_total, 2); ?>
                          <?php else: ?>
                            <span class="text-muted">--</span>
                          <?php endif; ?>
                        </span>
                      </td>
                      <td class="text-end">
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
          </form>

          <!-- Order Summary -->
          <?php if ($totals && $totals['total'] > 0): ?>
            <div class="card mt-4 border-success">
              <div class="card-header bg-light">
                <i class="fas fa-receipt me-2"></i><?php echo __('Order Summary'); ?>
              </div>
              <div class="card-body">
                <table class="table table-sm mb-0">
                  <tr>
                    <td><?php echo __('Items'); ?></td>
                    <td class="text-end"><?php echo $totals['item_count']; ?></td>
                  </tr>
                  <tr>
                    <td><?php echo __('Subtotal (excl. VAT)'); ?></td>
                    <td class="text-end"><?php echo $totals['currency']; ?> <?php echo number_format($totals['net_amount'], 2); ?></td>
                  </tr>
                  <tr>
                    <td><?php echo __('VAT'); ?> (<?php echo $totals['vat_rate']; ?>%)</td>
                    <td class="text-end"><?php echo $totals['currency']; ?> <?php echo number_format($totals['vat_amount'], 2); ?></td>
                  </tr>
                  <tr class="table-success fw-bold">
                    <td><?php echo __('Total'); ?></td>
                    <td class="text-end"><?php echo $totals['currency']; ?> <?php echo number_format($totals['total'], 2); ?></td>
                  </tr>
                </table>
              </div>
            </div>
          <?php endif; ?>

          <div class="mt-4 d-flex justify-content-between">
            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-2"></i><?php echo __('Continue Browsing'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'checkout']); ?>" class="btn btn-success btn-lg">
              <i class="fas fa-credit-card me-2"></i><?php echo __('Proceed to Checkout'); ?>
              <?php if ($totals && $totals['total'] > 0): ?>
                (<?php echo $totals['currency']; ?> <?php echo number_format($totals['total'], 2); ?>)
              <?php endif; ?>
            </a>
          </div>

        <?php else: ?>
          <!-- STANDARD MODE (Request to Publish) -->
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

          <div class="mt-4 d-flex justify-content-between">
            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-2"></i><?php echo __('Continue Browsing'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'checkout']); ?>" class="btn btn-success">
              <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Request to Publish'); ?>
            </a>
          </div>
        <?php endif; ?>
        
      <?php endif; ?>
    </div>
  </div>
</div>

<?php end_slot(); ?>
