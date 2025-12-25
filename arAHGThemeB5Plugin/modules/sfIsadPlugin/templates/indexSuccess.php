<?php decorate_with('layout_3col'); ?>
<?php echo "<!-- DEBUG: This is ISAD module -->\n"; ?>
<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>

  <?php echo get_component('informationobject', 'descriptionHeader', ['resource' => $resource, 'title' => (string) $isad]); ?>
  <?php include_partial('arSecurityClearance/securityBadge', ['resource' => $resource]); ?>
  <?php if (isset($errorSchema)) { ?>
    <div class="alert alert-danger" role="alert">
      <ul class="<?php echo render_b5_show_list_css_classes(); ?>">
        <?php foreach ($errorSchema as $error) { ?>
          <?php $error = sfOutputEscaper::unescape($error); ?>
          <li><?php echo $error->getMessage(); ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>
  <?php include_component("accessFilter", "accessBadge", ["objectId" => $resource->id]); ?>
  <?php if (QubitInformationObject::ROOT_ID != $resource->parentId) { ?>
    <?php echo include_partial('default/breadcrumb', ['resource' => $resource, 'objects' => $resource->getAncestors()->andSelf()->orderBy('lft')]); ?>
  <?php } ?>

  <?php echo get_component('default', 'translationLinks', ['resource' => $resource]); ?>

<?php end_slot(); ?>

<?php slot('context-menu'); ?>

  <nav>

    <?php echo get_partial('informationobject/actionIcons', ['resource' => $resource]); ?>

    <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>

    <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>

    <?php echo get_partial('informationobject/genreAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>

    <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>

    <?php if (check_field_visibility('app_element_visibility_physical_storage')) { ?>
      <?php echo get_component('physicalobject', 'contextMenu', ['resource' => $resource]); ?>
    <?php } ?>

  </nav>

<?php end_slot(); ?>

<?php slot('before-content'); ?>

  <?php echo get_component('digitalobject', 'imageflow', ['resource' => $resource]); ?>

<?php end_slot(); ?>

<?php use_helper('informationobject', 'DigitalObjectViewer'); ?>
<?php if (0 < count($resource->digitalObjectsRelatedByobjectId)) { // Multiple digital objects ?>
	<?php foreach ($resource->digitalObjectsRelatedByobjectId as $obj) { ?>
		<?php echo render_digital_object_viewer($resource, $obj); ?>
	<?php } ?>
<!-- Fix model-viewer containment -->
<style>
.iiif-viewer-container,
.viewer-container,
.model-wrapper,
[id^="viewer-area-"],
[id^="3d-viewer-"],
[id^="container-"] {
  position: relative !important;
  z-index: 1 !important;
  overflow: hidden !important;
  max-height: 600px !important;
  contain: layout style !important;
}
model-viewer {
  position: relative !important;
  display: block !important;
  height: 500px !important;
  max-height: 500px !important;
  overflow: hidden !important;
}
.osd-viewer {
  max-height: 500px !important;
}
</style>
<?php } ?>

<!-- User Action Buttons -->
<div class="card mb-4 mt-3" style="position: relative; z-index: 100;">
  <div class="card-body py-2">
    <div class="d-flex flex-wrap gap-2">
      <?php
        $userId = $this->context->user->getAttribute('user_id');
        $favorate_id = QubitFavorites::getByUserIDandObjectId($userId, $resource->id);
      ?>
      <?php echo link_to('<i class="fas fa-comment me-1"></i>' . __('Item Feedback'), [$resource, 'module' => 'informationobject', 'action' => 'editFeedback'], ['class' => 'btn btn-sm btn-outline-secondary']); ?>
      <?php if ('' != $favorate_id->id && '' != $userId): ?>
        <?php echo link_to('<i class="fas fa-heart-broken me-1"></i>' . __('Remove from Favorites'), [$resource, 'module' => 'informationobject', 'action' => 'removeFavorites'], ['class' => 'btn btn-sm btn-outline-danger']); ?>
      <?php elseif ('' != $userId): ?>
        <?php echo link_to('<i class="fas fa-heart me-1"></i>' . __('Add to Favorites'), [$resource, 'module' => 'informationobject', 'action' => 'addFavorites'], ['class' => 'btn btn-sm btn-outline-danger']); ?>
      <?php endif; ?>
      <?php echo link_to('<i class="fas fa-paper-plane me-1"></i>' . __('Request to Publish'), [$resource, 'module' => 'informationobject', 'action' => 'editRequestToPublish'], ['class' => 'btn btn-sm btn-outline-primary']); ?>
      <?php
        $cart_id = QubitCart::getByUserIDandObjectId($userId, $resource->id);
      ?>
      <?php if ('' != $cart_id->id && '' != $userId): ?>
        <?php echo link_to('<i class="fas fa-shopping-cart me-1"></i>' . __('Go to Cart'), [$resource, 'module' => 'cart', 'action' => 'browse'], ['class' => 'btn btn-sm btn-outline-success']); ?>
      <?php elseif ('' != $userId): ?>
        <?php echo link_to('<i class="fas fa-cart-plus me-1"></i>' . __('Add to Cart'), [$resource, 'module' => 'informationobject', 'action' => 'addCart'], ['class' => 'btn btn-sm btn-outline-success']); ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
    // TODO: Move this to the controller when we only have B5 themes
    $headingsCondition = SecurityPrivileges::editCredentials($sf_user, 'informationObject');
    $headingsUrl = [$resource, 'module' => 'informationobject', 'action' => 'edit'];
?>

<?php
    // TODO: Move this to the controller when we only have B5 themes
    $headingsCondition = SecurityPrivileges::editCredentials($sf_user, 'informationObject');
    $headingsUrl = [$resource, 'module' => 'informationobject', 'action' => 'edit'];
?>

<section id="identityArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_identity_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Identity area'),
        $headingsCondition,
        $headingsUrl,
        [
            'anchor' => 'identity-collapse',
            'class' => 0 < count($resource->digitalObjectsRelatedByobjectId) ? '' : 'rounded-top',
        ]
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Reference code'), $isad->referenceCode, ['fieldLabel' => 'referenceCode']); ?>

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

  <?php echo render_show(__('Extent and medium'), render_value($resource->getCleanExtentAndMedium(['cultureFallback' => true])), ['fieldLabel' => 'extentAndMedium']); ?>
</section> <!-- /section#identityArea -->

<section id="contextArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_context_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Context area'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'context-collapse']
    ); ?>
  <?php } ?>

  <div class="creatorHistories">
    <?php echo get_component('informationobject', 'creatorDetail', [
        'resource' => $resource,
        'creatorHistoryLabels' => $creatorHistoryLabels, ]); ?>
  </div>

  <div class="relatedFunctions">
    <?php foreach ($functionRelations as $item) { ?>
      <?php echo render_show(__('Related function'), link_to(render_title($item->subject->getLabel()), [$item->subject, 'module' => 'function'])); ?>
    <?php } ?>
  </div>

  <div class="repository">
    <?php echo render_show_repository(__('Repository'), $resource); ?>
  </div>

  <?php if (check_field_visibility('app_element_visibility_isad_archival_history')) { ?>
    <?php echo render_show(__('Archival history'), render_value($resource->getArchivalHistory(['cultureFallback' => true])), ['fieldLabel' => 'archivalHistory']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_immediate_source')) { ?>
    <?php echo render_show(__('Immediate source of acquisition or transfer'), render_value($resource->getAcquisition(['cultureFallback' => true])), ['fieldLabel' => 'immediateSourceOfAcquisitionOrTransfer']); ?>
  <?php } ?>

</section> <!-- /section#contextArea -->

<section id="contentAndStructureArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_content_and_structure_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Content and structure area'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'content-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Scope and content'), render_value($resource->getScopeAndContent(['cultureFallback' => true])), ['fieldLabel' => 'scopeAndContent']); ?>

  <?php if (check_field_visibility('app_element_visibility_isad_appraisal_destruction')) { ?>
    <?php echo render_show(__('Appraisal, destruction and scheduling'), render_value($resource->getAppraisal(['cultureFallback' => true])), ['fieldLabel' => 'appraisalDestructionAndScheduling']); ?>
  <?php } ?>

  <?php echo render_show(__('Accruals'), render_value($resource->getAccruals(['cultureFallback' => true])), ['fieldLabel' => 'accruals']); ?>

  <?php echo render_show(__('System of arrangement'), render_value($resource->getArrangement(['cultureFallback' => true])), ['fieldLabel' => 'systemOfArrangement']); ?>
</section> <!-- /section#contentAndStructureArea -->

<section id="conditionsOfAccessAndUseArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_conditions_of_access_use_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Conditions of access and use area'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'conditions-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Conditions governing access'), render_value($resource->getAccessConditions(['cultureFallback' => true])), ['fieldLabel' => 'conditionsGoverningAccess']); ?>

  <?php echo render_show(__('Conditions governing reproduction'), render_value($resource->getReproductionConditions(['cultureFallback' => true])), ['fieldLabel' => 'conditionsGoverningReproduction']); ?>

  <?php
      $languages = [];
      foreach ($resource->language as $code) {
          $languages[] = format_language($code);
      }
      echo render_show(__('Language of material'), $languages);
  ?>

  <?php
      $scripts = [];
      foreach ($resource->script as $code) {
          $scripts[] = format_script($code);
      }
      echo render_show(__('Script of material'), $scripts);
  ?>

  <?php echo render_show(__('Language and script notes'), render_value($isad->languageNotes), ['fieldLabel' => 'languageAndScriptNotes']); ?>

  <?php if (check_field_visibility('app_element_visibility_isad_physical_condition')) { ?>
    <?php echo render_show(__('Physical characteristics and technical requirements'), render_value($resource->getPhysicalCharacteristics(['cultureFallback' => true])), ['fieldLabel' => 'physicalCharacteristics']); ?>
  <?php } ?>

  <?php echo render_show(__('Finding aids'), render_value($resource->getFindingAids(['cultureFallback' => true])), ['fieldLabel' => 'findingAids']); ?>

  <?php echo get_component('informationobject', 'findingAidLink', ['resource' => $resource]); ?>

</section> <!-- /section#conditionsOfAccessAndUseArea -->

<section id="alliedMaterialsArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_allied_materials_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Allied materials area'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'allied-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Existence and location of originals'), render_value($resource->getLocationOfOriginals(['cultureFallback' => true])), ['fieldLabel' => 'existenceAndLocationOfOriginals']); ?>

  <?php echo render_show(__('Existence and location of copies'), render_value($resource->getLocationOfCopies(['cultureFallback' => true])), ['fieldLabel' => 'existenceAndLocationOfCopies']); ?>

  <?php echo render_show(__('Related units of description'), render_value($resource->getRelatedUnitsOfDescription(['cultureFallback' => true])), ['fieldLabel' => 'relatedUnitsOfDescription']); ?>

  <div class="relatedMaterialDescriptions">
    <?php echo get_partial('informationobject/relatedMaterialDescriptions', ['resource' => $resource, 'template' => 'isad']); ?>
  </div>

  <?php foreach ($resource->getNotesByType(['noteTypeId' => QubitTerm::PUBLICATION_NOTE_ID]) as $item) { ?>
    <?php echo render_show(__('Publication note'), render_value($item->getContent(['cultureFallback' => true])), ['fieldLabel' => 'publicationNote']); ?>
  <?php } ?>
</section> <!-- /section#alliedMaterialsArea -->

<section id="notesArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_notes_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Notes area'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'notes-collapse']
    ); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_notes')) { ?>
    <?php foreach ($resource->getNotesByType(['noteTypeId' => QubitTerm::GENERAL_NOTE_ID]) as $item) { ?>
      <?php echo render_show(__('Note'), render_value($item->getContent(['cultureFallback' => true])), ['fieldLabel' => 'generalNote']); ?>
    <?php } ?>
  <?php } ?>

  <div class="alternativeIdentifiers">
    <?php echo get_partial('informationobject/alternativeIdentifiersIndex', ['resource' => $resource]); ?>
  </div>
</section> <!-- /section#notesArea -->

<section id="accessPointsArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_access_points_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Access points'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'access-collapse']
    ); ?>
  <?php } ?>

  <div class="subjectAccessPoints">
    <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource]); ?>
  </div>

  <div class="placeAccessPoints">
    <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource]); ?>
  </div>

  <div class="nameAccessPoints">
    <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource, 'showActorEvents' => true]); ?>
  </div>

  <div class="genreAccessPoints">
    <?php echo get_partial('informationobject/genreAccessPoints', ['resource' => $resource]); ?>
  </div>
</section> <!-- /section#accessPointsArea -->

<section id="museumControlArea" class="border-bottom">
<?php use_helper('MuseumMetadata'); ?>

<!-- Add this where you want museum metadata to appear -->
<?php if (has_museum_metadata($resource)) { ?>
  <?php include_partial('informationobject/museumMetadata', ['resource' => $resource]) ?>
<?php } ?>
</section> 

<section id="descriptionControlArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_isad_description_control_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Description control area'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'description-collapse']
    ); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_description_identifier')) { ?>
    <?php echo render_show(__('Description identifier'), $resource->getDescriptionIdentifier(['cultureFallback' => true]), ['fieldLabel' => 'descriptionIdentifier']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_institution_identifier')) { ?>
    <?php echo render_show(__('Institution identifier'), $resource->getInstitutionResponsibleIdentifier(['cultureFallback' => true]), ['fieldLabel' => 'institutionIdentifier']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_rules_conventions')) { ?>
    <?php echo render_show(__('Rules and/or conventions used'), render_value($resource->getRules(['cultureFallback' => true])), ['fieldLabel' => 'rulesAndOrConventionsUsed']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_status')) { ?>
    <?php echo render_show(__('Status'), render_value_inline($resource->descriptionStatus), ['fieldLabel' => 'descriptionStatus']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_level_of_detail')) { ?>
    <?php echo render_show(__('Level of detail'), render_value_inline($resource->descriptionDetail), ['fieldLabel' => 'levelOfDetail']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_dates')) { ?>
    <?php echo render_show(__('Dates of creation revision deletion'), render_value($resource->getRevisionHistory(['cultureFallback' => true])), ['fieldLabel' => 'datesOfCreationRevisionDeletion']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_languages')) { ?>
    <?php
        $languages = [];
        foreach ($resource->languageOfDescription as $code) {
            $languages[] = format_language($code);
        }
        echo render_show(__('Language(s)'), $languages);
    ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_scripts')) { ?>
    <?php
        $scripts = [];
        foreach ($resource->scriptOfDescription as $code) {
            $scripts[] = format_script($code);
        }
        echo render_show(__('Script(s)'), $scripts);
    ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_sources')) { ?>
    <?php echo render_show(__('Sources'), render_value($resource->getSources(['cultureFallback' => true])), ['fieldLabel' => 'sources']); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_archivists_notes')) { ?>
    <?php foreach ($resource->getNotesByType(['noteTypeId' => QubitTerm::ARCHIVIST_NOTE_ID]) as $item) { ?>
      <?php echo render_show(__('Archivist\'s note'), render_value($item->getContent(['cultureFallback' => true])), ['fieldLabel' => 'archivistNote']); ?>
    <?php } ?>
  <?php } ?>

</section> <!-- /section#descriptionControlArea -->

<?php if ($sf_user->isAuthenticated()) { ?>

  <div class="section border-bottom" id="rightsArea">

    <?php echo render_b5_section_heading(__('Rights area')); ?>

    <div class="relatedRights">
      <?php echo get_component('right', 'relatedRights', ['resource' => $resource]); ?>
    </div>

  </div> <!-- /section#rightsArea -->

<?php } ?>
  <div class="digitalObjectMetadata">
	<?php echo get_component('digitalobject', 'metadata', ['resource' => $resource->digitalObjectsRelatedByobjectId[0], 'object' => $resource]); ?>
  </div>

<section id="accessionArea" class="border-bottom">

  <?php echo render_b5_section_heading(__('Accession area')); ?>

  <div class="accessions">
    <?php echo get_component('informationobject', 'accessions', ['resource' => $resource]); ?>
  </div>

  <!-- EXTENDED RIGHTS AREA -->
  <?php include_partial('informationobject/extendedRightsArea', ['resource' => $resource]); ?>

</section> <!-- /section#accessionArea -->

<?php include_component('arRicExplorer', 'ricPanel', ['resource' => $resource]); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?> 
<?php end_slot(); ?>

<?php echo get_component('object', 'gaInstitutionsDimension', ['resource' => $resource]); ?>
