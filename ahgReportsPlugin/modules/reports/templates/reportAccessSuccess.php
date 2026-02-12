<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1 class="multiline">
    <?php echo image_tag('/images/icons-large/icon-new.png', ['width' => '42', 'height' => '42']); ?>
    <?php echo __('Browse Access Items'); ?>
  </h1>
<?php end_slot(); ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
input[type="date"] {
  height: 45px !important;
  font-size: 16px !important;
  width: 100% !important;
  padding: 6px 12px !important;
  box-sizing: border-box;
}
</style>

<?php slot('content'); ?>
<?php echo $form->renderGlobalErrors(); ?>
<section class="text-section">
	<body>
		<div>
	        <button type="submit" class="btn"><?php echo link_to(__('Back to reports'), ['module' => 'reports', 'action' => 'reportSelect'], ['title' => __('Back to reports')]); ?></button>
		</div>
		<table class="table table-bordered" border="1" cellpadding="0" cellspacing="0" bordercolor="#999999">
		<h4><?php echo __('Filter options'); ?></h4>
		<div>
			<?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAccess']), ['method' => 'get']); ?>

			<?php echo $form->renderHiddenFields(); ?>

			<div id='typeOfReport' style="display: none">
				<?php echo $form->className->label('Types of Reports')->renderRow(); ?>
			</div>

			<div class="col-md-4">
			<?php if (sfConfig::get('app_multi_repository')) { ?>
			<tr>
				<td colspan="2">
					<?php echo $form->repositories->label(__('Repository'))->renderRow(); ?>
				</td>
			<?php } ?>
			</tr>
			
			<tr>
				<td colspan="2">
			<?php echo $form->dateOf->renderRow(); ?>
				</td>
			</tr>

			<div class="col-md-4 start-date">
			<tr>
				<td>
				  <?php echo render_field($form->dateStart->label(__('Date Start')), null, ['type' => 'date']); ?>
				<td>
				  <?php echo render_field($form->dateEnd->label(__('Date End')), null, ['type' => 'date']); ?>
				</td>
			</tr>
			<tr>
			</tr>
				<td colspan="2">
				<button type="submit" class="btn"><?php echo __('Search'); ?></button>
				</td>
			</div>

			<div class="col-md-4">
			</div>	
		</div>
		</table>
      </form>

	</div>

  <table class="table table-bordered" border="1" cellpadding="0" cellspacing="0" bordercolor="#999999">
    <thead>
      <tr>
		<th style="width: 110px"><?php echo __('Identifier'); ?></th>
		<th style="width: 250px"><?php echo __('Title'); ?></th>
		<th><?php echo __('Refusal'); ?></th>
		<th><?php echo __('Sensitive'); ?></th>
		<th><?php echo __('Publish'); ?></th>
		<th><?php echo __('Classification'); ?></th>
		<th><?php echo __('Restriction'); ?></th>


        <?php if ('CREATED_AT' != $form->getValue('dateOf')) { ?>
          <th style="width: 110px"><?php echo __('Updated'); ?></th>
        <?php } else { ?>
          <th style="width: 110px"><?php echo __('Created'); ?></th>
        <?php } ?>
      </tr>
    </thead><tbody>
	<?php
      foreach ($pager->getResults() as $result) { ?>
        <tr class="<?php echo 0 == @++$row % 2 ? 'even' : 'odd'; ?>">
			<?php
                $infoObjectExist = QubitInformationObject::getById($result->object_id);
                if (isset($infoObjectExist)) {
                    foreach (QubitRelation::getRelationsBySubjectId($result->id) as $item2) {
                        $this->informationObjects = QubitInformationObject::getById($item2->objectId); ?>
						<?php if (isset($this->informationObjects->identifier)) { ?> <td><?php echo link_to($this->informationObjects->identifier, [$this->informationObjects, 'module' => 'informationobject']); ?></td> <?php } else { ?> <td>-</td> <?php }	?>
						<?php if (isset($this->informationObjects->title)) { ?> <td><?php echo $this->informationObjects->title; ?></td> <?php } else { ?> <td>-</td> <?php }	?>
					<?php
                }
            ?>

			<?php if (isset($result->refusalId)) { ?> 
				<?php if ('Please Select' == term_name($result->refusalId)) {?> <td>-</td> <?php } else { ?>
				<td><?php echo __('%1%', ['%1%' => term_name($result->refusalId)]); ?></td> <?php } ?>
			<?php } else { ?> 
				<td>-</td> 
			<?php }	?>
			<?php if (isset($result->sensitivityId)) { ?> 
				<?php if ('Please Select' == term_name($result->sensitivityId)) {?> <td>-</td> <?php } else { ?>
				<td><?php echo __('%1%', ['%1%' => term_name($result->sensitivityId)]); ?></td> <?php } ?>
			<?php } else { ?> 
				<td>-</td> 
			<?php }	?>
			<?php if (isset($result->publishId)) { ?> 
				<?php if ('Please Select' == term_name($result->publishId)) {?> <td>-</td> <?php } else { ?>
				<td><?php echo __('%1%', ['%1%' => term_name($result->publishId)]); ?></td> <?php } ?>
			<?php } else { ?> 
				<td>-</td> 
			<?php }	?>
			<?php if (isset($result->classificationId)) { ?> 
				<?php if ('Please Select' == term_name($result->publishId)) {?> <td>-</td> <?php } else { ?>
				<td><?php echo __('%1%', ['%1%' => term_name($result->classificationId)]); ?></td> <?php } ?>
			<?php } else { ?> 
				<td>-</td> 
			<?php }	?>
			<?php if (isset($result->restrictionId)) { ?> 
				<?php if ('Please Select' == term_name($result->restrictionId)) {?> <td>-</td> <?php } else { ?>
				<td><?php echo __('%1%', ['%1%' => term_name($result->restrictionId)]); ?></td> <?php } ?>
			<?php } else { ?> 
				<td>-</td> 
			<?php }	?>
			<td>
				<?php if ('CREATED_AT' != $form->getValue('dateOf')) { ?>
				<?php echo $result->updatedAt; ?>
				<?php } else { ?>
				<?php echo $result->createdAt; ?>
				<?php } ?>
			</td>

        </tr>
		<?php } ?>
				

      <?php } ?>

</section>



    </tbody>
  </table>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php end_slot(); ?>
