<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('View function'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo esc_specialchars($func['authorizedFormOfName'] ?: __('Untitled')); ?>
    </span>
  </div>
<?php end_slot(); ?>

<div class="section border-bottom" id="identityArea">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Identity area'); ?></h2>

  <?php echo render_show(__('Type'), render_value_inline($func['typeName'])); ?>
  <?php echo render_show(__('Authorized form of name'), render_value_inline($func['authorizedFormOfName'])); ?>
  <?php echo render_show(__('Classification'), render_value_inline($func['classification'])); ?>
  <?php echo render_show(__('Dates'), render_value_inline($func['dates'])); ?>
  <?php echo render_show(__('Description'), render_value($func['description'])); ?>

</div>

<div class="section border-bottom" id="contextArea">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Context area'); ?></h2>

  <?php echo render_show(__('History'), render_value($func['history'])); ?>
  <?php echo render_show(__('Legislation'), render_value($func['legislation'])); ?>

</div>

<?php
  $rawFunc = $sf_data->getRaw('func');
  $relatedActors = $rawFunc['relatedActors'] ?? [];
?>
<?php if (!empty($relatedActors)) { ?>
<div class="section border-bottom" id="relationshipsArea">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Relationships area'); ?></h2>

  <?php foreach ($relatedActors as $rel) { ?>
    <?php
      $relatedId = ($rel->subject_id == $rawFunc['id']) ? $rel->object_id : $rel->subject_id;
      $relatedName = ($rel->subject_id == $rawFunc['id']) ? $rel->object_name : $rel->subject_name;
      $relatedSlug = ($rel->subject_id == $rawFunc['id']) ? $rel->object_slug : $rel->subject_slug;
    ?>
    <?php if ($relatedSlug) { ?>
      <?php echo render_show(__('Related authority record'), link_to(esc_specialchars($relatedName ?: __('Untitled')), '@actor_view_override?slug=' . $relatedSlug)); ?>
    <?php } else { ?>
      <?php echo render_show(__('Related authority record'), render_value_inline($relatedName)); ?>
    <?php } ?>
  <?php } ?>

</div>
<?php } ?>

<div class="section" id="controlArea">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Control area'); ?></h2>

  <?php echo render_show(__('Description identifier'), render_value_inline($func['descriptionIdentifier'])); ?>
  <?php echo render_show(__('Institution identifier'), render_value_inline($func['institutionIdentifier'])); ?>
  <?php echo render_show(__('Rules and/or conventions used'), render_value($func['rules'])); ?>
  <?php echo render_show(__('Status'), render_value_inline($func['descriptionStatusName'])); ?>
  <?php echo render_show(__('Level of detail'), render_value_inline($func['descriptionDetailName'])); ?>
  <?php echo render_show(__('Dates of creation, revision and deletion'), render_value($func['revisionHistory'])); ?>
  <?php echo render_show(__('Sources'), render_value($func['sources'])); ?>

</div>

<?php slot('after-content'); ?>
  <ul class="actions mb-3 nav gap-2">
    <?php if ($canEdit) { ?>
      <li><?php echo link_to(__('Edit'), '@function_edit_override?slug=' . $func['slug'], ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php } ?>
    <?php if ($canDelete) { ?>
      <li><?php echo link_to(__('Delete'), '@function_delete_override?slug=' . $func['slug'], ['class' => 'btn atom-btn-outline-danger']); ?></li>
    <?php } ?>
    <?php if ($canCreate) { ?>
      <li><?php echo link_to(__('Add new'), '@function_add_override', ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php } ?>
    <li><?php echo link_to(__('Return to function list'), '@function_browse_override', ['class' => 'btn atom-btn-outline-light']); ?></li>
  </ul>
<?php end_slot(); ?>
