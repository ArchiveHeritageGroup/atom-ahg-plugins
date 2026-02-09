<?php decorate_with('layout_2col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('repository', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Photo/DAM (IPTC/XMP)'); ?></h1>
  <?php if (isset($sf_request->getAttribute('sf_route')->resource)): ?>
    <span class="text-muted"><?php echo esc_entities($resource->title ?? $resource->slug); ?></span>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <?php if (isset($sf_request->getAttribute('sf_route')->resource)) { ?>
    <?php echo $form->renderFormTag(url_for([$resource, 'module' => 'dam', 'action' => 'edit']), ['id' => 'editForm', 'data-sector' => 'dam']); ?>
  <?php } else { ?>
    <?php echo $form->renderFormTag(url_for(['module' => 'dam', 'action' => 'create']), ['id' => 'editForm', 'data-sector' => 'dam']); ?>
  <?php } ?>

    <?php echo $form->renderHiddenFields(); ?>

    <?php // Auto-generated identifier component for new records ?>
    <?php if (!isset($sf_request->getAttribute('sf_route')->resource)): ?>
      <?php echo get_component('informationobject', 'identifierGenerator', [
        'sector' => 'dam',
        'current_identifier' => '',
        'field_name' => 'identifier',
        'repository_id' => null,
      ]); ?>
    <?php endif; ?>

    <!-- Identification -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white">
        <i class="fas fa-id-card"></i> <?php echo __('Identification'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <?php echo $form->identifier
                ->help(__('Unique identifier for this digital asset'))
                ->label(__('Identifier').' <span class="text-danger">*</span>')
                ->renderRow(); ?>
          </div>
          <div class="col-md-6 mb-3">
            <?php echo render_field($form->title
                ->help(__('Title or name of the digital asset'))
                ->label(__('Title').' <span class="text-danger">*</span>'), $resource); ?>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <?php echo $form->levelOfDescription
                ->help(__('Level of description'))
                ->label(__('Level of description'))
                ->renderRow(); ?>
          </div>
          <div class="col-md-6 mb-3">
            <?php echo render_field($form->extentAndMedium
                ->help(__('File format, size, dimensions'))
                ->label(__('Extent and medium')), $resource, ['class' => 'resizable']); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Asset Type Selection -->
    <div class="card mb-3 border-primary">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-tag"></i> <?php echo __('Asset Type'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold"><?php echo __('Asset Type'); ?></label>
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
            <label class="form-label"><?php echo __('Genre'); ?></label>
            <input type="text" class="form-control" name="genre" value="<?php echo esc_entities($iptc->genre ?? ''); ?>" placeholder="<?php echo __('e.g., Documentary, Drama, Portrait'); ?>">
          </div>
          <div class="col-md-4 mb-3 field-video field-audio" style="display:none;">
            <label class="form-label"><?php echo __('Color'); ?></label>
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

    <!-- Film/Video Production (Only for video types) -->
    <div class="card mb-3 field-video" style="display:none;">
      <div class="card-header bg-danger text-white">
        <i class="fas fa-film"></i> <?php echo __('Production Details'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label"><?php echo __('Running Time'); ?></label>
            <div class="input-group">
              <input type="number" class="form-control" name="duration_minutes" min="1" value="<?php echo esc_entities($iptc->duration_minutes ?? ''); ?>">
              <span class="input-group-text"><?php echo __('min'); ?></span>
            </div>
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label"><?php echo __('Production Company'); ?></label>
            <input type="text" class="form-control" name="production_company" value="<?php echo esc_entities($iptc->production_company ?? ''); ?>" placeholder="<?php echo __('e.g., African Film Productions'); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Distributor / Broadcaster'); ?></label>
            <input type="text" class="form-control" name="distributor" value="<?php echo esc_entities($iptc->distributor ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label"><?php echo __('Broadcast / Release Date'); ?></label>
            <input type="text" class="form-control" name="broadcast_date" value="<?php echo esc_entities($iptc->broadcast_date ?? ''); ?>" placeholder="<?php echo __('e.g., 1954'); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label"><?php echo __('Production Country'); ?></label>
            <input type="text" class="form-control" name="production_country" value="<?php echo esc_entities($iptc->production_country ?? ''); ?>" placeholder="<?php echo __('e.g., South Africa'); ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Country Code'); ?></label>
            <input type="text" class="form-control" name="production_country_code" maxlength="3" value="<?php echo esc_entities($iptc->production_country_code ?? ''); ?>" placeholder="<?php echo __('ZAF'); ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Season'); ?></label>
            <input type="number" class="form-control" name="season_number" value="<?php echo esc_entities($iptc->season_number ?? ''); ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label"><?php echo __('Episode'); ?></label>
            <input type="number" class="form-control" name="episode_number" value="<?php echo esc_entities($iptc->episode_number ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Series Title'); ?></label>
            <input type="text" class="form-control" name="series_title" value="<?php echo esc_entities($iptc->series_title ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Awards / Recognition'); ?></label>
            <input type="text" class="form-control" name="awards" value="<?php echo esc_entities($iptc->awards ?? ''); ?>" placeholder="<?php echo __('e.g., Nominated for Golden Calf Award'); ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Production Credits (Video/Audio) -->
    <div class="card mb-3 field-video field-audio" style="display:none;">
      <div class="card-header bg-secondary text-white">
        <i class="fas fa-users"></i> <?php echo __('Production Credits'); ?>
      </div>
      <div class="card-body">
        <div id="creditsContainer">
          <?php
          $rawIptc = $sf_data->getRaw('iptc'); $contributors = json_decode($rawIptc->contributors_json ?? '[]', true) ?: [];
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
                <option value="Writer" <?php echo ($contributor['role'] ?? '') == 'Writer' ? 'selected' : ''; ?>><?php echo __('Writer'); ?></option>
                <option value="Photography" <?php echo ($contributor['role'] ?? '') == 'Photography' ? 'selected' : ''; ?>><?php echo __('Photography'); ?></option>
                <option value="Editor" <?php echo ($contributor['role'] ?? '') == 'Editor' ? 'selected' : ''; ?>><?php echo __('Editor'); ?></option>
                <option value="Sound" <?php echo ($contributor['role'] ?? '') == 'Sound' ? 'selected' : ''; ?>><?php echo __('Sound'); ?></option>
                <option value="Music" <?php echo ($contributor['role'] ?? '') == 'Music' ? 'selected' : ''; ?>><?php echo __('Music'); ?></option>
                <option value="Cast" <?php echo ($contributor['role'] ?? '') == 'Cast' ? 'selected' : ''; ?>><?php echo __('Cast'); ?></option>
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
        <button type="button" class="btn btn-outline-primary btn-sm" id="addCreditBtn"><i class="fas fa-plus"></i> <?php echo __('Add Credit'); ?></button>
      </div>
    </div>

    <!-- Language (Video/Audio) -->
    <div class="card mb-3 field-video field-audio" style="display:none;">
      <div class="card-header bg-info text-white">
        <i class="fas fa-language"></i> <?php echo __('Language'); ?>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Audio Language'); ?></label>
            <input type="text" class="form-control" name="audio_language" value="<?php echo esc_entities($iptc->audio_language ?? ''); ?>" placeholder="<?php echo __('e.g., Afrikaans, English'); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Subtitle Language'); ?></label>
            <input type="text" class="form-control" name="subtitle_language" value="<?php echo esc_entities($iptc->subtitle_language ?? ''); ?>" placeholder="<?php echo __('e.g., English'); ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Creator Information -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcCreatorSection" style="cursor:pointer;">
        <i class="fas fa-user"></i> <?php echo __('IPTC - Creator / Photographer'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcCreatorSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Creator / Photographer'); ?></label>
              <input type="text" name="iptc_creator" class="form-control" value="<?php echo esc_entities($iptc->creator ?? ''); ?>">
              <div class="form-text"><?php echo __('Name of the photographer or creator of the image'); ?></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Job Title'); ?></label>
              <input type="text" name="iptc_creator_job_title" class="form-control" value="<?php echo esc_entities($iptc->creator_job_title ?? ''); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" name="iptc_creator_email" class="form-control" value="<?php echo esc_entities($iptc->creator_email ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Phone'); ?></label>
              <input type="text" name="iptc_creator_phone" class="form-control" value="<?php echo esc_entities($iptc->creator_phone ?? ''); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Website'); ?></label>
              <input type="text" name="iptc_creator_website" class="form-control" value="<?php echo esc_entities($iptc->creator_website ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('City'); ?></label>
              <input type="text" name="iptc_creator_city" class="form-control" value="<?php echo esc_entities($iptc->creator_city ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Address'); ?></label>
            <textarea name="iptc_creator_address" class="form-control" rows="2"><?php echo esc_entities($iptc->creator_address ?? ''); ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Content Description -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcContentSection" style="cursor:pointer;">
        <i class="fas fa-align-left"></i> <?php echo __('IPTC - Content Description'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcContentSection">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Headline'); ?></label>
            <input type="text" name="iptc_headline" class="form-control" value="<?php echo esc_entities($iptc->headline ?? ''); ?>">
            <div class="form-text"><?php echo __('Brief synopsis or summary of the image content'); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Caption / Description'); ?></label>
            <textarea name="iptc_caption" class="form-control" rows="4"><?php echo esc_entities($iptc->caption ?? ''); ?></textarea>
            <div class="form-text"><?php echo __('Detailed description of the image content'); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Keywords'); ?></label>
            <input type="text" name="iptc_keywords" class="form-control" value="<?php echo esc_entities($iptc->keywords ?? ''); ?>">
            <div class="form-text"><?php echo __('Comma-separated keywords for search and categorization'); ?></div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('IPTC Subject Code'); ?></label>
              <input type="text" name="iptc_subject_code" class="form-control" value="<?php echo esc_entities($iptc->iptc_subject_code ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Intellectual Genre'); ?></label>
              <input type="text" name="iptc_intellectual_genre" class="form-control" value="<?php echo esc_entities($iptc->intellectual_genre ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Persons Shown'); ?></label>
            <input type="text" name="iptc_persons_shown" class="form-control" value="<?php echo esc_entities($iptc->persons_shown ?? ''); ?>">
            <div class="form-text"><?php echo __('Names of people depicted in the image'); ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Location -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcLocationSection" style="cursor:pointer;">
        <i class="fas fa-map-marker-alt"></i> <?php echo __('IPTC - Location'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcLocationSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Date Created'); ?></label>
              <input type="date" name="iptc_date_created" class="form-control" value="<?php echo esc_entities($iptc->date_created ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('City'); ?></label>
              <input type="text" name="iptc_city" class="form-control" value="<?php echo esc_entities($iptc->city ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('State / Province'); ?></label>
              <input type="text" name="iptc_state_province" class="form-control" value="<?php echo esc_entities($iptc->state_province ?? ''); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" name="iptc_country" class="form-control" value="<?php echo esc_entities($iptc->country ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Country Code'); ?></label>
              <input type="text" name="iptc_country_code" class="form-control" maxlength="3" value="<?php echo esc_entities($iptc->country_code ?? ''); ?>">
              <div class="form-text"><?php echo __('ISO 3166-1 alpha-3'); ?></div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Sublocation'); ?></label>
              <input type="text" name="iptc_sublocation" class="form-control" value="<?php echo esc_entities($iptc->sublocation ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Copyright & Rights -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcRightsSection" style="cursor:pointer;">
        <i class="fas fa-copyright"></i> <?php echo __('IPTC - Copyright & Rights'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcRightsSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Credit Line'); ?></label>
              <input type="text" name="iptc_credit_line" class="form-control" value="<?php echo esc_entities($iptc->credit_line ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Source'); ?></label>
              <input type="text" name="iptc_source" class="form-control" value="<?php echo esc_entities($iptc->source ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Copyright Notice'); ?></label>
            <input type="text" name="iptc_copyright_notice" class="form-control" value="<?php echo esc_entities($iptc->copyright_notice ?? ''); ?>" placeholder="© 2024 Photographer Name. All rights reserved.">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Rights Usage Terms'); ?></label>
            <textarea name="iptc_rights_usage_terms" class="form-control" rows="2"><?php echo esc_entities($iptc->rights_usage_terms ?? ''); ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('License Type'); ?></label>
              <select name="iptc_license_type" class="form-select">
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
              <input type="text" name="iptc_license_url" class="form-control" value="<?php echo esc_entities($iptc->license_url ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('License Expiry'); ?></label>
              <input type="date" name="iptc_license_expiry" class="form-control" value="<?php echo esc_entities($iptc->license_expiry ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Releases -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcReleasesSection" style="cursor:pointer;">
        <i class="fas fa-file-signature"></i> <?php echo __('IPTC - Model & Property Releases'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcReleasesSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Model Release Status'); ?></label>
              <select name="iptc_model_release_status" class="form-select">
                <option value="none" <?php echo ($iptc->model_release_status ?? 'none') == 'none' ? 'selected' : ''; ?>><?php echo __('None'); ?></option>
                <option value="not_applicable" <?php echo ($iptc->model_release_status ?? '') == 'not_applicable' ? 'selected' : ''; ?>><?php echo __('Not Applicable'); ?></option>
                <option value="unlimited" <?php echo ($iptc->model_release_status ?? '') == 'unlimited' ? 'selected' : ''; ?>><?php echo __('Unlimited Model Releases'); ?></option>
                <option value="limited" <?php echo ($iptc->model_release_status ?? '') == 'limited' ? 'selected' : ''; ?>><?php echo __('Limited / Incomplete'); ?></option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Model Release ID'); ?></label>
              <input type="text" name="iptc_model_release_id" class="form-control" value="<?php echo esc_entities($iptc->model_release_id ?? ''); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Property Release Status'); ?></label>
              <select name="iptc_property_release_status" class="form-select">
                <option value="none" <?php echo ($iptc->property_release_status ?? 'none') == 'none' ? 'selected' : ''; ?>><?php echo __('None'); ?></option>
                <option value="not_applicable" <?php echo ($iptc->property_release_status ?? '') == 'not_applicable' ? 'selected' : ''; ?>><?php echo __('Not Applicable'); ?></option>
                <option value="unlimited" <?php echo ($iptc->property_release_status ?? '') == 'unlimited' ? 'selected' : ''; ?>><?php echo __('Unlimited Property Releases'); ?></option>
                <option value="limited" <?php echo ($iptc->property_release_status ?? '') == 'limited' ? 'selected' : ''; ?>><?php echo __('Limited / Incomplete'); ?></option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Property Release ID'); ?></label>
              <input type="text" name="iptc_property_release_id" class="form-control" value="<?php echo esc_entities($iptc->property_release_id ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Artwork -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcArtworkSection" style="cursor:pointer;">
        <i class="fas fa-palette"></i> <?php echo __('IPTC - Artwork / Object in Image'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcArtworkSection">
        <div class="card-body">
          <p class="text-muted"><?php echo __('For reproductions of artworks or physical objects'); ?></p>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Artwork Title'); ?></label>
              <input type="text" name="iptc_artwork_title" class="form-control" value="<?php echo esc_entities($iptc->artwork_title ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Artwork Creator'); ?></label>
              <input type="text" name="iptc_artwork_creator" class="form-control" value="<?php echo esc_entities($iptc->artwork_creator ?? ''); ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Artwork Date'); ?></label>
              <input type="text" name="iptc_artwork_date" class="form-control" value="<?php echo esc_entities($iptc->artwork_date ?? ''); ?>" placeholder="e.g., 1889 or circa 1920">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Artwork Source'); ?></label>
              <input type="text" name="iptc_artwork_source" class="form-control" value="<?php echo esc_entities($iptc->artwork_source ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Artwork Copyright'); ?></label>
              <input type="text" name="iptc_artwork_copyright" class="form-control" value="<?php echo esc_entities($iptc->artwork_copyright ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- IPTC Administrative -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#iptcAdminSection" style="cursor:pointer;">
        <i class="fas fa-cog"></i> <?php echo __('IPTC - Administrative'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="iptcAdminSection">
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Title / Object Name'); ?></label>
              <input type="text" name="iptc_title" class="form-control" value="<?php echo esc_entities($iptc->title ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Job / Assignment ID'); ?></label>
              <input type="text" name="iptc_job_id" class="form-control" value="<?php echo esc_entities($iptc->job_id ?? ''); ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Special Instructions'); ?></label>
            <textarea name="iptc_instructions" class="form-control" rows="2"><?php echo esc_entities($iptc->instructions ?? ''); ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Scope and Content -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#scopeSection" style="cursor:pointer;">
        <i class="fas fa-file-alt"></i> <?php echo __('Scope and content'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="scopeSection">
        <div class="card-body">
          <?php echo render_field($form->scopeAndContent
              ->help(__('Additional notes or description'))
              ->label(__('Scope and content')), $resource, ['class' => 'resizable']); ?>
        </div>
      </div>
    </div>


    <!-- Access Points -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#accessPointsSection" style="cursor:pointer;">
        <i class="fas fa-tags"></i> <?php echo __('Access Points'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="accessPointsSection">
        <div class="card-body">
            <?php
                $taxonomy = QubitTaxonomy::getById(QubitTaxonomy::SUBJECT_ID);
                $taxonomyUrl = url_for([$taxonomy, 'module' => 'taxonomy']);
                $extraInputs = '<input class="list" type="hidden" value="'
                    .url_for(['module' => 'term', 'action' => 'autocomplete', 'taxonomy' => $taxonomyUrl])
                    .'">';
                if (\AtomExtensions\Services\AclService::check($taxonomy, 'createTerm')) {
                    $extraInputs .= '<input class="add" type="hidden" data-link-existing="true" value="'
                        .url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => $taxonomyUrl])
                        .' #name">';
                }
                echo render_field(
                    $form->subjectAccessPoints->label(__('Subject access points')),
                    null,
                    ['class' => 'form-autocomplete', 'extraInputs' => $extraInputs]
                );
            ?>
            <?php
                $taxonomy = QubitTaxonomy::getById(QubitTaxonomy::PLACE_ID);
                $taxonomyUrl = url_for([$taxonomy, 'module' => 'taxonomy']);
                $extraInputs = '<input class="list" type="hidden" value="'
                    .url_for(['module' => 'term', 'action' => 'autocomplete', 'taxonomy' => $taxonomyUrl])
                    .'">';
                if (\AtomExtensions\Services\AclService::check($taxonomy, 'createTerm')) {
                    $extraInputs .= '<input class="add" type="hidden" data-link-existing="true" value="'
                        .url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => $taxonomyUrl])
                        .' #name">';
                }
                echo render_field(
                    $form->placeAccessPoints->label(__('Place access points')),
                    null,
                    ['class' => 'form-autocomplete', 'extraInputs' => $extraInputs]
                );
            ?>
            <?php
                $taxonomy = QubitTaxonomy::getById(QubitTaxonomy::GENRE_ID);
                $taxonomyUrl = url_for([$taxonomy, 'module' => 'taxonomy']);
                $extraInputs = '<input class="list" type="hidden" value="'
                    .url_for(['module' => 'term', 'action' => 'autocomplete', 'taxonomy' => $taxonomyUrl])
                    .'">';
                if (\AtomExtensions\Services\AclService::check($taxonomy, 'createTerm')) {
                    $extraInputs .= '<input class="add" type="hidden" data-link-existing="true" value="'
                        .url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => $taxonomyUrl])
                        .' #name">';
                }
                echo render_field(
                    $form->genreAccessPoints->label(__('Genre access points')),
                    null,
                    ['class' => 'form-autocomplete', 'extraInputs' => $extraInputs]
                );
            ?>
            <?php
                $extraInputs = '<input class="list" type="hidden" value="'
                    .url_for(['module' => 'actor', 'action' => 'autocomplete', 'showOnlyActors' => 'true'])
                    .'">';
                if (\AtomExtensions\Services\AclService::check(QubitActor::getRoot(), 'create')) {
                    $extraInputs .= '<input class="add" type="hidden" data-link-existing="true" value="'
                        .url_for(['module' => 'actor', 'action' => 'add'])
                        .' #authorizedFormOfName">';
                }
                echo render_field(
                    $form->nameAccessPoints->label(__('Name access points (subjects)')),
                    null,
                    ['class' => 'form-autocomplete', 'extraInputs' => $extraInputs]
                );
            ?>
        </div>
      </div>
    </div>
    <!-- Repository -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#repositorySection" style="cursor:pointer;">
        <i class="fas fa-building"></i> <?php echo __('Repository'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="repositorySection">
        <div class="card-body">
          <?php
              $repoExtraInputs = '<input class="list" type="hidden" value="'
                  .url_for(['module' => 'repository', 'action' => 'autocomplete'])
                  .'">';
              $repoExtraInputs .= '<input class="add" type="hidden" data-link-existing="true" value="'
                  .url_for(['module' => 'repository', 'action' => 'add'])
                  .' #authorizedFormOfName">';
              echo render_field(
                  $form->repository->label(__('Repository')),
                  $resource,
                  ['class' => 'form-autocomplete', 'extraInputs' => $repoExtraInputs]
              );
          ?>
        </div>
      </div>
    </div>

    <!-- Alternative Versions (Film/Video) -->
    <div class="card mb-3">
      <div class="card-header" style="background-color: #17a2b8; color: white;">
        <i class="fas fa-language"></i> <?php echo __('Alternative Versions'); ?>
      </div>
      <div class="card-body">
        <p class="text-muted small"><?php echo __('Other language versions, formats, or edits of this work'); ?></p>
        <div id="versions-container">
          <?php foreach ($versionLinks as $v): ?>
          <div class="version-row border rounded p-2 mb-2 bg-light">
            <input type="hidden" name="version_id[]" value="<?php echo $v->id; ?>">
            <div class="row mb-2">
              <div class="col-md-4">
                <label class="form-label small"><?php echo __('Title'); ?></label>
                <input type="text" class="form-control form-control-sm" name="version_title[]" value="<?php echo esc_entities($v->title); ?>" placeholder="<?php echo __('e.g., Kuddes van die veld'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Type'); ?></label>
                <select class="form-select form-select-sm" name="version_type[]">
                  <option value="language" <?php echo $v->version_type == 'language' ? 'selected' : ''; ?>><?php echo __('Language'); ?></option>
                  <option value="format" <?php echo $v->version_type == 'format' ? 'selected' : ''; ?>><?php echo __('Format'); ?></option>
                  <option value="restoration" <?php echo $v->version_type == 'restoration' ? 'selected' : ''; ?>><?php echo __('Restoration'); ?></option>
                  <option value="directors_cut" <?php echo $v->version_type == 'directors_cut' ? 'selected' : ''; ?>><?php echo __("Director's Cut"); ?></option>
                  <option value="censored" <?php echo $v->version_type == 'censored' ? 'selected' : ''; ?>><?php echo __('Censored'); ?></option>
                  <option value="other" <?php echo $v->version_type == 'other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Language'); ?></label>
                <input type="text" class="form-control form-control-sm" name="version_language[]" value="<?php echo esc_entities($v->language_name); ?>" placeholder="<?php echo __('Afrikaans'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('ISO Code'); ?></label>
                <input type="text" class="form-control form-control-sm" name="version_language_code[]" value="<?php echo esc_entities($v->language_code); ?>" maxlength="3" placeholder="<?php echo __('afr'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Year'); ?></label>
                <input type="text" class="form-control form-control-sm" name="version_year[]" value="<?php echo esc_entities($v->year); ?>" placeholder="<?php echo __('1954'); ?>">
              </div>
            </div>
            <div class="row">
              <div class="col-md-11">
                <label class="form-label small"><?php echo __('Notes'); ?></label>
                <input type="text" class="form-control form-control-sm" name="version_notes[]" value="<?php echo esc_entities($v->notes); ?>" placeholder="<?php echo __('Additional information about this version'); ?>">
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-version w-100"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addVersionBtn">
          <i class="fas fa-plus"></i> <?php echo __('Add Version'); ?>
        </button>
      </div>
    </div>

    <!-- Format Holdings (Film/Video) -->
    <div class="card mb-3">
      <div class="card-header" style="background-color: #6c757d; color: white;">
        <i class="fas fa-archive"></i> <?php echo __('Format Holdings & Access'); ?>
      </div>
      <div class="card-body">
        <p class="text-muted small"><?php echo __('Physical formats held at institutions'); ?></p>
        <div id="holdings-container">
          <?php foreach ($formatHoldings as $h): ?>
          <div class="holding-row border rounded p-2 mb-2 bg-light">
            <input type="hidden" name="holding_id[]" value="<?php echo $h->id; ?>">
            <div class="row mb-2">
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Format'); ?></label>
                <select class="form-select form-select-sm" name="holding_format[]">
                  <optgroup label="<?php echo __('Film'); ?>">
                    <option value="35mm" <?php echo $h->format_type == '35mm' ? 'selected' : ''; ?>>35mm</option>
                    <option value="16mm" <?php echo $h->format_type == '16mm' ? 'selected' : ''; ?>>16mm</option>
                    <option value="8mm" <?php echo $h->format_type == '8mm' ? 'selected' : ''; ?>>8mm</option>
                    <option value="Super8" <?php echo $h->format_type == 'Super8' ? 'selected' : ''; ?>>Super 8</option>
                    <option value="Nitrate" <?php echo $h->format_type == 'Nitrate' ? 'selected' : ''; ?>><?php echo __('Nitrate'); ?></option>
                    <option value="Safety" <?php echo $h->format_type == 'Safety' ? 'selected' : ''; ?>><?php echo __('Safety'); ?></option>
                    <option value="Polyester" <?php echo $h->format_type == 'Polyester' ? 'selected' : ''; ?>><?php echo __('Polyester'); ?></option>
                  </optgroup>
                  <optgroup label="<?php echo __('Video'); ?>">
                    <option value="VHS" <?php echo $h->format_type == 'VHS' ? 'selected' : ''; ?>>VHS</option>
                    <option value="Betacam" <?php echo $h->format_type == 'Betacam' ? 'selected' : ''; ?>>Betacam</option>
                    <option value="U-matic" <?php echo $h->format_type == 'U-matic' ? 'selected' : ''; ?>>U-matic</option>
                    <option value="DV" <?php echo $h->format_type == 'DV' ? 'selected' : ''; ?>>DV</option>
                  </optgroup>
                  <optgroup label="<?php echo __('Digital'); ?>">
                    <option value="DVD" <?php echo $h->format_type == 'DVD' ? 'selected' : ''; ?>>DVD</option>
                    <option value="Blu-ray" <?php echo $h->format_type == 'Blu-ray' ? 'selected' : ''; ?>>Blu-ray</option>
                    <option value="LaserDisc" <?php echo $h->format_type == 'LaserDisc' ? 'selected' : ''; ?>>LaserDisc</option>
                    <option value="Digital_File" <?php echo $h->format_type == 'Digital_File' ? 'selected' : ''; ?>><?php echo __('Digital File'); ?></option>
                    <option value="DCP" <?php echo $h->format_type == 'DCP' ? 'selected' : ''; ?>>DCP</option>
                    <option value="ProRes" <?php echo $h->format_type == 'ProRes' ? 'selected' : ''; ?>>ProRes</option>
                  </optgroup>
                  <optgroup label="<?php echo __('Audio'); ?>">
                    <option value="Audio_Reel" <?php echo $h->format_type == 'Audio_Reel' ? 'selected' : ''; ?>><?php echo __('Audio Reel'); ?></option>
                    <option value="Audio_Cassette" <?php echo $h->format_type == 'Audio_Cassette' ? 'selected' : ''; ?>><?php echo __('Audio Cassette'); ?></option>
                    <option value="Vinyl" <?php echo $h->format_type == 'Vinyl' ? 'selected' : ''; ?>><?php echo __('Vinyl'); ?></option>
                    <option value="CD" <?php echo $h->format_type == 'CD' ? 'selected' : ''; ?>>CD</option>
                  </optgroup>
                  <option value="Other" <?php echo $h->format_type == 'Other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Format Details'); ?></label>
                <input type="text" class="form-control form-control-sm" name="holding_format_details[]" value="<?php echo esc_entities($h->format_details); ?>" placeholder="<?php echo __('Color, sound, ratio'); ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label small"><?php echo __('Institution'); ?></label>
                <input type="text" class="form-control form-control-sm" name="holding_institution[]" value="<?php echo esc_entities($h->holding_institution); ?>" placeholder="<?php echo __('e.g., NFVSA, WCPLS'); ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label small"><?php echo __('Location'); ?></label>
                <input type="text" class="form-control form-control-sm" name="holding_location[]" value="<?php echo esc_entities($h->holding_location); ?>" placeholder="<?php echo __('Department/vault'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Accession #'); ?></label>
                <input type="text" class="form-control form-control-sm" name="holding_accession[]" value="<?php echo esc_entities($h->accession_number); ?>" placeholder="<?php echo __('Ref number'); ?>">
              </div>
            </div>
            <div class="row mb-2">
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Condition'); ?></label>
                <select class="form-select form-select-sm" name="holding_condition[]">
                  <option value="unknown" <?php echo $h->condition_status == 'unknown' ? 'selected' : ''; ?>><?php echo __('Unknown'); ?></option>
                  <option value="excellent" <?php echo $h->condition_status == 'excellent' ? 'selected' : ''; ?>><?php echo __('Excellent'); ?></option>
                  <option value="good" <?php echo $h->condition_status == 'good' ? 'selected' : ''; ?>><?php echo __('Good'); ?></option>
                  <option value="fair" <?php echo $h->condition_status == 'fair' ? 'selected' : ''; ?>><?php echo __('Fair'); ?></option>
                  <option value="poor" <?php echo $h->condition_status == 'poor' ? 'selected' : ''; ?>><?php echo __('Poor'); ?></option>
                  <option value="deteriorating" <?php echo $h->condition_status == 'deteriorating' ? 'selected' : ''; ?>><?php echo __('Deteriorating'); ?></option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Access'); ?></label>
                <select class="form-select form-select-sm" name="holding_access[]">
                  <option value="unknown" <?php echo $h->access_status == 'unknown' ? 'selected' : ''; ?>><?php echo __('Unknown'); ?></option>
                  <option value="available" <?php echo $h->access_status == 'available' ? 'selected' : ''; ?>><?php echo __('Available'); ?></option>
                  <option value="restricted" <?php echo $h->access_status == 'restricted' ? 'selected' : ''; ?>><?php echo __('Restricted'); ?></option>
                  <option value="preservation_only" <?php echo $h->access_status == 'preservation_only' ? 'selected' : ''; ?>><?php echo __('Preservation Only'); ?></option>
                  <option value="digitized_available" <?php echo $h->access_status == 'digitized_available' ? 'selected' : ''; ?>><?php echo __('Digitized'); ?></option>
                  <option value="on_request" <?php echo $h->access_status == 'on_request' ? 'selected' : ''; ?>><?php echo __('On Request'); ?></option>
                  <option value="staff_only" <?php echo $h->access_status == 'staff_only' ? 'selected' : ''; ?>><?php echo __('Staff Only'); ?></option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small"><?php echo __('Access URL'); ?></label>
                <input type="url" class="form-control form-control-sm" name="holding_url[]" value="<?php echo esc_entities($h->access_url); ?>" placeholder="<?php echo __('Streaming/download URL'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Verified Date'); ?></label>
                <input type="date" class="form-control form-control-sm" name="holding_verified[]" value="<?php echo esc_entities($h->verified_date); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Primary'); ?></label>
                <div class="form-check mt-1">
                  <input type="checkbox" class="form-check-input" name="holding_primary[]" value="<?php echo $h->id; ?>" <?php echo $h->is_primary ? 'checked' : ''; ?>>
                  <label class="form-check-label small"><?php echo __('Primary copy'); ?></label>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <label class="form-label small"><?php echo __('Access Notes'); ?></label>
                <input type="text" class="form-control form-control-sm" name="holding_access_notes[]" value="<?php echo esc_entities($h->access_notes); ?>" placeholder="<?php echo __('How to request, viewing conditions'); ?>">
              </div>
              <div class="col-md-5">
                <label class="form-label small"><?php echo __('Notes'); ?></label>
                <input type="text" class="form-control form-control-sm" name="holding_notes[]" value="<?php echo esc_entities($h->notes); ?>" placeholder="<?php echo __('Additional notes'); ?>">
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-holding w-100"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addHoldingBtn">
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
        <p class="text-muted small"><?php echo __('Links to ESAT, IMDb, Wikipedia, and other databases'); ?></p>
        <div id="links-container">
          <?php foreach ($externalLinks as $l): ?>
          <div class="link-row border rounded p-2 mb-2 bg-light">
            <input type="hidden" name="link_id[]" value="<?php echo $l->id; ?>">
            <div class="row mb-2">
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Type'); ?></label>
                <select class="form-select form-select-sm" name="link_type[]">
                  <optgroup label="<?php echo __('South African'); ?>">
                    <option value="ESAT" <?php echo $l->link_type == 'ESAT' ? 'selected' : ''; ?>>ESAT</option>
                    <option value="SAFILM" <?php echo $l->link_type == 'SAFILM' ? 'selected' : ''; ?>>SA Film</option>
                    <option value="NFVSA" <?php echo $l->link_type == 'NFVSA' ? 'selected' : ''; ?>>NFVSA</option>
                  </optgroup>
                  <optgroup label="<?php echo __('Film Databases'); ?>">
                    <option value="IMDb" <?php echo $l->link_type == 'IMDb' ? 'selected' : ''; ?>>IMDb</option>
                    <option value="BFI" <?php echo $l->link_type == 'BFI' ? 'selected' : ''; ?>>BFI</option>
                    <option value="AFI" <?php echo $l->link_type == 'AFI' ? 'selected' : ''; ?>>AFI</option>
                    <option value="Letterboxd" <?php echo $l->link_type == 'Letterboxd' ? 'selected' : ''; ?>>Letterboxd</option>
                    <option value="MUBI" <?php echo $l->link_type == 'MUBI' ? 'selected' : ''; ?>>MUBI</option>
                    <option value="Filmography" <?php echo $l->link_type == 'Filmography' ? 'selected' : ''; ?>><?php echo __('Filmography'); ?></option>
                  </optgroup>
                  <optgroup label="<?php echo __('Knowledge Bases'); ?>">
                    <option value="Wikipedia" <?php echo $l->link_type == 'Wikipedia' ? 'selected' : ''; ?>>Wikipedia</option>
                    <option value="Wikidata" <?php echo $l->link_type == 'Wikidata' ? 'selected' : ''; ?>>Wikidata</option>
                    <option value="VIAF" <?php echo $l->link_type == 'VIAF' ? 'selected' : ''; ?>>VIAF</option>
                  </optgroup>
                  <optgroup label="<?php echo __('Media Platforms'); ?>">
                    <option value="YouTube" <?php echo $l->link_type == 'YouTube' ? 'selected' : ''; ?>>YouTube</option>
                    <option value="Vimeo" <?php echo $l->link_type == 'Vimeo' ? 'selected' : ''; ?>>Vimeo</option>
                    <option value="Archive_org" <?php echo $l->link_type == 'Archive_org' ? 'selected' : ''; ?>>Archive.org</option>
                  </optgroup>
                  <optgroup label="<?php echo __('Other'); ?>">
                    <option value="Review" <?php echo $l->link_type == 'Review' ? 'selected' : ''; ?>><?php echo __('Review'); ?></option>
                    <option value="Academic" <?php echo $l->link_type == 'Academic' ? 'selected' : ''; ?>><?php echo __('Academic'); ?></option>
                    <option value="Press" <?php echo $l->link_type == 'Press' ? 'selected' : ''; ?>><?php echo __('Press'); ?></option>
                    <option value="Other" <?php echo $l->link_type == 'Other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
                  </optgroup>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small"><?php echo __('URL'); ?></label>
                <input type="url" class="form-control form-control-sm" name="link_url[]" value="<?php echo esc_entities($l->url); ?>" placeholder="<?php echo __('https://...'); ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label small"><?php echo __('Title'); ?></label>
                <input type="text" class="form-control form-control-sm" name="link_title[]" value="<?php echo esc_entities($l->title); ?>" placeholder="<?php echo __('Link display text'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Verified'); ?></label>
                <input type="date" class="form-control form-control-sm" name="link_verified[]" value="<?php echo esc_entities($l->verified_date); ?>">
              </div>
              <div class="col-md-1">
                <label class="form-label small"><?php echo __('Primary'); ?></label>
                <div class="form-check mt-1">
                  <input type="checkbox" class="form-check-input" name="link_primary[]" value="<?php echo $l->id; ?>" <?php echo $l->is_primary ? 'checked' : ''; ?>>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Person'); ?></label>
                <input type="text" class="form-control form-control-sm" name="link_person[]" value="<?php echo esc_entities($l->person_name); ?>" placeholder="<?php echo __('e.g., Donald Swanson'); ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small"><?php echo __('Role'); ?></label>
                <input type="text" class="form-control form-control-sm" name="link_role[]" value="<?php echo esc_entities($l->person_role); ?>" placeholder="<?php echo __('Director, Actor'); ?>">
              </div>
              <div class="col-md-7">
                <label class="form-label small"><?php echo __('Description'); ?></label>
                <input type="text" class="form-control form-control-sm" name="link_description[]" value="<?php echo esc_entities($l->description); ?>" placeholder="<?php echo __('What this link provides'); ?>">
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-link w-100"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addLinkBtn">
          <i class="fas fa-plus"></i> <?php echo __('Add Link'); ?>
        </button>
      </div>
    </div>

    <!-- Admin Area -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white collapsed" data-bs-toggle="collapse" data-bs-target="#adminSection" style="cursor:pointer;">
        <i class="fas fa-lock"></i> <?php echo __('Administration area'); ?>
        <i class="fas fa-chevron-down float-end mt-1"></i>
      </div>
      <div class="collapse" id="adminSection">
        <div class="card-body">
          <?php if (isset($form->publicationStatus)): ?>
            <?php echo $form->publicationStatus
                ->help(__('Status'))
                ->label(__('Publication status'))
                ->renderRow(); ?>
          <?php endif; ?>

          <?php if (isset($form->displayStandard)): ?>
            <?php echo $form->displayStandard
                ->help(__('Choose a metadata standard'))
                ->label(__('Display standard'))
                ->renderRow(); ?>
          <?php endif; ?>
        </div>
      </div>
<!-- Item Physical Location -->
<?php include_partial("dam/itemPhysicalLocationCard", ["resource" => $resource ?? null, "itemLocation" => $itemLocation ?? []]); ?>
    </div>

    <section class="actions">
      <ul class="list-unstyled d-flex flex-wrap gap-2">
        <li><?php echo link_to(__('Cancel'), isset($resource->id) ? [$resource, 'module' => 'informationobject'] : ['module' => 'informationobject', 'action' => 'browse'], ['class' => 'btn atom-btn-outline-light']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      </ul>
    </section>

  </form>


<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  const fieldGroups = {
    photo: [],
    artwork: ['field-artwork'],
    scan: [],
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

  const assetSelect = document.getElementById('assetTypeSelect');
  if (!assetSelect) return;

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
  }

  assetSelect.addEventListener('change', updateFieldVisibility);
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
      `;
      creditsContainer.appendChild(row);
    });
  }

  // Remove credit row
  document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-remove-credit')) {
      const row = e.target.closest('.credit-row');
      if (row && creditsContainer.querySelectorAll('.credit-row').length > 1) {
        row.remove();
      }
    }
    // Remove version/holding/link rows
    if (e.target.closest('.btn-remove-version')) {
      e.target.closest('.version-row').remove();
    }
    if (e.target.closest('.btn-remove-holding')) {
      e.target.closest('.holding-row').remove();
    }
    if (e.target.closest('.btn-remove-link')) {
      e.target.closest('.link-row').remove();
    }
  });

  // Add Version
  document.getElementById('addVersionBtn')?.addEventListener('click', function() {
    const container = document.getElementById('versions-container');
    const row = document.createElement('div');
    row.className = 'version-row border rounded p-2 mb-2 bg-light';
    row.innerHTML = `
      <input type="hidden" name="version_id[]" value="">
      <div class="row mb-2">
        <div class="col-md-4">
          <label class="form-label small"><?php echo __('Title'); ?></label>
          <input type="text" class="form-control form-control-sm" name="version_title[]" placeholder="<?php echo __('e.g., Kuddes van die veld'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Type'); ?></label>
          <select class="form-select form-select-sm" name="version_type[]">
            <option value="language"><?php echo __('Language'); ?></option>
            <option value="format"><?php echo __('Format'); ?></option>
            <option value="restoration"><?php echo __('Restoration'); ?></option>
            <option value="directors_cut"><?php echo __("Director's Cut"); ?></option>
            <option value="censored"><?php echo __('Censored'); ?></option>
            <option value="other"><?php echo __('Other'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Language'); ?></label>
          <input type="text" class="form-control form-control-sm" name="version_language[]" placeholder="<?php echo __('Afrikaans'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('ISO Code'); ?></label>
          <input type="text" class="form-control form-control-sm" name="version_language_code[]" maxlength="3" placeholder="<?php echo __('afr'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Year'); ?></label>
          <input type="text" class="form-control form-control-sm" name="version_year[]" placeholder="<?php echo __('1954'); ?>">
        </div>
      </div>
      <div class="row">
        <div class="col-md-11">
          <label class="form-label small"><?php echo __('Notes'); ?></label>
          <input type="text" class="form-control form-control-sm" name="version_notes[]" placeholder="<?php echo __('Additional information'); ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-version w-100"><i class="fas fa-times"></i></button>
        </div>
      </div>
    `;
    container.appendChild(row);
  });

  // Add Holding
  document.getElementById('addHoldingBtn')?.addEventListener('click', function() {
    const container = document.getElementById('holdings-container');
    const row = document.createElement('div');
    row.className = 'holding-row border rounded p-2 mb-2 bg-light';
    row.innerHTML = `
      <input type="hidden" name="holding_id[]" value="">
      <div class="row mb-2">
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Format'); ?></label>
          <select class="form-select form-select-sm" name="holding_format[]">
            <optgroup label="<?php echo __('Film'); ?>">
              <option value="35mm">35mm</option>
              <option value="16mm">16mm</option>
              <option value="8mm">8mm</option>
              <option value="Super8">Super 8</option>
              <option value="Nitrate"><?php echo __('Nitrate'); ?></option>
              <option value="Safety"><?php echo __('Safety'); ?></option>
              <option value="Polyester"><?php echo __('Polyester'); ?></option>
            </optgroup>
            <optgroup label="<?php echo __('Video'); ?>">
              <option value="VHS">VHS</option>
              <option value="Betacam">Betacam</option>
              <option value="U-matic">U-matic</option>
              <option value="DV">DV</option>
            </optgroup>
            <optgroup label="<?php echo __('Digital'); ?>">
              <option value="DVD">DVD</option>
              <option value="Blu-ray">Blu-ray</option>
              <option value="Digital_File"><?php echo __('Digital File'); ?></option>
              <option value="DCP">DCP</option>
              <option value="ProRes">ProRes</option>
            </optgroup>
            <optgroup label="<?php echo __('Audio'); ?>">
              <option value="Audio_Reel"><?php echo __('Audio Reel'); ?></option>
              <option value="Audio_Cassette"><?php echo __('Audio Cassette'); ?></option>
              <option value="Vinyl"><?php echo __('Vinyl'); ?></option>
              <option value="CD">CD</option>
            </optgroup>
            <option value="Other"><?php echo __('Other'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Format Details'); ?></label>
          <input type="text" class="form-control form-control-sm" name="holding_format_details[]" placeholder="<?php echo __('Color, sound'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small"><?php echo __('Institution'); ?></label>
          <input type="text" class="form-control form-control-sm" name="holding_institution[]" placeholder="<?php echo __('e.g., NFVSA'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small"><?php echo __('Location'); ?></label>
          <input type="text" class="form-control form-control-sm" name="holding_location[]" placeholder="<?php echo __('Department/vault'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Accession #'); ?></label>
          <input type="text" class="form-control form-control-sm" name="holding_accession[]" placeholder="<?php echo __('Ref number'); ?>">
        </div>
      </div>
      <div class="row mb-2">
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Condition'); ?></label>
          <select class="form-select form-select-sm" name="holding_condition[]">
            <option value="unknown"><?php echo __('Unknown'); ?></option>
            <option value="excellent"><?php echo __('Excellent'); ?></option>
            <option value="good"><?php echo __('Good'); ?></option>
            <option value="fair"><?php echo __('Fair'); ?></option>
            <option value="poor"><?php echo __('Poor'); ?></option>
            <option value="deteriorating"><?php echo __('Deteriorating'); ?></option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Access'); ?></label>
          <select class="form-select form-select-sm" name="holding_access[]">
            <option value="unknown"><?php echo __('Unknown'); ?></option>
            <option value="available"><?php echo __('Available'); ?></option>
            <option value="restricted"><?php echo __('Restricted'); ?></option>
            <option value="preservation_only"><?php echo __('Preservation Only'); ?></option>
            <option value="digitized_available"><?php echo __('Digitized'); ?></option>
            <option value="on_request"><?php echo __('On Request'); ?></option>
            <option value="staff_only"><?php echo __('Staff Only'); ?></option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small"><?php echo __('Access URL'); ?></label>
          <input type="url" class="form-control form-control-sm" name="holding_url[]" placeholder="<?php echo __('Streaming URL'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Verified Date'); ?></label>
          <input type="date" class="form-control form-control-sm" name="holding_verified[]">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Primary'); ?></label>
          <div class="form-check mt-1">
            <input type="checkbox" class="form-check-input" name="holding_primary[]" value="new">
            <label class="form-check-label small"><?php echo __('Primary copy'); ?></label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <label class="form-label small"><?php echo __('Access Notes'); ?></label>
          <input type="text" class="form-control form-control-sm" name="holding_access_notes[]" placeholder="<?php echo __('How to request'); ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label small"><?php echo __('Notes'); ?></label>
          <input type="text" class="form-control form-control-sm" name="holding_notes[]" placeholder="<?php echo __('Additional notes'); ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-holding w-100"><i class="fas fa-times"></i></button>
        </div>
      </div>
    `;
    container.appendChild(row);
  });

  // Add Link
  document.getElementById('addLinkBtn')?.addEventListener('click', function() {
    const container = document.getElementById('links-container');
    const row = document.createElement('div');
    row.className = 'link-row border rounded p-2 mb-2 bg-light';
    row.innerHTML = `
      <input type="hidden" name="link_id[]" value="">
      <div class="row mb-2">
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Type'); ?></label>
          <select class="form-select form-select-sm" name="link_type[]">
            <optgroup label="<?php echo __('South African'); ?>">
              <option value="ESAT">ESAT</option>
              <option value="SAFILM">SA Film</option>
              <option value="NFVSA">NFVSA</option>
            </optgroup>
            <optgroup label="<?php echo __('Film Databases'); ?>">
              <option value="IMDb">IMDb</option>
              <option value="BFI">BFI</option>
              <option value="AFI">AFI</option>
              <option value="Letterboxd">Letterboxd</option>
              <option value="MUBI">MUBI</option>
              <option value="Filmography"><?php echo __('Filmography'); ?></option>
            </optgroup>
            <optgroup label="<?php echo __('Knowledge Bases'); ?>">
              <option value="Wikipedia">Wikipedia</option>
              <option value="Wikidata">Wikidata</option>
              <option value="VIAF">VIAF</option>
            </optgroup>
            <optgroup label="<?php echo __('Media Platforms'); ?>">
              <option value="YouTube">YouTube</option>
              <option value="Vimeo">Vimeo</option>
              <option value="Archive_org">Archive.org</option>
            </optgroup>
            <optgroup label="<?php echo __('Other'); ?>">
              <option value="Review"><?php echo __('Review'); ?></option>
              <option value="Academic"><?php echo __('Academic'); ?></option>
              <option value="Press"><?php echo __('Press'); ?></option>
              <option value="Other"><?php echo __('Other'); ?></option>
            </optgroup>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small"><?php echo __('URL'); ?></label>
          <input type="url" class="form-control form-control-sm" name="link_url[]" placeholder="<?php echo __('https://...'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small"><?php echo __('Title'); ?></label>
          <input type="text" class="form-control form-control-sm" name="link_title[]" placeholder="<?php echo __('Link text'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Verified'); ?></label>
          <input type="date" class="form-control form-control-sm" name="link_verified[]">
        </div>
        <div class="col-md-1">
          <label class="form-label small"><?php echo __('Primary'); ?></label>
          <div class="form-check mt-1">
            <input type="checkbox" class="form-check-input" name="link_primary[]" value="new">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Person'); ?></label>
          <input type="text" class="form-control form-control-sm" name="link_person[]" placeholder="<?php echo __('Name'); ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small"><?php echo __('Role'); ?></label>
          <input type="text" class="form-control form-control-sm" name="link_role[]" placeholder="<?php echo __('Director'); ?>">
        </div>
        <div class="col-md-7">
          <label class="form-label small"><?php echo __('Description'); ?></label>
          <input type="text" class="form-control form-control-sm" name="link_description[]" placeholder="<?php echo __('What this link provides'); ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-outline-danger btn-remove-link w-100"><i class="fas fa-times"></i></button>
        </div>
      </div>
    `;
    container.appendChild(row);
  });
});
</script>
<?php end_slot(); ?>
