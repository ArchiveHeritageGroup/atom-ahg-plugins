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

  <?php echo get_component('informationobject', 'descriptionHeader', ['resource' => $resource, 'title' => (string) $mods]); ?>

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

<section id="elementsArea" class="border-bottom">

  <?php echo render_b5_section_heading(
      __('Elements area'),
      SecurityPrivileges::editCredentials($sf_user, 'informationObject'),
      [$resource, 'module' => 'informationobject', 'action' => 'edit'],
      ['anchor' => 'elements-collapse', 'class' => 'rounded-top']
  ); ?>

  <?php echo render_show(__('Identifier'), $resource->identifier); ?>

  <?php echo render_show(__('Title'), render_value_inline($resource->getTitle(['cultureFallback' => true]))); ?>

  <?php echo get_partial('informationobject/dates', ['resource' => $resource]); ?>

  <?php
      $types = [];
      foreach ($mods->typeOfResource as $item) {
          $types[] = $item->term;
      }
      echo render_show(__('Types of resource'), $types);
  ?>

  <?php
      $languages = [];
      foreach ($resource->language as $code) {
          $languages[] = format_language($code);
      }
      echo render_show(__('Languages'), $languages);
  ?>

  <?php if (0 < count($resource->digitalObjectsRelatedByobjectId)) { ?>
    <?php echo render_show(__('Internet media type'), render_value_inline($resource->digitalObjectsRelatedByobjectId[0]->mimeType)); ?>
  <?php } ?>

  <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource, 'mods' => true]); ?>

  <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource, 'mods' => true]); ?>

  <?php $sidebar = false; $mods = true; $showActorEvents = true; include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_nameAccessPoints.php'); ?>

  <?php echo render_show(__('Access condition'), render_value($resource->getAccessConditions(['cultureFallback' => true]))); ?>

  <?php if (0 < count($resource->digitalObjectsRelatedByobjectId)) { ?>
    <?php echo render_show(__('URL'), link_to(null, $resource->getDigitalObjectPublicUrl()), ['valueClass' => 'text-break']); ?>
  <?php } ?>

  <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
    <?php echo render_b5_show_label(__('Physical location')); ?>
    <div class="<?php echo render_b5_show_value_css_classes(); ?>">
      <?php if (isset($resource->repository)) { ?>

        <?php if (isset($resource->repository->identifier)) { ?>
          <?php echo render_value_inline($resource->repository->identifier); ?> -
        <?php } ?>

        <?php echo link_to(render_title($resource->repository), [$resource->repository, 'module' => 'repository']); ?>

        <?php if (null !== $contact = $resource->repository->getPrimaryContact()) { ?>

          <?php if (isset($contact->city)) { ?>
            <?php echo render_value_inline($contact->city); ?>
          <?php } ?>

          <?php if (isset($contact->region)) { ?>
            <?php echo render_value_inline($contact->region); ?>
          <?php } ?>

          <?php if (isset($contact->countryCode)) { ?>
            <?php echo format_country($contact->countryCode); ?>
          <?php } ?>

        <?php } ?>

      <?php } ?>
    </div>
  </div>

  <?php echo render_show(__('Description'), render_value(pii_filter($resource->id, $resource->getScopeAndContent(['cultureFallback' => true])))); ?>
  <?php if (function_exists('pii_has_redacted') && pii_has_redacted($resource->id)): ?>
  <div class="alert alert-warning py-1 px-2 mt-2">
    <i class="fas fa-shield-alt me-1"></i>
    <small><?php echo __('This record contains redacted PII'); ?></small>
  </div>
  <?php endif; ?>

</section> <!-- /section#elementsArea -->

<?php if ($sf_user->isAuthenticated()) { ?>

  <section id="rightsArea" class="border-bottom">

    <?php echo render_b5_section_heading(__('Rights area')); ?>

    <?php echo get_component('right', 'relatedRights', ['resource' => $resource]); ?>

  </section> <!-- /section#rightsArea -->

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
