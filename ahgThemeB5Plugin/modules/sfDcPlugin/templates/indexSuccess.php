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

  <?php echo get_component('informationobject', 'descriptionHeader', ['resource' => $resource, 'title' => (string) $dc, 'hideLevelOfDescription' => true]); ?>

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
      [
          'anchor' => 'elements-collapse',
          'class' => 0 < count($resource->digitalObjectsRelatedByobjectId) ? '' : 'rounded-top',
      ]
  ); ?>

  <?php echo render_show(__('Identifier'), $resource->identifier); ?>

  <?php echo render_show(__('Title'), render_value_inline($resource->getTitle(['cultureFallback' => true]))); ?>

  <?php $actorsShown = []; ?>
  <?php foreach ($resource->getCreators() as $item) { ?>
    <?php if (!isset($actorsShown[$item->id])) { ?>
      <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
        <?php echo render_b5_show_label(__('Creator')); ?>
        <div class="<?php echo render_b5_show_value_css_classes(); ?>">
          <?php echo link_to(render_title($item), [$item, 'module' => 'actor']); ?><?php if (0 < strlen($value = $item->getDatesOfExistence(['cultureFallback' => true]))) { ?> <span class="note2">(<?php echo $value; ?>)</span><?php } ?>
        </div>
      </div>
      <?php $actorsShown[$item->id] = true; ?>
    <?php } ?>
  <?php } ?>

  <?php foreach ($resource->getPublishers() as $item) { ?>
    <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
      <?php echo render_b5_show_label(__('Publisher')); ?>
      <div class="<?php echo render_b5_show_value_css_classes(); ?>">
        <?php echo link_to(render_title($item), [$item, 'module' => 'actor']); ?><?php if ($value = $item->getDatesOfExistence(['cultureFallback' => true])) { ?> <span class="note2">(<?php echo $value; ?>)</span><?php } ?>
      </div>
    </div>
  <?php } ?>

  <?php foreach ($resource->getContributors() as $item) { ?>
    <div class="field <?php echo render_b5_show_field_css_classes(); ?>">
      <?php echo render_b5_show_label(__('Contributor')); ?>
      <div class="<?php echo render_b5_show_value_css_classes(); ?>">
        <?php echo link_to(render_title($item), [$item, 'module' => 'actor']); ?><?php if ($value = $item->getDatesOfExistence(['cultureFallback' => true])) { ?> <span class="note2">(<?php echo $value; ?>)</span><?php } ?>
      </div>
    </div>
  <?php } ?>

  <?php echo get_partial('informationobject/dates', ['resource' => $resource]); ?>

  <?php echo render_show(__('Description'), render_value(pii_filter($resource->id, $resource->getScopeAndContent(['cultureFallback' => true])))); ?>
  <?php if (function_exists('pii_has_redacted') && pii_has_redacted($resource->id)): ?>
  <div class="alert alert-warning py-1 px-2 mt-2">
    <i class="fas fa-shield-alt me-1"></i>
    <small><?php echo __('This record contains redacted PII'); ?></small>
  </div>
  <?php endif; ?>

  <?php
      $subjects = [];
      foreach ($resource->getSubjectAccessPoints() as $item) {
          $subjects[] = link_to(render_title($item->term), [$item->term, 'module' => 'term']);
      }
      echo render_show(__('Subjects'), $subjects);
  ?>

  <?php echo render_show(__('Types'), $dc->type); ?>

  <?php echo render_show(__('Format'), render_value($dc->format)); ?>

  <?php echo render_show(__('Source'), render_value($resource->getLocationOfOriginals(['cultureFallback' => true]))); ?>

  <?php
      $languages = [];
      foreach ($resource->language as $code) {
          $languages[] = format_language($code);
      }
      echo render_show(__('Languages'), $languages);
  ?>

  <?php echo render_show_repository(__('Relation (isLocatedAt)'), $resource); ?>

  <?php
      $coverage = [];
      foreach ($dc->coverage as $item) {
          $coverage[] = link_to(render_title($item), [$item, 'module' => 'term']);
      }
      echo render_show(__('Coverage (spatial)'), $coverage);
  ?>

  <?php echo render_show(__('Rights'), render_value($resource->getAccessConditions(['cultureFallback' => true]))); ?>

</section> <!-- /section#elementsArea -->

<?php if ($sf_user->isAuthenticated()) { ?>

  <section id="rightsArea" class="border-bottom">

    <?php if (\AtomExtensions\Services\AclService::check($resource, 'update')) { ?>
      <?php echo render_b5_section_heading(__('Rights area')); ?>
    <?php } ?>

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

  <!-- EXTENDED RIGHTS AREA -->
  <?php include_partial('informationobject/extendedRightsArea', ['resource' => $resource]); ?>

</section> <!-- /section#accessionArea -->

</div><!-- /TTS Content Area -->

<?php slot('after-content'); ?>
  <?php echo get_partial('informationobject/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php echo get_component('object', 'gaInstitutionsDimension', ['resource' => $resource]); ?>
