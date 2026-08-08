<?php use_helper('Date'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .sahra-max-height-220px-overflow-y--d8f2 { max-height:220px; overflow-y:auto; }
  .sahra-z-index-1000-max-height-280p-4549 { z-index:1000; max-height:280px; overflow-y:auto; }
</style>

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
            Complete this application and submit it to your supervisor for endorsement. Once endorsed,
            the archive's heritage coordinator lodges it with SAHRA (or the relevant provincial authority).
          </p>

          <form method="post" action="<?php echo url_for('@sahra_create'); ?>" enctype="multipart/form-data"
                data-search-url="<?php echo url_for('@sahra_search_sites'); ?>"
                data-areas-url="<?php echo url_for('@sahra_site_areas'); ?>">

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

            <!-- Site (catalogue record) + its dig areas -->
            <div class="mb-3 position-relative">
              <label class="form-label">Site (archaeological record)</label>
              <input type="text" class="form-control" id="sahra-site-search" autocomplete="off" placeholder="Type at least 2 characters of the site title...">
              <input type="hidden" name="site_object_id" id="sahra-site-id">
              <input type="hidden" name="site_name" id="sahra-site-name">
              <div id="sahra-site-results" class="list-group position-absolute w-100 shadow-sm d-none sahra-z-index-1000-max-height-280p-4549" ></div>
              <div id="sahra-site-chosen" class="form-text mt-2 d-none">
                <i class="fas fa-check-circle text-success me-1"></i><span id="sahra-site-chosen-title"></span>
                <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="sahra-site-clear">change</button>
              </div>
              <small class="form-text text-muted">Link the permit to the site record; its dig areas (child records) load below.</small>
            </div>

            <div id="sahra-areas-wrap" class="mb-3 d-none">
              <label class="form-label">Dig areas covered <small class="text-muted fw-normal">(child records of the site)</small></label>
              <div id="sahra-areas" class="border rounded p-2 sahra-max-height-220px-overflow-y--d8f2" ></div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Site location / coordinates</label>
                <input type="text" name="site_location" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
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
                <label class="form-label">Supervisor <span class="text-danger">*</span></label>
                <select name="supervisor_user_id" class="form-select" required>
                  <option value="">-- Select supervisor --</option>
                  <?php foreach ($supervisors as $s): ?>
                    <option value="<?php echo $s->id; ?>"><?php echo htmlspecialchars($s->username); ?><?php echo $s->email ? ' (' . htmlspecialchars($s->email) . ')' : ''; ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Your supervisor endorses the application before it goes to SAHRA.</small>
              </div>
            </div>

            <h6 class="text-uppercase text-muted mt-3">Supporting documents</h6>
            <div class="mb-3">
              <div id="sahra-doc-inputs">
                <div class="input-group mb-2 sahra-doc-row">
                  <input type="file" name="documents[]" class="form-control" multiple>
                  <button type="button" class="btn btn-outline-secondary sahra-doc-remove" title="Remove"><i class="fas fa-times"></i></button>
                </div>
              </div>
              <button type="button" id="sahra-doc-add" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus me-1"></i>Add another document</button>
              <small class="form-text text-muted d-block mt-1">Attach the SAHRA application form, method statement, CVs, existing permits, etc. (PDF / Word / Excel / images / zip, up to 25 MB each). Each field can also select several files at once. You can add more after submitting.</small>
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

<link rel="stylesheet" href="/plugins/ahgSAHRAPlugin/web/css/sahra.css">
<script src="/plugins/ahgSAHRAPlugin/web/js/sahra.js"></script>
