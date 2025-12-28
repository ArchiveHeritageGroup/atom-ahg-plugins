<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-user-plus text-primary me-2"></i><?php echo __('Researcher Registration'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-clipboard-list me-2"></i><?php echo __('Create Your Research Account'); ?>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('Register to access the reading room, request materials, and save your research.'); ?>
          <?php echo __('Your account will be reviewed and activated within 1-2 business days.'); ?>
        </div>

        <form method="post">
          <div class="row">
            <!-- Account Information -->
            <div class="col-md-6">
              <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-key me-2"></i><?php echo __('Account Information'); ?></h5>
              
              <div class="mb-3">
                <label class="form-label"><?php echo __('Username'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required minlength="3" placeholder="<?php echo __('Choose a username'); ?>">
                <small class="text-muted"><?php echo __('At least 3 characters, letters and numbers only'); ?></small>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Email Address'); ?> <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="your.email@example.com">
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Password'); ?> <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="8" id="password">
                <small class="text-muted"><?php echo __('At least 8 characters'); ?></small>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Confirm Password'); ?> <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
              </div>

              <h5 class="mb-3 mt-4 border-bottom pb-2"><i class="fas fa-id-card me-2"></i><?php echo __('Identification'); ?></h5>

              <div class="row mb-3">
                <div class="col-md-5">
                  <label class="form-label"><?php echo __('ID Type'); ?></label>
                  <select name="id_type" class="form-select">
                    <option value="">--</option>
                    <option value="passport"><?php echo __('Passport'); ?></option>
                    <option value="national_id"><?php echo __('National ID'); ?></option>
                    <option value="drivers_license"><?php echo __("Driver's License"); ?></option>
                    <option value="student_card"><?php echo __('Student Card'); ?></option>
                  </select>
                </div>
                <div class="col-md-7">
                  <label class="form-label"><?php echo __('ID Number'); ?></label>
                  <input type="text" name="id_number" class="form-control">
                </div>
              </div>
            </div>

            <!-- Personal Information -->
            <div class="col-md-6">
              <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-user me-2"></i><?php echo __('Personal Information'); ?></h5>

              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label"><?php echo __('Title'); ?></label>
                  <select name="title" class="form-select">
                    <option value="">--</option>
                    <option value="Mr">Mr</option>
                    <option value="Mrs">Mrs</option>
                    <option value="Ms">Ms</option>
                    <option value="Dr">Dr</option>
                    <option value="Prof">Prof</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label"><?php echo __('First Name'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-5">
                  <label class="form-label"><?php echo __('Last Name'); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="last_name" class="form-control" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Phone'); ?></label>
                <input type="tel" name="phone" class="form-control">
              </div>

              <h5 class="mb-3 mt-4 border-bottom pb-2"><i class="fas fa-university me-2"></i><?php echo __('Affiliation'); ?></h5>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Affiliation Type'); ?> <span class="text-danger">*</span></label>
                <select name="affiliation_type" class="form-select" required>
                  <option value="independent"><?php echo __('Independent Researcher'); ?></option>
                  <option value="academic"><?php echo __('Academic Institution'); ?></option>
                  <option value="government"><?php echo __('Government'); ?></option>
                  <option value="private"><?php echo __('Private Organization'); ?></option>
                  <option value="student"><?php echo __('Student'); ?></option>
                  <option value="other"><?php echo __('Other'); ?></option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Institution'); ?></label>
                <input type="text" name="institution" class="form-control" placeholder="<?php echo __('University, Organization, etc.'); ?>">
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?php echo __('Department'); ?></label>
                  <input type="text" name="department" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?php echo __('Position'); ?></label>
                  <input type="text" name="position" class="form-control">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('ORCID ID'); ?></label>
                <input type="text" name="orcid_id" class="form-control" placeholder="0000-0000-0000-0000">
              </div>
            </div>
          </div>

          <!-- Research Information -->
          <div class="row mt-3">
            <div class="col-12">
              <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-flask me-2"></i><?php echo __('Research Information'); ?></h5>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Research Interests'); ?></label>
                <textarea name="research_interests" class="form-control" rows="3" placeholder="<?php echo __('Describe your research interests...'); ?>"></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Current Project'); ?></label>
                <textarea name="current_project" class="form-control" rows="3" placeholder="<?php echo __('Describe your current research project...'); ?>"></textarea>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-muted"><?php echo __('Already have an account?'); ?></span>
              <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>"><?php echo __('Login here'); ?></a>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane me-2"></i><?php echo __('Submit Registration'); ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php end_slot() ?>
