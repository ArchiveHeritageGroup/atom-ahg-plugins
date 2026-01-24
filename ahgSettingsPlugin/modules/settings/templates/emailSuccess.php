<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-envelope text-primary me-2"></i><?php echo __('Email Settings'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="post">
  <div class="row">
    <div class="col-md-6">
      <!-- SMTP Settings -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-server me-2"></i><?php echo __('SMTP Configuration'); ?>
        </div>
        <div class="card-body">
          <?php foreach ($smtpSettings as $setting): ?>
            <div class="mb-3">
              <label class="form-label">
                <?php echo __(ucwords(str_replace('_', ' ', str_replace('smtp_', '', $setting->setting_key)))); ?>
              </label>
              <?php if ($setting->setting_type === 'boolean'): ?>
                <select name="settings[<?php echo $setting->setting_key; ?>]" class="form-select">
                  <option value="0" <?php echo $setting->setting_value == '0' ? 'selected' : ''; ?>><?php echo __('Disabled'); ?></option>
                  <option value="1" <?php echo $setting->setting_value == '1' ? 'selected' : ''; ?>><?php echo __('Enabled'); ?></option>
                </select>
              <?php elseif ($setting->setting_type === 'password'): ?>
                <input type="password" name="settings[<?php echo $setting->setting_key; ?>]" 
                       class="form-control" value="<?php echo htmlspecialchars($setting->setting_value ?? ''); ?>"
                       placeholder="••••••••">
              <?php elseif ($setting->setting_type === 'number'): ?>
                <input type="number" name="settings[<?php echo $setting->setting_key; ?>]" 
                       class="form-control" value="<?php echo htmlspecialchars($setting->setting_value ?? ''); ?>">
              <?php else: ?>
                <input type="<?php echo $setting->setting_type === 'email' ? 'email' : 'text'; ?>" 
                       name="settings[<?php echo $setting->setting_key; ?>]" 
                       class="form-control" value="<?php echo htmlspecialchars($setting->setting_value ?? ''); ?>">
              <?php endif; ?>
              <?php if ($setting->description): ?>
                <small class="text-muted"><?php echo htmlspecialchars($setting->description); ?></small>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Test Email -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-paper-plane me-2"></i><?php echo __('Test Email'); ?>
        </div>
        <div class="card-body">
          <p class="small text-muted"><?php echo __('Save settings first, then send a test email to verify configuration.'); ?></p>
          <div class="input-group">
            <input type="email" name="test_email" class="form-control" placeholder="test@example.com" id="testEmailInput">
            <button type="button" class="btn btn-outline-primary" onclick="sendTestEmail()">
              <i class="fas fa-paper-plane me-1"></i><?php echo __('Send Test'); ?>
            </button>
          </div>
        </div>
      </div>

      <!-- Notification Recipients -->
      <div class="card mb-4">
        <div class="card-header bg-info text-white">
          <i class="fas fa-bell me-2"></i><?php echo __('Notification Recipients'); ?>
        </div>
        <div class="card-body">
          <?php foreach ($notificationSettings as $setting): ?>
            <div class="mb-3">
              <label class="form-label">
                <?php echo __(ucwords(str_replace('_', ' ', str_replace('notify_', '', $setting->setting_key)))); ?>
              </label>
              <input type="email" name="settings[<?php echo $setting->setting_key; ?>]" 
                     class="form-control" value="<?php echo htmlspecialchars($setting->setting_value ?? ''); ?>"
                     placeholder="admin@example.com">
              <?php if ($setting->description): ?>
                <small class="text-muted"><?php echo htmlspecialchars($setting->description); ?></small>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <!-- Email Templates -->
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <i class="fas fa-file-alt me-2"></i><?php echo __('Email Templates'); ?>
        </div>
        <div class="card-body">
          <div class="alert alert-info small">
            <strong><?php echo __('Available placeholders:'); ?></strong><br>
            <code>{name}</code> - <?php echo __('Recipient name'); ?><br>
            <code>{email}</code> - <?php echo __('Recipient email'); ?><br>
            <code>{institution}</code> - <?php echo __('Institution name'); ?><br>
            <code>{login_url}</code> - <?php echo __('Login page URL'); ?><br>
            <code>{reset_url}</code> - <?php echo __('Password reset URL'); ?><br>
            <code>{review_url}</code> - <?php echo __('Review page URL'); ?><br>
            <code>{date}</code>, <code>{time}</code>, <code>{room}</code> - <?php echo __('Booking details'); ?><br>
            <code>{reason}</code> - <?php echo __('Rejection reason'); ?>
          </div>
          
          <div class="accordion" id="templateAccordion">
            <?php 
            $templateGroups = [
                'researcher_pending' => 'New Registration (to Researcher)',
                'researcher_approved' => 'Approval (to Researcher)',
                'researcher_rejected' => 'Rejection (to Researcher)',
                'password_reset' => 'Password Reset (to User)',
                'booking_confirmed' => 'Booking Confirmed (to Researcher)',
                'admin_new_researcher' => 'New Registration (to Admin)',
            ];
            $index = 0;
            foreach ($templateGroups as $templateKey => $templateLabel): 
              $subjectKey = 'email_' . $templateKey . '_subject';
              $bodyKey = 'email_' . $templateKey . '_body';
              $subjectSetting = null;
              $bodySetting = null;
              foreach ($templateSettings as $ts) {
                  if ($ts->setting_key === $subjectKey) $subjectSetting = $ts;
                  if ($ts->setting_key === $bodyKey) $bodySetting = $ts;
              }
              if (!$subjectSetting || !$bodySetting) continue;
              $index++;
            ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false">
                  <?php echo __($templateLabel); ?>
                </button>
              </h2>
              <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" 
                   aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#templateAccordion">
                <div class="accordion-body">
                  <div class="mb-3">
                    <label class="form-label"><?php echo __('Subject'); ?></label>
                    <input type="text" name="settings[<?php echo $subjectKey; ?>]" 
                           class="form-control" value="<?php echo htmlspecialchars($subjectSetting->setting_value ?? ''); ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label"><?php echo __('Body'); ?></label>
                    <textarea name="settings[<?php echo $bodyKey; ?>]" class="form-control" 
                              rows="6"><?php echo htmlspecialchars($bodySetting->setting_value ?? ''); ?></textarea>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <hr>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'index']); ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Settings'); ?>
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i><?php echo __('Save Settings'); ?>
    </button>
  </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function sendTestEmail() {
    var email = document.getElementById('testEmailInput').value;
    if (email) {
        window.location.href = '<?php echo url_for(['module' => 'settings', 'action' => 'emailTest']); ?>?email=' + encodeURIComponent(email);
    } else {
        alert('Please enter an email address');
    }
}
</script>
<?php end_slot() ?>
