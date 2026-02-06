<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <div class="d-flex align-items-center justify-content-between">
    <h1 class="mb-0">
      <i class="fas fa-shopping-cart me-2"></i><?php echo __('Cart Items'); ?>
      <?php if ($ecommerceEnabled): ?>
        <span class="badge bg-success ms-2"><?php echo __('E-Commerce'); ?></span>
      <?php endif; ?>
    </h1>
    <?php if ($count > 0): ?>
      <form action="<?php echo url_for(['module' => 'cart', 'action' => 'clear']); ?>" method="post" style="display: inline;">
        <button type="submit" class="btn btn-outline-danger btn-sm"
                onclick="return confirm('<?php echo __('Clear all items from cart?'); ?>');">
          <i class="fas fa-trash me-1"></i><?php echo __('Clear Cart'); ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
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

  <?php if ($isGuest): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i>
      <?php echo __('You are shopping as a guest.'); ?>
      <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>"><?php echo __('Log in'); ?></a>
      <?php echo __('to save your cart to your account.'); ?>
    </div>
  <?php endif; ?>

  <?php if ($count === 0): ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
        <h3 class="text-muted"><?php echo __('Your cart is empty'); ?></h3>
        <p class="text-muted"><?php echo __('Browse the archive to add items to your cart.'); ?></p>
        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-primary">
          <i class="fas fa-search me-2"></i><?php echo __('Browse Archive'); ?>
        </a>
      </div>
    </div>

  <?php elseif ($ecommerceEnabled): ?>
    <!-- E-COMMERCE MODE -->
    <?php
    // Build pricing lookup
    $pricingLookup = [];
    foreach ($pricing as $p) {
        $pricingLookup[$p->product_type_id] = (float)$p->price;
    }
    ?>

    <div id="cart-items">
      <?php foreach ($items as $index => $item): ?>
      <div class="card mb-3 cart-item-card" data-cart-id="<?php echo $item->id; ?>" data-description-id="<?php echo $item->archival_description_id; ?>">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-file-image me-2"></i>
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" class="text-white text-decoration-none fw-bold">
              <?php echo esc_entities($item->title); ?>
            </a>
            <?php if ($item->has_digital_object): ?>
              <span class="badge bg-warning text-dark ms-2"><i class="fas fa-image me-1"></i><?php echo __('Has Image'); ?></span>
            <?php endif; ?>
          </div>
          <div>
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" class="btn btn-sm btn-outline-light me-1" title="<?php echo __('View'); ?>">
              <i class="fas fa-eye"></i>
            </a>
            <a href="<?php echo url_for(['module' => 'cart', 'action' => 'remove', 'id' => $item->id]); ?>" 
               class="btn btn-sm btn-outline-light" 
               title="<?php echo __('Remove'); ?>"
               onclick="return confirm('<?php echo __('Remove this item from cart?'); ?>');">
              <i class="fas fa-trash"></i>
            </a>
          </div>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            <i class="fas fa-info-circle me-1"></i>
            <?php echo __('Select one or more products for this item:'); ?>
          </p>
          
          <!-- Product rows container -->
          <div class="product-rows" data-cart-id="<?php echo $item->id; ?>">
            <!-- Initial row -->
            <div class="product-row mb-2 d-flex align-items-center gap-2" data-row-index="0">
              <select class="form-select product-select" style="max-width: 400px;" data-cart-id="<?php echo $item->id; ?>">
                <option value=""><?php echo __('-- Select Product Type --'); ?></option>
                <?php foreach ($productTypes as $type): ?>
                  <?php $price = $pricingLookup[$type->id] ?? 0; ?>
                  <option value="<?php echo $type->id; ?>" data-price="<?php echo $price; ?>">
                    <?php echo esc_entities($type->name); ?> - R <?php echo number_format($price, 2); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="row-price fw-bold text-success" style="min-width: 100px;">R 0.00</span>
              <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="<?php echo __('Remove'); ?>" style="display: none;">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          
          <!-- Add product button -->
          <button type="button" class="btn btn-sm btn-outline-primary add-row-btn mt-2" data-cart-id="<?php echo $item->id; ?>">
            <i class="fas fa-plus me-1"></i><?php echo __('Add Another Product'); ?>
          </button>
          
          <!-- Item total -->
          <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted">
              <i class="fas fa-calculator me-1"></i>
              <span class="product-count" data-cart-id="<?php echo $item->id; ?>">0</span> <?php echo __('product(s) selected'); ?>
            </span>
            <span class="fw-bold text-success fs-5 item-total" data-cart-id="<?php echo $item->id; ?>">R 0.00</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Order Summary -->
    <div class="card border-success mt-4">
      <div class="card-header bg-success text-white">
        <i class="fas fa-receipt me-2"></i><?php echo __('Order Summary'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <div id="selected-products-summary">
              <p class="text-muted"><i class="fas fa-info-circle me-1"></i><?php echo __('Select products above to see summary'); ?></p>
            </div>
          </div>
          <div class="col-md-4">
            <table class="table table-sm mb-0">
              <tr>
                <td><?php echo __('Subtotal'); ?></td>
                <td class="text-end" id="summary-subtotal">R 0.00</td>
              </tr>
              <tr>
                <td><?php echo __('VAT'); ?> (<?php echo $settings->vat_rate ?? 15; ?>%)</td>
                <td class="text-end" id="summary-vat">R 0.00</td>
              </tr>
              <tr class="table-success fw-bold fs-5">
                <td><?php echo __('Total'); ?></td>
                <td class="text-end" id="summary-total">R 0.00</td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="mt-4 d-flex justify-content-between">
      <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i><?php echo __('Continue Browsing'); ?>
      </a>
      <button type="button" class="btn btn-success btn-lg" id="checkout-btn" disabled>
        <i class="fas fa-credit-card me-2"></i><?php echo __('Proceed to Checkout'); ?>
        <span id="checkout-total"></span>
      </button>
    </div>

    <!-- Hidden template for new rows -->
    <template id="product-row-template">
      <div class="product-row mb-2 d-flex align-items-center gap-2">
        <select class="form-select product-select" style="max-width: 400px;">
          <option value=""><?php echo __('-- Select Product Type --'); ?></option>
          <?php foreach ($productTypes as $type): ?>
            <?php $price = $pricingLookup[$type->id] ?? 0; ?>
            <option value="<?php echo $type->id; ?>" data-price="<?php echo $price; ?>">
              <?php echo esc_entities($type->name); ?> - R <?php echo number_format($price, 2); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="row-price fw-bold text-success" style="min-width: 100px;">R 0.00</span>
        <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="<?php echo __('Remove'); ?>">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </template>

    <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    (function() {
      var currency = 'R';
      var vatRate = <?php echo $settings->vat_rate ?? 15; ?>;
      var cartSelections = {}; // cartId -> [{productTypeId, price, name}]

      // Initialize
      document.querySelectorAll('.cart-item-card').forEach(function(card) {
        var cartId = card.dataset.cartId;
        cartSelections[cartId] = [];
        updateRemoveButtons(cartId);
      });

      // Add row button
      document.querySelectorAll('.add-row-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var cartId = this.dataset.cartId;
          addProductRow(cartId);
        });
      });

      // Product select change - delegate
      document.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
          var cartId = e.target.dataset.cartId || e.target.closest('.product-rows').dataset.cartId;
          updateRowPrice(e.target);
          collectSelections(cartId);
          updateItemTotal(cartId);
          updateOrderSummary();
        }
      });

      // Remove row button - delegate
      document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row-btn')) {
          var row = e.target.closest('.product-row');
          var container = row.closest('.product-rows');
          var cartId = container.dataset.cartId;
          
          // Don't remove if it's the only row
          if (container.querySelectorAll('.product-row').length > 1) {
            row.remove();
            collectSelections(cartId);
            updateItemTotal(cartId);
            updateOrderSummary();
            updateRemoveButtons(cartId);
          }
        }
      });

      function addProductRow(cartId) {
        var container = document.querySelector('.product-rows[data-cart-id="' + cartId + '"]');
        var template = document.getElementById('product-row-template');
        var clone = template.content.cloneNode(true);
        
        // Set cart ID on select
        clone.querySelector('.product-select').dataset.cartId = cartId;
        
        container.appendChild(clone);
        updateRemoveButtons(cartId);
      }

      function updateRowPrice(select) {
        var row = select.closest('.product-row');
        var priceSpan = row.querySelector('.row-price');
        var selected = select.options[select.selectedIndex];
        var price = selected ? parseFloat(selected.dataset.price) || 0 : 0;
        priceSpan.textContent = currency + ' ' + price.toFixed(2);
      }

      function updateRemoveButtons(cartId) {
        var container = document.querySelector('.product-rows[data-cart-id="' + cartId + '"]');
        var rows = container.querySelectorAll('.product-row');
        rows.forEach(function(row, index) {
          var btn = row.querySelector('.remove-row-btn');
          // Show remove button only if more than one row
          btn.style.display = rows.length > 1 ? 'inline-block' : 'none';
        });
      }

      function collectSelections(cartId) {
        var container = document.querySelector('.product-rows[data-cart-id="' + cartId + '"]');
        var selects = container.querySelectorAll('.product-select');
        cartSelections[cartId] = [];
        
        selects.forEach(function(select) {
          if (select.value) {
            var selected = select.options[select.selectedIndex];
            cartSelections[cartId].push({
              productTypeId: parseInt(select.value),
              price: parseFloat(selected.dataset.price) || 0,
              name: selected.textContent.trim()
            });
          }
        });
      }

      function updateItemTotal(cartId) {
        var total = 0;
        var count = 0;
        
        cartSelections[cartId].forEach(function(item) {
          total += item.price;
          count++;
        });
        
        var itemTotal = document.querySelector('.item-total[data-cart-id="' + cartId + '"]');
        if (itemTotal) {
          itemTotal.textContent = currency + ' ' + total.toFixed(2);
        }
        
        var countSpan = document.querySelector('.product-count[data-cart-id="' + cartId + '"]');
        if (countSpan) {
          countSpan.textContent = count;
        }
      }

      function updateOrderSummary() {
        var subtotal = 0;
        var summaryHtml = '';
        var hasProducts = false;

        for (var cartId in cartSelections) {
          if (cartSelections[cartId].length > 0) {
            hasProducts = true;
            var card = document.querySelector('.cart-item-card[data-cart-id="' + cartId + '"]');
            var titleEl = card ? card.querySelector('.fw-bold') : null;
            var itemTitle = titleEl ? titleEl.textContent.trim().substring(0, 50) : 'Item';

            summaryHtml += '<div class="mb-2"><strong>' + itemTitle + '</strong><ul class="mb-1 small">';
            cartSelections[cartId].forEach(function(product) {
              summaryHtml += '<li>' + product.name + '</li>';
              subtotal += product.price;
            });
            summaryHtml += '</ul></div>';
          }
        }

        // VAT is included in prices
        var vatAmount = subtotal - (subtotal / (1 + (vatRate / 100)));
        var netAmount = subtotal - vatAmount;

        document.getElementById('selected-products-summary').innerHTML = hasProducts
          ? summaryHtml
          : '<p class="text-muted"><i class="fas fa-info-circle me-1"></i>Select products above to see summary</p>';
        document.getElementById('summary-subtotal').textContent = currency + ' ' + netAmount.toFixed(2);
        document.getElementById('summary-vat').textContent = currency + ' ' + vatAmount.toFixed(2);
        document.getElementById('summary-total').textContent = currency + ' ' + subtotal.toFixed(2);

        var checkoutBtn = document.getElementById('checkout-btn');
        var checkoutTotal = document.getElementById('checkout-total');
        if (hasProducts && subtotal > 0) {
          checkoutBtn.disabled = false;
          checkoutTotal.textContent = '(' + currency + ' ' + subtotal.toFixed(2) + ')';
        } else if (hasProducts) {
          // Free items (Research Use Only)
          checkoutBtn.disabled = false;
          checkoutTotal.textContent = '(Free)';
        } else {
          checkoutBtn.disabled = true;
          checkoutTotal.textContent = '';
        }
      }

      // Checkout button
      document.getElementById('checkout-btn').addEventListener('click', function() {
        if (this.disabled) return;
        
        // Build selections data
        var selectionsData = {};
        for (var cartId in cartSelections) {
          if (cartSelections[cartId].length > 0) {
            selectionsData[cartId] = cartSelections[cartId].map(function(p) {
              return { id: p.productTypeId, price: p.price, name: p.name };
            });
          }
        }
        
        // Submit form
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo url_for(['module' => 'cart', 'action' => 'checkout']); ?>';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selections';
        input.value = JSON.stringify(selectionsData);
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      });
    })();
    </script>

  <?php else: ?>
    <!-- STANDARD MODE (Request to Publish) -->
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Title'); ?></th>
            <th class="text-center"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>">
                <?php echo esc_entities($item->archival_description ?? $item->title ?? 'Untitled'); ?>
              </a>
            </td>
            <td class="text-center">
              <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('View'); ?>">
                <i class="fas fa-eye"></i>
              </a>
              <a href="<?php echo url_for(['module' => 'cart', 'action' => 'remove', 'id' => $item->id]); ?>" 
                 class="btn btn-sm btn-outline-danger" 
                 title="<?php echo __('Remove'); ?>"
                 onclick="return confirm('<?php echo __('Remove this item?'); ?>');">
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
      <a href="<?php echo url_for(['module' => 'cart', 'action' => 'checkout']); ?>" class="btn btn-success">
        <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Request to Publish'); ?>
      </a>
    </div>
  <?php endif; ?>
</div>

<?php end_slot(); ?>
