<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-store me-2"></i><?php echo __('E-Commerce Settings'); ?></h1>
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

  <ul class="nav nav-tabs mb-4" id="ecommerceTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">
        <i class="fas fa-cog me-1"></i><?php echo __('General'); ?>
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#payment" type="button">
        <i class="fas fa-credit-card me-1"></i><?php echo __('Payment'); ?>
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pricing" type="button">
        <i class="fas fa-tags me-1"></i><?php echo __('Pricing'); ?>
      </button>
    </li>
  </ul>

  <div class="tab-content">
    
    <!-- General Settings Tab -->
    <div class="tab-pane fade show active" id="general">
      <form method="post" action="<?php echo url_for(['module' => 'cart', 'action' => 'adminSettings']); ?>">
        <input type="hidden" name="action_type" value="save_settings">
        
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <i class="fas fa-sliders-h me-2"></i><?php echo __('General Settings'); ?>
          </div>
          <div class="card-body">
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="is_enabled" name="is_enabled" value="1"
                           <?php echo ($settings && $settings->is_enabled) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_enabled">
                      <strong><?php echo __('Enable E-Commerce Mode'); ?></strong>
                    </label>
                  </div>
                  <small class="text-muted"><?php echo __('When disabled, cart will use standard Request to Publish workflow.'); ?></small>
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Currency'); ?></label>
                  <select name="currency" class="form-select">
                    <option value="ZAR" <?php echo ($settings && $settings->currency === 'ZAR') ? 'selected' : ''; ?>>ZAR - South African Rand</option>
                    <option value="USD" <?php echo ($settings && $settings->currency === 'USD') ? 'selected' : ''; ?>>USD - US Dollar</option>
                    <option value="EUR" <?php echo ($settings && $settings->currency === 'EUR') ? 'selected' : ''; ?>>EUR - Euro</option>
                    <option value="GBP" <?php echo ($settings && $settings->currency === 'GBP') ? 'selected' : ''; ?>>GBP - British Pound</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('VAT Rate (%)'); ?></label>
                  <input type="number" name="vat_rate" class="form-control" step="0.01" min="0" max="50"
                         value="<?php echo $settings->vat_rate ?? 15.00; ?>">
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('VAT Number'); ?></label>
                  <input type="text" name="vat_number" class="form-control"
                         value="<?php echo esc_entities($settings->vat_number ?? ''); ?>">
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label"><?php echo __('Admin Notification Email'); ?></label>
                  <input type="email" name="admin_notification_email" class="form-control"
                         value="<?php echo esc_entities($settings->admin_notification_email ?? ''); ?>"
                         placeholder="orders@example.com">
                  <small class="text-muted"><?php echo __('Receive order notifications at this email.'); ?></small>
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Terms and Conditions'); ?></label>
                  <textarea name="terms_conditions" class="form-control" rows="6"><?php echo esc_entities($settings->terms_conditions ?? ''); ?></textarea>
                </div>
              </div>
            </div>

          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-2"></i><?php echo __('Save Settings'); ?>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Payment Tab -->
    <div class="tab-pane fade" id="payment">
      <form method="post" action="<?php echo url_for(['module' => 'cart', 'action' => 'adminSettings']); ?>">
        <input type="hidden" name="action_type" value="save_settings">
        
        <!-- Copy general settings to maintain state -->
        <input type="hidden" name="is_enabled" value="<?php echo ($settings && $settings->is_enabled) ? '1' : '0'; ?>">
        <input type="hidden" name="currency" value="<?php echo esc_entities($settings->currency ?? 'ZAR'); ?>">
        <input type="hidden" name="vat_rate" value="<?php echo $settings->vat_rate ?? 15.00; ?>">
        <input type="hidden" name="vat_number" value="<?php echo esc_entities($settings->vat_number ?? ''); ?>">
        <input type="hidden" name="admin_notification_email" value="<?php echo esc_entities($settings->admin_notification_email ?? ''); ?>">
        <input type="hidden" name="terms_conditions" value="<?php echo esc_entities($settings->terms_conditions ?? ''); ?>">
        
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white">
            <i class="fas fa-credit-card me-2"></i><?php echo __('PayFast Configuration'); ?>
          </div>
          <div class="card-body">
            
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              <?php echo __('PayFast is a South African payment gateway. Sign up at'); ?> 
              <a href="https://www.payfast.co.za" target="_blank">www.payfast.co.za</a>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label"><?php echo __('Merchant ID'); ?></label>
                  <input type="text" name="payfast_merchant_id" class="form-control"
                         value="<?php echo esc_entities($settings->payfast_merchant_id ?? ''); ?>">
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Merchant Key'); ?></label>
                  <input type="text" name="payfast_merchant_key" class="form-control"
                         value="<?php echo esc_entities($settings->payfast_merchant_key ?? ''); ?>">
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Passphrase'); ?></label>
                  <input type="password" name="payfast_passphrase" class="form-control"
                         value="<?php echo esc_entities($settings->payfast_passphrase ?? ''); ?>">
                  <small class="text-muted"><?php echo __('Optional but recommended for security.'); ?></small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="payfast_sandbox" name="payfast_sandbox" value="1"
                           <?php echo (!$settings || $settings->payfast_sandbox) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="payfast_sandbox">
                      <strong><?php echo __('Sandbox Mode (Testing)'); ?></strong>
                    </label>
                  </div>
                  <small class="text-muted"><?php echo __('Enable for testing. Disable for live payments.'); ?></small>
                </div>

                <div class="alert alert-warning mt-4">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  <strong><?php echo __('ITN URL'); ?></strong><br>
                  <?php echo __('Configure this URL in your PayFast dashboard:'); ?><br>
                  <code><?php echo sfConfig::get('app_siteBaseUrl', 'https://your-site.com'); ?>/cart/payment/notify</code>
                </div>
              </div>
            </div>

          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save me-2"></i><?php echo __('Save Payment Settings'); ?>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Pricing Tab -->
    <div class="tab-pane fade" id="pricing">
      <form method="post" action="<?php echo url_for(['module' => 'cart', 'action' => 'adminSettings']); ?>">
        <input type="hidden" name="action_type" value="save_pricing">
        
        <div class="card shadow-sm">
          <div class="card-header bg-warning text-dark">
            <i class="fas fa-tags me-2"></i><?php echo __('Product Pricing'); ?>
          </div>
          <div class="card-body">
            
            <p class="text-muted mb-4"><?php echo __('Set prices for each product type. Prices include VAT.'); ?></p>

            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th><?php echo __('Product Type'); ?></th>
                    <th><?php echo __('Type'); ?></th>
                    <th class="text-center" style="width: 100px;"><?php echo __('Active'); ?></th>
                    <th style="width: 150px;"><?php echo __('Price'); ?> (<?php echo $settings->currency ?? 'ZAR'; ?>)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($productTypes as $type): ?>
                    <?php 
                    $currentPrice = null;
                    foreach ($pricing as $p) {
                        if ($p->product_type_id == $type->id) {
                            $currentPrice = $p;
                            break;
                        }
                    }
                    ?>
                    <tr>
                      <td>
                        <strong><?php echo esc_entities($type->name); ?></strong>
                        <?php if ($type->description): ?>
                          <br><small class="text-muted"><?php echo esc_entities($type->description); ?></small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($type->is_digital): ?>
                          <span class="badge bg-info"><i class="fas fa-download me-1"></i>Digital</span>
                        <?php else: ?>
                          <span class="badge bg-secondary"><i class="fas fa-print me-1"></i>Physical</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input" name="price_active[<?php echo $type->id; ?>]" value="1"
                               <?php echo (!$currentPrice || $currentPrice->is_active) ? 'checked' : ''; ?>>
                      </td>
                      <td>
                        <input type="number" name="price[<?php echo $type->id; ?>]" class="form-control" 
                               step="0.01" min="0"
                               value="<?php echo $currentPrice->price ?? 0; ?>">
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-save me-2"></i><?php echo __('Save Pricing'); ?>
            </button>
          </div>
        </div>
      </form>
    </div>

  </div>

  <div class="mt-4">
    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Admin'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'cart', 'action' => 'adminOrders']); ?>" class="btn btn-outline-primary ms-2">
      <i class="fas fa-list me-2"></i><?php echo __('View Orders'); ?>
    </a>
  </div>

</div>

<?php end_slot(); ?>
