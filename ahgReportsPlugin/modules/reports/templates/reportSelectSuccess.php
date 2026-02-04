<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <?php if (isset($resource)) { ?>
    <h1 class="multiline">
      <?php echo $title; ?>
      <span class="sub"><?php echo $resource->title ?? $resource->slug ?? ''; ?></span>
    </h1>
  <?php } else { ?>
    <h1><?php echo __('Select Report Type'); ?></h1>
  <?php } ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (isset($resource)) { ?>
    <?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportSelect', 'slug' => $resource->slug]), ['enctype' => 'multipart/form-data']); ?>
  <?php } else { ?>
    <?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportSelect']), ['enctype' => 'multipart/form-data']); ?>
  <?php } ?>

    <?php echo $form->renderHiddenFields(); ?>

    <section id="content">

      <fieldset class="collapsible">

        <input type="hidden" name="importType" value="<?php echo esc_entities($type); ?>"/>

          <div class="form-item">
            <label><?php echo __('Type'); ?></label>
            <select  name="objectType" id="objectType">
              <!--<option value="access"><?php //echo sfConfig::get('app_ui_label_accession', __('Access (Archival Description)')); ?></option>-->
              <option value="accession"><?php echo sfConfig::get('app_ui_label_accession', __('Accession')); ?></option>
              
			  <option value="informationObject"><?php echo sfConfig::get('app_ui_label_informationobject', __('Archival Description')); ?></option>
              <!--<option value="audit_trail"><?php //echo __('Audit Trail'); ?></option>-->
              <option value="authorityRecord"><?php echo sfConfig::get('app_ui_label_actor', __('Authority Record/Actor')); ?></option>
              <!--<option value="booked_in"><?php //echo sfConfig::get('app_ui_label_user', __('Booked In')); ?></option>-->
              <!--<option value="booked_out"><?php //echo sfConfig::get('app_ui_label_user', __('Booked Out')); ?></option>-->
              <option value="donor"><?php echo sfConfig::get('app_ui_label_donor', __('Donor')); ?></option>
              <option value="physical_storage"><?php echo __('Physical Storage'); ?></option>
              <!--<option value="preservation"><?php //echo __('Preservation'); ?></option>-->
              <!--<option value="registry"><?php //echo __('Registry'); ?></option>-->
              <option value="repository"><?php echo sfConfig::get('app_ui_label_donor', __('Repository/Archival Institution')); ?></option>
              <!--<option value="researcher"><?php //echo sfConfig::get('app_ui_label_researcher', __('Researcher')); ?></option>-->
              <!--<option value="service_provider"><?php //echo __('Service Provider'); ?></option>-->
              <!--<option value="user"><?php //echo sfConfig::get('app_ui_label_user', __('User Action')); ?></option>-->
            </select>
 
          <div class="form-item">
    </section>

	<section class="actions mb-3">
		<input class="btn atom-btn-outline-success" type="submit" id="bookout" value="<?php echo __('Select'); ?>"/>
	</section>
  </form>

<?php end_slot(); ?>
