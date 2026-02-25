<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('User profile'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo esc_specialchars($userRecord['username']); ?>
      <?php if ($isSelf) { ?>
        <span class="badge bg-info"><?php echo __('(you)'); ?></span>
      <?php } ?>
    </span>
  </div>
<?php end_slot(); ?>

<div class="section border-bottom" id="basicInfo">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Basic info'); ?></h2>

  <?php echo render_show(__('Username'), render_value_inline($userRecord['username'])); ?>
  <?php echo render_show(__('Email'), render_value_inline($userRecord['email'])); ?>
  <?php echo render_show(__('Active'), $userRecord['active'] ? __('Yes') : __('No')); ?>

</div>

<?php if (!empty($userRecord['authorizedFormOfName']) || !empty($entityTypeName)) { ?>
<div class="section border-bottom" id="profile">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Profile'); ?></h2>

  <?php if (!empty($userRecord['authorizedFormOfName'])) { ?>
    <?php echo render_show(__('Authorized form of name'), render_value_inline($userRecord['authorizedFormOfName'])); ?>
  <?php } ?>
  <?php if (!empty($entityTypeName)) { ?>
    <?php echo render_show(__('Entity type'), render_value_inline($entityTypeName)); ?>
  <?php } ?>

</div>
<?php } ?>

<?php
  $rawContact = $sf_data->getRaw('userRecord')['contact'] ?? null;
?>
<?php if ($rawContact) { ?>
<div class="section border-bottom" id="contactInfo">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Contact information'); ?></h2>

  <?php if (!empty($rawContact->contact_person)) { ?>
    <?php echo render_show(__('Contact person'), render_value_inline($rawContact->contact_person)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->email)) { ?>
    <?php echo render_show(__('Contact email'), render_value_inline($rawContact->email)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->telephone)) { ?>
    <?php echo render_show(__('Telephone'), render_value_inline($rawContact->telephone)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->fax)) { ?>
    <?php echo render_show(__('Fax'), render_value_inline($rawContact->fax)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->street_address)) { ?>
    <?php echo render_show(__('Street address'), render_value_inline($rawContact->street_address)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->city)) { ?>
    <?php echo render_show(__('City'), render_value_inline($rawContact->city)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->region)) { ?>
    <?php echo render_show(__('Region/province'), render_value_inline($rawContact->region)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->postal_code)) { ?>
    <?php echo render_show(__('Postal code'), render_value_inline($rawContact->postal_code)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->country_code)) { ?>
    <?php echo render_show(__('Country'), render_value_inline($rawContact->country_code)); ?>
  <?php } ?>
  <?php if (!empty($rawContact->website)) { ?>
    <?php echo render_show(__('Website'), '<a href="' . esc_specialchars($rawContact->website) . '" target="_blank">' . esc_specialchars($rawContact->website) . '</a>'); ?>
  <?php } ?>
  <?php if (!empty($rawContact->note)) { ?>
    <?php echo render_show(__('Note'), render_value_inline($rawContact->note)); ?>
  <?php } ?>

</div>
<?php } ?>

<div class="section border-bottom" id="accessControl">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Access control'); ?></h2>

  <?php
    $groupNames = [];
    foreach ($sf_data->getRaw('userRecord')['groups'] as $group) {
        $groupNames[] = esc_specialchars($group->name ?? __('Group %1%', ['%1%' => $group->id]));
    }
    echo render_show(__('User groups'), !empty($groupNames) ? implode(', ', $groupNames) : '<em>' . __('None') . '</em>');
  ?>

</div>

<div class="section border-bottom" id="translate">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Allowed languages for translation'); ?></h2>

  <?php
    $rawTransLangs = $sf_data->getRaw('translateLanguages');
    if (!empty($rawTransLangs)) {
        $langNames = array_map(function ($code) { return format_language($code); }, $rawTransLangs);
        echo render_show(__('Translate'), implode(', ', $langNames));
    } else {
        echo render_show(__('Translate'), '<em>' . __('None') . '</em>');
    }
  ?>

</div>

<div class="section border-bottom" id="apiKeys">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('API keys'); ?></h2>

  <?php
    $restKey = $sf_data->getRaw('restApiKey');
    $oaiKey = $sf_data->getRaw('oaiApiKey');
  ?>
  <?php echo render_show(__('REST API access key'), $restKey ? '<code>' . esc_specialchars($restKey) . '</code>' : '<em>' . __('Not generated yet.') . '</em>'); ?>
  <?php echo render_show(__('OAI-PMH API access key'), $oaiKey ? '<code>' . esc_specialchars($oaiKey) . '</code>' : '<em>' . __('Not generated yet.') . '</em>'); ?>

</div>

<?php if (class_exists('\\AhgSecurityClearance\\Services\\SecurityClearanceService')) { ?>
<div class="section border-bottom" id="securityClearance">

  <h2 class="h5 mb-0 atom-section-header d-flex p-3 border-bottom text-primary"><?php echo __('Security clearance'); ?></h2>

  <?php if ($clearance !== null) { ?>
    <?php echo render_show(__('Clearance level'), render_value_inline($clearance->level_name ?? __('None')) . ' ' . link_to(__('Manage'), '@security_clearance_user?slug=' . $userRecord['slug'], ['class' => 'btn btn-sm atom-btn-outline-light ms-2'])); ?>
    <?php if (!empty($clearance->granted_at)) { ?>
      <?php echo render_show(__('Granted'), render_value_inline($clearance->granted_at)); ?>
    <?php } ?>
    <?php if (!empty($clearance->expires_at)) { ?>
      <?php echo render_show(__('Expires'), render_value_inline($clearance->expires_at)); ?>
    <?php } ?>
  <?php } else { ?>
    <?php echo render_show(__('Clearance level'), '<em>' . __('None') . '</em> ' . link_to(__('Grant clearance'), '@security_clearance_user?slug=' . $userRecord['slug'], ['class' => 'btn btn-sm atom-btn-outline-light ms-2'])); ?>
  <?php } ?>

</div>
<?php } ?>

<?php slot('after-content'); ?>
  <ul class="actions mb-3 nav gap-2">
    <li><?php echo link_to(__('Edit'), '@user_edit_override?slug=' . $userRecord['slug'], ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php if (!$isSelf) { ?>
      <li><?php echo link_to(__('Delete'), '@user_delete_override?slug=' . $userRecord['slug'], ['class' => 'btn atom-btn-outline-danger']); ?></li>
    <?php } ?>
    <li><?php echo link_to(__('Add new'), '@user_add_override', ['class' => 'btn atom-btn-outline-light']); ?></li>
    <li><?php echo link_to(__('Return to user list'), '@user_list_override', ['class' => 'btn atom-btn-outline-light']); ?></li>
  </ul>
<?php end_slot(); ?>
