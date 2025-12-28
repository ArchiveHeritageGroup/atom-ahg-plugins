<?php
/**
 * ISAD Edit Template with Security Classification.
 *
 * This is the modified editSuccess.php template for sfIsadPlugin
 * that includes the security classification fieldset.
 *
 * Copy this to: plugins/sfIsadPlugin/modules/sfIsadPlugin/templates/editSuccess.php
 *
 * The key addition is the security classification fieldset after Control Area.
 */
?>

<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1 class="multiline">
    <?php echo __('Edit %1% - ISAD(G)', ['%1%' => render_title($resource)]); ?>
    <span class="sub"><?php echo render_title($resource->getCollectionRoot()); ?></span>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <?php if (isset($sf_request->getAttribute('sf_route')->resource)): ?>
    <?php echo $form->renderFormTag(url_for([$resource, 'module' => 'informationobject', 'action' => 'edit']), ['id' => 'editForm']); ?>
  <?php else: ?>
    <?php echo $form->renderFormTag(url_for(['module' => 'informationobject', 'action' => 'add']), ['id' => 'editForm']); ?>
  <?php endif; ?>

    <?php echo $form->renderHiddenFields(); ?>

    <div id="content">

      <?php if ($sf_user->getAttribute('publicationStatusError')): ?>
        <div class="alert alert-danger">
          <?php echo __('Could not change the publication status. Please check that the parent record is published.'); ?>
        </div>
        <?php $sf_user->setAttribute('publicationStatusError', null); ?>
      <?php endif; ?>

      <!-- Identity Area -->
      <fieldset class="collapsible" id="identityArea">
        <legend><?php echo __('Identity area'); ?></legend>
        <?php echo render_field($form->identifier); ?>
        <?php echo render_field($form->title); ?>
        <?php echo get_partial('informationobject/dateClasses', ['form' => $form]); ?>
        <?php echo render_field($form->levelOfDescription); ?>
        <?php echo render_field($form->extentAndMedium, $resource, ['class' => 'resizable']); ?>
      </fieldset>

      <!-- Context Area -->
      <fieldset class="collapsible collapsed" id="contextArea">
        <legend><?php echo __('Context area'); ?></legend>
        <?php echo get_partial('informationobject/childLevels', ['form' => $form]); ?>
        <?php echo render_field($form->repository); ?>
        <?php echo render_field($form->archivalHistory, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->acquisition, $resource, ['class' => 'resizable']); ?>
      </fieldset>

      <!-- Content and Structure Area -->
      <fieldset class="collapsible collapsed" id="contentAndStructureArea">
        <legend><?php echo __('Content and structure area'); ?></legend>
        <?php echo render_field($form->scopeAndContent, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->appraisal, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->accruals, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->arrangement, $resource, ['class' => 'resizable']); ?>
      </fieldset>

      <!-- Conditions of Access and Use Area -->
      <fieldset class="collapsible collapsed" id="conditionsOfAccessAndUseArea">
        <legend><?php echo __('Conditions of access and use area'); ?></legend>
        <?php echo render_field($form->accessConditions, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->reproductionConditions, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->language); ?>
        <?php echo render_field($form->script); ?>
        <?php echo render_field($form->languageNotes, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->physicalCharacteristics, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->findingAids, $resource, ['class' => 'resizable']); ?>
      </fieldset>

      <!-- Allied Materials Area -->
      <fieldset class="collapsible collapsed" id="alliedMaterialsArea">
        <legend><?php echo __('Allied materials area'); ?></legend>
        <?php echo render_field($form->locationOfOriginals, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->locationOfCopies, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->relatedUnitsOfDescription, $resource, ['class' => 'resizable']); ?>
        <?php echo get_partial('informationobject/relatedMaterialDescriptions', ['form' => $form, 'resource' => $resource]); ?>
        <?php echo render_field($form->publicationNote, $resource, ['class' => 'resizable']); ?>
      </fieldset>

      <!-- Notes Area -->
      <fieldset class="collapsible collapsed" id="notesArea">
        <legend><?php echo __('Notes area'); ?></legend>
        <?php echo get_partial('informationobject/notes', ['form' => $form, 'resource' => $resource]); ?>
      </fieldset>

      <!-- Access Points -->
      <fieldset class="collapsible collapsed" id="accessPointsArea">
        <legend><?php echo __('Access points'); ?></legend>
        <?php echo get_partial('informationobject/subjectAccessPoints', ['form' => $form, 'resource' => $resource]); ?>
        <?php echo get_partial('informationobject/placeAccessPoints', ['form' => $form, 'resource' => $resource]); ?>
        <?php echo get_partial('informationobject/nameAccessPoints', ['form' => $form, 'resource' => $resource]); ?>
        <?php echo get_partial('informationobject/genreAccessPoints', ['form' => $form, 'resource' => $resource]); ?>
      </fieldset>

      <!-- Description Control Area -->
      <fieldset class="collapsible collapsed" id="descriptionControlArea">
        <legend><?php echo __('Description control area'); ?></legend>
        <?php echo render_field($form->descriptionIdentifier); ?>
        <?php echo render_field($form->institutionResponsibleIdentifier); ?>
        <?php echo render_field($form->rules, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->descriptionStatus); ?>
        <?php echo render_field($form->levelOfDetail); ?>
        <?php echo render_field($form->revisionHistory, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->languageOfDescription); ?>
        <?php echo render_field($form->scriptOfDescription); ?>
        <?php echo render_field($form->sources, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->archivistNote, $resource, ['class' => 'resizable']); ?>
        <?php echo render_field($form->maintenanceNotes, $resource, ['class' => 'resizable']); ?>
      </fieldset>

      <!-- ============================================================= -->
      <!-- SECURITY CLASSIFICATION AREA - Added for arSecurityClearancePlugin -->
      <!-- ============================================================= -->
      <?php if (sfConfig::get('app_plugins_arSecurityClearancePlugin', false)): ?>
        <?php include_partial('arSecurityClearance/securityFieldset', ['resource' => $resource]); ?>
      <?php endif; ?>
      <!-- ============================================================= -->

      <!-- Rights Area -->
      <?php echo get_partial('informationobject/rightsArea', ['form' => $form, 'resource' => $resource]); ?>

      <!-- Administration Area -->
      <fieldset class="collapsible collapsed" id="adminArea">
        <legend><?php echo __('Administration area'); ?></legend>
        <?php echo render_field($form->displayStandard); ?>
        <?php echo render_field($form->displayStandardUpdateDescendants); ?>
        <?php echo render_field($form->publicationStatus); ?>
      </fieldset>

    </div>

    <section class="actions">
      <ul>
        <?php if (isset($sf_request->getAttribute('sf_route')->resource)): ?>
          <li><?php echo link_to(__('Cancel'), [$resource, 'module' => 'informationobject'], ['class' => 'c-btn']); ?></li>
          <li><input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Save'); ?>"></li>
        <?php else: ?>
          <li><?php echo link_to(__('Cancel'), ['module' => 'informationobject', 'action' => 'browse'], ['class' => 'c-btn']); ?></li>
          <li><input class="c-btn c-btn-submit" type="submit" value="<?php echo __('Create'); ?>"></li>
        <?php endif; ?>
      </ul>
    </section>

  </form>

<?php end_slot(); ?>
