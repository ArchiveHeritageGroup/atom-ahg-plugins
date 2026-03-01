<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Create an Account'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($registrationDisabled)): ?>
  <div class="alert alert-warning">
    <?php echo __('Registration is currently disabled. Please contact the administrator.'); ?>
  </div>
  <?php return; ?>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="alert alert-success">
    <h5><?php echo __('Registration submitted!'); ?></h5>
    <p><?php echo __('A verification email has been sent to your email address. Please click the link in the email to verify your account.'); ?></p>
    <p class="mb-0"><?php echo __('Your registration will be reviewed by an administrator after email verification.'); ?></p>
  </div>
  <?php return; ?>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger">
    <?php echo esc_entities($error); ?>
  </div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'userRegistration', 'action' => 'register']); ?>">
          <div class="mb-3">
            <label for="full_name" class="form-label"><?php echo __('Full Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control" required
                   value="<?php echo esc_entities($formData['full_name'] ?? ''); ?>"
                   placeholder="<?php echo __('Your full name'); ?>">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?php echo esc_entities($formData['email'] ?? ''); ?>"
                   placeholder="<?php echo __('your.email@example.com'); ?>">
          </div>

          <div class="mb-3">
            <label for="username" class="form-label"><?php echo __('Username'); ?> <span class="text-danger">*</span></label>
            <input type="text" id="username" name="username" class="form-control" required minlength="3"
                   pattern="[a-zA-Z0-9._-]+"
                   value="<?php echo esc_entities($formData['username'] ?? ''); ?>"
                   placeholder="<?php echo __('Choose a username'); ?>">
            <div class="form-text"><?php echo __('Letters, numbers, dots, hyphens, and underscores only.'); ?></div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label"><?php echo __('Password'); ?> <span class="text-danger">*</span></label>
              <input type="password" id="password" name="password" class="form-control" required minlength="8"
                     placeholder="<?php echo __('Minimum 8 characters'); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label for="confirm_password" class="form-label"><?php echo __('Confirm Password'); ?> <span class="text-danger">*</span></label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8"
                     placeholder="<?php echo __('Repeat password'); ?>">
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <label for="institution" class="form-label"><?php echo __('Institution / Organization'); ?></label>
            <input type="text" id="institution" name="institution" class="form-control"
                   value="<?php echo esc_entities($formData['institution'] ?? ''); ?>"
                   placeholder="<?php echo __('Your institution or organization'); ?>">
          </div>

          <div class="mb-3">
            <label for="research_interest" class="form-label"><?php echo __('Research Interest'); ?></label>
            <textarea id="research_interest" name="research_interest" class="form-control" rows="2"
                      placeholder="<?php echo __('Describe your research interests'); ?>"><?php echo esc_entities($formData['research_interest'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label for="reason" class="form-label"><?php echo __('Reason for Registration'); ?> <span class="text-danger">*</span></label>
            <textarea id="reason" name="reason" class="form-control" rows="3" required
                      placeholder="<?php echo __('Why do you need access to this system?'); ?>"><?php echo esc_entities($formData['reason'] ?? ''); ?></textarea>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary">
              <?php echo __('Submit Registration'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <p class="text-center mt-3">
      <small><?php echo __('Already have an account?'); ?>
        <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>"><?php echo __('Log in'); ?></a>
      </small>
    </p>
  </div>
</div>
