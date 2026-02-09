<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('View rights holder'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo esc_specialchars($rightsHolder['authorizedFormOfName'] ?: __('Untitled')); ?>
    </span>
  </div>
<?php end_slot(); ?>

<div class="section border-bottom" id="basicInfo">

  <?php echo render_b5_section_heading(
      __('Identity area'),
      $canEdit,
      '@rightsholder_edit_override?slug=' . $rightsHolder['slug'],
      ['anchor' => 'identity-collapse', 'class' => 'rounded-top']
  ); ?>

  <?php echo render_show(__('Authorized form of name'), render_value_inline($rightsHolder['authorizedFormOfName'])); ?>

</div>

<div class="section" id="contactArea">

  <?php echo render_b5_section_heading(
      __('Contact area'),
      $canEdit,
      '@rightsholder_edit_override?slug=' . $rightsHolder['slug'],
      ['anchor' => 'contact-collapse']
  ); ?>

  <?php foreach ($sf_data->getRaw('rightsHolder')['contacts'] as $contactItem) { ?>
    <div class="mb-3">
      <?php if (!empty($contactItem->contact_person)) { ?>
        <?php echo render_show(__('Contact person'), render_value_inline($contactItem->contact_person)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->street_address)) { ?>
        <?php echo render_show(__('Street address'), render_value_inline($contactItem->street_address)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->city)) { ?>
        <?php echo render_show(__('City'), render_value_inline($contactItem->city)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->region)) { ?>
        <?php echo render_show(__('Region/province'), render_value_inline($contactItem->region)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->postal_code)) { ?>
        <?php echo render_show(__('Postal code'), render_value_inline($contactItem->postal_code)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->country_code)) { ?>
        <?php echo render_show(__('Country'), render_value_inline($contactItem->country_code)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->telephone)) { ?>
        <?php echo render_show(__('Telephone'), render_value_inline($contactItem->telephone)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->fax)) { ?>
        <?php echo render_show(__('Fax'), render_value_inline($contactItem->fax)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->email)) { ?>
        <?php echo render_show(__('Email'), render_value_inline($contactItem->email)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->website)) { ?>
        <?php echo render_show(__('Website'), render_value_inline($contactItem->website)); ?>
      <?php } ?>
      <?php if (!empty($contactItem->note)) { ?>
        <?php echo render_show(__('Note'), render_value_inline($contactItem->note)); ?>
      <?php } ?>
    </div>
  <?php } ?>

</div>

<?php slot('after-content'); ?>
  <?php if ($canEdit || $canDelete || $canCreate) { ?>
    <ul class="actions mb-3 nav gap-2">
      <?php if ($canEdit) { ?>
        <li><?php echo link_to(__('Edit'), '@rightsholder_edit_override?slug=' . $rightsHolder['slug'], ['class' => 'btn atom-btn-outline-light']); ?></li>
      <?php } ?>
      <?php if ($canDelete) { ?>
        <li><?php echo link_to(__('Delete'), '@rightsholder_delete_override?slug=' . $rightsHolder['slug'], ['class' => 'btn atom-btn-outline-danger']); ?></li>
      <?php } ?>
      <?php if ($canCreate) { ?>
        <li><?php echo link_to(__('Add new'), '@rightsholder_add_override', ['class' => 'btn atom-btn-outline-light']); ?></li>
      <?php } ?>
    </ul>
  <?php } ?>
<?php end_slot(); ?>
