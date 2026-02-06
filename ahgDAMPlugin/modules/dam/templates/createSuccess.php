<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-plus-circle fa-2x text-success me-3"></i>
    <div>
      <h1 class="mb-0"><?php echo __('Create DAM Asset'); ?></h1>
      <span class="small text-muted"><?php echo __('Add a new digital asset with IPTC/XMP metadata'); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dam', 'action' => 'dashboard']); ?>"><?php echo __('DAM Dashboard'); ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('Create'); ?></li>
    </ol>
  </nav>

  <form method="post" action="<?php echo url_for(['module' => 'dam', 'action' => 'create']); ?>">
    
    <!-- Identification -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white">
        <i class="fas fa-id-card"></i> <?php echo __('Identification'); ?>
      </div>
      <div class="card-body">
        <?php echo get_component('informationobject', 'identifierGenerator', [
          'sector' => 'dam',
          'current_identifier' => '',
          'field_name' => 'identifier',
          'repository_id' => null,
        ]); ?>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="identifier" class="form-label"><?php echo __('Identifier / Reference Code'); ?></label>
            <input type="text" class="form-control" id="identifier" name="identifier" placeholder="e.g., DAM-2024-001">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="parent_id" class="form-label"><?php echo __('Parent Collection'); ?></label>
            <select class="form-select" id="parent_id" name="parent_id">
              <option value="1"><?php echo __('-- Top level (no parent) --'); ?></option>
              <?php foreach ($parents as $p): ?>
                <option value="<?php echo $p->id; ?>"><?php echo esc_entities($p->title ?: $p->identifier); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="repository_id" class="form-label"><?php echo __('Repository'); ?></label>
            <select class="form-select" id="repository_id" name="repository_id">
              <option value=""><?php echo __('-- Select repository --'); ?></option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo $repo->id; ?>"><?php echo esc_entities($repo->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="level_of_description_id" class="form-label"><?php echo __('Level of Description'); ?></label>
            <select class="form-select" id="level_of_description_id" name="level_of_description_id">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($levels as $id => $name): ?>
                <option value="<?php echo $id; ?>"><?php echo esc_entities($name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label for="scope_content" class="form-label"><?php echo __('Description / Scope and Content'); ?></label>
          <textarea class="form-control" id="scope_content" name="scope_content" rows="3"></textarea>
        </div>
      </div>
    </div>


    <!-- Asset Type & Classification -->
    <div class="card mb-3 border-primary">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-tag"></i> <?php echo __('Asset Type & Classification'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold"><?php echo __('Asset Type'); ?></label>
            <select class="form-select" name="asset_type" id="assetTypeSelect">
              <option value=""><?php echo __('-- Select Asset Type --'); ?></option>
              <optgroup label="<?php echo __('Image'); ?>">
                <option value="photo"><?php echo __('Photo / Image'); ?></option>
                <option value="artwork"><?php echo __('Artwork / Painting'); ?></option>
                <option value="scan"><?php echo __('Scan / Digitized'); ?></option>
              </optgroup>
              <optgroup label="<?php echo __('Video / Film'); ?>">
                <option value="documentary"><?php echo __('Documentary'); ?></option>
                <option value="feature"><?php echo __('Feature Film'); ?></option>
                <option value="short"><?php echo __('Short Film'); ?></option>
                <option value="news"><?php echo __('News / Footage'); ?></option>
                <option value="interview"><?php echo __('Interview'); ?></option>
                <option value="home_movie"><?php echo __('Home Movie'); ?></option>
              </optgroup>
              <optgroup label="<?php echo __('Audio'); ?>">
                <option value="oral_history"><?php echo __('Oral History'); ?></option>
                <option value="music"><?php echo __('Music Recording'); ?></option>
                <option value="podcast"><?php echo __('Podcast / Radio'); ?></option>
                <option value="speech"><?php echo __('Speech / Lecture'); ?></option>
              </optgroup>
              <optgroup label="<?php echo __('Document'); ?>">
                <option value="document"><?php echo __('Document / PDF'); ?></option>
                <option value="manuscript"><?php echo __('Manuscript'); ?></option>
              </optgroup>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold"><?php echo __('Genre'); ?></label>
            <input type="text" class="form-control" name="genre" placeholder="<?php echo __('e.g., Documentary, Portrait'); ?>">
          </div>
          <div class="col-md-4 mb-3 field-video field-audio" style="display:none;">
            <label class="form-label fw-bold"><?php echo __('Color Type'); ?></label>
            <select class="form-select" name="color_type">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <option value="color"><?php echo __('Color'); ?></option>
              <option value="black_and_white"><?php echo __('Black and White'); ?></option>
              <option value="mixed"><?php echo __('Mixed'); ?></option>
              <option value="colorized"><?php echo __('Colorized'); ?></option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Film/Video Production -->
    <div class="card mb-3 field-video" style="display:none;">
      <div class="card-header bg-danger text-white">
        <i class="fas fa-film"></i> <?php echo __('Production Details'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Production Company'); ?></label>
            <input type="text" class="form-control" name="production_company">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Distributor / Broadcaster'); ?></label>
            <input type="text" class="form-control" name="distributor">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Broadcast Date'); ?></label>
            <input type="date" class="form-control" name="broadcast_date">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Series Title'); ?></label>
            <input type="text" class="form-control" name="series_title">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Season'); ?></label>
            <input type="text" class="form-control" name="season_number">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Episode'); ?></label>
            <input type="text" class="form-control" name="episode_number">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Awards'); ?></label>
          <textarea class="form-control" name="awards" rows="2"></textarea>
        </div>
      </div>
    </div>

    <!-- Production Credits -->
    <div class="card mb-3 field-video field-audio" style="display:none;">
      <div class="card-header bg-info text-white">
        <i class="fas fa-users"></i> <?php echo __('Production Credits'); ?>
      </div>
      <div class="card-body">
        <div id="creditsContainer">
          <div class="row credit-row mb-2">
            <div class="col-md-4">
              <select class="form-select" name="credit_role[]">
                <option value=""><?php echo __('-- Role --'); ?></option>
                <option value="Director"><?php echo __('Director'); ?></option>
                <option value="Producer"><?php echo __('Producer'); ?></option>
                <option value="Writer"><?php echo __('Writer'); ?></option>
                <option value="Photography"><?php echo __('Photography'); ?></option>
                <option value="Editor"><?php echo __('Editor'); ?></option>
                <option value="Sound"><?php echo __('Sound'); ?></option>
                <option value="Music"><?php echo __('Music'); ?></option>
                <option value="Cast"><?php echo __('Cast'); ?></option>
                <option value="Sponsor"><?php echo __('Sponsor'); ?></option>
                <option value="Other"><?php echo __('Other'); ?></option>
              </select>
            </div>
            <div class="col-md-6">
              <input type="text" class="form-control" name="credit_name[]" placeholder="<?php echo __('Name'); ?>">
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-outline-danger btn-remove-credit"><i class="fas fa-times"></i></button>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addCreditBtn">
          <i class="fas fa-plus"></i> <?php echo __('Add Credit'); ?>
        </button>
      </div>
    </div>

    <!-- Language -->
    <div class="card mb-3 field-video field-audio" style="display:none;">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-language"></i> <?php echo __('Language'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Audio Language(s)'); ?></label>
            <input type="text" class="form-control" name="audio_language">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Subtitle Language(s)'); ?></label>
            <input type="text" class="form-control" name="subtitle_language">
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Creator -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#creatorSection" style="cursor:pointer;">
        <i class="fas fa-user"></i> <?php echo __('IPTC - Creator / Photographer'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="creatorSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Creator / Photographer'); ?></label>
              <input type="text" name="iptc_creator" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Job Title'); ?></label>
              <input type="text" name="iptc_creator_job_title" class="form-control">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" name="iptc_creator_email" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Phone'); ?></label>
              <input type="text" name="iptc_creator_phone" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Website'); ?></label>
              <input type="text" name="iptc_creator_website" class="form-control">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('City'); ?></label>
              <input type="text" name="iptc_creator_city" class="form-control">
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label"><?php echo __('Address'); ?></label>
              <input type="text" name="iptc_creator_address" class="form-control">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Content -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#contentSection" style="cursor:pointer;">
        <i class="fas fa-file-alt"></i> <?php echo __('IPTC - Content Description'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="contentSection">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Headline'); ?></label>
            <input type="text" name="iptc_headline" class="form-control">
            <small class="text-muted"><?php echo __('Brief synopsis or summary'); ?></small>
          </div>
          <div class="mb-3 field-video field-audio" style="display:none;">
            <label class="form-label"><?php echo __('Running Time'); ?> <small class="text-muted">(<?php echo __('Minutes'); ?>)</small></label>
            <div class="input-group" style="max-width: 200px;">
              <input type="number" class="form-control" name="iptc_duration_minutes" min="1">
              <span class="input-group-text"><?php echo __('min'); ?></span>
            </div>
            <small class="text-muted"><?php echo __('Round to nearest minute'); ?></small>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Caption / Description'); ?></label>
            <textarea name="iptc_caption" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Keywords'); ?></label>
            <input type="text" name="iptc_keywords" class="form-control" placeholder="<?php echo __('Comma-separated'); ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('IPTC Subject Code'); ?></label>
              <input type="text" name="iptc_subject_code" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Intellectual Genre'); ?></label>
              <input type="text" name="iptc_intellectual_genre" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Persons Shown'); ?></label>
            <input type="text" name="iptc_persons_shown" class="form-control">
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Location -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#locationSection" style="cursor:pointer;">
        <i class="fas fa-map-marker-alt"></i> <?php echo __('IPTC - Location'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="locationSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Date Created'); ?></label>
              <input type="date" name="iptc_date_created" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('City'); ?></label>
              <input type="text" name="iptc_city" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('State / Province'); ?></label>
              <input type="text" name="iptc_state_province" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Sublocation'); ?></label>
              <input type="text" name="iptc_sublocation" class="form-control">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Country (Filming Location)'); ?></label>
              <input type="text" name="iptc_country" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Country Code'); ?></label>
              <input type="text" name="iptc_country_code" class="form-control" maxlength="3" placeholder="ISO 3166-1 alpha-3">
            </div>
          </div>
          <div class="row field-video field-audio" style="display:none;">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Production Country'); ?></label>
              <input type="text" name="iptc_production_country" class="form-control" placeholder="e.g., Netherlands, South Africa">
              <small class="text-muted"><?php echo __('Country where film/video was produced (may differ from filming location)'); ?></small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Production Country Code'); ?></label>
              <input type="text" name="iptc_production_country_code" class="form-control" maxlength="3" placeholder="e.g., NLD, ZAF">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Copyright -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#copyrightSection" style="cursor:pointer;">
        <i class="fas fa-copyright"></i> <?php echo __('IPTC - Copyright & Rights'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="copyrightSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Credit Line'); ?></label>
              <input type="text" name="iptc_credit_line" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Source'); ?></label>
              <input type="text" name="iptc_source" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Copyright Notice'); ?></label>
            <input type="text" name="iptc_copyright_notice" class="form-control" placeholder="© 2024 Photographer Name">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Rights Usage Terms'); ?></label>
            <textarea name="iptc_rights_usage_terms" class="form-control" rows="2"></textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('License Type'); ?></label>
              <select name="iptc_license_type" class="form-select">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <option value="rights_managed"><?php echo __('Rights Managed'); ?></option>
                <option value="royalty_free"><?php echo __('Royalty Free'); ?></option>
                <option value="creative_commons"><?php echo __('Creative Commons'); ?></option>
                <option value="public_domain"><?php echo __('Public Domain'); ?></option>
                <option value="editorial"><?php echo __('Editorial Use Only'); ?></option>
                <option value="other"><?php echo __('Other'); ?></option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('License URL'); ?></label>
              <input type="text" name="iptc_license_url" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('License Expiry'); ?></label>
              <input type="date" name="iptc_license_expiry" class="form-control">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Releases -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#releasesSection" style="cursor:pointer;">
        <i class="fas fa-file-signature"></i> <?php echo __('IPTC - Model & Property Releases'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="releasesSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Model Release Status'); ?></label>
                <select name="iptc_model_release_status" class="form-select">
                  <option value="none"><?php echo __('None'); ?></option>
                  <option value="not_applicable"><?php echo __('Not Applicable'); ?></option>
                  <option value="unlimited"><?php echo __('Unlimited Model Releases'); ?></option>
                  <option value="limited"><?php echo __('Limited / Incomplete'); ?></option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Model Release ID'); ?></label>
                <input type="text" name="iptc_model_release_id" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Property Release Status'); ?></label>
                <select name="iptc_property_release_status" class="form-select">
                  <option value="none"><?php echo __('None'); ?></option>
                  <option value="not_applicable"><?php echo __('Not Applicable'); ?></option>
                  <option value="unlimited"><?php echo __('Unlimited Property Releases'); ?></option>
                  <option value="limited"><?php echo __('Limited / Incomplete'); ?></option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Property Release ID'); ?></label>
                <input type="text" name="iptc_property_release_id" class="form-control">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Artwork -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#artworkSection" style="cursor:pointer;">
        <i class="fas fa-palette"></i> <?php echo __('IPTC - Artwork / Object in Image'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="artworkSection">
        <div class="card-body">
          <p class="text-muted small"><?php echo __('For reproductions of artworks or physical objects'); ?></p>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Artwork Title'); ?></label>
              <input type="text" name="iptc_artwork_title" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Artwork Creator'); ?></label>
              <input type="text" name="iptc_artwork_creator" class="form-control">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Artwork Date'); ?></label>
              <input type="text" name="iptc_artwork_date" class="form-control" placeholder="e.g., 1889">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Artwork Source'); ?></label>
              <input type="text" name="iptc_artwork_source" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Artwork Copyright'); ?></label>
              <input type="text" name="iptc_artwork_copyright" class="form-control">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Administrative -->
    <div class="card mb-3">
      <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#adminSection" style="cursor:pointer;">
        <i class="fas fa-cogs"></i> <?php echo __('IPTC - Administrative'); ?>
        <i class="fas fa-chevron-down float-end"></i>
      </div>
      <div class="collapse" id="adminSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Title / Object Name'); ?></label>
              <input type="text" name="iptc_title" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Job / Assignment ID'); ?></label>
              <input type="text" name="iptc_job_id" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Special Instructions'); ?></label>
            <textarea name="iptc_instructions" class="form-control" rows="2"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2 mt-4">
      <button type="submit" class="btn btn-success btn-lg">
        <i class="fas fa-save"></i> <?php echo __('Create Asset'); ?>
      </button>
      <a href="<?php echo url_for(['module' => 'dam', 'action' => 'dashboard']); ?>" class="btn btn-secondary btn-lg">
        <?php echo __('Cancel'); ?>
      </a>
    </div>
  </form>

  <div class="alert alert-info mt-4">
    <i class="fas fa-info-circle"></i>
    <?php echo __('After creating the asset, you can attach digital files (images, videos, documents) through the standard AtoM interface.'); ?>
  </div>

  <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  (function() {
    const fieldGroups = {
      photo: ['field-photo'],
      artwork: ['field-photo', 'field-artwork'],
      scan: ['field-photo'],
      documentary: ['field-video'],
      feature: ['field-video'],
      short: ['field-video'],
      news: ['field-video'],
      interview: ['field-video', 'field-audio'],
      home_movie: ['field-video'],
      oral_history: ['field-audio'],
      music: ['field-audio'],
      podcast: ['field-audio'],
      speech: ['field-audio'],
      document: [],
      manuscript: []
    };

    var assetSelect = document.getElementById('assetTypeSelect');
    
    function updateFieldVisibility() {
      var assetType = assetSelect ? assetSelect.value : '';
      var els = document.querySelectorAll('.field-photo, .field-video, .field-audio, .field-artwork');
      for (var i = 0; i < els.length; i++) {
        els[i].style.display = 'none';
      }
      if (assetType && fieldGroups[assetType]) {
        var groups = fieldGroups[assetType];
        for (var j = 0; j < groups.length; j++) {
          var clsEls = document.querySelectorAll('.' + groups[j]);
          for (var k = 0; k < clsEls.length; k++) {
            clsEls[k].style.display = '';
          }
        }
      }
    }

    if (assetSelect) {
      assetSelect.addEventListener('change', updateFieldVisibility);
      updateFieldVisibility();
    }

    // Credits management
    var creditsContainer = document.getElementById('creditsContainer');
    var addCreditBtn = document.getElementById('addCreditBtn');
    
    if (addCreditBtn && creditsContainer) {
      addCreditBtn.addEventListener('click', function() {
        var row = document.createElement('div');
        row.className = 'row credit-row mb-2';
        row.innerHTML = '<div class="col-md-4"><select class="form-select" name="credit_role[]"><option value="">-- Role --</option><option value="Director">Director</option><option value="Producer">Producer</option><option value="Writer">Writer</option><option value="Photography">Photography</option><option value="Editor">Editor</option><option value="Sound">Sound</option><option value="Music">Music</option><option value="Cast">Cast</option><option value="Sponsor">Sponsor</option><option value="Other">Other</option></select></div><div class="col-md-6"><input type="text" class="form-control" name="credit_name[]" placeholder="Name"></div><div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-remove-credit"><i class="fas fa-times"></i></button></div>';
        creditsContainer.appendChild(row);
      });

      creditsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-credit')) {
          var row = e.target.closest('.credit-row');
          if (creditsContainer.querySelectorAll('.credit-row').length > 1) {
            row.remove();
          } else {
            var inputs = row.querySelectorAll('input, select');
            for (var i = 0; i < inputs.length; i++) {
              inputs[i].value = '';
            }
          }
        }
      });
    }
  })();
  </script>
<?php end_slot(); ?>
