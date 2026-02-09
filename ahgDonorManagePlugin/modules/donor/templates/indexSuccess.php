<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('View donor'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo esc_specialchars($donor['authorizedFormOfName'] ?: __('Untitled')); ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

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

<?php end_slot(); ?>

<div class="section border-bottom" id="basicInfo">

  <?php echo render_b5_section_heading(
      __('Basic info'),
      $canEdit,
      ['module' => 'donor', 'slug' => $donor['slug'], 'action' => 'edit'],
      ['anchor' => 'identity-collapse', 'class' => 'rounded-top']
  ); ?>

  <?php echo render_show(__('Authorized form of name'), render_value_inline($donor['authorizedFormOfName'])); ?>

</div>

<div class="section border-bottom" id="contactArea">

  <?php echo render_b5_section_heading(
      __('Contact area'),
      $canEdit,
      ['module' => 'donor', 'slug' => $donor['slug'], 'action' => 'edit'],
      ['anchor' => 'contact-collapse']
  ); ?>

  <?php foreach ($sf_data->getRaw('donor')['contacts'] as $contactItem) { ?>
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

<div class="section" id="accessionArea">

  <?php echo render_b5_section_heading(__('Accession area')); ?>

  <?php
      $relatedAccessions = [];
      foreach ($sf_data->getRaw('donor')['accessions'] as $acc) {
          $label = !empty($acc->title) ? $acc->title : $acc->identifier;
          $relatedAccessions[] = link_to(esc_specialchars($label), ['module' => 'accession', 'slug' => $acc->slug]);
      }
      echo render_show(__('Related accession(s)'), $relatedAccessions);
  ?>

</div>

<?php slot('after-content'); ?>
  <?php if ($canEdit || $canDelete || $canCreate) { ?>
    <ul class="actions mb-3 nav gap-2">
      <?php if ($canEdit) { ?>
        <li><?php echo link_to(__('Edit'), ['module' => 'donor', 'slug' => $donor['slug'], 'action' => 'edit'], ['class' => 'btn atom-btn-outline-light']); ?></li>
      <?php } ?>
      <?php if ($canDelete) { ?>
        <li><?php echo link_to(__('Delete'), ['module' => 'donor', 'slug' => $donor['slug'], 'action' => 'delete'], ['class' => 'btn atom-btn-outline-danger']); ?></li>
      <?php } ?>
      <?php if ($canCreate) { ?>
        <li><?php echo link_to(__('Add new'), ['module' => 'donor', 'action' => 'add'], ['class' => 'btn atom-btn-outline-light']); ?></li>
      <?php } ?>
    </ul>
  <?php } ?>
<?php end_slot(); ?>
