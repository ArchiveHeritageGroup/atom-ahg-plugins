<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-camera fa-2x text-danger me-3"></i>
    <div>
      <h1 class="mb-0"><?php echo __('IPTC/XMP Metadata'); ?></h1>
      <span class="small text-muted"><?php echo esc_entities($resource->getTitle(['cultureFallback' => true])); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dam', 'action' => 'dashboard']); ?>"><?php echo __('DAM Dashboard'); ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('@slug?slug=' . $resource->slug); ?>"><?php echo esc_entities($resource->getTitle(['cultureFallback' => true])); ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('IPTC Metadata'); ?></li>
    </ol>
  </nav>

  <?php if ($digitalObject): ?>
  <div class="mb-3">
    <button type="button" class="btn btn-info" id="extractMetadataBtn">
      <i class="fas fa-magic"></i> <?php echo __('Extract metadata from file'); ?>
    </button>
    <small class="text-muted ms-2"><?php echo __('Reads EXIF/IPTC/XMP from the uploaded image'); ?></small>
  </div>
  <?php endif; ?>

  <form method="post" action="<?php echo url_for(['module' => 'dam', 'action' => 'editIptc', 'slug' => $resource->slug]); ?>">
    
    <!-- Creator Information -->
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-user"></i> <?php echo __('Creator / Photographer'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Creator / Photographer'); ?></label>
            <input type="text" class="form-control" name="creator" value="<?php echo esc_entities($iptc->creator ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Job Title'); ?></label>
            <input type="text" class="form-control" name="creator_job_title" value="<?php echo esc_entities($iptc->creator_job_title ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Email'); ?></label>
            <input type="email" class="form-control" name="creator_email" value="<?php echo esc_entities($iptc->creator_email ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Website'); ?></label>
            <input type="url" class="form-control" name="creator_website" value="<?php echo esc_entities($iptc->creator_website ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Phone'); ?></label>
            <input type="text" class="form-control" name="creator_phone" value="<?php echo esc_entities($iptc->creator_phone ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('City'); ?></label>
            <input type="text" class="form-control" name="creator_city" value="<?php echo esc_entities($iptc->creator_city ?? ''); ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Address'); ?></label>
          <textarea class="form-control" name="creator_address" rows="2"><?php echo esc_entities($iptc->creator_address ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Content Description -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white">
        <i class="fas fa-align-left"></i> <?php echo __('Content Description'); ?>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label"><?php echo __('Headline'); ?> <small class="text-muted">(<?php echo __('Brief synopsis'); ?>)</small></label>
          <input type="text" class="form-control" name="headline" value="<?php echo esc_entities($iptc->headline ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Caption / Description'); ?></label>
          <textarea class="form-control" name="caption" rows="4"><?php echo esc_entities($iptc->caption ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Keywords'); ?> <small class="text-muted">(<?php echo __('Comma-separated'); ?>)</small></label>
          <input type="text" class="form-control" name="keywords" value="<?php echo esc_entities($iptc->keywords ?? ''); ?>">
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('IPTC Subject Code'); ?></label>
            <input type="text" class="form-control" name="iptc_subject_code" value="<?php echo esc_entities($iptc->iptc_subject_code ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Intellectual Genre'); ?></label>
            <input type="text" class="form-control" name="intellectual_genre" value="<?php echo esc_entities($iptc->intellectual_genre ?? ''); ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Persons Shown'); ?> <small class="text-muted">(<?php echo __('Names of people in image'); ?>)</small></label>
          <input type="text" class="form-control" name="persons_shown" value="<?php echo esc_entities($iptc->persons_shown ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Location -->
    <div class="card mb-3">
      <div class="card-header bg-info text-white">
        <i class="fas fa-map-marker-alt"></i> <?php echo __('Location'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Date Created'); ?></label>
            <input type="date" class="form-control" name="date_created" value="<?php echo esc_entities($iptc->date_created ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('City'); ?></label>
            <input type="text" class="form-control" name="city" value="<?php echo esc_entities($iptc->city ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('State / Province'); ?></label>
            <input type="text" class="form-control" name="state_province" value="<?php echo esc_entities($iptc->state_province ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Country'); ?></label>
            <input type="text" class="form-control" name="country" value="<?php echo esc_entities($iptc->country ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Country Code'); ?> <small class="text-muted">(ISO 3166)</small></label>
            <input type="text" class="form-control" name="country_code" maxlength="3" value="<?php echo esc_entities($iptc->country_code ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Sublocation'); ?></label>
            <input type="text" class="form-control" name="sublocation" value="<?php echo esc_entities($iptc->sublocation ?? ''); ?>">
          </div>
        </div>
        <?php if ($iptc->gps_latitude && $iptc->gps_longitude): ?>
        <div class="alert alert-info">
          <i class="fas fa-globe"></i> 
          <?php echo __('GPS Coordinates'); ?>: <?php echo $iptc->gps_latitude; ?>, <?php echo $iptc->gps_longitude; ?>
          <a href="https://www.google.com/maps?q=<?php echo $iptc->gps_latitude; ?>,<?php echo $iptc->gps_longitude; ?>" target="_blank" class="ms-2">
            <i class="fas fa-external-link-alt"></i> <?php echo __('View on map'); ?>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Copyright & Rights -->
    <div class="card mb-3">
      <div class="card-header bg-warning">
        <i class="fas fa-copyright"></i> <?php echo __('Copyright & Rights'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Credit Line'); ?></label>
            <input type="text" class="form-control" name="credit_line" value="<?php echo esc_entities($iptc->credit_line ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Source'); ?></label>
            <input type="text" class="form-control" name="source" value="<?php echo esc_entities($iptc->source ?? ''); ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Copyright Notice'); ?></label>
          <input type="text" class="form-control" name="copyright_notice" value="<?php echo esc_entities($iptc->copyright_notice ?? ''); ?>" placeholder="© 2024 Photographer Name. All rights reserved.">
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Rights Usage Terms'); ?></label>
          <textarea class="form-control" name="rights_usage_terms" rows="2"><?php echo esc_entities($iptc->rights_usage_terms ?? ''); ?></textarea>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('License Type'); ?></label>
            <select class="form-select" name="license_type">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <option value="rights_managed" <?php echo ($iptc->license_type ?? '') == 'rights_managed' ? 'selected' : ''; ?>><?php echo __('Rights Managed'); ?></option>
              <option value="royalty_free" <?php echo ($iptc->license_type ?? '') == 'royalty_free' ? 'selected' : ''; ?>><?php echo __('Royalty Free'); ?></option>
              <option value="creative_commons" <?php echo ($iptc->license_type ?? '') == 'creative_commons' ? 'selected' : ''; ?>><?php echo __('Creative Commons'); ?></option>
              <option value="public_domain" <?php echo ($iptc->license_type ?? '') == 'public_domain' ? 'selected' : ''; ?>><?php echo __('Public Domain'); ?></option>
              <option value="editorial" <?php echo ($iptc->license_type ?? '') == 'editorial' ? 'selected' : ''; ?>><?php echo __('Editorial Use Only'); ?></option>
              <option value="other" <?php echo ($iptc->license_type ?? '') == 'other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('License URL'); ?></label>
            <input type="url" class="form-control" name="license_url" value="<?php echo esc_entities($iptc->license_url ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('License Expiry'); ?></label>
            <input type="date" class="form-control" name="license_expiry" value="<?php echo esc_entities($iptc->license_expiry ?? ''); ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Releases -->
    <div class="card mb-3">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-file-signature"></i> <?php echo __('Model & Property Releases'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><?php echo __('Model Release Status'); ?></label>
              <select class="form-select" name="model_release_status">
                <option value="none" <?php echo ($iptc->model_release_status ?? 'none') == 'none' ? 'selected' : ''; ?>><?php echo __('None'); ?></option>
                <option value="not_applicable" <?php echo ($iptc->model_release_status ?? '') == 'not_applicable' ? 'selected' : ''; ?>><?php echo __('Not Applicable'); ?></option>
                <option value="unlimited" <?php echo ($iptc->model_release_status ?? '') == 'unlimited' ? 'selected' : ''; ?>><?php echo __('Unlimited Model Releases'); ?></option>
                <option value="limited" <?php echo ($iptc->model_release_status ?? '') == 'limited' ? 'selected' : ''; ?>><?php echo __('Limited / Incomplete'); ?></option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label"><?php echo __('Model Release ID'); ?></label>
              <input type="text" class="form-control" name="model_release_id" value="<?php echo esc_entities($iptc->model_release_id ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label"><?php echo __('Property Release Status'); ?></label>
              <select class="form-select" name="property_release_status">
                <option value="none" <?php echo ($iptc->property_release_status ?? 'none') == 'none' ? 'selected' : ''; ?>><?php echo __('None'); ?></option>
                <option value="not_applicable" <?php echo ($iptc->property_release_status ?? '') == 'not_applicable' ? 'selected' : ''; ?>><?php echo __('Not Applicable'); ?></option>
                <option value="unlimited" <?php echo ($iptc->property_release_status ?? '') == 'unlimited' ? 'selected' : ''; ?>><?php echo __('Unlimited Property Releases'); ?></option>
                <option value="limited" <?php echo ($iptc->property_release_status ?? '') == 'limited' ? 'selected' : ''; ?>><?php echo __('Limited / Incomplete'); ?></option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label"><?php echo __('Property Release ID'); ?></label>
              <input type="text" class="form-control" name="property_release_id" value="<?php echo esc_entities($iptc->property_release_id ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Technical Metadata (read-only from EXIF) -->
    <?php if ($iptc->camera_make || $iptc->image_width): ?>
    <div class="card mb-3">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-cog"></i> <?php echo __('Technical Metadata'); ?> <small>(<?php echo __('extracted from file'); ?>)</small>
      </div>
      <div class="card-body">
        <div class="row">
          <?php if ($iptc->camera_make): ?>
          <div class="col-md-3 mb-2">
            <strong><?php echo __('Camera'); ?>:</strong><br>
            <?php echo esc_entities($iptc->camera_make . ' ' . $iptc->camera_model); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->lens): ?>
          <div class="col-md-3 mb-2">
            <strong><?php echo __('Lens'); ?>:</strong><br>
            <?php echo esc_entities($iptc->lens); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->focal_length): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Focal Length'); ?>:</strong><br>
            <?php echo esc_entities($iptc->focal_length); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->aperture): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Aperture'); ?>:</strong><br>
            <?php echo esc_entities($iptc->aperture); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->shutter_speed): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Shutter'); ?>:</strong><br>
            <?php echo esc_entities($iptc->shutter_speed); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->iso_speed): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('ISO'); ?>:</strong><br>
            <?php echo esc_entities($iptc->iso_speed); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->image_width && $iptc->image_height): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Dimensions'); ?>:</strong><br>
            <?php echo $iptc->image_width; ?> × <?php echo $iptc->image_height; ?>px
          </div>
          <?php endif; ?>
          <?php if ($iptc->color_space): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Color Space'); ?>:</strong><br>
            <?php echo esc_entities($iptc->color_space); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Artwork/Object (for reproductions) -->
    <div class="card mb-3">
      <div class="card-header" style="background-color: #6f42c1; color: white;">
        <i class="fas fa-palette"></i> <?php echo __('Artwork / Object in Image'); ?> <small>(<?php echo __('for reproductions'); ?>)</small>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Artwork Title'); ?></label>
            <input type="text" class="form-control" name="artwork_title" value="<?php echo esc_entities($iptc->artwork_title ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Artwork Creator'); ?></label>
            <input type="text" class="form-control" name="artwork_creator" value="<?php echo esc_entities($iptc->artwork_creator ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Artwork Date'); ?></label>
            <input type="text" class="form-control" name="artwork_date" value="<?php echo esc_entities($iptc->artwork_date ?? ''); ?>" placeholder="e.g., 1889 or circa 1920">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Artwork Source'); ?></label>
            <input type="text" class="form-control" name="artwork_source" value="<?php echo esc_entities($iptc->artwork_source ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Artwork Copyright'); ?></label>
            <input type="text" class="form-control" name="artwork_copyright" value="<?php echo esc_entities($iptc->artwork_copyright ?? ''); ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Administrative -->
    <div class="card mb-3">
      <div class="card-header bg-light">
        <i class="fas fa-tasks"></i> <?php echo __('Administrative'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Title / Object Name'); ?></label>
            <input type="text" class="form-control" name="iptc_title" value="<?php echo esc_entities($iptc->title ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Job / Assignment ID'); ?></label>
            <input type="text" class="form-control" name="job_id" value="<?php echo esc_entities($iptc->job_id ?? ''); ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Special Instructions'); ?></label>
          <textarea class="form-control" name="instructions" rows="2"><?php echo esc_entities($iptc->instructions ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-4">
      <a href="<?php echo url_for('@slug?slug=' . $resource->slug); ?>" class="btn btn-secondary">
        <?php echo __('Cancel'); ?>
      </a>
      <button type="submit" class="btn btn-success">
        <i class="fas fa-save"></i> <?php echo __('Save'); ?>
      </button>
    </div>
  </form>

  <script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
  document.getElementById('extractMetadataBtn')?.addEventListener('click', function() {
    if (!confirm('<?php echo __('This will overwrite existing metadata with values from the file. Continue?'); ?>')) {
      return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo __('Extracting...'); ?>';
    
    fetch('<?php echo url_for(['module' => 'dam', 'action' => 'extractMetadata', 'id' => $resource->id]); ?>')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert(data.error || '<?php echo __('Error extracting metadata'); ?>');
          this.disabled = false;
          this.innerHTML = '<i class="fas fa-magic"></i> <?php echo __('Extract metadata from file'); ?>';
        }
      })
      .catch(err => {
        alert('<?php echo __('Error extracting metadata'); ?>');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-magic"></i> <?php echo __('Extract metadata from file'); ?>';
      });
  });
  </script>
<?php end_slot(); ?>
