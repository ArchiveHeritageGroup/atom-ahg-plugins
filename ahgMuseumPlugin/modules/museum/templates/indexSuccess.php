<?php
require_once dirname(__FILE__)."/../../../lib/MuseumConstants.php";
require_once sfConfig::get('sf_plugins_dir') . '/ahgUiOverridesPlugin/lib/helper/informationobjectHelper.php';
require_once sfConfig::get('sf_plugins_dir') . '/ahgUiOverridesPlugin/lib/helper/AhgLaravelHelper.php';
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
        // Object Identification
        'object_number' => $rawResource->identifier,
        'work_type' => $data['work_type'] ?? null,
        'title_type' => $data['object_type'] ?? null,
        'classification' => $data['classification'] ?? null,
        'object_class' => $data['object_class'] ?? null,
        'object_category' => $data['object_category'] ?? null,
        'object_sub_category' => $data['object_sub_category'] ?? null,

        // Creator Information
        'creator_display' => $data['creator_identity'] ?? null,
        'creator_role' => $data['creator_role'] ?? null,
        'creator_extent' => $data['creator_extent'] ?? null,
        'creator_qualifier' => $data['creator_qualifier'] ?? null,
        'attribution_qualifier' => $data['creator_attribution'] ?? null,

        // Creation Context
        'creation_date_display' => $data['creation_date_display'] ?? null,
        'creation_date_earliest' => $data['creation_date_earliest'] ?? null,
        'creation_date_latest' => $data['creation_date_latest'] ?? null,
        'creation_date_qualifier' => $data['creation_date_qualifier'] ?? null,
        'creation_place' => $data['creation_place'] ?? null,
        'creation_place_type' => $data['creation_place_type'] ?? null,
        'culture' => $data['cultural_context'] ?? null,
        'cultural_group' => $data['cultural_group'] ?? null,
        'style' => $data['style'] ?? null,
        'period' => $data['period'] ?? null,
        'style_period' => $data['style_period'] ?? null,
        'movement' => $data['movement'] ?? null,
        'school' => $data['school'] ?? null,
        'dynasty' => $data['dynasty'] ?? null,
        'discovery_place' => $data['discovery_place'] ?? null,
        'discovery_place_type' => $data['discovery_place_type'] ?? null,

        // Physical Description
        'dimensions_display' => $data['dimensions'] ?? null,
        'measurements' => $data['measurements'] ?? null,
        'materials' => $data['materials'] ?? null,
        'techniques' => $data['techniques'] ?? null,
        'technique_cco' => $data['technique_cco'] ?? null,
        'technique_qualifier' => $data['technique_qualifier'] ?? null,
        'color' => $data['color'] ?? null,
        'shape' => $data['shape'] ?? null,
        'orientation' => $data['orientation'] ?? null,
        'physical_appearance' => $data['physical_appearance'] ?? null,
        'facture_description' => $data['facture_description'] ?? null,
        'support' => null,

        // Edition / State
        'edition_description' => $data['edition_description'] ?? null,
        'edition_number' => $data['edition_number'] ?? null,
        'edition_size' => $data['edition_size'] ?? null,
        'state_description' => $data['state_description'] ?? null,
        'state_identification' => $data['state_identification'] ?? null,

        // Subject & Content
        'subject_indexing_type' => $data['subject_indexing_type'] ?? null,
        'subject_display' => $data['subject_display'] ?? null,
        'subject_extent' => $data['subject_extent'] ?? null,
        'description' => null,
        'historical_context' => $data['historical_context'] ?? null,
        'architectural_context' => $data['architectural_context'] ?? null,
        'archaeological_context' => $data['archaeological_context'] ?? null,

        // Inscriptions & Marks
        'inscriptions' => $data['inscription'] ?? $data['inscriptions'] ?? null,
        'inscription_transcription' => $data['inscription_transcription'] ?? null,
        'inscription_type' => $data['inscription_type'] ?? null,
        'inscription_location' => $data['inscription_location'] ?? null,
        'inscription_language' => $data['inscription_language'] ?? null,
        'inscription_translation' => $data['inscription_translation'] ?? null,
        'mark_type' => $data['mark_type'] ?? null,
        'mark_description' => $data['mark_description'] ?? null,
        'mark_location' => $data['mark_location'] ?? null,

        // Condition & Treatment
        'condition_summary' => $data['condition_term'] ?? null,
        'condition_description' => $data['condition_description'] ?? null,
        'condition_date' => $data['condition_date'] ?? null,
        'condition_agent' => $data['condition_agent'] ?? null,
        'condition_notes' => $data['condition_notes'] ?? null,
        'treatment_type' => $data['treatment_type'] ?? null,
        'treatment_description' => $data['treatment_description'] ?? null,
        'treatment_date' => $data['treatment_date'] ?? null,
        'treatment_agent' => $data['treatment_agent'] ?? null,

        // Provenance & Location
        'provenance' => $data['provenance'] ?? null,
        'provenance_text' => $data['provenance_text'] ?? null,
        'ownership_history' => $data['ownership_history'] ?? null,
        'current_location' => $data['current_location'] ?? null,
        'current_location_repository' => $data['current_location_repository'] ?? null,
        'current_location_geography' => $data['current_location_geography'] ?? null,
        'current_location_coordinates' => $data['current_location_coordinates'] ?? null,
        'current_location_ref_number' => $data['current_location_ref_number'] ?? null,

        // Rights
        'legal_status' => $data['legal_status'] ?? null,
        'rights_type' => $data['rights_type'] ?? null,
        'rights_holder' => $data['rights_holder'] ?? null,
        'rights_date' => $data['rights_date'] ?? null,
        'rights_remarks' => $data['rights_remarks'] ?? null,

        // Related Works
        'related_work_type' => $data['related_work_type'] ?? null,
        'related_work_relationship' => $data['related_work_relationship'] ?? null,
        'related_work_label' => $data['related_work_label'] ?? null,
        'related_work_id' => $data['related_work_id'] ?? null,

        // Cataloging
        'cataloger_name' => $data['cataloger_name'] ?? null,
        'cataloging_date' => $data['cataloging_date'] ?? null,
        'cataloging_institution' => $data['cataloging_institution'] ?? null,
        'cataloging_remarks' => $data['cataloging_remarks'] ?? null,
        'record_type' => $data['record_type'] ?? null,
        'record_level' => $data['record_level'] ?? null,
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
$headingsUrl = ['module' => 'museum', 'action' => 'edit', 'slug' => $rawResource->slug];

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
$hasObjectIdentification = !empty($museumData['object_number']) || !empty($museumData['work_type']) || !empty($museumData['title_type']) || !empty($museumData['classification']) || !empty($museumData['object_class']) || !empty($museumData['object_category']) || !empty($museumData['object_sub_category']);
$hasCreatorInfo = !empty($museumData['creator_display']) || !empty($museumData['creator_role']) || !empty($museumData['creator_extent']) || !empty($museumData['creator_qualifier']) || !empty($museumData['attribution_qualifier']);
$hasCreationContext = !empty($museumData['creation_date_display']) || !empty($museumData['creation_date_earliest']) || !empty($museumData['creation_date_latest']) || !empty($museumData['creation_date_qualifier']) || !empty($museumData['creation_place']) || !empty($museumData['creation_place_type']) || !empty($museumData['culture']) || !empty($museumData['cultural_group']) || !empty($museumData['style']) || !empty($museumData['period']) || !empty($museumData['style_period']) || !empty($museumData['movement']) || !empty($museumData['school']) || !empty($museumData['dynasty']) || !empty($museumData['discovery_place']) || !empty($museumData['discovery_place_type']);
$hasPhysicalDescription = !empty($museumData['dimensions_display']) || !empty($museumData['measurements']) || !empty($museumData['materials']) || !empty($museumData['techniques']) || !empty($museumData['technique_cco']) || !empty($museumData['technique_qualifier']) || !empty($museumData['color']) || !empty($museumData['shape']) || !empty($museumData['orientation']) || !empty($museumData['physical_appearance']) || !empty($museumData['facture_description']) || !empty($museumData['support']);
$hasEditionState = !empty($museumData['edition_description']) || !empty($museumData['edition_number']) || !empty($museumData['edition_size']) || !empty($museumData['state_description']) || !empty($museumData['state_identification']);
$hasSubjectContent = !empty($museumData['subject_indexing_type']) || !empty($museumData['subject_display']) || !empty($museumData['subject_extent']) || !empty($museumData['description']) || !empty($museumData['historical_context']) || !empty($museumData['architectural_context']) || !empty($museumData['archaeological_context']);
$hasInscriptions = !empty($museumData['inscriptions']) || !empty($museumData['inscription_transcription']) || !empty($museumData['inscription_type']) || !empty($museumData['inscription_location']) || !empty($museumData['inscription_language']) || !empty($museumData['inscription_translation']) || !empty($museumData['mark_type']) || !empty($museumData['mark_description']) || !empty($museumData['mark_location']);
$hasCondition = !empty($museumData['condition_summary']) || !empty($museumData['condition_description']) || !empty($museumData['condition_date']) || !empty($museumData['condition_agent']) || !empty($museumData['condition_notes']) || !empty($museumData['treatment_type']) || !empty($museumData['treatment_description']) || !empty($museumData['treatment_date']) || !empty($museumData['treatment_agent']);
$hasProvenance = !empty($museumData['provenance']) || !empty($museumData['provenance_text']) || !empty($museumData['ownership_history']) || !empty($museumData['current_location']) || !empty($museumData['current_location_repository']) || !empty($museumData['current_location_geography']) || !empty($museumData['current_location_coordinates']) || !empty($museumData['current_location_ref_number']);
$hasRights = !empty($museumData['legal_status']) || !empty($museumData['rights_type']) || !empty($museumData['rights_holder']) || !empty($museumData['rights_date']) || !empty($museumData['rights_remarks']);
$hasRelatedWorks = !empty($museumData['related_work_type']) || !empty($museumData['related_work_relationship']) || !empty($museumData['related_work_label']) || !empty($museumData['related_work_id']);
$hasCataloging = !empty($museumData['cataloger_name']) || !empty($museumData['cataloging_date']) || !empty($museumData['cataloging_institution']) || !empty($museumData['cataloging_remarks']) || !empty($museumData['record_type']) || !empty($museumData['record_level']);
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
    <?php $sidebar = true; include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_nameAccessPoints.php'); ?>
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

<link rel="stylesheet" href="/plugins/ahgCorePlugin/web/css/tts.css">
<script src="/plugins/ahgCorePlugin/web/js/tts.js"></script>

<div id="tts-content-area" data-tts-content>

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
<!-- User Actions (compact with tooltips) -->
<?php
$userId = $sf_user->getAttribute('user_id');
$sessionId = session_id();
if (empty($sessionId) && !$userId) { @session_start(); $sessionId = session_id(); }
$favoriteId = null;
$cartId = null;
if ($userId) {
    $favoriteId = DB::table('favorites')->where('user_id', $userId)->where('archival_description_id', $rawResource->id)->value('id');
    $cartId = DB::table('cart')->where('user_id', $userId)->where('archival_description_id', $rawResource->id)->whereNull('completed_at')->value('id');
} elseif ($sessionId) {
    $cartId = DB::table('cart')->where('session_id', $sessionId)->where('archival_description_id', $rawResource->id)->whereNull('completed_at')->value('id');
}
$hasDigitalObject = DB::table('digital_object')->where('object_id', $rawResource->id)->exists();
$pdfDigitalObject = DB::table('digital_object')->where('object_id', $rawResource->id)->where('mime_type', 'application/pdf')->first();
?>
<div class="d-flex flex-wrap gap-1 mb-3 align-items-center">
  <!-- TTS Button -->
  <button type="button" class="btn btn-sm btn-outline-secondary" data-tts-action="toggle" data-tts-target="#tts-content-area" title="<?php echo __('Read metadata aloud'); ?>" data-bs-toggle="tooltip"><i class="fas fa-volume-up"></i></button>
  <?php if ($pdfDigitalObject): ?>
  <button type="button" class="btn btn-sm btn-outline-info" data-tts-action="read-pdf" data-tts-pdf-id="<?php echo $pdfDigitalObject->id; ?>" title="<?php echo __('Read PDF content aloud'); ?>" data-bs-toggle="tooltip"><i class="fas fa-file-pdf"></i></button>
  <?php endif; ?>
  <?php if (in_array('ahgFavoritesPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $userId): ?>
    <?php if ($favoriteId): ?>
      <a href="<?php echo url_for(['module' => 'favorites', 'action' => 'remove', 'id' => $favoriteId]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Remove from Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart-broken"></i></a>
    <?php else: ?>
      <a href="<?php echo url_for(['module' => 'favorites', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Add to Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart"></i></a>
    <?php endif; ?>
  <?php endif; ?>
  <?php if (in_array('ahgFeedbackPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
    <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-secondary" title="<?php echo __('Item Feedback'); ?>" data-bs-toggle="tooltip"><i class="fas fa-comment"></i></a>
  <?php endif; ?>
  <?php if (in_array('ahgRequestToPublishPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $hasDigitalObject): ?>
    <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-primary" title="<?php echo __('Request to Publish'); ?>" data-bs-toggle="tooltip"><i class="fas fa-paper-plane"></i></a>
  <?php endif; ?>
  <?php if (in_array('ahgCartPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $hasDigitalObject): ?>
    <?php if ($cartId): ?>
      <a href="<?php echo url_for(['module' => 'cart', 'action' => 'browse']); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Go to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-shopping-cart"></i></a>
    <?php else: ?>
      <a href="<?php echo url_for(['module' => 'cart', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Add to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-cart-plus"></i></a>
    <?php endif; ?>
  <?php endif; ?>
  <?php if (in_array('ahgLoanPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $sf_user->isAuthenticated()): ?>
    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'add', 'type' => 'out', 'sector' => 'museum', 'object_id' => $rawResource->id]); ?>" class="btn btn-xs btn-outline-warning" title="<?php echo __('New Loan'); ?>" data-bs-toggle="tooltip"><i class="fas fa-hand-holding"></i></a>
    <a href="<?php echo url_for(['module' => 'loan', 'action' => 'index', 'sector' => 'museum', 'object_id' => $rawResource->id]); ?>" class="btn btn-xs btn-outline-info" title="<?php echo __('Manage Loans'); ?>" data-bs-toggle="tooltip"><i class="fas fa-exchange-alt"></i></a>
  <?php endif; ?>
</div>
<section id="identityArea" class="border-bottom">
  <?php echo render_b5_section_heading(
      __('Identity area'),
      $headingsCondition,
      $headingsUrl,
      ['anchor' => 'identity-collapse', 'class' => 0 < count($resource->digitalObjectsRelatedByobjectId) ? '' : 'rounded-top']
  ); ?>

  <?php echo render_show(__('Reference code'), $resource->identifier, ['fieldLabel' => 'referenceCode']); ?>
  <?php echo render_show(__('Title'), $resource->title ?? $resource->slug, ['fieldLabel' => 'title']); ?>

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
      <?php if (!empty($museumData['classification'])) { echo render_show(__('Classification'), $museumData['classification']); } ?>
      <?php if (!empty($museumData['object_class'])) { echo render_show(__('Object class'), $museumData['object_class']); } ?>
      <?php if (!empty($museumData['object_category'])) { echo render_show(__('Object category'), $museumData['object_category']); } ?>
      <?php if (!empty($museumData['object_sub_category'])) { echo render_show(__('Object sub-category'), $museumData['object_sub_category']); } ?>
    <?php } ?>

    <?php // Creator Information ?>
    <?php if ($hasCreatorInfo) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Creator information'); ?></h4>
      <?php if (!empty($museumData['creator_display'])) { echo render_show(__('Creator (display)'), $museumData['creator_display']); } ?>
      <?php if (!empty($museumData['creator_role'])) { echo render_show(__('Creator role'), $museumData['creator_role']); } ?>
      <?php if (!empty($museumData['creator_extent'])) { echo render_show(__('Creator extent'), $museumData['creator_extent']); } ?>
      <?php if (!empty($museumData['creator_qualifier'])) { echo render_show(__('Creator qualifier'), $museumData['creator_qualifier']); } ?>
      <?php if (!empty($museumData['attribution_qualifier'])) { echo render_show(__('Attribution'), $attributionLabels[$museumData['attribution_qualifier']] ?? $museumData['attribution_qualifier']); } ?>
    <?php } ?>

    <?php // Creation Context ?>
    <?php if ($hasCreationContext) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Creation context'); ?></h4>
      <?php if (!empty($museumData['creation_date_display'])) { echo render_show(__('Date of creation'), $museumData['creation_date_display']); } ?>
      <?php if (!empty($museumData['creation_date_earliest'])) { echo render_show(__('Earliest date'), $museumData['creation_date_earliest']); } ?>
      <?php if (!empty($museumData['creation_date_latest'])) { echo render_show(__('Latest date'), $museumData['creation_date_latest']); } ?>
      <?php if (!empty($museumData['creation_date_qualifier'])) { echo render_show(__('Date qualifier'), $museumData['creation_date_qualifier']); } ?>
      <?php if (!empty($museumData['creation_place'])) { echo render_show(__('Place of creation'), $museumData['creation_place']); } ?>
      <?php if (!empty($museumData['creation_place_type'])) { echo render_show(__('Place type'), $museumData['creation_place_type']); } ?>
      <?php if (!empty($museumData['culture'])) { echo render_show(__('Culture/People'), $museumData['culture']); } ?>
      <?php if (!empty($museumData['cultural_group'])) { echo render_show(__('Cultural group'), $museumData['cultural_group']); } ?>
      <?php if (!empty($museumData['style'])) { echo render_show(__('Style'), $museumData['style']); } ?>
      <?php if (!empty($museumData['period'])) { echo render_show(__('Period'), $museumData['period']); } ?>
      <?php if (!empty($museumData['style_period'])) { echo render_show(__('Style/Period'), $museumData['style_period']); } ?>
      <?php if (!empty($museumData['movement'])) { echo render_show(__('Movement'), $museumData['movement']); } ?>
      <?php if (!empty($museumData['school'])) { echo render_show(__('School'), $museumData['school']); } ?>
      <?php if (!empty($museumData['dynasty'])) { echo render_show(__('Dynasty'), $museumData['dynasty']); } ?>
      <?php if (!empty($museumData['discovery_place'])) { echo render_show(__('Discovery place'), $museumData['discovery_place']); } ?>
      <?php if (!empty($museumData['discovery_place_type'])) { echo render_show(__('Discovery place type'), $museumData['discovery_place_type']); } ?>
    <?php } ?>

    <?php // Physical Description ?>
    <?php if ($hasPhysicalDescription) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Physical description'); ?></h4>
      <?php if (!empty($museumData['dimensions_display'])) { echo render_show(__('Dimensions'), $museumData['dimensions_display']); } ?>
      <?php if (!empty($museumData['measurements'])) { echo render_show(__('Measurements'), $museumData['measurements']); } ?>
      <?php if (!empty($museumData['materials'])) { echo render_show(__('Materials'), $museumData['materials']); } ?>
      <?php if (!empty($museumData['techniques'])) { echo render_show(__('Techniques'), $museumData['techniques']); } ?>
      <?php if (!empty($museumData['technique_cco'])) { echo render_show(__('Technique (CCO)'), $museumData['technique_cco']); } ?>
      <?php if (!empty($museumData['technique_qualifier'])) { echo render_show(__('Technique qualifier'), $museumData['technique_qualifier']); } ?>
      <?php if (!empty($museumData['color'])) { echo render_show(__('Colour'), $museumData['color']); } ?>
      <?php if (!empty($museumData['shape'])) { echo render_show(__('Shape'), $museumData['shape']); } ?>
      <?php if (!empty($museumData['orientation'])) { echo render_show(__('Orientation'), $museumData['orientation']); } ?>
      <?php if (!empty($museumData['physical_appearance'])) { echo render_show(__('Physical appearance'), $museumData['physical_appearance']); } ?>
      <?php if (!empty($museumData['facture_description'])) { echo render_show(__('Facture'), $museumData['facture_description']); } ?>
      <?php if (!empty($museumData['support'])) { echo render_show(__('Support'), $museumData['support']); } ?>
    <?php } ?>

    <?php // Edition / State ?>
    <?php if ($hasEditionState) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Edition and state'); ?></h4>
      <?php if (!empty($museumData['edition_description'])) { echo render_show(__('Edition description'), $museumData['edition_description']); } ?>
      <?php if (!empty($museumData['edition_number'])) { echo render_show(__('Edition number'), $museumData['edition_number']); } ?>
      <?php if (!empty($museumData['edition_size'])) { echo render_show(__('Edition size'), $museumData['edition_size']); } ?>
      <?php if (!empty($museumData['state_description'])) { echo render_show(__('State description'), $museumData['state_description']); } ?>
      <?php if (!empty($museumData['state_identification'])) { echo render_show(__('State identification'), $museumData['state_identification']); } ?>
    <?php } ?>

    <?php // Subject & Content ?>
    <?php if ($hasSubjectContent) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Subject and content'); ?></h4>
      <?php if (!empty($museumData['subject_indexing_type'])) { echo render_show(__('Subject indexing type'), $museumData['subject_indexing_type']); } ?>
      <?php if (!empty($museumData['subject_display'])) { echo render_show(__('Subject (display)'), $museumData['subject_display']); } ?>
      <?php if (!empty($museumData['subject_extent'])) { echo render_show(__('Subject extent'), $museumData['subject_extent']); } ?>
      <?php if (!empty($museumData['description'])) { echo render_show(__('Description'), $museumData['description']); } ?>
      <?php if (!empty($museumData['historical_context'])) { echo render_show(__('Historical context'), $museumData['historical_context']); } ?>
      <?php if (!empty($museumData['architectural_context'])) { echo render_show(__('Architectural context'), $museumData['architectural_context']); } ?>
      <?php if (!empty($museumData['archaeological_context'])) { echo render_show(__('Archaeological context'), $museumData['archaeological_context']); } ?>
    <?php } ?>

    <?php // Inscriptions & Marks ?>
    <?php if ($hasInscriptions) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Inscriptions and marks'); ?></h4>
      <?php if (!empty($museumData['inscriptions'])) { echo render_show(__('Inscriptions'), $museumData['inscriptions']); } ?>
      <?php if (!empty($museumData['inscription_transcription'])) { echo render_show(__('Inscription transcription'), $museumData['inscription_transcription']); } ?>
      <?php if (!empty($museumData['inscription_type'])) { echo render_show(__('Inscription type'), $museumData['inscription_type']); } ?>
      <?php if (!empty($museumData['inscription_location'])) { echo render_show(__('Inscription location'), $museumData['inscription_location']); } ?>
      <?php if (!empty($museumData['inscription_language'])) { echo render_show(__('Inscription language'), $museumData['inscription_language']); } ?>
      <?php if (!empty($museumData['inscription_translation'])) { echo render_show(__('Inscription translation'), $museumData['inscription_translation']); } ?>
      <?php if (!empty($museumData['mark_type'])) { echo render_show(__('Mark type'), $museumData['mark_type']); } ?>
      <?php if (!empty($museumData['mark_description'])) { echo render_show(__('Mark description'), $museumData['mark_description']); } ?>
      <?php if (!empty($museumData['mark_location'])) { echo render_show(__('Mark location'), $museumData['mark_location']); } ?>
    <?php } ?>

    <?php // Condition & Treatment ?>
    <?php if ($hasCondition) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Condition and treatment'); ?></h4>
      <?php if (!empty($museumData['condition_summary'])) { echo render_show(__('Condition'), $conditionLabels[$museumData['condition_summary']] ?? $museumData['condition_summary']); } ?>
      <?php if (!empty($museumData['condition_description'])) { echo render_show(__('Condition description'), $museumData['condition_description']); } ?>
      <?php if (!empty($museumData['condition_date'])) { echo render_show(__('Condition date'), $museumData['condition_date']); } ?>
      <?php if (!empty($museumData['condition_agent'])) { echo render_show(__('Condition agent'), $museumData['condition_agent']); } ?>
      <?php if (!empty($museumData['condition_notes'])) { echo render_show(__('Condition notes'), $museumData['condition_notes']); } ?>
      <?php if (!empty($museumData['treatment_type'])) { echo render_show(__('Treatment type'), $museumData['treatment_type']); } ?>
      <?php if (!empty($museumData['treatment_description'])) { echo render_show(__('Treatment description'), $museumData['treatment_description']); } ?>
      <?php if (!empty($museumData['treatment_date'])) { echo render_show(__('Treatment date'), $museumData['treatment_date']); } ?>
      <?php if (!empty($museumData['treatment_agent'])) { echo render_show(__('Treatment agent'), $museumData['treatment_agent']); } ?>
    <?php } ?>

    <?php // Provenance & Location ?>
    <?php if ($hasProvenance) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Provenance and location'); ?></h4>
      <?php if (!empty($museumData['provenance'])) { echo render_show(__('Provenance'), $museumData['provenance']); } ?>
      <?php if (!empty($museumData['provenance_text'])) { echo render_show(__('Provenance text'), $museumData['provenance_text']); } ?>
      <?php if (!empty($museumData['ownership_history'])) { echo render_show(__('Ownership history'), $museumData['ownership_history']); } ?>
      <?php if (!empty($museumData['current_location'])) { echo render_show(__('Current location'), $museumData['current_location']); } ?>
      <?php if (!empty($museumData['current_location_repository'])) { echo render_show(__('Repository'), $museumData['current_location_repository']); } ?>
      <?php if (!empty($museumData['current_location_geography'])) { echo render_show(__('Geography'), $museumData['current_location_geography']); } ?>
      <?php if (!empty($museumData['current_location_coordinates'])) { echo render_show(__('Coordinates'), $museumData['current_location_coordinates']); } ?>
      <?php if (!empty($museumData['current_location_ref_number'])) { echo render_show(__('Reference number'), $museumData['current_location_ref_number']); } ?>
    <?php } ?>

    <?php // Rights (museum-metadata level) ?>
    <?php if ($hasRights) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Rights information'); ?></h4>
      <?php if (!empty($museumData['legal_status'])) { echo render_show(__('Legal status'), $museumData['legal_status']); } ?>
      <?php if (!empty($museumData['rights_type'])) { echo render_show(__('Rights type'), $museumData['rights_type']); } ?>
      <?php if (!empty($museumData['rights_holder'])) { echo render_show(__('Rights holder'), $museumData['rights_holder']); } ?>
      <?php if (!empty($museumData['rights_date'])) { echo render_show(__('Rights date'), $museumData['rights_date']); } ?>
      <?php if (!empty($museumData['rights_remarks'])) { echo render_show(__('Rights remarks'), $museumData['rights_remarks']); } ?>
    <?php } ?>

    <?php // Related Works ?>
    <?php if ($hasRelatedWorks) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Related works'); ?></h4>
      <?php if (!empty($museumData['related_work_type'])) { echo render_show(__('Related work type'), $museumData['related_work_type']); } ?>
      <?php if (!empty($museumData['related_work_relationship'])) { echo render_show(__('Relationship'), $museumData['related_work_relationship']); } ?>
      <?php if (!empty($museumData['related_work_label'])) { echo render_show(__('Related work'), $museumData['related_work_label']); } ?>
      <?php if (!empty($museumData['related_work_id'])) { echo render_show(__('Related work ID'), $museumData['related_work_id']); } ?>
    <?php } ?>

    <?php // Cataloging ?>
    <?php if ($hasCataloging) { ?>
      <h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Cataloging information'); ?></h4>
      <?php if (!empty($museumData['cataloger_name'])) { echo render_show(__('Cataloger'), $museumData['cataloger_name']); } ?>
      <?php if (!empty($museumData['cataloging_date'])) { echo render_show(__('Cataloging date'), $museumData['cataloging_date']); } ?>
      <?php if (!empty($museumData['cataloging_institution'])) { echo render_show(__('Cataloging institution'), $museumData['cataloging_institution']); } ?>
      <?php if (!empty($museumData['cataloging_remarks'])) { echo render_show(__('Cataloging remarks'), $museumData['cataloging_remarks']); } ?>
      <?php if (!empty($museumData['record_type'])) { echo render_show(__('Record type'), $museumData['record_type']); } ?>
      <?php if (!empty($museumData['record_level'])) { echo render_show(__('Record level'), $museumData['record_level']); } ?>
    <?php } ?>

  <?php } else { ?>
    <div class="field">
      <div class="text-muted"><?php echo __('No museum-specific data recorded.'); ?></div>
  <?php } ?>
</section>
<!-- Item Physical Location -->
<?php if (!empty($itemLocation)): ?>
<?php if (file_exists(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_itemPhysicalLocationView.php')) { include_partial('informationobject/itemPhysicalLocationView', ['itemLocation' => $itemLocation]); } ?>
<?php endif; ?>

<!-- GRAP Financial Section -->
<?php if (!empty($grapData) && ahg_is_plugin_enabled('ahgHeritageAccountingPlugin')) { ?>
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
    <?php $sidebar = false; $showActorEvents = true; include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_nameAccessPoints.php'); ?>
  </div>
  <!-- EXTENDED RIGHTS AREA -->
  

</section>

<!-- Digital Object Metadata (Access Copies) -->
<?php if (0 < count($resource->digitalObjectsRelatedByobjectId) && EmbargoHelper::canViewDigitalObject($rawResource->id)): ?>
  <?php echo get_component('digitalobject', 'metadata', ['resource' => $resource->digitalObjectsRelatedByobjectId[0], 'object' => $resource]); ?>
<?php endif; ?>


<!-- Provenance & Chain of Custody -->
<?php if (in_array('ahgProvenancePlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
<section id="provenanceArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('Provenance & Chain of Custody'), $headingsCondition, $headingsUrl, ['anchor' => 'provenance-collapse']); ?>
  <?php include_component('provenance', 'provenanceDisplay', ['objectId' => $resource->id]); ?>
</section>
<?php endif ?>

</div><!-- /TTS Content Area -->

<?php slot('after-content'); ?>

  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php echo get_component('object', 'gaInstitutionsDimension', ['resource' => $resource]); ?>
