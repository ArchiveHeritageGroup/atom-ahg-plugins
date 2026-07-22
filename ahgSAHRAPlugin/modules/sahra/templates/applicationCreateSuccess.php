<?php use_helper('Date'); ?>

<div class="container">
  <div class="row">
    <div class="col-lg-9 mx-auto">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo url_for('@sahra_my'); ?>">My permit applications</a></li>
          <li class="breadcrumb-item active">New application</li>
        </ol>
      </nav>

      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="fas fa-landmark me-2"></i>Apply for a SAHRA / NHRA heritage permit</h4>
        </div>
        <div class="card-body">
          <p class="text-muted">
            Complete this application and submit it to your supervising professor for endorsement. Once endorsed,
            the archive's heritage coordinator lodges it with SAHRA (or the relevant provincial authority).
          </p>

          <form method="post" action="<?php echo url_for('@sahra_create'); ?>">

            <h6 class="text-uppercase text-muted mt-2">The work</h6>
            <div class="mb-3">
              <label class="form-label">Project title <span class="text-danger">*</span></label>
              <input type="text" name="project_title" class="form-control" required>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">NHRA section / permit type</label>
                <select name="nhra_section" class="form-select">
                  <?php foreach ($sections as $key => $label): ?>
                    <option value="<?php echo $key; ?>"<?php echo $key === 's35_archaeology' ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Issuing authority</label>
                <select name="issuing_authority" class="form-select">
                  <?php foreach ($authorities as $a): ?>
                    <option value="<?php echo htmlspecialchars($a); ?>"><?php echo htmlspecialchars($a); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Project description / purpose</label>
              <textarea name="project_description" class="form-control" rows="4" placeholder="Nature of the fieldwork, methods, and the material to be excavated, collected, disturbed or exported..."></textarea>
            </div>

            <div class="row">
              <div class="col-md-5 mb-3">
                <label class="form-label">Site name</label>
                <input type="text" name="site_name" class="form-control">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Site location / coordinates</label>
                <input type="text" name="site_location" class="form-control">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Province</label>
                <input type="text" name="province" class="form-control">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Proposed start date</label>
                <input type="date" name="start_date" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Proposed end date</label>
                <input type="date" name="end_date" class="form-control">
              </div>
            </div>

            <h6 class="text-uppercase text-muted mt-3">Applicant &amp; supervisor</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Applicant name</label>
                <input type="text" name="applicant_name" class="form-control" value="<?php echo htmlspecialchars($currentUser->username ?? ''); ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Applicant email</label>
                <input type="email" name="applicant_email" class="form-control" value="<?php echo htmlspecialchars($currentUser->email ?? ''); ?>">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Institution / affiliation</label>
                <input type="text" name="institution" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Supervising professor <span class="text-danger">*</span></label>
                <select name="supervisor_user_id" class="form-select" required>
                  <option value="">-- Select supervisor --</option>
                  <?php foreach ($supervisors as $s): ?>
                    <option value="<?php echo $s->id; ?>"><?php echo htmlspecialchars($s->username); ?><?php echo $s->email ? ' (' . htmlspecialchars($s->email) . ')' : ''; ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Your supervisor endorses the application before it goes to SAHRA.</small>
              </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
              <a href="<?php echo url_for('@sahra_my'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
              <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Submit for endorsement</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
