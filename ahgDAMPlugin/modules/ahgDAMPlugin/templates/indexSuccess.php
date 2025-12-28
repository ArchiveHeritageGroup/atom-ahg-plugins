<?php decorate_with('layout_2col.php'); ?>
<?php use_helper('Date'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo render_title($resource); ?></h1>
  <span class="badge bg-danger"><?php echo __('Photo/DAM (IPTC/XMP)'); ?></span>
<?php end_slot(); ?>

<?php slot('content'); ?>
<!-- DEBUG START -->
<!-- resource id: <?php echo $resource->id; ?> -->
<!-- digitalObjects count: <?php echo count($resource->digitalObjectsRelatedByobjectId); ?> -->
<!-- digitalObjectLink: <?php echo $digitalObjectLink; ?> -->
<!-- DEBUG END -->


  <?php if (isset($errorSchema)) { ?>
    <div class="messages error">
      <ul>
        <?php foreach ($errorSchema as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <?php if (0 < count($resource->digitalObjectsRelatedByobjectId)) { ?>
    <?php echo get_component('digitalobject', 'show', ['link' => $digitalObjectLink, 'resource' => $resource->digitalObjectsRelatedByobjectId[0], 'usageType' => QubitTerm::REFERENCE_ID]); ?>
  <?php } ?>

  <!-- Basic Identification -->
  <section class="card mb-3">
    <div class="card-header bg-light">
      <h4 class="mb-0"><?php echo __('Identification'); ?></h4>
    </div>
    <div class="card-body">
      <?php echo render_show(__('Identifier'), $resource->identifier); ?>
      <?php echo render_show(__('Title'), render_title($resource)); ?>
      
      <?php foreach ($resource->getDates() as $item) { ?>
        <?php echo render_show(__('Date'), render_value_inline(Qubit::renderDateStartEnd($item->getDate(['cultureFallback' => true]), $item->startDate, $item->endDate))); ?>
      <?php } ?>

      <?php if ($resource->levelOfDescription) { ?>
        <?php echo render_show(__('Level of description'), render_value_inline(QubitTerm::getById($resource->levelOfDescriptionId) ? QubitTerm::getById($resource->levelOfDescriptionId)->getName(['cultureFallback' => true]) : '')); ?>
      <?php } ?>

      <?php echo render_show(__('Extent and medium'), render_value($resource->getExtentAndMedium(['cultureFallback' => true]))); ?>
    </div>
  </section>

  <!-- IPTC Creator Information -->
  <?php if ($iptc && !empty($iptc->creator)): ?>
  <section class="card mb-3">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0"><i class="fas fa-user"></i> <?php echo __('IPTC - Creator / Photographer'); ?></h4>
    </div>
    <div class="card-body">
      <?php echo render_show(__('Creator / Photographer'), $iptc->creator); ?>
      <?php if (!empty($iptc->creator_job_title)) echo render_show(__('Job Title'), $iptc->creator_job_title); ?>
      <?php if (!empty($iptc->creator_email)) echo render_show(__('Email'), $iptc->creator_email); ?>
      <?php if (!empty($iptc->creator_website)) echo render_show(__('Website'), '<a href="' . esc_entities($iptc->creator_website) . '" target="_blank">' . esc_entities($iptc->creator_website) . '</a>'); ?>
      <?php if (!empty($iptc->creator_phone)) echo render_show(__('Phone'), $iptc->creator_phone); ?>
      <?php if (!empty($iptc->creator_city)) echo render_show(__('City'), $iptc->creator_city); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- IPTC Content Description -->
  <?php if ($iptc && (!empty($iptc->headline) || !empty($iptc->caption) || !empty($iptc->keywords))): ?>
  <section class="card mb-3">
    <div class="card-header bg-success text-white">
      <h4 class="mb-0"><i class="fas fa-align-left"></i> <?php echo __('IPTC - Content Description'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->headline)) echo render_show(__('Headline'), $iptc->headline); ?>
      <?php if (!empty($iptc->caption)) echo render_show(__('Caption / Description'), nl2br(esc_entities($iptc->caption))); ?>
      <?php if (!empty($iptc->keywords)) echo render_show(__('Keywords'), $iptc->keywords); ?>
      <?php if (!empty($iptc->iptc_subject_code)) echo render_show(__('IPTC Subject Code'), $iptc->iptc_subject_code); ?>
      <?php if (!empty($iptc->intellectual_genre)) echo render_show(__('Intellectual Genre'), $iptc->intellectual_genre); ?>
      <?php if (!empty($iptc->persons_shown)) echo render_show(__('Persons Shown'), $iptc->persons_shown); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- IPTC Location -->
  <?php if ($iptc && (!empty($iptc->city) || !empty($iptc->country) || !empty($iptc->date_created))): ?>
  <section class="card mb-3">
    <div class="card-header bg-info text-white">
      <h4 class="mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo __('IPTC - Location'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->date_created)) echo render_show(__('Date Created'), $iptc->date_created); ?>
      <?php if (!empty($iptc->sublocation)) echo render_show(__('Sublocation'), $iptc->sublocation); ?>
      <?php if (!empty($iptc->city)) echo render_show(__('City'), $iptc->city); ?>
      <?php if (!empty($iptc->state_province)) echo render_show(__('State / Province'), $iptc->state_province); ?>
      <?php if (!empty($iptc->country)) echo render_show(__('Country'), $iptc->country . (!empty($iptc->country_code) ? ' (' . $iptc->country_code . ')' : '')); ?>
      
      <?php if (!empty($iptc->gps_latitude) && !empty($iptc->gps_longitude)): ?>
        <div class="field">
          <h3><?php echo __('GPS Coordinates'); ?></h3>
          <div>
            <?php echo $iptc->gps_latitude; ?>, <?php echo $iptc->gps_longitude; ?>
            <a href="https://www.google.com/maps?q=<?php echo $iptc->gps_latitude; ?>,<?php echo $iptc->gps_longitude; ?>" target="_blank" class="ms-2 btn btn-sm btn-outline-info">
              <i class="fas fa-external-link-alt"></i> <?php echo __('View on map'); ?>
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- IPTC Copyright & Rights -->
  <?php if ($iptc && (!empty($iptc->copyright_notice) || !empty($iptc->license_type) || !empty($iptc->credit_line))): ?>
  <section class="card mb-3">
    <div class="card-header bg-warning">
      <h4 class="mb-0"><i class="fas fa-copyright"></i> <?php echo __('IPTC - Copyright & Rights'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->credit_line)) echo render_show(__('Credit Line'), $iptc->credit_line); ?>
      <?php if (!empty($iptc->source)) echo render_show(__('Source'), $iptc->source); ?>
      <?php if (!empty($iptc->copyright_notice)) echo render_show(__('Copyright Notice'), $iptc->copyright_notice); ?>
      <?php if (!empty($iptc->rights_usage_terms)) echo render_show(__('Rights Usage Terms'), nl2br(esc_entities($iptc->rights_usage_terms))); ?>
      
      <?php if (!empty($iptc->license_type)): ?>
        <?php 
        $licenseLabels = [
          'rights_managed' => __('Rights Managed'),
          'royalty_free' => __('Royalty Free'),
          'creative_commons' => __('Creative Commons'),
          'public_domain' => __('Public Domain'),
          'editorial' => __('Editorial Use Only'),
          'other' => __('Other'),
        ];
        ?>
        <?php echo render_show(__('License Type'), $licenseLabels[$iptc->license_type] ?? $iptc->license_type); ?>
      <?php endif; ?>
      
      <?php if (!empty($iptc->license_url)) echo render_show(__('License URL'), '<a href="' . esc_entities($iptc->license_url) . '" target="_blank">' . esc_entities($iptc->license_url) . '</a>'); ?>
      <?php if (!empty($iptc->license_expiry)) echo render_show(__('License Expiry'), $iptc->license_expiry); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- IPTC Releases -->
  <?php if ($iptc && (($iptc->model_release_status ?? 'none') != 'none' || ($iptc->property_release_status ?? 'none') != 'none')): ?>
  <section class="card mb-3">
    <div class="card-header bg-secondary text-white">
      <h4 class="mb-0"><i class="fas fa-file-signature"></i> <?php echo __('IPTC - Model & Property Releases'); ?></h4>
    </div>
    <div class="card-body">
      <?php 
      $releaseLabels = [
        'none' => __('None'),
        'not_applicable' => __('Not Applicable'),
        'unlimited' => __('Unlimited'),
        'limited' => __('Limited / Incomplete'),
      ];
      ?>
      <?php if (($iptc->model_release_status ?? 'none') != 'none') echo render_show(__('Model Release Status'), $releaseLabels[$iptc->model_release_status] ?? $iptc->model_release_status); ?>
      <?php if (!empty($iptc->model_release_id)) echo render_show(__('Model Release ID'), $iptc->model_release_id); ?>
      <?php if (($iptc->property_release_status ?? 'none') != 'none') echo render_show(__('Property Release Status'), $releaseLabels[$iptc->property_release_status] ?? $iptc->property_release_status); ?>
      <?php if (!empty($iptc->property_release_id)) echo render_show(__('Property Release ID'), $iptc->property_release_id); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- IPTC Artwork -->
  <?php if ($iptc && (!empty($iptc->artwork_title) || !empty($iptc->artwork_creator))): ?>
  <section class="card mb-3">
    <div class="card-header" style="background-color: #6f42c1; color: white;">
      <h4 class="mb-0"><i class="fas fa-palette"></i> <?php echo __('IPTC - Artwork / Object in Image'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->artwork_title)) echo render_show(__('Artwork Title'), $iptc->artwork_title); ?>
      <?php if (!empty($iptc->artwork_creator)) echo render_show(__('Artwork Creator'), $iptc->artwork_creator); ?>
      <?php if (!empty($iptc->artwork_date)) echo render_show(__('Artwork Date'), $iptc->artwork_date); ?>
      <?php if (!empty($iptc->artwork_source)) echo render_show(__('Artwork Source'), $iptc->artwork_source); ?>
      <?php if (!empty($iptc->artwork_copyright)) echo render_show(__('Artwork Copyright'), $iptc->artwork_copyright); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Technical Metadata -->
  <?php if ($iptc && (!empty($iptc->camera_make) || !empty($iptc->image_width))): ?>
  <section class="card mb-3">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="fas fa-cog"></i> <?php echo __('Technical Metadata'); ?></h4>
    </div>
    <div class="card-body">
      <div class="row">
        <?php if (!empty($iptc->camera_make)): ?>
        <div class="col-md-4">
          <?php echo render_show(__('Camera'), $iptc->camera_make . ' ' . ($iptc->camera_model ?? '')); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($iptc->lens)): ?>
        <div class="col-md-4">
          <?php echo render_show(__('Lens'), $iptc->lens); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($iptc->focal_length)): ?>
        <div class="col-md-4">
          <?php echo render_show(__('Focal Length'), $iptc->focal_length); ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="row">
        <?php if (!empty($iptc->aperture)): ?>
        <div class="col-md-3">
          <?php echo render_show(__('Aperture'), $iptc->aperture); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($iptc->shutter_speed)): ?>
        <div class="col-md-3">
          <?php echo render_show(__('Shutter Speed'), $iptc->shutter_speed); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($iptc->iso_speed)): ?>
        <div class="col-md-3">
          <?php echo render_show(__('ISO'), $iptc->iso_speed); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($iptc->flash_used)): ?>
        <div class="col-md-3">
          <?php echo render_show(__('Flash'), $iptc->flash_used ? __('Yes') : __('No')); ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="row">
        <?php if (!empty($iptc->image_width) && !empty($iptc->image_height)): ?>
        <div class="col-md-4">
          <?php echo render_show(__('Dimensions'), $iptc->image_width . ' × ' . $iptc->image_height . ' px'); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($iptc->color_space)): ?>
        <div class="col-md-4">
          <?php echo render_show(__('Color Space'), $iptc->color_space); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($iptc->bit_depth)): ?>
        <div class="col-md-4">
          <?php echo render_show(__('Bit Depth'), $iptc->bit_depth); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Scope and Content (from AtoM) -->
  <?php if ($resource->getScopeAndContent(['cultureFallback' => true])): ?>
  <section class="card mb-3">
    <div class="card-header bg-light">
      <h4 class="mb-0"><?php echo __('Scope and content'); ?></h4>
    </div>
    <div class="card-body">
      <?php echo render_value($resource->getScopeAndContent(['cultureFallback' => true])); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Access Points -->
  <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource]); ?>
  <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource]); ?>
  <?php echo get_partial('informationobject/genreAccessPoints', ['resource' => $resource]); ?>
  <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource]); ?>

  <!-- Repository -->
  <?php if ($resource->repository): ?>
  <section class="card mb-3">
    <div class="card-header bg-light">
      <h4 class="mb-0"><?php echo __('Repository'); ?></h4>
    </div>
    <div class="card-body">
      <?php echo link_to(render_title($resource->repository), [$resource->repository, 'module' => 'repository']); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Item Physical Location -->
  <?php if (!empty($itemLocation)): ?>
  <?php include_partial("informationobject/itemPhysicalLocationView", ["itemLocation" => $itemLocation]); ?>
  <?php endif; ?>
  <!-- Digital object metadata -->
  <?php if (0 < count($resource->digitalObjectsRelatedByobjectId)): ?>
    <?php echo get_component('digitalobject', 'metadata', ['resource' => $resource->digitalObjectsRelatedByobjectId[0], 'object' => $resource]); ?>
  <?php endif; ?>

  <!-- Admin Info -->
  

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>
