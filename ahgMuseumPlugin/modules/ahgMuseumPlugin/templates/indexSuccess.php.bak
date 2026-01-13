<?php
require_once dirname(__FILE__)."/../../../lib/MuseumConstants.php";
/**
 * Museum Plugin Index Template
 */

use Illuminate\Database\Capsule\Manager as DB;

// Initialize Laravel if needed
if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
    \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
}

// Get the raw resource (unwrap from escaper)
$rawResource = sfOutputEscaper::unescape($resource);

// Get CCO/Museum data from museum_metadata table
$museumData = [];
$museumRecord = DB::table('museum_metadata')
    ->where('object_id', $rawResource->id)
    ->first();

if ($museumRecord) {
    $data = (array) $museumRecord;
    $museumData = [
        'object_number' => $rawResource->identifier,
        'work_type' => $data['work_type'] ?? null,
        'title_type' => $data['object_type'] ?? null,
        'classification' => $data['classification'] ?? null,
        'creator_display' => $data['creator_identity'] ?? null,
        'creator_role' => $data['creator_role'] ?? null,
        'attribution_qualifier' => $data['creator_attribution'] ?? null,
        'school' => $data['school'] ?? null,
        'creation_date_display' => $data['creation_date_display'] ?? null,
        'creation_date_earliest' => $data['creation_date_earliest'] ?? null,
        'creation_date_latest' => $data['creation_date_latest'] ?? null,
        'creation_place' => $data['creation_place'] ?? null,
        'culture' => $data['cultural_context'] ?? null,
        'style' => $data['style'] ?? null,
        'period' => $data['period'] ?? null,
        'style_period' => $data['style_period'] ?? null,
        'movement' => $data['movement'] ?? null,
        'dimensions_display' => $data['dimensions'] ?? $data['measurements'] ?? null,
        'materials_display' => $data['materials'] ?? null,
        'materials' => $data['materials'] ?? null,
        'techniques' => $data['techniques'] ?? null,
        'color' => $data['color'] ?? null,
        'support' => null,
        'subject_display' => $data['subject_display'] ?? null,
        'subjects_depicted' => $data['subject_display'] ?? null,
        'description' => null,
        'inscriptions' => $data['inscription'] ?? $data['inscriptions'] ?? null,
        'inscription_type' => $data['inscription_type'] ?? null,
        'inscription_location' => $data['inscription_location'] ?? null,
        'signature' => $data['mark_description'] ?? null,
        'condition_summary' => $data['condition_term'] ?? null,
        'condition_description' => $data['condition_description'] ?? null,
        'condition_date' => $data['condition_date'] ?? null,
        'condition_agent' => $data['condition_agent'] ?? null,
        'treatment_type' => $data['treatment_type'] ?? null,
        'provenance' => $data['provenance'] ?? $data['provenance_text'] ?? null,
        'ownership_history' => $data['ownership_history'] ?? null,
        'legal_status' => $data['legal_status'] ?? null,
        'rights_holder' => $data['rights_holder'] ?? null,
        'current_location' => $data['current_location'] ?? $data['current_location_repository'] ?? null,
        'cataloger_name' => $data['cataloger_name'] ?? null,
        'cataloging_date' => $data['cataloging_date'] ?? null,
    ];
} else {
    // Fallback to ccoData property
    $ccoDataProperty = $rawResource->getPropertyByName('ccoData');
    if ($ccoDataProperty) {
        $ccoJson = $ccoDataProperty->getValue(['cultureFallback' => true]);
        if ($ccoJson) {
            $museumData = json_decode($ccoJson, true) ?: [];
        }
    }
}

// Get GRAP data if table exists
$grapData = [];
try {
    $grapData = (array) DB::table('grap_heritage_asset')
        ->where('information_object_id', $rawResource->id)
        ->first();
} catch (Exception $e) {
    // Table may not exist
}

// Headings condition for edit links
$headingsCondition = SecurityPrivileges::editCredentials($sf_user, 'informationObject');
$headingsUrl = ['module' => 'ahgMuseumPlugin', 'action' => 'edit', 'slug' => $rawResource->slug];

// Attribution qualifier labels
$attributionLabels = [
    'attributed_to' => 'Attributed to',
    'after' => 'After',
    'follower_of' => 'Follower of',
    'circle_of' => 'Circle of',
    'school_of' => 'School of',
    'studio_of' => 'Studio of',
    'workshop_of' => 'Workshop of',
    'manner_of' => 'Manner of',
    'copy_after' => 'Copy after'
];

// Condition labels
$conditionLabels = [
    'excellent' => 'Excellent',
    'good' => 'Good',
    'fair' => 'Fair',
    'poor' => 'Poor',
    'unknown' => 'Unknown'
];

// Check if field groups have data
$hasObjectIdentification = !empty($museumData['object_number']) || !empty($museumData['work_type']) || !empty($museumData['title_type']);
$hasCreatorInfo = !empty($museumData['creator_display']) || !empty($museumData['creator_role']) || !empty($museumData['attribution_qualifier']);
$hasCreationContext = !empty($museumData['creation_date_display']) || !empty($museumData['creation_place']) || !empty($museumData['culture']) || !empty($museumData['style']) || !empty($museumData['period']);
$hasPhysicalDescription = !empty($museumData['dimensions_display']) || !empty($museumData['materials_display']) || !empty($museumData['materials']) || !empty($museumData['techniques']) || !empty($museumData['support']);
$hasSubjectContent = !empty($museumData['subject_display']) || !empty($museumData['subjects_depicted']) || !empty($museumData['description']);
$hasInscriptions = !empty($museumData['inscriptions']) || !empty($museumData['signature']);
$hasCondition = !empty($museumData['condition_summary']);
$hasProvenance = !empty($museumData['provenance']) || !empty($museumData['current_location']);
?>

<?php decorate_with('layout_3col'); ?>
<?php use_helper('informationobject', 'DigitalObjectViewer'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <?php echo get_component('informationobject', 'descriptionHeader', ['resource' => $resource, 'title' => (string) $resource]); ?>

  <?php if (MuseumConstants::INFORMATION_OBJECT_ROOT_ID != $resource->parentId) { ?>
    <?php echo include_partial('default/breadcrumb', ['resource' => $resource, 'objects' => $resource->getAncestors()->andSelf()->orderBy('lft')]); ?>
  <?php } ?>

  <?php echo get_component('default', 'translationLinks', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php slot('context-menu'); ?>
  <nav>
    <?php echo get_partial('informationobject/actionIcons', ['resource' => $resource]); ?>
    <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>
    <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>
    <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>
    <?php if (check_field_visibility('app_element_visibility_physical_storage')) { ?>
      <?php echo get_component('physicalobject', 'contextMenu', ['resource' => $resource]); ?>
    <?php } ?>
  </nav>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <?php if (EmbargoHelper::canViewThumbnail($rawResource->id)): ?>
  <?php echo get_component('digitalobject', 'imageflow', ['resource' => $resource]); ?>
<?php endif; ?>
<?php end_slot(); ?>

<?php // Digital Object Viewer ?>
<?php if (0 < count($resource->digitalObjectsRelatedByobjectId)): ?>
  <?php if (EmbargoHelper::canViewDigitalObject($rawResource->id)): ?>
    <?php foreach ($resource->digitalObjectsRelatedByobjectId as $obj): ?>
      <?php echo render_digital_object_viewer($resource, $obj); ?>
    <?php endforeach; ?>
  <?php elseif (EmbargoHelper::canViewThumbnail($rawResource->id)): ?>
    <?php // Show thumbnail only - no full viewer ?>
    <div class="digital-object-restricted text-center p-4 bg-light rounded mb-3">
      <i class="fas fa-image fa-3x text-muted mb-2"></i>
      <?php include_partial('extendedRights/embargoBlock', ['objectId' => $rawResource->id, 'type' => 'digital_object']); ?>
    </div>
  <?php else: ?>
    <?php include_partial('extendedRights/embargoBlock', ['objectId' => $rawResource->id, 'type' => 'thumbnail']); ?>
  <?php endif; ?>
<?php endif; ?>

<!-- Identity Area -->

<!-- User Action Buttons -->
<div class="card mb-4 mt-3">
  <div class="card-body py-2">
    <div class="d-flex flex-wrap gap-2">
      <?php
        $userId = $sf_user->getAttribute('user_id');
      ?>
      <?php if (class_exists('QubitFavorites')): ?>
        <?php $favorate_id = QubitFavorites::getByUserIDandObjectId($userId, $resource->id); ?>
        <?php if (!empty($favorate_id->id) && !empty($userId)): ?>
          <?php echo link_to('<i class="fas fa-heart-broken me-1"></i>' . __('Remove from Favorites'), [$resource, 'module' => 'informationobject', 'action' => 'removeFavorites'], ['class' => 'btn btn-sm btn-outline-danger']); ?>
        <?php elseif (!empty($userId)): ?>
          <?php echo link_to('<i class="fas fa-heart me-1"></i>' . __('Add to Favorites'), [$resource, 'module' => 'informationobject', 'action' => 'addFavorites'], ['class' => 'btn btn-sm btn-outline-danger']); ?>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (class_exists('QubitCart')): ?>
      <?php
        $cart_id = QubitCart::getByUserIDandObjectId($userId, $resource->id);
      ?>
      <?php if ('' != $cart_id->id && '' != $userId): ?>
        <?php echo link_to('<i class="fas fa-shopping-cart me-1"></i>' . __('Go to Cart'), [$resource, 'module' => 'cart', 'action' => 'browse'], ['class' => 'btn btn-sm btn-outline-success']); ?>
      <?php elseif ('' != $userId): ?>
        <?php echo link_to('<i class="fas fa-cart-plus me-1"></i>' . __('Add to Cart'), [$resource, 'module' => 'informationobject', 'action' => 'addCart'], ['class' => 'btn btn-sm btn-outline-success']); ?>
      <?php endif; ?>
      <?php endif; ?>
      <?php if (class_exists('ahgFeedbackPluginConfiguration')): ?>
        <?php echo link_to('<i class="fas fa-comment me-1"></i>' . __('Item Feedback'), ['module' => 'ahgFeedback', 'action' => 'submit', 'slug' => $resource->slug], ['class' => 'btn btn-sm btn-outline-secondary']); ?>
      <?php endif; ?>
      <?php if (class_exists('ahgRequestToPublishPluginConfiguration')): ?>
        <?php echo link_to('<i class="fas fa-paper-plane me-1"></i>' . __('Request to Publish'), ['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $resource->slug], ['class' => 'btn btn-sm btn-outline-primary']); ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<section id="identityArea" class="border-bottom">
  <?php echo render_b5_section_heading(
      __('Identity area'),
      $headingsCondition,
      $headingsUrl,
      ['anchor' => 'identity-collapse', 'class' => 0 < count($resource->digitalObjectsRelatedByobjectId) ? '' : 'rounded-top']
  ); ?>

  <?php echo render_show(__('Reference code'), $resource->identifier, ['fieldLabel' => 'referenceCode']); ?>
  <?php echo render_show(__('Title'), render_title($resource), ['fieldLabel' => 'title']); ?>

  <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
    <?php echo render_b5_show_label(__('Date(s)')); ?>
    <div class="creationDates <?php echo render_b5_show_value_css_classes(); ?>">
      <ul class="<?php echo render_b5_show_list_css_classes(); ?>">
        <?php foreach ($resource->getDates() as $item) { ?>
          <li>
            <?php echo render_value_inline(Qubit::renderDateStartEnd($item->getDate(['cultureFallback' => true]), $item->startDate, $item->endDate)); ?> (<?php echo $item->getType(['cultureFallback' => true]); ?>)
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>

  <?php echo render_show(__('Level of description'), render_value_inline($resource->levelOfDescription), ['fieldLabel' => 'levelOfDescription']); ?>
</section>

<!-- Context Area -->
<section id="contextArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('Context area'), $headingsCondition, $headingsUrl, ['anchor' => 'context-collapse']); ?>

  <div class="creatorHistories">
    <?php echo get_component('informationobject', 'creatorDetail', ['resource' => $resource]); ?>
  </div>

  <div class="repository">
    <?php echo render_show_repository(__('Repository'), $resource); ?>
  </div>
</section>

<!-- Museum Object Information -->
<section id="museumObjectArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('Museum object information'), $headingsCondition, $headingsUrl, ['anchor' => 'museum-collapse']); ?>

  <?php if (!empty($museumData)) { ?>

    <?php // Object Identification ?>
    <?php if ($hasObjectIdentification) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Object identification'); ?></h4>
      <?php if (!empty($museumData['object_number'])) { echo render_show(__('Object number'), $museumData['object_number']); } ?>
      <?php if (!empty($museumData['work_type'])) { echo render_show(__('Work type'), $museumData['work_type']); } ?>
      <?php if (!empty($museumData['title_type'])) { echo render_show(__('Title type'), $museumData['title_type']); } ?>
    <?php } ?>

    <?php // Creator Information ?>
    <?php if ($hasCreatorInfo) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Creator information'); ?></h4>
      <?php if (!empty($museumData['creator_display'])) { echo render_show(__('Creator (display)'), $museumData['creator_display']); } ?>
      <?php if (!empty($museumData['creator_role'])) { echo render_show(__('Creator role'), $museumData['creator_role']); } ?>
      <?php if (!empty($museumData['attribution_qualifier'])) { echo render_show(__('Attribution'), $attributionLabels[$museumData['attribution_qualifier']] ?? $museumData['attribution_qualifier']); } ?>
    <?php } ?>

    <?php // Creation Context ?>
    <?php if ($hasCreationContext) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Creation context'); ?></h4>
      <?php if (!empty($museumData['creation_date_display'])) { echo render_show(__('Date of creation'), $museumData['creation_date_display']); } ?>
      <?php if (!empty($museumData['creation_place'])) { echo render_show(__('Place of creation'), $museumData['creation_place']); } ?>
      <?php if (!empty($museumData['culture'])) { echo render_show(__('Culture/People'), $museumData['culture']); } ?>
      <?php if (!empty($museumData['style'])) { echo render_show(__('Style'), $museumData['style']); } ?>
      <?php if (!empty($museumData['period'])) { echo render_show(__('Period'), $museumData['period']); } ?>
    <?php } ?>

    <?php // Physical Description ?>
    <?php if ($hasPhysicalDescription) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Physical description'); ?></h4>
      <?php if (!empty($museumData['dimensions_display'])) { echo render_show(__('Dimensions'), $museumData['dimensions_display']); } ?>
      <?php if (!empty($museumData['materials_display'])) { echo render_show(__('Medium/Materials'), $museumData['materials_display']); } ?>
      <?php if (!empty($museumData['materials'])) { echo render_show(__('Materials (indexed)'), $museumData['materials']); } ?>
      <?php if (!empty($museumData['techniques'])) { echo render_show(__('Techniques'), $museumData['techniques']); } ?>
      <?php if (!empty($museumData['support'])) { echo render_show(__('Support'), $museumData['support']); } ?>
    <?php } ?>

    <?php // Subject/Content ?>
    <?php if ($hasSubjectContent) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Subject and content'); ?></h4>
      <?php if (!empty($museumData['subject_display'])) { echo render_show(__('Subject (display)'), $museumData['subject_display']); } ?>
      <?php if (!empty($museumData['subjects_depicted'])) { echo render_show(__('Subjects depicted'), $museumData['subjects_depicted']); } ?>
      <?php if (!empty($museumData['description'])) { echo render_show(__('Description'), $museumData['description']); } ?>
    <?php } ?>

    <?php // Inscriptions/Marks ?>
    <?php if ($hasInscriptions) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Inscriptions and marks'); ?></h4>
      <?php if (!empty($museumData['inscriptions'])) { echo render_show(__('Inscriptions'), $museumData['inscriptions']); } ?>
      <?php if (!empty($museumData['signature'])) { echo render_show(__('Signature'), $museumData['signature']); } ?>
    <?php } ?>

    <?php // Condition ?>
    <?php if ($hasCondition) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Condition'); ?></h4>
      <?php if (!empty($museumData['condition_summary'])) { echo render_show(__('Condition summary'), $conditionLabels[$museumData['condition_summary']] ?? $museumData['condition_summary']); } ?>
    <?php } ?>

    <?php // Provenance/Location ?>
    <?php if ($hasProvenance) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Provenance and location'); ?></h4>
      <?php if (!empty($museumData['provenance'])) { echo render_show(__('Provenance'), $museumData['provenance']); } ?>
      <?php if (!empty($museumData['current_location'])) { echo render_show(__('Current location'), $museumData['current_location']); } ?>
    <?php } ?>

  <?php } else { ?>
    <div class="field">
      <div class="text-muted"><?php echo __('No museum-specific data recorded.'); ?></div>
  <?php } ?>
</section>
<!-- Item Physical Location -->
<?php if (!empty($itemLocation)): ?>
<?php include_partial("informationobject/itemPhysicalLocationView", ["itemLocation" => $itemLocation]); ?>
<?php endif; ?>

<!-- GRAP Financial Section -->
<?php if (!empty($grapData)) { ?>
<section id="grapArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('GRAP Financial Compliance'), $headingsCondition, ['module' => 'grap', 'action' => 'edit', 'slug' => $rawResource->slug], ['anchor' => 'grap-collapse']); ?>
  
  <?php if (!empty($grapData['recognition_status'])) { ?>
    <?php $statusLabels = ['recognised' => 'Recognised', 'not_recognised' => 'Not Recognised']; ?>
    <?php echo render_show(__('Recognition status'), $statusLabels[$grapData['recognition_status']] ?? $grapData['recognition_status']); ?>
  <?php } ?>
  
  <?php if (!empty($grapData['initial_recognition_value'])) { ?>
    <?php echo render_show(__('Initial recognition value'), 'R ' . number_format($grapData['initial_recognition_value'], 2)); ?>
  <?php } ?>
  
  <?php if (!empty($grapData['asset_class'])) { ?>
    <?php $classLabels = ['heritage_asset' => 'Heritage Asset', 'operational_asset' => 'Operational Asset', 'investment' => 'Investment']; ?>
    <?php echo render_show(__('Asset class'), $classLabels[$grapData['asset_class']] ?? $grapData['asset_class']); ?>
  <?php } ?>
</section>
<?php } ?>

<!-- Rights Area -->
<?php if ($sf_user->isAuthenticated()) { ?>
<section id="rightsArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('Rights area'), $headingsCondition, $headingsUrl, ['anchor' => 'rights-collapse']); ?>
  <div class="relatedRights">
    <?php echo get_component('right', 'relatedRights', ['resource' => $resource]); ?>
  </div>
  
  <!-- Extended Rights Display -->
  <div class="extendedRights mt-3">
    <?php echo get_component('extendedRights', 'rightsDisplay', ['objectId' => $resource->id]); ?>
  </div>
</section>
<?php } ?>


<!-- Access Points Area -->
<section id="accessPointsArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('Access points'), $headingsCondition, $headingsUrl, ['anchor' => 'access-collapse']); ?>

  <div class="subjectAccessPoints">
    <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource]); ?>
  </div>
  <div class="placeAccessPoints">
    <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource]); ?>
  </div>
  <div class="nameAccessPoints">
    <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource, 'showActorEvents' => true]); ?>
  </div>
  <!-- EXTENDED RIGHTS AREA -->
  

</section>

<!-- Digital Object Metadata (Access Copies) -->
<?php if (0 < count($resource->digitalObjectsRelatedByobjectId) && EmbargoHelper::canViewDigitalObject($rawResource->id)): ?>
  <?php echo get_component('digitalobject', 'metadata', ['resource' => $resource->digitalObjectsRelatedByobjectId[0], 'object' => $resource]); ?>
<?php endif; ?>


<?php slot('after-content'); ?>

  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php echo get_component('object', 'gaInstitutionsDimension', ['resource' => $resource]); ?>
