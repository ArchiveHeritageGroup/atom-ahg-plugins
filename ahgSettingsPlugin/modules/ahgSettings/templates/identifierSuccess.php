<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Identifier Settings'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="alert alert-info" role="alert">
  <i class="fas fa-info-circle me-2"></i>
  <?php echo __('Configure global identifier and accession numbering. Clear the application cache and rebuild the search index if you change the reference code separator.'); ?>
  <pre class="mt-2 mb-0">$ php symfony cc && php symfony search:populate</pre>
</div>

<?php echo $form->renderGlobalErrors(); ?>

<?php echo $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'identifier'])); ?>
  <?php echo $form->renderHiddenFields(); ?>

  <div class="card mb-4">
    <div class="card-header">
      <i class="fas fa-box me-2"></i><?php echo __('Accession Numbering'); ?>
    </div>
    <div class="card-body">
      <?php echo render_field($form->accession_mask_enabled->label(__('Accession mask enabled'))); ?>
      <?php echo render_field($form->accession_mask->label(__('Accession mask'))); ?>
      <?php echo render_field($form->accession_counter->label(__('Accession counter')), null, ['type' => 'number']); ?>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <i class="fas fa-fingerprint me-2"></i><?php echo __('Identifier Numbering'); ?>
    </div>
    <div class="card-body">
      <?php echo render_field($form->identifier_mask_enabled->label(__('Identifier mask enabled'))); ?>
      <?php echo render_field($form->identifier_mask->label(__('Identifier mask'))); ?>
      <?php echo render_field($form->identifier_counter->label(__('Identifier counter')), null, ['type' => 'number']); ?>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <i class="fas fa-cog me-2"></i><?php echo __('Reference Code Options'); ?>
    </div>
    <div class="card-body">
      <?php echo render_field($form->separator_character->label(__('Reference code separator'))); ?>
      <?php echo render_field($form->inherit_code_informationobject->label(__('Inherit reference code (information object)'))); ?>
      <?php echo render_field($form->inherit_code_dc_xml->label(__('Inherit reference code (DC XML)'))); ?>
      <?php echo render_field($form->prevent_duplicate_actor_identifiers->label(__(
          '%1% identifiers: prevent entry/import of duplicates',
          ['%1%' => sfConfig::get('app_ui_label_actor')]
      ))); ?>
    </div>
  </div>

  <div class="alert alert-secondary" role="alert">
    <i class="fas fa-layer-group me-2"></i>
    <strong><?php echo __('Sector-specific numbering?'); ?></strong>
    <?php echo __('Configure different numbering schemes per GLAM/DAM sector in'); ?>
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'sectorNumbering']); ?>">
      <?php echo __('Sector Numbering Settings'); ?>
    </a>.
  </div>

  <section class="actions">
    <input class="btn btn-success" type="submit" value="<?php echo __('Save'); ?>">
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']); ?>" class="btn btn-outline-secondary ms-2">
      <?php echo __('Cancel'); ?>
    </a>
  </section>

</form>

<?php end_slot(); ?>
