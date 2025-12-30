<?php decorate_with('layout_2col.php'); ?>
<?php use_helper('Date'); ?>

<?php
/**
 * Check if user can create information objects
 */
function user_can_create_information_object(): bool
{
    $user = sfContext::getInstance()->getUser();
    
    if (!$user->isAuthenticated()) {
        return false;
    }
    
    $userId = $user->getAttribute('user_id');
    if (!$userId) {
        return false;
    }
    
    // Check if user is in administrator or editor group
    $adminGroups = [100, 101]; // GROUP_ADMINISTRATOR, GROUP_EDITOR
    
    return \Illuminate\Database\Capsule\Manager::table('acl_user_group')
        ->where('user_id', $userId)
        ->whereIn('group_id', $adminGroups)
        ->exists();
}

/**
 * Build edit form action URL using slug
 */
function cco_edit_form_url($resource): string
{
    if ($resource && isset($resource->id) && $resource->id) {
        // Edit existing - use slug if available
        $slug = $resource->slug ?? null;
        if ($slug) {
            return url_for(['module' => 'cco', 'action' => 'edit', 'slug' => $slug]);
        }
        // Fallback to ID
        return url_for(['module' => 'cco', 'action' => 'edit', 'id' => $resource->id]);
    }
    // Add new
    return url_for(['module' => 'cco', 'action' => 'add']);
}
?>

<?php slot('sidebar'); ?>
    <?php include_component('repository', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
    <h1><?php echo isset($resource) && $resource->id ? __('Edit museum object') : __('Add new museum object'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php echo $form->renderGlobalErrors(); ?>

<?php echo $form->renderFormTag(cco_edit_form_url($resource), ['id' => 'editForm', 'enctype' => 'multipart/form-data']); ?>

<?php echo $form->renderHiddenFields(); ?>

<div class="accordion mb-3">
    <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse" aria-expanded="false" aria-controls="identity-collapse">
            <?php echo __('Identity area'); ?>
          </button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse" aria-labelledby="identity-heading">
            <div class="accordion-body">
            <!-- Identity Area -->
                <?php echo render_field($form->identifier
                    ->label(__('Identifier').' <span class="form-required" title="'.__('This is a mandatory element.').'">*</span>')
                    ->help(__('Unique identifier for this object')), $resource); ?>

                <?php echo get_partial('cco/identifierOptions', ['mask' => $mask]); ?>
                <?php echo get_partial('cco/alternativeIdentifiers', $sf_data->getRaw('alternativeIdentifiersComponent')->getVarHolder()->getAll()); ?>

                <?php echo render_field($form->title
                    ->label(__('Title').' <span class="form-required" title="'.__('This is a mandatory element.').'">*</span>')
                    ->help(__('Title of the object')), $resource); ?>

                <?php // echo get_partial('event', $sf_data->getRaw('eventComponent')->getVarHolder()->getAll() + ['help' => __('Record the date(s) of the unit of description. The Date display field can be used to enter free-text date information.')]);?>

                <?php echo $form->levelOfDescription
                    ->label(__('Level of description'))
                    ->help(__('Level of archival description'))
                    ->renderRow(); ?>

                <?php echo get_partial('cco/childLevels', ['help' => __('Add child levels to create hierarchical descriptions.')]); ?>

                <?php echo render_field($form->extentAndMedium
                    ->label(__('Extent and medium'))
                    ->help(__('Physical extent and medium of the object')), $resource, ['class' => 'resizable']); ?>

            </div>
        </div>
    </div>

     <div class="accordion-item">
        <h2 class="accordion-header" id="cco-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cco-collapse" aria-expanded="false" aria-controls="cco-collapse">
            <?php echo __('Museum object identification (CCO)'); ?>
          </button>
        </h2>
        <div id="cco-collapse" class="accordion-collapse collapse" aria-labelledby="cco-heading">
            <div class="accordion-body">
                <?php echo render_field($form->museum_work_type
                    ->label(__('Work type'))
                    ->help(__('Select the CCO work type that best describes this object')), null); ?>

                <?php echo render_field($form->museum_creation_date_earliest
                    ->label(__('Creation date (earliest)'))
                    ->help(__('Earliest possible creation date')), null); ?>

                <?php echo render_field($form->museum_creation_date_latest
                    ->label(__('Creation date (latest)'))
                    ->help(__('Latest possible creation date')), null); ?>

                <?php echo render_field($form->museum_object_type
                    ->label(__('Object type'))
                    ->help(__('Type of object')), null); ?>

                <?php echo render_field($form->museum_classification
                    ->label(__('Classification'))
                    ->help(__('Classification')), null); ?>

                <?php echo render_field($form->museum_dimensions
                    ->label(__('Dimensions'))
                    ->help(__('Dimensions')), null); ?>

                <?php echo render_field($form->museum_current_location
                    ->label(__('Current location'))
                    ->help(__('Current location')), null, ['class' => 'resizable']); ?>
            </div>
        </div>
    </div>

     <div class="accordion-item">
        <h2 class="accordion-header" id="materials-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#materials-collapse" aria-expanded="false" aria-controls="materials-collapse">
            <?php echo __('Materials and techniques'); ?>
          </button>
        </h2>
        <div id="materials-collapse" class="accordion-collapse collapse" aria-labelledby="materials-heading">
            <div class="accordion-body">
            <!-- Materials and Techniques -->
            <fieldset class="collapsible" id="materialsArea">
                <legend><?php echo __('Materials and techniques'); ?></legend>

                <div class="form-item">
                    <?php echo $form->museum_materials->renderLabel(__('Materials')); ?>
                    <?php echo $form->museum_materials->render(['size' => 5, 'class' => 'form-control']); ?>
                    <div class="description"><?php echo __('Hold Ctrl/Cmd to select multiple materials'); ?></div>
                </div>

                <div class="form-item">
                    <?php echo $form->museum_techniques->renderLabel(__('Techniques')); ?>
                    <?php echo $form->museum_techniques->render(['size' => 5, 'class' => 'form-control']); ?>
                    <div class="description"><?php echo __('Hold Ctrl/Cmd to select multiple techniques'); ?></div>
                </div>

                <?php echo render_field($form->museum_measurements
                    ->label(__('Measurements'))
                    ->help(__('Physical measurements')), null, ['class' => 'resizable']); ?>
            </div>
        </div>
    </div>
                    
     <div class="accordion-item">
        <h2 class="accordion-header" id="creator-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#creator-collapse" aria-expanded="false" aria-controls="creator-collapse">
            <?php echo __('Creator information'); ?>
          </button>
        </h2>
        <div id="creator-collapse" class="accordion-collapse collapse" aria-labelledby="creator-heading">
            <div class="accordion-body">
              <?php echo render_field($form->museum_creator_identity->label(__('Creator/Maker')), null); ?>
              <?php echo render_field($form->museum_creator_role->label(__('Role')), null); ?>
              <?php echo render_field($form->museum_creator_extent->label(__('Extent')), null); ?>
              <?php echo render_field($form->museum_creator_qualifier->label(__('Qualifier')), null); ?>
              <?php echo render_field($form->museum_creator_attribution->label(__('Attribution')), null, ['class' => 'resizable']); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="creation-date-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#creation-date-collapse" aria-expanded="false" aria-controls="creation-date-collapse">
            <?php echo __('Creation date'); ?>
          </button>
        </h2>
    <div id="creation-date-collapse" class="accordion-collapse collapse" aria-labelledby="creation-date-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_creation_date_display->label(__('Display date')), null); ?>
          <?php echo render_field($form->museum_creation_date_qualifier->label(__('Date qualifier')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="styles-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#styles-collapse" aria-expanded="false" aria-controls="styles-collapse">
            <?php echo __('Styles, periods, groups, movements'); ?>
          </button>
        </h2>
    <div id="styles-collapse" class="accordion-collapse collapse" aria-labelledby="styles-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_style->label(__('Style')), null); ?>
          <?php echo render_field($form->museum_period->label(__('Period')), null); ?>
          <?php echo render_field($form->museum_cultural_group->label(__('Culture/Group')), null); ?>
          <?php echo render_field($form->museum_movement->label(__('Movement')), null); ?>
          <?php echo render_field($form->museum_school->label(__('School')), null); ?>
          <?php echo render_field($form->museum_dynasty->label(__('Dynasty')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="subject-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#subject-collapse" aria-expanded="false" aria-controls="subject-collapse">
            <?php echo __('Subject matter'); ?>
          </button>
        </h2>
    <div id="subject-collapse" class="accordion-collapse collapse" aria-labelledby="subject-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_subject_indexing_type->label(__('Indexing type')), null); ?>
          <?php echo render_field($form->museum_subject_display->label(__('Subject')), null, ['class' => 'resizable']); ?>
          <?php echo render_field($form->museum_subject_extent->label(__('Extent')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="context-cco-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#context-cco-collapse" aria-expanded="false" aria-controls="context-cco-collapse">
            <?php echo __('Context'); ?>
          </button>
        </h2>
    <div id="context-cco-collapse" class="accordion-collapse collapse" aria-labelledby="context-cco-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_historical_context->label(__('Historical')), null, ['class' => 'resizable']); ?>
          <?php echo render_field($form->museum_cultural_context->label(__('Cultural')), null); ?>
          <?php echo render_field($form->museum_architectural_context->label(__('Architectural')), null); ?>
          <?php echo render_field($form->museum_archaeological_context->label(__('Archaeological')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="classification-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#classification-collapse" aria-expanded="false" aria-controls="classification-collapse">
            <?php echo __('Classification'); ?>
          </button>
        </h2>
    <div id="classification-collapse" class="accordion-collapse collapse" aria-labelledby="classification-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_object_class->label(__('Class')), null); ?>
          <?php echo render_field($form->museum_object_category->label(__('Category')), null); ?>
          <?php echo render_field($form->museum_object_sub_category->label(__('Sub-category')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="edition-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#edition-collapse" aria-expanded="false" aria-controls="edition-collapse">
            <?php echo __('Edition'); ?>
          </button>
        </h2>
    <div id="edition-collapse" class="accordion-collapse collapse" aria-labelledby="edition-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_edition_number->label(__('Number')), null); ?>
          <?php echo render_field($form->museum_edition_size->label(__('Size')), null); ?>
          <?php echo render_field($form->museum_edition_description->label(__('Description')), null, ['class' => 'resizable']); ?>
          <?php echo render_field($form->museum_state_description->label(__('State description')), null); ?>
          <?php echo render_field($form->museum_state_identification->label(__('State identification')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="facture-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#facture-collapse" aria-expanded="false" aria-controls="facture-collapse">
            <?php echo __('Facture/Technique'); ?>
          </button>
        </h2>
    <div id="facture-collapse" class="accordion-collapse collapse" aria-labelledby="facture-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_facture_description->label(__('Facture')), null, ['class' => 'resizable']); ?>
          <?php echo render_field($form->museum_technique_cco->label(__('Technique')), null); ?>
          <?php echo render_field($form->museum_technique_qualifier->label(__('Qualifier')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="physical-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#physical-collapse" aria-expanded="false" aria-controls="physical-collapse">
            <?php echo __('Physical description'); ?>
          </button>
        </h2>
    <div id="physical-collapse" class="accordion-collapse collapse" aria-labelledby="physical-heading">
      <div class="accordion-body">
          <?php echo render_field($form->museum_physical_appearance->label(__('Appearance')), null, ['class' => 'resizable']); ?>
          <?php echo render_field($form->museum_color->label(__('Color')), null); ?>
          <?php echo render_field($form->museum_shape->label(__('Shape')), null); ?>
          <?php echo render_field($form->museum_orientation->label(__('Orientation')), null); ?>
            </div>
        </div>
    </div>

     <div class="accordion-item">
        <h2 class="accordion-header" id="condition-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#condition-collapse" aria-expanded="false" aria-controls="condition-collapse">
            <?php echo __('Condition and provenance'); ?>
          </button>
        </h2>
    <div id="condition-collapse" class="accordion-collapse collapse" aria-labelledby="condition-heading">
      <div class="accordion-body">  
            <!-- Condition and Provenance -->
            <?php echo render_field($form->museum_inscription
                ->label(__('Inscription'))
                ->help(__('Any inscriptions, marks, or signatures')), null, ['class' => 'resizable']); ?>

            <?php echo render_field($form->museum_condition_notes
                ->label(__('Condition notes'))
                ->help(__('Current condition of the object')), null, ['class' => 'resizable']); ?>

            <?php echo render_field($form->museum_provenance
                ->label(__('Provenance'))
                ->help(__('History of ownership')), null, ['class' => 'resizable']); ?>

            <?php echo render_field($form->museum_style_period
                ->label(__('Style or period'))
                ->help(__('Artistic style or historical period')), null); ?>

            <?php echo render_field($form->museum_cultural_context
                ->label(__('Cultural context'))
                ->help(__('Cultural or geographic origin')), null); ?>
            </div>
        </div>
    </div>
     <div class="accordion-item">
        <h2 class="accordion-header" id="context-area-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#context-area-collapse" aria-expanded="false" aria-controls="context-area-collapse">
            <?php echo __('Context area'); ?>
          </button>
        </h2>
    <div id="context-area-collapse" class="accordion-collapse collapse" aria-labelledby="context-area-heading">
      <div class="accordion-body">       
            <!-- Context Area -->
            <div class="form-item">
                <label class="form-label"><?php echo __('Creator (Authority)'); ?> <span class="text-danger">*</span></label>
                <select name="creators[]" id="creators-select" class="form-select" multiple>
                    <?php 
                    $actorsRaw = $sf_data->getRaw('actors') ?? [];
                    $currentCreators = [];
                    if (isset($resource) && $resource && method_exists($resource, 'getCreators')) {
                        foreach ($resource->getCreators() as $creator) {
                            $currentCreators[] = $creator->id;
                        }
                    }
                    foreach ($actorsRaw as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo in_array($id, $currentCreators) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted"><?php echo __('Record the name of the organization(s) or the individual(s) responsible for the creation.'); ?></small>
            </div>

            <div class="form-item">
                <?php echo $form->repository->renderLabel(); ?>
                <?php echo $form->repository->render(); ?>
                <input class="add" type="hidden" data-link-existing="true" value="<?php echo url_for(['module' => 'repository', 'action' => 'add']); ?> #authorizedFormOfName"/>
                <input class="list" type="hidden" value="<?php echo url_for($sf_data->getRaw('repoAcParams')); ?>"/>
                <?php echo $form->repository
                    ->help(__('Record the name of the organization which has custody of the archival material.'))
                    ->renderHelp(); ?>
            </div>

            <?php echo render_field($form->archivalHistory
                ->label(__('Archival history'))
                ->help(__('History of custody and ownership')), $resource, ['class' => 'resizable']); ?>

            <?php echo render_field($form->acquisition
                ->label(__('Immediate source of acquisition'))
                ->help(__('Source from which the object was acquired')), $resource, ['class' => 'resizable']); ?>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="content-structure-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#content-structure-collapse" aria-expanded="false" aria-controls="content-structure-collapse">
            <?php echo __('Content and structure area'); ?>
          </button>
        </h2>
    <div id="content-structure-collapse" class="accordion-collapse collapse" aria-labelledby="content-structure-heading">
        <div class="accordion-body">
            <!-- Content and Structure Area -->
            <?php echo render_field($form->scopeAndContent
                ->label(__('Scope and content'))
                ->help(__('Summary of the scope and content of the object')), $resource, ['class' => 'resizable']); ?>

            <?php echo render_field($form->accruals
                ->label(__('Accruals')), $resource, ['class' => 'resizable']); ?>

            <?php echo render_field($form->arrangement
                ->label(__('System of arrangement')), $resource, ['class' => 'resizable']); ?>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="access-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#access-collapse" aria-expanded="false" aria-controls="access-collapse">
            <?php echo __('Conditions of access and use area'); ?>
          </button>
        </h2>
    <div id="access-collapse" class="accordion-collapse collapse" aria-labelledby="access-heading">
      <div class="accordion-body">
            <?php echo render_field($form->accessConditions
                ->label(__('Conditions governing access')), $resource, ['class' => 'resizable']); ?>

            <?php echo render_field($form->reproductionConditions
                ->label(__('Conditions governing reproduction')), $resource, ['class' => 'resizable']); ?>

            <?php echo $form->language
                ->label(__('Language of material'))
                ->renderRow(['class' => 'form-autocomplete']); ?>

            <?php echo $form->script
                ->label(__('Script of material'))
                ->renderRow(['class' => 'form-autocomplete']); ?>

            <?php // echo render_field($form->languageNotes
                // ->label(__('Language and script notes')), $isad, ['class' => 'resizable']);?>

            <?php echo render_field($form->physicalCharacteristics
                ->label(__('Physical characteristics and technical requirements')), $resource, ['class' => 'resizable']); ?>

            <?php echo render_field($form->findingAids
                ->label(__('Finding aids')), $resource, ['class' => 'resizable']); ?>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="allied-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#allied-collapse" aria-expanded="false" aria-controls="allied-collapse">
            <?php echo __('Allied materials area'); ?>
          </button>
        </h2>
    <div id="allied-collapse" class="accordion-collapse collapse" aria-labelledby="allied-heading">
      <div class="accordion-body">
        <?php echo render_field($form->locationOfOriginals
            ->label(__('Existence and location of originals')), $resource, ['class' => 'resizable']); ?>

        <?php echo render_field($form->locationOfCopies
            ->label(__('Existence and location of copies')), $resource, ['class' => 'resizable']); ?>

        <?php echo render_field($form->relatedUnitsOfDescription
            ->label(__('Related units of description')), $resource, ['class' => 'resizable']); ?>

        <div class="form-item">
            <?php echo $form->relatedMaterialDescriptions
                ->label(__('Related descriptions'))
                ->renderLabel(); ?>
            <?php echo $form->relatedMaterialDescriptions->render(['class' => 'form-autocomplete']); ?>
            <?php if (user_can_create_information_object()) { ?>
                <input class="add" type="hidden" data-link-existing="true" value="<?php echo url_for(['module' => 'informationobject', 'action' => 'add']); ?> #title"/>
            <?php } ?>
            <input class="list" type="hidden" value="<?php echo url_for(['module' => 'informationobject', 'action' => 'autocomplete']); ?>"/>
            <?php echo $form->relatedMaterialDescriptions
                ->help(__('To create a relationship between this description and another description.'))
                ->renderHelp(); ?>
        </div>

        <?php // echo get_partial('object/notes', $sf_data->getRaw('publicationNotesComponent')->getVarHolder()->getAll());?>
            </div>
        </div>
    </div>
</div>

        <!-- Notes Area -->
        <!--fieldset class="collapsible" id="notesArea">
            <legend><?php // echo __('Notes area');?></legend> 

            <?php //echo get_partial('object/notes', $sf_data->getRaw('notesComponent')->getVarHolder()->getAll());?>
        </fieldset-->
    <div>

    <?php // ===== SECURITY CLASSIFICATION ===== ?>
    <div class="accordion mb-3">
      <?php include_partial('ahgSecurityClearance/securityFieldset', ['resource' => $resource]); ?>
    </div>
    <?php // ===== END SECURITY CLASSIFICATION ===== ?>
        <?php echo get_partial('informationobject/adminInfo', ['form' => $form, 'resource' => $resource]); ?>

    </div>

    <?php echo get_partial('informationobject/editActions', ['resource' => (null !== $parent ? $parent : $resource)]); ?>


    </form>

<!-- Select2 for Creator dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2-creators').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search or select creator...',
        allowClear: true,
        width: '100%'
    });
});
</script>
<?php end_slot(); ?>