<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-<?php echo $ecommerceEnabled ? 'credit-card' : 'paper-plane'; ?> me-2"></i>
    <?php echo $ecommerceEnabled ? __('Checkout') : __('Submit Request to Publish'); ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  
  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Left Column: Form -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-user me-2"></i>
          <?php echo $ecommerceEnabled ? __('Billing Details') : __('Your Details'); ?>
        </div>
        <div class="card-body">
          <form method="post" action="<?php echo url_for(['module' => 'ahgCart', 'action' => 'checkout']); ?>">
            
            <?php if ($ecommerceEnabled): ?>
              <!-- E-COMMERCE FORM -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Full Name'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="billing_name" class="form-control" required
                         value="<?php echo esc_entities($user->username ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Email'); ?> <span class="text-danger">*</span></label>
                  <input type="email" name="billing_email" class="form-control" required
                         value="<?php echo esc_entities($user->email ?? ''); ?>">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Phone'); ?></label>
                  <input type="tel" name="billing_phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Company/Institution'); ?></label>
                  <input type="text" name="billing_company" class="form-control">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Billing Address'); ?></label>
                <textarea name="billing_address" class="form-control" rows="2"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Order Notes'); ?></label>
                <textarea name="notes" class="form-control" rows="2" 
                          placeholder="<?php echo __('Special instructions for your order...'); ?>"></textarea>
              </div>

              <?php if ($settings && $settings->terms_conditions): ?>
                <div class="mb-3">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree_terms" required>
                    <label class="form-check-label" for="agreeTerms">
                      <?php echo __('I agree to the'); ?> 
                      <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal"><?php echo __('Terms and Conditions'); ?></a>
                      <span class="text-danger">*</span>
                    </label>
                  </div>
                </div>
              <?php endif; ?>

              <div class="d-flex justify-content-between mt-4">
                <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
                  <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Cart'); ?>
                </a>
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-lock me-2"></i>
                  <?php if ($totals['total'] > 0): ?>
                    <?php echo __('Proceed to Payment'); ?> (<?php echo $totals['currency']; ?> <?php echo number_format($totals['total'], 2); ?>)
                  <?php else: ?>
                    <?php echo __('Complete Order'); ?>
                  <?php endif; ?>
                </button>
              </div>

            <?php else: ?>
              <!-- STANDARD REQUEST TO PUBLISH FORM -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('First Name'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="rtp_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Surname'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="rtp_surname" class="form-control" required>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Email'); ?> <span class="text-danger">*</span></label>
                  <input type="email" name="rtp_email" class="form-control" required
                         value="<?php echo esc_entities($user->email ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label"><?php echo __('Phone'); ?> <span class="text-danger">*</span></label>
                  <input type="tel" name="rtp_phone" class="form-control" required>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Institution/Company'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="rtp_institution" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Planned Use'); ?> <span class="text-danger">*</span></label>
                <select name="rtp_planned_use" class="form-select" required>
                  <option value=""><?php echo __('-- Select --'); ?></option>
                  <option value="Personal research"><?php echo __('Personal research'); ?></option>
                  <option value="Academic publication"><?php echo __('Academic publication'); ?></option>
                  <option value="Book/Magazine publication"><?php echo __('Book/Magazine publication'); ?></option>
                  <option value="Documentary/Film"><?php echo __('Documentary/Film'); ?></option>
                  <option value="Exhibition"><?php echo __('Exhibition'); ?></option>
                  <option value="Website"><?php echo __('Website'); ?></option>
                  <option value="Commercial use"><?php echo __('Commercial use'); ?></option>
                  <option value="Other"><?php echo __('Other'); ?></option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Motivation/Purpose'); ?></label>
                <textarea name="rtp_motivation" class="form-control" rows="3"
                          placeholder="<?php echo __('Please describe why you need these images...'); ?>"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Need Images By'); ?></label>
                <input type="date" name="rtp_need_image_by" class="form-control">
              </div>

              <div class="d-flex justify-content-between mt-4">
                <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
                  <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Cart'); ?>
                </a>
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Request'); ?> (<?php echo $count; ?> <?php echo __('items'); ?>)
                </button>
              </div>
            <?php endif; ?>

          </form>
        </div>
      </div>
    </div>

    <!-- Right Column: Order Summary -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <i class="fas fa-shopping-cart me-2"></i><?php echo __('Order Summary'); ?>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php foreach ($items as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                  <div class="fw-bold small"><?php echo esc_entities(mb_substr($item->title, 0, 40)); ?><?php echo strlen($item->title) > 40 ? '...' : ''; ?></div>
                  <?php if ($ecommerceEnabled && $item->product_name): ?>
                    <small class="text-muted"><?php echo esc_entities($item->product_name); ?></small>
                  <?php endif; ?>
                </div>
                <?php if ($ecommerceEnabled && $item->unit_price !== null): ?>
                  <span class="badge bg-success rounded-pill">
                    <?php echo $settings->currency ?? 'ZAR'; ?> <?php echo number_format($item->line_total, 2); ?>
                  </span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php if ($ecommerceEnabled && $totals): ?>
          <div class="card-footer">
            <table class="table table-sm mb-0">
              <tr>
                <td><?php echo __('Subtotal'); ?></td>
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
        <?php endif; ?>
      </div>

      <?php if ($ecommerceEnabled): ?>
        <div class="card mt-3 shadow-sm">
          <div class="card-body text-center">
            <i class="fas fa-lock fa-2x text-success mb-2"></i>
            <p class="small text-muted mb-0"><?php echo __('Your payment is secured with SSL encryption'); ?></p>
            <div class="mt-2">
              <img src="/plugins/ahgCartPlugin/images/payfast-logo.png" alt="PayFast" height="30" class="me-2" onerror="this.style.display='none'">
              <img src="/plugins/ahgCartPlugin/images/visa.png" alt="Visa" height="20" class="me-1" onerror="this.style.display='none'">
              <img src="/plugins/ahgCartPlugin/images/mastercard.png" alt="Mastercard" height="20" onerror="this.style.display='none'">
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($ecommerceEnabled && $settings && $settings->terms_conditions): ?>
<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Terms and Conditions'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php echo $settings->terms_conditions; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php end_slot(); ?>
