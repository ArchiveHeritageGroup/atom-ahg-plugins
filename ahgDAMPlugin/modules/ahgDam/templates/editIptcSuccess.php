<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-layer-group fa-2x text-danger me-3"></i>
    <div>
      <h1 class="mb-0"><?php echo __('DAM Metadata'); ?></h1>
      <span class="small text-muted"><?php echo esc_entities($resource->title ?? $resource->slug); ?></span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dam', 'action' => 'dashboard']); ?>"><?php echo __('DAM Dashboard'); ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('@slug?slug=' . $resource->slug); ?>"><?php echo esc_entities($resource->title ?? $resource->slug); ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('DAM Metadata'); ?></li>
    </ol>
  </nav>

  <?php if ($digitalObject): ?>
  <div class="mb-3">
    <button type="button" class="btn btn-info" id="extractMetadataBtn">
      <i class="fas fa-magic"></i> <?php echo __('Extract metadata from file'); ?>
    </button>
    <small class="text-muted ms-2"><?php echo __('Reads EXIF/IPTC/XMP/FFmpeg metadata from the uploaded file'); ?></small>
  </div>
  <?php endif; ?>

  <form method="post" action="<?php echo url_for(['module' => 'dam', 'action' => 'editIptc', 'slug' => $resource->slug]); ?>" id="damMetadataForm">

    <!-- Asset Type Selector (Controls which fields show) -->
    <div class="card mb-3 border-primary">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-tag"></i> <?php echo __('Asset Type'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold"><?php echo __('Asset Type'); ?> <span class="text-danger">*</span></label>
            <select class="form-select form-select-lg" name="asset_type" id="assetTypeSelect">
              <option value=""><?php echo __('-- Select Asset Type --'); ?></option>
              <optgroup label="<?php echo __('Image'); ?>">
                <option value="photo" <?php echo ($iptc->asset_type ?? '') == 'photo' ? 'selected' : ''; ?>><?php echo __('Photo / Image'); ?></option>
                <option value="artwork" <?php echo ($iptc->asset_type ?? '') == 'artwork' ? 'selected' : ''; ?>><?php echo __('Artwork / Painting'); ?></option>
                <option value="scan" <?php echo ($iptc->asset_type ?? '') == 'scan' ? 'selected' : ''; ?>><?php echo __('Scan / Digitized'); ?></option>
              </optgroup>
              <optgroup label="<?php echo __('Video / Film'); ?>">
                <option value="documentary" <?php echo ($iptc->asset_type ?? '') == 'documentary' ? 'selected' : ''; ?>><?php echo __('Documentary'); ?></option>
                <option value="feature" <?php echo ($iptc->asset_type ?? '') == 'feature' ? 'selected' : ''; ?>><?php echo __('Feature Film'); ?></option>
                <option value="short" <?php echo ($iptc->asset_type ?? '') == 'short' ? 'selected' : ''; ?>><?php echo __('Short Film'); ?></option>
                <option value="news" <?php echo ($iptc->asset_type ?? '') == 'news' ? 'selected' : ''; ?>><?php echo __('News / Footage'); ?></option>
                <option value="interview" <?php echo ($iptc->asset_type ?? '') == 'interview' ? 'selected' : ''; ?>><?php echo __('Interview'); ?></option>
                <option value="home_movie" <?php echo ($iptc->asset_type ?? '') == 'home_movie' ? 'selected' : ''; ?>><?php echo __('Home Movie'); ?></option>
              </optgroup>
              <optgroup label="<?php echo __('Audio'); ?>">
                <option value="oral_history" <?php echo ($iptc->asset_type ?? '') == 'oral_history' ? 'selected' : ''; ?>><?php echo __('Oral History'); ?></option>
                <option value="music" <?php echo ($iptc->asset_type ?? '') == 'music' ? 'selected' : ''; ?>><?php echo __('Music Recording'); ?></option>
                <option value="podcast" <?php echo ($iptc->asset_type ?? '') == 'podcast' ? 'selected' : ''; ?>><?php echo __('Podcast / Radio'); ?></option>
                <option value="speech" <?php echo ($iptc->asset_type ?? '') == 'speech' ? 'selected' : ''; ?>><?php echo __('Speech / Lecture'); ?></option>
              </optgroup>
              <optgroup label="<?php echo __('Document'); ?>">
                <option value="document" <?php echo ($iptc->asset_type ?? '') == 'document' ? 'selected' : ''; ?>><?php echo __('Document / PDF'); ?></option>
                <option value="manuscript" <?php echo ($iptc->asset_type ?? '') == 'manuscript' ? 'selected' : ''; ?>><?php echo __('Manuscript'); ?></option>
              </optgroup>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold"><?php echo __('Genre'); ?></label>
            <input type="text" class="form-control" name="genre" id="genreInput" value="<?php echo esc_entities($iptc->genre ?? ''); ?>" placeholder="<?php echo __('e.g., Documentary, Drama, Portrait'); ?>">
          </div>
          <div class="col-md-4 mb-3 field-video field-audio" style="display:none;">
            <label class="form-label fw-bold"><?php echo __('Color Type'); ?></label>
            <select class="form-select" name="color_type">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <option value="color" <?php echo ($iptc->color_type ?? '') == 'color' ? 'selected' : ''; ?>><?php echo __('Color'); ?></option>
              <option value="black_and_white" <?php echo ($iptc->color_type ?? '') == 'black_and_white' ? 'selected' : ''; ?>><?php echo __('Black & White'); ?></option>
              <option value="mixed" <?php echo ($iptc->color_type ?? '') == 'mixed' ? 'selected' : ''; ?>><?php echo __('Mixed'); ?></option>
              <option value="colorized" <?php echo ($iptc->color_type ?? '') == 'colorized' ? 'selected' : ''; ?>><?php echo __('Colorized'); ?></option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Creator Information -->
    <div class="card mb-3">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-user"></i> <span class="creator-label"><?php echo __('Creator / Photographer'); ?></span>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label creator-label"><?php echo __('Creator / Photographer'); ?></label>
            <input type="text" class="form-control" name="creator" value="<?php echo esc_entities($iptc->creator ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label creator-job-label"><?php echo __('Job Title'); ?></label>
            <input type="text" class="form-control" name="creator_job_title" value="<?php echo esc_entities($iptc->creator_job_title ?? ''); ?>">
          </div>
        </div>
        <div class="row field-photo">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Email'); ?></label>
            <input type="email" class="form-control" name="creator_email" value="<?php echo esc_entities($iptc->creator_email ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Website'); ?></label>
            <input type="url" class="form-control" name="creator_website" value="<?php echo esc_entities($iptc->creator_website ?? ''); ?>">
          </div>
        </div>
        <div class="row field-photo">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Phone'); ?></label>
            <input type="text" class="form-control" name="creator_phone" value="<?php echo esc_entities($iptc->creator_phone ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('City'); ?></label>
            <input type="text" class="form-control" name="creator_city" value="<?php echo esc_entities($iptc->creator_city ?? ''); ?>">
          </div>
        </div>
        <div class="mb-3 field-photo">
          <label class="form-label"><?php echo __('Address'); ?></label>
          <textarea class="form-control" name="creator_address" rows="2"><?php echo esc_entities($iptc->creator_address ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Film/Video Production (Only for video types) -->
    <div class="card mb-3 field-video" style="display:none;">
      <div class="card-header bg-danger text-white">
        <i class="fas fa-film"></i> <?php echo __('Production Details'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Production Company'); ?></label>
            <input type="text" class="form-control" name="production_company" value="<?php echo esc_entities($iptc->production_company ?? ''); ?>" placeholder="<?php echo __('e.g., African Film Productions'); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Distributor / Broadcaster'); ?></label>
            <input type="text" class="form-control" name="distributor" value="<?php echo esc_entities($iptc->distributor ?? ''); ?>" placeholder="<?php echo __('e.g., VPRO, SABC'); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Broadcast / Release Date'); ?></label>
            <input type="date" class="form-control" name="broadcast_date" value="<?php echo esc_entities($iptc->broadcast_date ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Series Title'); ?></label>
            <input type="text" class="form-control" name="series_title" value="<?php echo esc_entities($iptc->series_title ?? ''); ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Season'); ?></label>
            <input type="text" class="form-control" name="season_number" value="<?php echo esc_entities($iptc->season_number ?? ''); ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Episode'); ?></label>
            <input type="text" class="form-control" name="episode_number" value="<?php echo esc_entities($iptc->episode_number ?? ''); ?>">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Awards & Nominations'); ?></label>
          <textarea class="form-control" name="awards" rows="2" placeholder="<?php echo __('e.g., Nominated for Golden Calf Award - Best Short Documentary 2006'); ?>"><?php echo esc_entities($iptc->awards ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Production Credits (Video/Audio) -->
    <div class="card mb-3 field-video field-audio" style="display:none;">
      <div class="card-header bg-info text-white">
        <i class="fas fa-users"></i> <?php echo __('Production Credits'); ?>
      </div>
      <div class="card-body">
        <div id="creditsContainer">
          <?php 
          $contributors = json_decode($iptc->contributors_json ?? '[]', true) ?: [];
          if (empty($contributors)) {
            $contributors = [['role' => '', 'name' => '']];
          }
          foreach ($contributors as $index => $contributor): 
          ?>
          <div class="row credit-row mb-2">
            <div class="col-md-4">
              <select class="form-select" name="credit_role[]">
                <option value=""><?php echo __('-- Role --'); ?></option>
                <option value="Director" <?php echo ($contributor['role'] ?? '') == 'Director' ? 'selected' : ''; ?>><?php echo __('Director'); ?></option>
                <option value="Producer" <?php echo ($contributor['role'] ?? '') == 'Producer' ? 'selected' : ''; ?>><?php echo __('Producer'); ?></option>
                <option value="Executive Producer" <?php echo ($contributor['role'] ?? '') == 'Executive Producer' ? 'selected' : ''; ?>><?php echo __('Executive Producer'); ?></option>
                <option value="Writer" <?php echo ($contributor['role'] ?? '') == 'Writer' ? 'selected' : ''; ?>><?php echo __('Writer'); ?></option>
                <option value="Screenplay" <?php echo ($contributor['role'] ?? '') == 'Screenplay' ? 'selected' : ''; ?>><?php echo __('Screenplay'); ?></option>
                <option value="Photography" <?php echo ($contributor['role'] ?? '') == 'Photography' ? 'selected' : ''; ?>><?php echo __('Photography / Cinematography'); ?></option>
                <option value="Camera" <?php echo ($contributor['role'] ?? '') == 'Camera' ? 'selected' : ''; ?>><?php echo __('Camera'); ?></option>
                <option value="Editor" <?php echo ($contributor['role'] ?? '') == 'Editor' ? 'selected' : ''; ?>><?php echo __('Editor'); ?></option>
                <option value="Sound" <?php echo ($contributor['role'] ?? '') == 'Sound' ? 'selected' : ''; ?>><?php echo __('Sound'); ?></option>
                <option value="Sound Design" <?php echo ($contributor['role'] ?? '') == 'Sound Design' ? 'selected' : ''; ?>><?php echo __('Sound Design & Mix'); ?></option>
                <option value="Music" <?php echo ($contributor['role'] ?? '') == 'Music' ? 'selected' : ''; ?>><?php echo __('Music / Composer'); ?></option>
                <option value="Narrator" <?php echo ($contributor['role'] ?? '') == 'Narrator' ? 'selected' : ''; ?>><?php echo __('Narrator'); ?></option>
                <option value="Presenter" <?php echo ($contributor['role'] ?? '') == 'Presenter' ? 'selected' : ''; ?>><?php echo __('Presenter / Host'); ?></option>
                <option value="Interviewer" <?php echo ($contributor['role'] ?? '') == 'Interviewer' ? 'selected' : ''; ?>><?php echo __('Interviewer'); ?></option>
                <option value="Interviewee" <?php echo ($contributor['role'] ?? '') == 'Interviewee' ? 'selected' : ''; ?>><?php echo __('Interviewee / Subject'); ?></option>
                <option value="Cast" <?php echo ($contributor['role'] ?? '') == 'Cast' ? 'selected' : ''; ?>><?php echo __('Cast / Actor'); ?></option>
                <option value="Commissioning Editor" <?php echo ($contributor['role'] ?? '') == 'Commissioning Editor' ? 'selected' : ''; ?>><?php echo __('Commissioning Editor'); ?></option>
                <option value="Sponsor" <?php echo ($contributor['role'] ?? '') == 'Sponsor' ? 'selected' : ''; ?>><?php echo __('Sponsor'); ?></option>
                <option value="Other" <?php echo ($contributor['role'] ?? '') == 'Other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
              </select>
            </div>
            <div class="col-md-6">
              <input type="text" class="form-control" name="credit_name[]" value="<?php echo esc_entities($contributor['name'] ?? ''); ?>" placeholder="<?php echo __('Name'); ?>">
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-outline-danger btn-remove-credit"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addCreditBtn">
          <i class="fas fa-plus"></i> <?php echo __('Add Credit'); ?>
        </button>
      </div>
    </div>

    <!-- Audio/Video Language -->
    <div class="card mb-3 field-video field-audio" style="display:none;">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-language"></i> <?php echo __('Language'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Audio Language(s)'); ?></label>
            <input type="text" class="form-control" name="audio_language" value="<?php echo esc_entities($iptc->audio_language ?? ''); ?>" placeholder="<?php echo __('e.g., Afrikaans, Dutch, English'); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Subtitle Language(s)'); ?></label>
            <input type="text" class="form-control" name="subtitle_language" value="<?php echo esc_entities($iptc->subtitle_language ?? ''); ?>" placeholder="<?php echo __('e.g., English'); ?>">
          </div>
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
          <label class="form-label"><?php echo __('Running Time'); ?> <small class="text-muted">(<?php echo __('Minutes, for video/audio'); ?>)</small></label>
          <div class="input-group" style="max-width: 200px;">
            <input type="number" class="form-control" name="duration_minutes" min="1" value="<?php echo esc_entities($iptc->duration_minutes ?? ''); ?>">
            <span class="input-group-text"><?php echo __('min'); ?></span>
          </div>
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
          <label class="form-label"><?php echo __('Persons Shown / Featured'); ?> <small class="text-muted">(<?php echo __('Names of people'); ?>)</small></label>
          <input type="text" class="form-control" name="persons_shown" value="<?php echo esc_entities($iptc->persons_shown ?? ''); ?>">
        </div>
      </div>
    </div>

    <!-- Location -->
    <div class="card mb-3">
      <div class="card-header bg-warning">
        <i class="fas fa-map-marker-alt"></i> <?php echo __('Location & Date'); ?>
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
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Production Country'); ?> <small class="text-muted">(<?php echo __('Where film/video was produced'); ?>)</small></label>
            <input type="text" class="form-control" name="production_country" value="<?php echo esc_entities($iptc->production_country ?? ''); ?>" placeholder="e.g., Netherlands, South Africa">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Production Country Code'); ?> <small class="text-muted">(ISO 3166)</small></label>
            <input type="text" class="form-control" name="production_country_code" maxlength="3" value="<?php echo esc_entities($iptc->production_country_code ?? ''); ?>" placeholder="e.g., NLD, ZAF">
          </div>
        </div>
        <?php if (($iptc->gps_latitude ?? null) && ($iptc->gps_longitude ?? null)): ?>
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
      <div class="card-header bg-dark text-white">
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
          <input type="text" class="form-control" name="copyright_notice" value="<?php echo esc_entities($iptc->copyright_notice ?? ''); ?>" placeholder="© 2024 Name. All rights reserved.">
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

    <!-- Releases (Photo-specific) -->
    <div class="card mb-3 field-photo">
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

    <!-- Technical Metadata (read-only from EXIF/FFmpeg) -->
    <?php if (($iptc->camera_make ?? null) || ($iptc->image_width ?? null)): ?>
    <div class="card mb-3 field-photo">
      <div class="card-header bg-light">
        <i class="fas fa-camera"></i> <?php echo __('Technical Metadata'); ?> <small>(<?php echo __('extracted from file'); ?>)</small>
      </div>
      <div class="card-body">
        <div class="row">
          <?php if ($iptc->camera_make ?? null): ?>
          <div class="col-md-3 mb-2">
            <strong><?php echo __('Camera'); ?>:</strong><br>
            <?php echo esc_entities($iptc->camera_make . ' ' . ($iptc->camera_model ?? '')); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->lens ?? null): ?>
          <div class="col-md-3 mb-2">
            <strong><?php echo __('Lens'); ?>:</strong><br>
            <?php echo esc_entities($iptc->lens); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->focal_length ?? null): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Focal Length'); ?>:</strong><br>
            <?php echo esc_entities($iptc->focal_length); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->aperture ?? null): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Aperture'); ?>:</strong><br>
            <?php echo esc_entities($iptc->aperture); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->shutter_speed ?? null): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Shutter'); ?>:</strong><br>
            <?php echo esc_entities($iptc->shutter_speed); ?>
          </div>
          <?php endif; ?>
          <?php if ($iptc->iso_speed ?? null): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('ISO'); ?>:</strong><br>
            <?php echo esc_entities($iptc->iso_speed); ?>
          </div>
          <?php endif; ?>
          <?php if (($iptc->image_width ?? null) && ($iptc->image_height ?? null)): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Dimensions'); ?>:</strong><br>
            <?php echo $iptc->image_width; ?> × <?php echo $iptc->image_height; ?>px
          </div>
          <?php endif; ?>
          <?php if ($iptc->color_space ?? null): ?>
          <div class="col-md-2 mb-2">
            <strong><?php echo __('Color Space'); ?>:</strong><br>
            <?php echo esc_entities($iptc->color_space); ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Artwork/Object (for reproductions - photo only) -->
    <div class="card mb-3 field-photo field-artwork">
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

    <!-- Alternative Versions -->
    <div class="card mb-3">
      <div class="card-header" style="background-color: #17a2b8; color: white;">
        <i class="fas fa-language"></i> <?php echo __('Alternative Versions'); ?>
      </div>
      <div class="card-body">
        <p class="text-muted small"><?php echo __('Other language versions, formats, or edits of this work'); ?></p>
        <div id="versions-container">
          <?php
          $versions = \Illuminate\Database\Capsule\Manager::table('dam_version_links')
              ->where('object_id', $resource->id)->get();
          foreach ($versions as $v): ?>
          <div class="row mb-2 version-row border-bottom pb-2">
            <input type="hidden" name="version_id[]" value="<?php echo $v->id; ?>">
            <div class="col-md-3">
              <input type="text" class="form-control form-control-sm" name="version_title[]" value="<?php echo esc_entities($v->title); ?>" placeholder="<?php echo __('Title'); ?>">
            </div>
            <div class="col-md-2">
              <select class="form-select form-select-sm" name="version_type[]">
                <option value="language" <?php echo $v->version_type == 'language' ? 'selected' : ''; ?>><?php echo __('Language'); ?></option>
                <option value="format" <?php echo $v->version_type == 'format' ? 'selected' : ''; ?>><?php echo __('Format'); ?></option>
                <option value="restoration" <?php echo $v->version_type == 'restoration' ? 'selected' : ''; ?>><?php echo __('Restoration'); ?></option>
                <option value="directors_cut" <?php echo $v->version_type == 'directors_cut' ? 'selected' : ''; ?>><?php echo __("Director's Cut"); ?></option>
                <option value="other" <?php echo $v->version_type == 'other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="version_language[]" value="<?php echo esc_entities($v->language_name); ?>" placeholder="<?php echo __('Language'); ?>">
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="version_year[]" value="<?php echo esc_entities($v->year); ?>" placeholder="<?php echo __('Year'); ?>">
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="version_notes[]" value="<?php echo esc_entities($v->notes); ?>" placeholder="<?php echo __('Notes'); ?>">
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.version-row').remove()"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVersionRow()">
          <i class="fas fa-plus"></i> <?php echo __('Add Version'); ?>
        </button>
      </div>
    </div>

    <!-- Format Holdings -->
    <div class="card mb-3">
      <div class="card-header" style="background-color: #6c757d; color: white;">
        <i class="fas fa-film"></i> <?php echo __('Format Holdings & Access'); ?>
      </div>
      <div class="card-body">
        <p class="text-muted small"><?php echo __('Physical formats held at institutions'); ?></p>
        <div id="holdings-container">
          <?php
          $holdings = \Illuminate\Database\Capsule\Manager::table('dam_format_holdings')
              ->where('object_id', $resource->id)->get();
          foreach ($holdings as $h): ?>
          <div class="row mb-2 holding-row border-bottom pb-2">
            <input type="hidden" name="holding_id[]" value="<?php echo $h->id; ?>">
            <div class="col-md-2">
              <select class="form-select form-select-sm" name="holding_format[]">
                <option value="35mm" <?php echo $h->format_type == '35mm' ? 'selected' : ''; ?>>35mm</option>
                <option value="16mm" <?php echo $h->format_type == '16mm' ? 'selected' : ''; ?>>16mm</option>
                <option value="8mm" <?php echo $h->format_type == '8mm' ? 'selected' : ''; ?>>8mm</option>
                <option value="VHS" <?php echo $h->format_type == 'VHS' ? 'selected' : ''; ?>>VHS</option>
                <option value="Betacam" <?php echo $h->format_type == 'Betacam' ? 'selected' : ''; ?>>Betacam</option>
                <option value="DVD" <?php echo $h->format_type == 'DVD' ? 'selected' : ''; ?>>DVD</option>
                <option value="Blu-ray" <?php echo $h->format_type == 'Blu-ray' ? 'selected' : ''; ?>>Blu-ray</option>
                <option value="Digital_File" <?php echo $h->format_type == 'Digital_File' ? 'selected' : ''; ?>><?php echo __('Digital File'); ?></option>
                <option value="DCP" <?php echo $h->format_type == 'DCP' ? 'selected' : ''; ?>>DCP</option>
                <option value="Other" <?php echo $h->format_type == 'Other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
              </select>
            </div>
            <div class="col-md-3">
              <input type="text" class="form-control form-control-sm" name="holding_institution[]" value="<?php echo esc_entities($h->holding_institution); ?>" placeholder="<?php echo __('Institution'); ?>">
            </div>
            <div class="col-md-2">
              <select class="form-select form-select-sm" name="holding_access[]">
                <option value="available" <?php echo $h->access_status == 'available' ? 'selected' : ''; ?>><?php echo __('Available'); ?></option>
                <option value="restricted" <?php echo $h->access_status == 'restricted' ? 'selected' : ''; ?>><?php echo __('Restricted'); ?></option>
                <option value="preservation_only" <?php echo $h->access_status == 'preservation_only' ? 'selected' : ''; ?>><?php echo __('Preservation Only'); ?></option>
                <option value="digitized_available" <?php echo $h->access_status == 'digitized_available' ? 'selected' : ''; ?>><?php echo __('Digitized'); ?></option>
                <option value="on_request" <?php echo $h->access_status == 'on_request' ? 'selected' : ''; ?>><?php echo __('On Request'); ?></option>
                <option value="unknown" <?php echo $h->access_status == 'unknown' ? 'selected' : ''; ?>><?php echo __('Unknown'); ?></option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="url" class="form-control form-control-sm" name="holding_url[]" value="<?php echo esc_entities($h->access_url); ?>" placeholder="<?php echo __('URL'); ?>">
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="holding_notes[]" value="<?php echo esc_entities($h->notes); ?>" placeholder="<?php echo __('Notes'); ?>">
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.holding-row').remove()"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addHoldingRow()">
          <i class="fas fa-plus"></i> <?php echo __('Add Holding'); ?>
        </button>
      </div>
    </div>

    <!-- External Links (ESAT, IMDb, etc.) -->
    <div class="card mb-3">
      <div class="card-header" style="background-color: #28a745; color: white;">
        <i class="fas fa-external-link-alt"></i> <?php echo __('External References'); ?>
      </div>
      <div class="card-body">
        <p class="text-muted small"><?php echo __('Links to ESAT, IMDb, Wikipedia, and other external databases'); ?></p>
        <div id="links-container">
          <?php
          $links = \Illuminate\Database\Capsule\Manager::table('dam_external_links')
              ->where('object_id', $resource->id)->get();
          foreach ($links as $l): ?>
          <div class="row mb-2 link-row border-bottom pb-2">
            <input type="hidden" name="link_id[]" value="<?php echo $l->id; ?>">
            <div class="col-md-2">
              <select class="form-select form-select-sm" name="link_type[]">
                <option value="ESAT" <?php echo $l->link_type == 'ESAT' ? 'selected' : ''; ?>>ESAT</option>
                <option value="IMDb" <?php echo $l->link_type == 'IMDb' ? 'selected' : ''; ?>>IMDb</option>
                <option value="NFVSA" <?php echo $l->link_type == 'NFVSA' ? 'selected' : ''; ?>>NFVSA</option>
                <option value="Wikipedia" <?php echo $l->link_type == 'Wikipedia' ? 'selected' : ''; ?>>Wikipedia</option>
                <option value="YouTube" <?php echo $l->link_type == 'YouTube' ? 'selected' : ''; ?>>YouTube</option>
                <option value="Vimeo" <?php echo $l->link_type == 'Vimeo' ? 'selected' : ''; ?>>Vimeo</option>
                <option value="Archive_org" <?php echo $l->link_type == 'Archive_org' ? 'selected' : ''; ?>>Archive.org</option>
                <option value="Academic" <?php echo $l->link_type == 'Academic' ? 'selected' : ''; ?>><?php echo __('Academic'); ?></option>
                <option value="Other" <?php echo $l->link_type == 'Other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
              </select>
            </div>
            <div class="col-md-3">
              <input type="url" class="form-control form-control-sm" name="link_url[]" value="<?php echo esc_entities($l->url); ?>" placeholder="<?php echo __('URL'); ?>" required>
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="link_title[]" value="<?php echo esc_entities($l->title); ?>" placeholder="<?php echo __('Title'); ?>">
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="link_person[]" value="<?php echo esc_entities($l->person_name); ?>" placeholder="<?php echo __('Person Name'); ?>">
            </div>
            <div class="col-md-2">
              <input type="text" class="form-control form-control-sm" name="link_role[]" value="<?php echo esc_entities($l->person_role); ?>" placeholder="<?php echo __('Role'); ?>">
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.link-row').remove()"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLinkRow()">
          <i class="fas fa-plus"></i> <?php echo __('Add Link'); ?>
        </button>
      </div>
    </div>

    <!-- AtoM Core Fields (Administration) -->
    <div class="card mb-3 border-dark">
      <div class="card-header bg-dark text-white">
        <i class="fas fa-cog"></i> <?php echo __('Administration Area'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Identifier'); ?></label>
            <input type="text" class="form-control" name="atom_identifier" value="<?php echo esc_entities($resource->identifier ?? ''); ?>">
          </div>
          <div class="col-md-8 mb-3">
            <label class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="atom_title" value="<?php echo esc_entities($resource->title ?? ''); ?>" required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Level of description'); ?></label>
            <select class="form-select" name="atom_level_of_description_id">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($levels as $levelId => $levelName): ?>
                <option value="<?php echo $levelId; ?>" <?php echo ($resource->level_of_description_id ?? '') == $levelId ? 'selected' : ''; ?>><?php echo esc_entities($levelName); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Repository'); ?></label>
            <select class="form-select" name="atom_repository_id">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo $repo->id; ?>" <?php echo ($resource->repository_id ?? '') == $repo->id ? 'selected' : ''; ?>><?php echo esc_entities($repo->name); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Publication status'); ?></label>
            <select class="form-select" name="atom_publication_status_id">
              <?php foreach ($publicationStatuses as $statusId => $statusName): ?>
                <option value="<?php echo $statusId; ?>" <?php echo ($currentPublicationStatus ?? '') == $statusId ? 'selected' : ''; ?>><?php echo esc_entities($statusName); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Display standard'); ?></label>
            <select class="form-select" name="atom_display_standard_id">
              <option value=""><?php echo __('-- Inherit from parent --'); ?></option>
              <?php foreach ($displayStandards as $standardId => $standardName): ?>
                <option value="<?php echo $standardId; ?>" <?php echo ($resource->display_standard_id ?? '') == $standardId ? 'selected' : ''; ?>><?php echo esc_entities($standardName); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Source language'); ?></label>
            <input type="text" class="form-control" value="<?php echo format_language($resource->source_culture ?? 'en'); ?>" disabled>
          </div>
        </div>
        <?php if (!empty($resource->updated_at)): ?>
        <div class="alert alert-secondary mb-0">
          <small><i class="fas fa-clock me-1"></i><?php echo __('Last updated'); ?>: <?php echo $resource->updated_at; ?></small>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- IPTC Administrative -->
    <div class="card mb-3">
      <div class="card-header bg-light">
        <i class="fas fa-tasks"></i> <?php echo __('IPTC Administrative'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('IPTC Title / Object Name'); ?></label>
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

  <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  (function() {
    // Asset type field visibility mapping
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

    // Label changes based on asset type
    const labelChanges = {
      photo: { creator: 'Photographer', job: 'Job Title' },
      artwork: { creator: 'Artist / Creator', job: 'Medium' },
      documentary: { creator: 'Director', job: 'Role' },
      feature: { creator: 'Director', job: 'Role' },
      short: { creator: 'Director', job: 'Role' },
      news: { creator: 'Reporter / Cameraperson', job: 'Role' },
      interview: { creator: 'Interviewer', job: 'Role' },
      oral_history: { creator: 'Interviewer', job: 'Organization' },
      music: { creator: 'Artist / Performer', job: 'Role' },
      podcast: { creator: 'Host / Presenter', job: 'Role' },
      speech: { creator: 'Speaker', job: 'Title / Position' }
    };

    const assetSelect = document.getElementById('assetTypeSelect');
    
    function updateFieldVisibility() {
      const assetType = assetSelect.value;
      
      // Hide all conditional fields first
      document.querySelectorAll('.field-photo, .field-video, .field-audio, .field-artwork').forEach(el => {
        el.style.display = 'none';
      });
      
      // Show relevant fields
      if (assetType && fieldGroups[assetType]) {
        fieldGroups[assetType].forEach(cls => {
          document.querySelectorAll('.' + cls).forEach(el => {
            el.style.display = '';
          });
        });
      }
      
      // Update labels
      const labels = labelChanges[assetType] || { creator: 'Creator', job: 'Job Title' };
      document.querySelectorAll('.creator-label').forEach(el => {
        el.textContent = labels.creator;
      });
      document.querySelectorAll('.creator-job-label').forEach(el => {
        el.textContent = labels.job;
      });
    }

    assetSelect.addEventListener('change', updateFieldVisibility);
    
    // Initial visibility
    updateFieldVisibility();

    // Credits management
    const creditsContainer = document.getElementById('creditsContainer');
    const addCreditBtn = document.getElementById('addCreditBtn');
    
    if (addCreditBtn) {
      addCreditBtn.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row credit-row mb-2';
        row.innerHTML = `
          <div class="col-md-4">
            <select class="form-select" name="credit_role[]">
              <option value="">-- Role --</option>
              <option value="Director">Director</option>
              <option value="Producer">Producer</option>
              <option value="Executive Producer">Executive Producer</option>
              <option value="Writer">Writer</option>
              <option value="Screenplay">Screenplay</option>
              <option value="Photography">Photography / Cinematography</option>
              <option value="Camera">Camera</option>
              <option value="Editor">Editor</option>
              <option value="Sound">Sound</option>
              <option value="Sound Design">Sound Design & Mix</option>
              <option value="Music">Music / Composer</option>
              <option value="Narrator">Narrator</option>
              <option value="Presenter">Presenter / Host</option>
              <option value="Interviewer">Interviewer</option>
              <option value="Interviewee">Interviewee / Subject</option>
              <option value="Cast">Cast / Actor</option>
              <option value="Commissioning Editor">Commissioning Editor</option>
              <option value="Sponsor">Sponsor</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <input type="text" class="form-control" name="credit_name[]" placeholder="Name">
          </div>
          <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-remove-credit"><i class="fas fa-times"></i></button>
          </div>
        `;
        creditsContainer.appendChild(row);
      });

      creditsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-credit')) {
          const row = e.target.closest('.credit-row');
          if (creditsContainer.querySelectorAll('.credit-row').length > 1) {
            row.remove();
          } else {
            row.querySelectorAll('input, select').forEach(el => el.value = '');
          }
        }
      });
    }

    // Extract metadata button
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

    // Dynamic row functions for Alternative Versions
    window.addVersionRow = function() {
      const container = document.getElementById('versions-container');
      const row = document.createElement('div');
      row.className = 'row mb-2 version-row border-bottom pb-2';
      row.innerHTML = `
        <input type="hidden" name="version_id[]" value="">
        <div class="col-md-3">
          <input type="text" class="form-control form-control-sm" name="version_title[]" placeholder="<?php echo __('Title'); ?>">
        </div>
        <div class="col-md-2">
          <select class="form-select form-select-sm" name="version_type[]">
            <option value="language"><?php echo __('Language'); ?></option>
            <option value="format"><?php echo __('Format'); ?></option>
            <option value="restoration"><?php echo __('Restoration'); ?></option>
            <option value="directors_cut"><?php echo __("Director's Cut"); ?></option>
            <option value="other"><?php echo __('Other'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="version_language[]" placeholder="<?php echo __('Language'); ?>">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="version_year[]" placeholder="<?php echo __('Year'); ?>">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="version_notes[]" placeholder="<?php echo __('Notes'); ?>">
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.version-row').remove()"><i class="fas fa-times"></i></button>
        </div>
      `;
      container.appendChild(row);
    };

    // Dynamic row functions for Format Holdings
    window.addHoldingRow = function() {
      const container = document.getElementById('holdings-container');
      const row = document.createElement('div');
      row.className = 'row mb-2 holding-row border-bottom pb-2';
      row.innerHTML = `
        <input type="hidden" name="holding_id[]" value="">
        <div class="col-md-2">
          <select class="form-select form-select-sm" name="holding_format[]">
            <option value="35mm">35mm</option>
            <option value="16mm">16mm</option>
            <option value="8mm">8mm</option>
            <option value="VHS">VHS</option>
            <option value="Betacam">Betacam</option>
            <option value="DVD">DVD</option>
            <option value="Blu-ray">Blu-ray</option>
            <option value="Digital_File"><?php echo __('Digital File'); ?></option>
            <option value="DCP">DCP</option>
            <option value="Other"><?php echo __('Other'); ?></option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control form-control-sm" name="holding_institution[]" placeholder="<?php echo __('Institution'); ?>">
        </div>
        <div class="col-md-2">
          <select class="form-select form-select-sm" name="holding_access[]">
            <option value="available"><?php echo __('Available'); ?></option>
            <option value="restricted"><?php echo __('Restricted'); ?></option>
            <option value="preservation_only"><?php echo __('Preservation Only'); ?></option>
            <option value="digitized_available"><?php echo __('Digitized'); ?></option>
            <option value="on_request"><?php echo __('On Request'); ?></option>
            <option value="unknown"><?php echo __('Unknown'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <input type="url" class="form-control form-control-sm" name="holding_url[]" placeholder="<?php echo __('URL'); ?>">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="holding_notes[]" placeholder="<?php echo __('Notes'); ?>">
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.holding-row').remove()"><i class="fas fa-times"></i></button>
        </div>
      `;
      container.appendChild(row);
    };

    // Dynamic row functions for External Links
    window.addLinkRow = function() {
      const container = document.getElementById('links-container');
      const row = document.createElement('div');
      row.className = 'row mb-2 link-row border-bottom pb-2';
      row.innerHTML = `
        <input type="hidden" name="link_id[]" value="">
        <div class="col-md-2">
          <select class="form-select form-select-sm" name="link_type[]">
            <option value="ESAT">ESAT</option>
            <option value="IMDb">IMDb</option>
            <option value="NFVSA">NFVSA</option>
            <option value="Wikipedia">Wikipedia</option>
            <option value="YouTube">YouTube</option>
            <option value="Vimeo">Vimeo</option>
            <option value="Archive_org">Archive.org</option>
            <option value="Academic"><?php echo __('Academic'); ?></option>
            <option value="Other"><?php echo __('Other'); ?></option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="url" class="form-control form-control-sm" name="link_url[]" placeholder="<?php echo __('URL'); ?>" required>
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="link_title[]" placeholder="<?php echo __('Title'); ?>">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="link_person[]" placeholder="<?php echo __('Person Name'); ?>">
        </div>
        <div class="col-md-2">
          <input type="text" class="form-control form-control-sm" name="link_role[]" placeholder="<?php echo __('Role'); ?>">
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.link-row').remove()"><i class="fas fa-times"></i></button>
        </div>
      `;
      container.appendChild(row);
    };
  })();
  </script>
<?php end_slot(); ?>
