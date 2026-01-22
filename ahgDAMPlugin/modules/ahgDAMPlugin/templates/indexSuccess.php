<?php decorate_with('layout_2col.php'); ?>
<?php use_helper('Date'); ?>
<?php $rawResource = sfOutputEscaper::unescape($resource); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_entities($resource->title ?? $resource->slug); ?></h1>
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

  <!-- Digital Object Display -->
  <?php if (isset($digitalObject) && $digitalObject): ?>
    <?php if (EmbargoHelper::canViewThumbnail($rawResource->id)): ?>
    <section class="card mb-3">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-image me-2"></i><?php echo __('Digital Object'); ?></h5>
      </div>
      <div class="card-body text-center">
        <?php
          $mimeType = $digitalObject->mimeType ?? '';
          $mediaTypeId = $digitalObject->mediaTypeId ?? null;
          $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
          $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
          $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
          $refPath = $refObj ? $refObj->getFullPath() : null;
          $masterPath = $digitalObject->getFullPath();
          $displayPath = $refPath ?: $thumbPath ?: $masterPath;

          $isVideo = ($mediaTypeId == QubitTerm::VIDEO_ID) || strpos($mimeType, 'video') !== false;
          $isAudio = ($mediaTypeId == QubitTerm::AUDIO_ID) || strpos($mimeType, 'audio') !== false;
        ?>
        <?php if ($isVideo || $isAudio): ?>
          <!-- Video/Audio player with transcription support -->
          <?php include_partial('digitalobject/showVideo', ['resource' => $digitalObject]); ?>
        <?php elseif (strpos($mimeType, 'image') !== false && $displayPath): ?>
          <a href="<?php echo $masterPath; ?>" target="_blank">
            <img src="<?php echo $displayPath; ?>" alt="<?php echo esc_entities($resource->title ?? $resource->slug); ?>" class="img-fluid rounded shadow-sm" style="max-height: 400px;">
          </a>
        <?php else: ?>
          <a href="<?php echo $masterPath; ?>" target="_blank" class="btn btn-outline-primary">
            <i class="fas fa-file me-2"></i><?php echo __('View file'); ?>
          </a>
        <?php endif; ?>
      </div>
    </section>
    <?php else: ?>
    <!-- Embargo notice -->
    <section class="card mb-3">
      <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Digital Object'); ?></h5>
      </div>
      <div class="card-body">
        <?php include_partial('extendedRights/embargoBlock', ['objectId' => $rawResource->id, 'type' => 'digital_object']); ?>
      </div>
    </section>
    <?php endif; ?>
  <?php endif; ?>


  <!-- User Actions (compact with tooltips) -->
  <?php
  use Illuminate\Database\Capsule\Manager as DB;
  $userId = $sf_user->getAttribute('user_id');
  $sessionId = session_id();
  if (empty($sessionId) && !$userId) { @session_start(); $sessionId = session_id(); }
  $favoriteId = null;
  $cartId = null;
  if ($userId) {
      $favoriteId = DB::table('favorites')->where('user_id', $userId)->where('archival_description_id', $resource->id)->value('id');
      $cartId = DB::table('cart')->where('user_id', $userId)->where('archival_description_id', $resource->id)->whereNull('completed_at')->value('id');
  } elseif ($sessionId) {
      $cartId = DB::table('cart')->where('session_id', $sessionId)->where('archival_description_id', $resource->id)->whereNull('completed_at')->value('id');
  }
  $hasDigitalObject = DB::table('digital_object')->where('object_id', $resource->id)->exists();
  ?>
  <div class="d-flex flex-wrap gap-1 mb-3">
    <?php if (in_array('ahgFavoritesPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $userId): ?>
      <?php if ($favoriteId): ?>
        <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'remove', 'id' => $favoriteId]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Remove from Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart-broken"></i></a>
      <?php else: ?>
        <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Add to Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart"></i></a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (in_array('ahgFeedbackPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
      <a href="<?php echo url_for(['module' => 'ahgFeedback', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-secondary" title="<?php echo __('Item Feedback'); ?>" data-bs-toggle="tooltip"><i class="fas fa-comment"></i></a>
    <?php endif; ?>
    <?php if (in_array('ahgRequestToPublishPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $hasDigitalObject): ?>
      <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-primary" title="<?php echo __('Request to Publish'); ?>" data-bs-toggle="tooltip"><i class="fas fa-paper-plane"></i></a>
    <?php endif; ?>
    <?php if (in_array('ahgCartPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $hasDigitalObject): ?>
      <?php if ($cartId): ?>
        <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Go to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-shopping-cart"></i></a>
      <?php else: ?>
        <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Add to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-cart-plus"></i></a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (in_array('ahgLoanPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $sf_user->isAuthenticated()): ?>
      <a href="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => 'out', 'sector' => 'dam', 'object_id' => $rawResource->id]); ?>" class="btn btn-xs btn-outline-warning" title="<?php echo __('New Loan'); ?>" data-bs-toggle="tooltip"><i class="fas fa-file-contract"></i></a>
      <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index', 'sector' => 'dam', 'object_id' => $rawResource->id]); ?>" class="btn btn-xs btn-outline-info" title="<?php echo __('Manage Loans'); ?>" data-bs-toggle="tooltip"><i class="fas fa-exchange-alt"></i></a>
    <?php endif; ?>
  </div>

  <!-- Basic Identification -->
  <section class="card mb-3">
    <div class="card-header bg-light">
      <h4 class="mb-0"><?php echo __('Identification'); ?></h4>
    </div>
    <div class="card-body">
      <?php echo render_show(__('Identifier'), $resource->identifier); ?>
      <?php echo render_show(__('Title'), $resource->title ?? $resource->slug); ?>
      
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
  <?php if ($iptc && (!empty($iptc->headline) || !empty($iptc->caption) || !empty($iptc->keywords) || !empty($iptc->duration_minutes))): ?>
  <section class="card mb-3">
    <div class="card-header bg-success text-white">
      <h4 class="mb-0"><i class="fas fa-align-left"></i> <?php echo __('IPTC - Content Description'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->headline)) echo render_show(__('Headline'), $iptc->headline); ?>
      <?php if (!empty($iptc->duration_minutes)) echo render_show(__('Running Time'), $iptc->duration_minutes . ' ' . __('minutes')); ?>
      <?php if (!empty($iptc->caption)) echo render_show(__('Caption / Description'), nl2br(esc_entities($iptc->caption))); ?>
      <?php if (!empty($iptc->keywords)) echo render_show(__('Keywords'), $iptc->keywords); ?>
      <?php if (!empty($iptc->iptc_subject_code)) echo render_show(__('IPTC Subject Code'), $iptc->iptc_subject_code); ?>
      <?php if (!empty($iptc->intellectual_genre)) echo render_show(__('Intellectual Genre'), $iptc->intellectual_genre); ?>
      <?php if (!empty($iptc->persons_shown)) echo render_show(__('Persons Shown'), $iptc->persons_shown); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- IPTC Location -->
  <?php if ($iptc && (!empty($iptc->city) || !empty($iptc->country) || !empty($iptc->date_created) || !empty($iptc->production_country))): ?>
  <section class="card mb-3">
    <div class="card-header bg-info text-white">
      <h4 class="mb-0"><i class="fas fa-map-marker-alt"></i> <?php echo __('IPTC - Location'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->date_created)) echo render_show(__('Date Created'), $iptc->date_created); ?>
      <?php if (!empty($iptc->sublocation)) echo render_show(__('Sublocation'), $iptc->sublocation); ?>
      <?php if (!empty($iptc->city)) echo render_show(__('City'), $iptc->city); ?>
      <?php if (!empty($iptc->state_province)) echo render_show(__('State / Province'), $iptc->state_province); ?>
      <?php if (!empty($iptc->country)) echo render_show(__('Country (Filming)'), $iptc->country . (!empty($iptc->country_code) ? ' (' . $iptc->country_code . ')' : '')); ?>
      <?php if (!empty($iptc->production_country)) echo render_show(__('Country (Production)'), $iptc->production_country . (!empty($iptc->production_country_code) ? ' (' . $iptc->production_country_code . ')' : '')); ?>

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

  <!-- Film / Video Production (PBCore) -->
  <?php if ($iptc && (!empty($iptc->asset_type) || !empty($iptc->genre) || !empty($iptc->production_company) || !empty($iptc->contributors_json))): ?>
  <section class="card mb-3">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="fas fa-film"></i> <?php echo __('Film / Video Production'); ?></h4>
    </div>
    <div class="card-body">
      <?php if (!empty($iptc->asset_type)) echo render_show(__('Asset Type'), ucfirst(str_replace('_', ' ', $iptc->asset_type))); ?>
      <?php if (!empty($iptc->genre)) echo render_show(__('Genre'), $iptc->genre); ?>
      <?php if (!empty($iptc->color_type)) echo render_show(__('Color'), ucfirst($iptc->color_type)); ?>
      <?php if (!empty($iptc->audio_language)) echo render_show(__('Audio Language'), $iptc->audio_language); ?>
      <?php if (!empty($iptc->production_company)) echo render_show(__('Production Company'), $iptc->production_company); ?>
      <?php if (!empty($iptc->distributor)) echo render_show(__('Distributor'), $iptc->distributor); ?>
      <?php if (!empty($iptc->broadcast_date)) echo render_show(__('Broadcast Date'), $iptc->broadcast_date); ?>
      <?php if (!empty($iptc->series_title)) echo render_show(__('Series'), $iptc->series_title . (!empty($iptc->season_number) ? ' - Season ' . $iptc->season_number : '') . (!empty($iptc->episode_number) ? ', Episode ' . $iptc->episode_number : '')); ?>
      <?php if (!empty($iptc->awards)) echo render_show(__('Awards'), nl2br(esc_entities($iptc->awards))); ?>
      <?php if (!empty($iptc->contributors_json)): ?>
        <?php $rawIptc = $sf_data->getRaw("iptc"); $credits = json_decode($rawIptc->contributors_json, true); ?>
        <?php if ($credits): ?>
        <div class="field">
          <h3><?php echo __('Production Credits'); ?></h3>
          <div>
            <table class="table table-sm table-striped">
              <?php foreach ($credits as $credit): ?>
              <tr>
                <td class="fw-bold" style="width: 200px;"><?php echo esc_entities($credit['role']); ?></td>
                <td><?php echo esc_entities($credit['name']); ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>
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

  <!-- Alternative Versions -->
  <?php
  $versions = DB::table('dam_version_links')->where('object_id', $resource->id)->get();
  ?>
  <?php if (count($versions) > 0): ?>
  <section class="card mb-3">
    <div class="card-header" style="background-color: #17a2b8; color: white;">
      <h4 class="mb-0"><i class="fas fa-language"></i> <?php echo __('Alternative Versions'); ?></h4>
    </div>
    <div class="card-body">
      <table class="table table-sm table-striped mb-0">
        <thead>
          <tr>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Language'); ?></th>
            <th><?php echo __('Year'); ?></th>
            <th><?php echo __('Notes'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($versions as $v): ?>
          <tr>
            <td><?php echo esc_entities($v->title); ?></td>
            <td><?php echo ucfirst(str_replace('_', ' ', $v->version_type)); ?></td>
            <td><?php echo esc_entities($v->language_name); ?></td>
            <td><?php echo esc_entities($v->year); ?></td>
            <td><?php echo esc_entities($v->notes); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <!-- Format Holdings -->
  <?php
  $holdings = DB::table('dam_format_holdings')->where('object_id', $resource->id)->get();
  ?>
  <?php if (count($holdings) > 0): ?>
  <section class="card mb-3">
    <div class="card-header" style="background-color: #6c757d; color: white;">
      <h4 class="mb-0"><i class="fas fa-film"></i> <?php echo __('Format Holdings & Access'); ?></h4>
    </div>
    <div class="card-body">
      <table class="table table-sm table-striped mb-0">
        <thead>
          <tr>
            <th><?php echo __('Format'); ?></th>
            <th><?php echo __('Institution'); ?></th>
            <th><?php echo __('Access'); ?></th>
            <th><?php echo __('Link'); ?></th>
            <th><?php echo __('Notes'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $accessLabels = [
            'available' => __('Available'),
            'restricted' => __('Restricted'),
            'preservation_only' => __('Preservation Only'),
            'digitized_available' => __('Digitized Available'),
            'on_request' => __('On Request'),
            'staff_only' => __('Staff Only'),
            'unknown' => __('Unknown'),
          ];
          ?>
          <?php foreach ($holdings as $h): ?>
          <tr>
            <td><?php echo str_replace('_', ' ', $h->format_type); ?></td>
            <td><?php echo esc_entities($h->holding_institution); ?></td>
            <td>
              <?php
              $badge = 'secondary';
              if ($h->access_status == 'available' || $h->access_status == 'digitized_available') $badge = 'success';
              elseif ($h->access_status == 'restricted' || $h->access_status == 'staff_only') $badge = 'warning';
              elseif ($h->access_status == 'preservation_only') $badge = 'danger';
              ?>
              <span class="badge bg-<?php echo $badge; ?>"><?php echo $accessLabels[$h->access_status] ?? $h->access_status; ?></span>
            </td>
            <td>
              <?php if (!empty($h->access_url)): ?>
              <a href="<?php echo esc_entities($h->access_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> <?php echo __('View'); ?>
              </a>
              <?php endif; ?>
            </td>
            <td><?php echo esc_entities($h->notes); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>

  <!-- External Links -->
  <?php
  $links = DB::table('dam_external_links')->where('object_id', $resource->id)->get();
  ?>
  <?php if (count($links) > 0): ?>
  <section class="card mb-3">
    <div class="card-header" style="background-color: #28a745; color: white;">
      <h4 class="mb-0"><i class="fas fa-external-link-alt"></i> <?php echo __('External References'); ?></h4>
    </div>
    <div class="card-body">
      <div class="list-group list-group-flush">
        <?php
        $linkIcons = [
          'ESAT' => 'fa-book',
          'IMDb' => 'fa-film',
          'NFVSA' => 'fa-archive',
          'Wikipedia' => 'fa-wikipedia-w fab',
          'Wikidata' => 'fa-database',
          'VIAF' => 'fa-id-card',
          'YouTube' => 'fa-youtube fab',
          'Vimeo' => 'fa-vimeo fab',
          'Archive_org' => 'fa-archive',
          'BFI' => 'fa-film',
          'AFI' => 'fa-film',
          'Academic' => 'fa-graduation-cap',
          'Press' => 'fa-newspaper',
          'Review' => 'fa-star',
          'Other' => 'fa-link',
        ];
        ?>
        <?php foreach ($links as $link): ?>
        <a href="<?php echo esc_entities($link->url); ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
          <div>
            <i class="<?php echo $linkIcons[$link->link_type] ?? 'fas fa-link'; ?> me-2"></i>
            <strong><?php echo esc_entities($link->title ?: $link->link_type); ?></strong>
            <?php if (!empty($link->person_name)): ?>
              <span class="text-muted ms-2">(<?php echo esc_entities($link->person_name); ?><?php if (!empty($link->person_role)) echo ' - ' . esc_entities($link->person_role); ?>)</span>
            <?php endif; ?>
          </div>
          <span class="badge bg-secondary"><?php echo str_replace('_', '.', $link->link_type); ?></span>
        </a>
        <?php endforeach; ?>
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
      <?php echo link_to($resource->repository->authorizedFormOfName ?? $resource->repository->slug, [$resource->repository, 'module' => 'repository']); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Item Physical Location -->
  <?php if (!empty($itemLocation)): ?>
  <?php if (file_exists(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_itemPhysicalLocationView.php')) { include_partial('informationobject/itemPhysicalLocationView', ['itemLocation' => $itemLocation]); } ?>
  <?php endif; ?>
  <!-- Digital object metadata -->
  <?php if (0 < count($resource->digitalObjectsRelatedByobjectId)): ?>
    <?php echo get_component('digitalobject', 'metadata', ['resource' => $resource->digitalObjectsRelatedByobjectId[0], 'object' => $resource]); ?>
  <?php endif; ?>

  <!-- Provenance & Chain of Custody -->
  <?php if (in_array('ahgProvenancePlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
  <section class="card mb-3">
    <div class="card-header bg-secondary text-white">
      <h4 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Provenance & Chain of Custody'); ?></h4>
    </div>
    <div class="card-body">
      <?php include_component('provenance', 'provenanceDisplay', ['objectId' => $resource->id]); ?>
      <?php if ($sf_user->isAuthenticated()): ?>
      <div class="mt-3">
        <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $resource->slug]) ?>" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-edit me-1"></i><?php echo __('Edit Provenance') ?>
        </a>
        <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $resource->slug]) ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-clock me-1"></i><?php echo __('View Full Timeline') ?>
        </a>
      </div>
      <?php endif ?>
    </div>
  </section>
  <?php endif ?>
  <!-- Admin Info -->
  

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>
