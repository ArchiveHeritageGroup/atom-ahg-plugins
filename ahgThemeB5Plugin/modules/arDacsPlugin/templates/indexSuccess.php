<?php decorate_with('layout_3col'); ?>
<?php
// Load PII masking helper if privacy plugin is enabled
if (in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins())) {
    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/helper/PiiHelper.php';
}
function pii_filter($objectId, $content) {
    if (function_exists('pii_mask')) {
        return pii_mask($objectId, $content);
    }
    return $content;
}
?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>

  <h1><?php echo render_title($dacs); ?></h1>

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

  <?php if (QubitInformationObject::ROOT_ID != $resource->parentId) { ?>
    <?php echo include_partial('default/breadcrumb', ['resource' => $resource, 'objects' => $resource->getAncestors()->andSelf()->orderBy('lft')]); ?>
  <?php } ?>

  <?php echo get_component('default', 'translationLinks', ['resource' => $resource]); ?>

<?php end_slot(); ?>

<?php slot('context-menu'); ?>

  <nav>

    <?php echo get_partial('informationobject/actionIcons', ['resource' => $resource]); ?>

    <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>

    <?php $sidebar = true; include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_nameAccessPoints.php'); ?>

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

<?php if (0 < count($resource->digitalObjectsRelatedByobjectId)) { ?>
  <?php echo get_component('digitalobject', 'show', ['link' => $digitalObjectLink, 'resource' => $resource->digitalObjectsRelatedByobjectId[0], 'usageType' => QubitTerm::REFERENCE_ID]); ?>
<?php } ?>

<!-- TTS Button -->
<link rel="stylesheet" href="/plugins/ahgCorePlugin/web/css/tts.css">
<script src="/plugins/ahgCorePlugin/web/js/tts.js"></script>
<div class="mb-3">
  <button type="button" class="btn btn-sm btn-outline-secondary" data-tts-action="toggle" data-tts-target="#tts-content-area" title="<?php echo __('Read aloud (Alt+P to play/pause)'); ?>" data-bs-toggle="tooltip"><i class="fas fa-volume-up"></i></button>
</div>

<div id="tts-content-area" data-tts-content>

<?php
    // TODO: Move this to the controller when we only have B5 themes
    $headingsCondition = SecurityPrivileges::editCredentials($sf_user, 'informationObject');
    $headingsUrl = [$resource, 'module' => 'informationobject', 'action' => 'edit'];
?>

<section id="identityArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_identity_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Identity elements'),
        $headingsCondition,
        $headingsUrl,
        [
            'anchor' => 'identity-collapse',
            'class' => 0 < count($resource->digitalObjectsRelatedByobjectId) ? '' : 'rounded-top',
        ]
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Reference code'), $dacs->getProperty('referenceCode')); ?>

  <?php echo render_show_repository(__('Name and location of repository'), $resource); ?>

  <?php echo render_show(__('Level of description'), render_value_inline($resource->levelOfDescription)); ?>

  <?php echo render_show(__('Title'), render_value_inline($resource->getTitle(['cultureFallback' => true]))); ?>

  <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
    <?php echo render_b5_show_label(__('Date(s)')); ?>
    <div class="<?php echo render_b5_show_value_css_classes(); ?>">
      <ul class="<?php echo render_b5_show_list_css_classes(); ?>">
        <?php foreach ($resource->getDates() as $item) { ?>
          <li>
            <?php echo render_value_inline(Qubit::renderDateStartEnd($item->getDate(['cultureFallback' => true]), $item->startDate, $item->endDate)); ?> (<?php echo $item->getType(['cultureFallback' => true]); ?>)
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>

  <?php echo render_show(__('Extent'), render_value($resource->getCleanExtentAndMedium(['cultureFallback' => true]))); ?>

  <?php echo get_component('informationobject', 'creatorDetail', [
      'resource' => $resource,
      'creatorHistoryLabels' => $creatorHistoryLabels, ]); ?>

  <?php foreach ($functionRelations as $item) { ?>
    <?php foreach ($functionRelations as $item) { ?>
      <?php echo render_show(__('Related function'), link_to(render_title($item->subject->getLabel()), [$item->subject, 'module' => 'function'])); ?>
    <?php } ?>
  <?php } ?>

</section> <!-- /section#identityArea -->

<section id="contentAndStructureArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_content_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Content and structure elements'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'content-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Scope and content'), render_value(pii_filter($resource->id, $resource->getScopeAndContent(['cultureFallback' => true])))); ?>
  <?php if (function_exists('pii_has_redacted') && pii_has_redacted($resource->id)): ?>
  <div class="alert alert-warning py-1 px-2 mt-2">
    <i class="fas fa-shield-alt me-1"></i>
    <small><?php echo __('This record contains redacted PII'); ?></small>
  </div>
  <?php endif; ?>

  <?php echo render_show(__('System of arrangement'), render_value($resource->getArrangement(['cultureFallback' => true]))); ?>

</section> <!-- /section#contentAndStructureArea -->

<section id="conditionsOfAccessAndUseArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_conditions_of_access_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Conditions of access and use elements'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'conditions-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Conditions governing access'), render_value($resource->getAccessConditions(['cultureFallback' => true]))); ?>

  <?php if (check_field_visibility('app_element_visibility_dacs_physical_access')) { ?>
    <?php echo render_show(__('Physical access'), render_value($resource->getPhysicalCharacteristics(['cultureFallback' => true]))); ?>
  <?php } ?>

  <?php echo render_show(__('Technical access'), render_value($dacs->getProperty('technicalAccess', ['cultureFallback' => true]))); ?>

  <?php echo render_show(__('Conditions governing reproduction'), render_value($resource->getReproductionConditions(['cultureFallback' => true]))); ?>

  <?php
      $languages = [];
      foreach ($resource->language as $code) {
          $languages[] = format_language($code);
      }
      echo render_show(__('Languages of the material'), $languages);
  ?>

  <?php
      $scripts = [];
      foreach ($resource->script as $code) {
          $scripts[] = format_script($code);
      }
      echo render_show(__('Scripts of the material'), $scripts);
  ?>

  <?php echo render_show(__('Language and script notes'), render_value($dacs->getProperty('languageNotes'))); ?>

  <?php echo render_show(__('Finding aids'), render_value($resource->getFindingAids(['cultureFallback' => true]))); ?>

  <?php echo get_component('informationobject', 'findingAidLink', ['resource' => $resource]); ?>

</section> <!-- /section#conditionsOfAccessAndUseArea -->

<section id="acquisitionAndAppraisalArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_acquisition_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Acquisition and appraisal elements'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'acquisition-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Custodial history'), render_value($resource->getArchivalHistory(['cultureFallback' => true]))); ?>

  <?php if (check_field_visibility('app_element_visibility_isad_immediate_source')) { ?>
    <?php echo render_show(__('Immediate source of acquisition'), render_value($resource->getAcquisition(['cultureFallback' => true]))); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_appraisal_destruction')) { ?>
    <?php echo render_show(__('Appraisal, destruction and scheduling information'), render_value($resource->getAppraisal(['cultureFallback' => true]))); ?>
  <?php } ?>

  <?php echo render_show(__('Accruals'), render_value($resource->getAccruals(['cultureFallback' => true]))); ?>

</section> <!-- /section#acquisitionAndAppraisalArea -->

<section id="alliedMaterialsArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_materials_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Related materials elements'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'related-collapse']
    ); ?>
  <?php } ?>

  <?php echo render_show(__('Existence and location of originals'), render_value($resource->getLocationOfOriginals(['cultureFallback' => true]))); ?>

  <?php echo render_show(__('Existence and location of copies'), render_value($resource->getLocationOfCopies(['cultureFallback' => true]))); ?>

  <?php echo render_show(__('Related archival materials'), render_value($resource->getRelatedUnitsOfDescription(['cultureFallback' => true]))); ?>

  <?php echo get_partial('informationobject/relatedMaterialDescriptions', ['resource' => $resource, 'template' => 'isad']); ?>

  <?php foreach ($resource->getNotesByType(['noteTypeId' => QubitTerm::PUBLICATION_NOTE_ID]) as $item) { ?>
    <?php echo render_show(__('Publication notes'), render_value($item->getContent(['cultureFallback' => true]))); ?>
  <?php } ?>

</section> <!-- /section#alliedMaterialsArea -->

<section id="notesArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_notes_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Notes element'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'notes-collapse']
    ); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_notes')) { ?>

    <?php foreach ($resource->getNotesByType(['noteTypeId' => QubitTerm::GENERAL_NOTE_ID]) as $item) { ?>
      <?php echo render_show(__('General note'), render_value($item->getContent(['cultureFallback' => true]))); ?>
    <?php } ?>

    <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
      <?php echo render_b5_show_label(__('Specialized notes')); ?>
      <div class="<?php echo render_b5_show_value_css_classes(); ?>">
        <ul class="<?php echo render_b5_show_list_css_classes(); ?>">
          <?php foreach ($resource->getNotesByTaxonomy(['taxonomyId' => QubitTaxonomy::DACS_NOTE_ID]) as $item) { ?>
            <li><?php echo render_value_inline($item->type); ?>: <?php echo render_value_inline($item->getContent(['cultureFallback' => true])); ?></li>
          <?php } ?>
        </ul>
      </div>
    </div>

  <?php } ?>

  <?php echo get_partial('informationobject/alternativeIdentifiersIndex', ['resource' => $resource]); ?>

</section> <!-- /section#notesArea -->

<section id="descriptionControlArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_control_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Description control element'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'description-collapse']
    ); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_rules_conventions')) { ?>
    <?php echo render_show(__('Rules or conventions'), render_value($resource->getRules(['cultureFallback' => true]))); ?>
  <?php } ?>

  <?php if (check_field_visibility('app_element_visibility_isad_control_sources')) { ?>
    <?php echo render_show(__('Sources used'), render_value($resource->getSources(['cultureFallback' => true]))); ?>
  <?php } ?>

  <!-- TODO: Make $archivistsNotesComponent to include ISAD 3.7.3 Date(s) of description as the first note and editable -->

  <?php if (check_field_visibility('app_element_visibility_isad_control_archivists_notes')) { ?>
    <?php foreach ($resource->getNotesByType(['noteTypeId' => QubitTerm::ARCHIVIST_NOTE_ID]) as $item) { ?>
      <?php echo render_show(__('Archivist\'s note'), render_value($item->getContent(['cultureFallback' => true]))); ?>
    <?php } ?>
  <?php } ?>

</section> <!-- /section#descriptionControlArea -->

<section id="accessPointsArea" class="border-bottom">

  <?php if (check_field_visibility('app_element_visibility_dacs_access_points_area')) { ?>
    <?php echo render_b5_section_heading(
        __('Access points'),
        $headingsCondition,
        $headingsUrl,
        ['anchor' => 'access-collapse']
    ); ?>
  <?php } ?>

  <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource]); ?>

  <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource]); ?>

  <?php $sidebar = false; $showActorEvents = true; include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_nameAccessPoints.php'); ?>

  <?php echo get_partial('informationobject/genreAccessPoints', ['resource' => $resource]); ?>

</section> <!-- /section#accessPointsArea -->

<?php if ($sf_user->isAuthenticated()) { ?>

  <div class="section border-bottom" id="rightsArea">

    <?php if (\AtomExtensions\Services\AclService::check($resource, 'update')) { ?>
      <?php echo render_b5_section_heading(__('Rights area')); ?>
    <?php } ?>

    <?php echo get_component('right', 'relatedRights', ['resource' => $resource]); ?>

  </div> <!-- /section#rightsArea -->

<?php } ?>

<?php if (0 < count($resource->digitalObjectsRelatedByobjectId)) { ?>

  <?php echo get_component('digitalobject', 'metadata', ['resource' => $resource->digitalObjectsRelatedByobjectId[0], 'object' => $resource]); ?>

  <?php echo get_partial('digitalobject/rights', ['resource' => $resource->digitalObjectsRelatedByobjectId[0]]); ?>

<?php } ?>

<section id="accessionArea" class="border-bottom">

  <?php echo render_b5_section_heading(__('Accession area')); ?>

  <?php echo get_component('informationobject', 'accessions', ['resource' => $resource]); ?>

</section> <!-- /section#accessionArea -->

</div><!-- /TTS Content Area -->

<?php slot('after-content'); ?>
  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php echo get_component('object', 'gaInstitutionsDimension', ['resource' => $resource]); ?>
