<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1 class="multiline">
    <?php echo image_tag('/images/icons-large/icon-new.png', ['width' => '42', 'height' => '42']); ?>
    <?php echo __('Browse Physical Storage'); ?>
  </h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php echo $form->renderGlobalErrors(); ?>
<section class="sidebar-widget">

	<body>
  
		<div>
	        <button type="submit" class="btn"><?php echo link_to(__('Back to reports'), ['module' => 'reports', 'action' => 'index'], ['title' => __('Back to reports')]); ?></button>
		</div>
		<h4><?php echo __('Filter options'); ?></h4>
		<div>

			<?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportPhysicalStorage']), ['method' => 'get']); ?>

			<?php echo $form->renderHiddenFields(); ?>

			<div id='divTypeOfReport' data-ahg-style="display: none"> 
				<?php echo $form->className->label('Types of Reports')->renderRow(); ?>
			</div>

			<?php if (false) { ?>
			<?php echo __('Date range'); ?>
			<?php echo $form->dateStart->renderError(); ?>
			<?php echo $form->dateEnd->renderError(); ?>
			<?php echo __('%1% to %2%', [
			    '%1%' => $form->dateStart->render(),
			    '%2%' => $form->dateEnd->render()]); ?>
			<?php } ?>

			<?php echo $form->dateOf->renderRow(); ?>
			
			<td>
				<?php $currentDate = date('d/m/Y H:i:s', strtotime('-3 months'));
                echo $form->dateStart->renderRow(['value' => $currentDate, 'readonly' => 'true', 'onchange' => 'checkBigger();'], 'Start Date'); ?>
				<div id="div0">Auto fill datepicker - Time Period - This will be deleted automatically</div>
			</td>

			<td>
				<?php $currentDate = date('d/m/Y H:i:s');
                echo $form->dateEnd->renderRow(['value' => $currentDate, 'readonly' => 'true', 'onchange' => ''], 'End Date'); ?>
				<div id="div1">Auto fill datepicker - dateEnd - This will be deleted automatically</div>
			</td>
						
	        <button type="submit" class="btn"><?php echo __('Search'); ?></button>
      </form>

	</div>

</section>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th><?php echo __('Taxonomy'); ?></th>
        <th><?php echo __('Usage'); ?></th>
        <th data-ahg-style="width: 110px"><?php echo 'CREATED_AT' != $form->getValue('dateOf') ? __('Updated') : __('Created'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pager->getResults() as $result) { ?>
        <tr>
          <td><?php echo link_to(esc_entities($result->getName(['cultureFallback' => true]) ?: '-'), ['module' => 'taxonomy', 'slug' => $result->slug]); ?></td>
          <td><?php echo esc_entities((string) $result->usage); ?></td>
          <td><?php echo 'CREATED_AT' != $form->getValue('dateOf') ? $result->updatedAt : $result->createdAt; ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php end_slot(); ?>
