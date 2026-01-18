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
    <?php echo $form->renderFormTag(url_for([$resource, 'module' => 'ahgDAMPlugin', 'action' => 'edit']), ['id' => 'editForm', 'data-sector' => 'dam']); ?>
  <?php } else { ?>
    <?php echo $form->renderFormTag(url_for(['module' => 'ahgDam', 'action' => 'create']), ['id' => 'editForm', 'data-sector' => 'dam']); ?>
  <?php } ?>

    <?php echo $form->renderHiddenFields(); ?>

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
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Production Company'); ?></label>
            <input type="text" class="form-control" name="production_company" value="<?php echo esc_entities($iptc->production_company ?? ''); ?>" placeholder="<?php echo __('e.g., VPRO, SABC'); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Distributor / Broadcaster'); ?></label>
            <input type="text" class="form-control" name="distributor" value="<?php echo esc_entities($iptc->distributor ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Broadcast / Release Date'); ?></label>
            <input type="text" class="form-control" name="broadcast_date" value="<?php echo esc_entities($iptc->broadcast_date ?? ''); ?>" placeholder="<?php echo __('e.g., 2006'); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Series Title'); ?></label>
            <input type="text" class="form-control" name="series_title" value="<?php echo esc_entities($iptc->series_title ?? ''); ?>">
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
        <div class="mb-3">
          <label class="form-label"><?php echo __('Awards / Recognition'); ?></label>
          <textarea class="form-control" name="awards" rows="2" placeholder="<?php echo __('e.g., Nominated for Golden Calf Award 2006'); ?>"><?php echo esc_entities($iptc->awards ?? ''); ?></textarea>
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
          <div class="form-item">
            <?php echo $form->repository->renderLabel(); ?>
            <?php echo $form->repository->render(['class' => 'form-autocomplete']); ?>
            <input class="add" type="hidden" data-link-existing="true" value="<?php echo url_for(['module' => 'repository', 'action' => 'add']); ?> #authorizedFormOfName"/>
            <input class="list" type="hidden" value="<?php echo url_for(['module' => 'repository', 'action' => 'autocomplete']); ?>"/>
          </div>
        </div>
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
<?php include_partial("ahgDAMPlugin/itemPhysicalLocationCard", ["resource" => $resource ?? null, "itemLocation" => $itemLocation ?? []]); ?>
    </div>

    <section class="actions">
      <ul class="list-unstyled d-flex flex-wrap gap-2">
        <li><?php echo link_to(__('Cancel'), isset($resource->id) ? [$resource, 'module' => 'informationobject'] : ['module' => 'informationobject', 'action' => 'browse'], ['class' => 'btn atom-btn-outline-light']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      </ul>
    </section>

  </form>


<script>
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
  });
});
</script>
<?php end_slot(); ?>
