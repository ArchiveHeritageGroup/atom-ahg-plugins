<?php if (isset($pager) && $pager->getNbResults() || sfConfig::get('app_enable_institutional_scoping')) { ?>
  <?php decorate_with('layout_2col'); ?>
<?php } else { ?>
  <?php decorate_with('layout_1col'); ?>
<?php } ?>

<?php slot('title'); ?>
  <h1 class="multiline">
    <?php echo image_tag('/images/icons-large/icon-new.png', ['width' => '42', 'height' => '42']); ?>
    <?php echo __('Browse User Activity'); ?>
		<?php if (isset($pager) && $pager->getNbResults()) { ?>
        	<?php echo __('Showing %1% results', ['%1%' => $pager->getNbResults()]); ?>
		<?php } else { ?>
			<?php echo __('No results found'); ?>
		<?php } ?>
  </h1>
<?php end_slot(); ?>
 
<?php slot('sidebar'); ?>
<?php echo $form->renderGlobalErrors(); ?>
<section class="sidebar-widget">
	<body onload="javascript:NewCal('dateStart','ddmmyyyy',false,false,24,true);renderCalendar('dateStart','div0');
			  javascript:NewCal('dateEnd','ddmmyyyy',false,false,24,true);renderCalendar('dateEnd','div1');toggleOff('div3');">

		<div>
	        <button type="submit" class="btn"><?php echo link_to(__('Back to reports'), ['module' => 'reports', 'action' => 'reportSelect'], ['title' => __('Back to reports')]); ?></button>
		</div>
		<h4><?php echo __('Filter options'); ?></h4>
		<div>
			<form>
			<?php echo $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAuditTrail']), ['method' => 'get']); ?>
			<?php echo $form->renderHiddenFields(); ?>
			<?php echo $form->actionUser->label('User')->renderRow(); ?>
			<?php echo $form->userAction->label('User Action')->renderRow(); ?>
			<?php echo $form->userActivity->label('User Activity')->renderRow(); ?>
			<?php echo $form->chkSummary->label('Summary')->renderRow(); ?>

			<td>
			  <?php echo render_field($form->dateStart->label(__('Date Start')), null, ['type' => 'date']); ?>
			<td>
			  <?php echo render_field($form->dateEnd->label(__('Date End')), null, ['type' => 'date']); ?>
			</td>
			<button type="submit" class="btn"><?php echo __('Search'); ?></button>
      </form>
	</div>

</section>
 
<?php end_slot(); ?>

<?php slot('content'); ?>
  <table class="table table-bordered" border="1" cellpadding="0" cellspacing="0" bordercolor="#999999">
    <thead>
		<?php $name = $_GET['chkSummary']; ?>
			<?php if ('on' == $name) { ?>
			  <tr>
				<th><?php echo __('User'); ?></th>
				<th><?php echo __('Action'); ?></th>
				<th><?php echo __('Table'); ?></th>
				<th><?php echo __('Repository'); ?></th>
				<th><?php echo __('Count'); ?></th>
			  </tr>
			
			<?php } else { ?>
			  <tr>
				<th><?php echo __('User'); ?></th>
				<th><?php echo __('Action'); ?></th>
				<th><?php echo __('Action Date'); ?></th>
				<th><?php echo __('Identifier'); ?></th>
				<th><?php echo __('Title'); ?></th>
				<th><?php echo __('Repository'); ?></th>
				<th><?php echo __('Activity Area'); ?></th>
			  </tr>
			<?php } ?>
    </thead>
	<tbody>

	<?php if (isset($pager) && (float) $pager->getNbResults() > 0) { ?>
		<?php if (null != $pager->getResults()) { ?>
			<?php if ('on' != $name) { ?>
				<?php foreach ($pager->getResults() as $item) { ?>
					<?php $title = ''; ?>
					<?php $identifier = ''; ?>
					<?php $getRepo = ''; ?>
					<?php $strFieldsAndValues = explode('||', $item['DB_QUERY']); ?> 
					<?php $strFields = explode('~', $strFieldsAndValues[0]); ?> 
					<?php $strValues = explode('~', $strFieldsAndValues[1]); ?>
					<?php $arr_length = count($strFields); ?>
					<?php for ($i = 0; $i < $arr_length; ++$i) { ?>
						<?php if ('identifier' == $strFields[$i]) { ?>
							<?php if ('' != $strValues[$i]) { ?>
								<?php $identifier = $strValues[$i]; ?>
							<?php } ?>
						<?php } elseif ('corporateBodyIdentifiers' == $strFields[$i]) { ?>
							<?php if ('' != $strValues[$i]) { ?>
								<?php $identifier = $strValues[$i]; ?>
							<?php } ?>
						<?php } ?>						

						<?php if ('title' == $strFields[$i]) { ?>
							<?php $title = $strValues[$i]; ?>
						<?php } ?>
						<?php if ('altTitle' == $strFields[$i]) { ?>
							<?php if ('' == $title) { ?>
								<?php $title = $strValues[$i]; ?>
							<?php } ?>
						<?php } ?>
						<?php if ('authorizedFormOfName' == $strFields[$i]) { ?>
							<?php if ('' == $title) { ?> 
								<?php $title = $strValues[$i]; ?>
							<?php } ?>
						<?php } ?>
						<?php if ('name' == $strFields[$i]) { ?>
							<?php if ('' == $title) { ?>
								<?php $title = $strValues[$i]; ?>
							<?php } ?>
						<?php } ?>
						<?php if ('sourceCulture' == $strFields[$i]) { ?>
							<?php if ('' == $title) { ?>
								<?php // $title = $strValues[$i]?>
							<?php } ?>
						<?php } ?>
						<?php if ('repositoryId' == $strFields[$i]) { ?>
							<?php if ('' != $strValues[$i]) { ?>
								<?php
                                    $getRepo = QubitRepository::getById($strValues[$i]);
                                ?>
							<?php } ?>
						<?php } ?>

						<?php if ('QubitActor' == $item['DB_TABLE']) { ?>
							<?php if ('sourceCulture' == $strFields[$i]) { ?>
								<?php $title = $strValues[$i]; ?>
							<?php } ?>
						<?php } ?>
					<?php } ?>
					
					<tr class="<?php echo 0 == @++$row % 2 ? 'even' : 'odd'; ?>">
					<td><?php echo $item['USER']; ?></td> 
					<td><?php echo $item['ACTION']; ?></td> 
					<td><?php echo $item['ACTION_DATE_TIME']; ?></td> 
					<?php if (isset($item['CLASS_NAME'])) {?>
					
					<?php // QubitAccessObject?>
						<?php if ('QubitAccessObject' == $item['CLASS_NAME']) { ?>
							<?php $accessObjectsAudit = QubitAccessObject::getById($item['RECORD_ID']); ?>
							<?php $accessObjectsAccess = QubitAccessObjectI18n::getById($accessObjectsAudit->id); ?>
							<?php $informationObjectsAudit = QubitInformationObject::getById($accessObjectsAccess->object_id); ?>
							<?php $informationObjectsRepo = QubitRepository::getById($informationObjectsAudit->repositoryId); ?>
							<td><?php echo $informationObjectsAudit->identifier; ?></td>
							<?php if (!isset($informationObjectsAudit)) { ?>
								<td><?php echo 'Deleted: Access'.$accessObjectsAccess->name; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($informationObjectsAudit, [$informationObjectsAudit, 'module' => 'informationobject']); ?></td> 
							<?php } ?>
							<?php if (isset($informationObjectsRepo)) {?>
								<td><?php echo $informationObjectsRepo; ?></td> 
							<?php } else { ?>
								<td><?php echo 'Repository not yet set'; ?></td> 
							<?php } ?>

					<?php // QubitInformationObject?>
						<?php } elseif ('QubitInformationObject' == $item['CLASS_NAME']) { ?>
							<?php $informationObjectsAudit = QubitInformationObject::getById($item['RECORD_ID']); ?>
							<td><?php echo $informationObjectsAudit->identifier; ?></td>
							<?php if (null == $informationObjectsAudit) { ?>
								<td><?php echo link_to('Deleted: Archival Description', ['module' => 'informationobject', 'action' => 'informationobject', 'source' => $item['RECORD_ID']]); ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($informationObjectsAudit, [$informationObjectsAudit, 'module' => 'informationobject']); ?></td> 
							<?php } ?>
							<td><?php echo QubitRepository::getById($informationObjectsAudit->repositoryId); ?></td>
							
					<?php // QubitRepository?>
						<?php } elseif ('QubitRepository' == $item['CLASS_NAME']) { ?>
							<?php $actorObjectsAudit = QubitActor::getById($item['RECORD_ID']); ?>
							<?php $repoObjects = QubitRepository::getById($item['RECORD_ID']); ?>
							<?php if (null == $repoObjects) { ?>
								<td><?php echo 'Not set'; ?></td> 
							<?php } else { ?>
								<td><?php echo $repoObjects->identifier; ?></td> 
							<?php } ?>
							<?php if (null == $actorObjectsAudit) { ?>
								<td><?php echo 'Deleted: Repository'; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($actorObjectsAudit, [$actorObjectsAudit, 'module' => 'repository']); ?></td> 
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>
							
					<?php // QubitActor?>
						<?php } elseif ('QubitActor' == $item['CLASS_NAME']) { ?>
							<?php $actorObjectsAudit = QubitActor::getById($item['RECORD_ID']); ?>
							<?php if (null == $actorObjectsAudit) { ?>
								<td><?php echo 'Deleted: Actor'; ?></td> 
							<?php } else { ?>
								<td><?php echo $actorObjectsAudit->corporateBodyIdentifiers; ?></td>
							<?php } ?>
							<?php if (null == $actorObjectsAudit) { ?>
								<td><?php echo 'Deleted: Actor'; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($actorObjectsAudit, [$actorObjectsAudit, 'module' => 'actor']); ?></td>
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>

					<?php // QubitBookoutObject to fix?>
						<?php } elseif ('QubitBookoutObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'N/A'; ?></td>
							<?php $bookOutObjectsAudit = QubitBookoutObject::getById($item['RECORD_ID']); ?>
							<?php if (null == $bookOutObjectsAudit) { ?>
								<td><?php echo 'Deleted: Bookout Object'; ?></td> 
							<?php } else { ?>
								<td><?php echo $bookOutObjectsAudit; ?></td>  
							<?php } ?>

							<?php $accessObjectsAudit = QubitBookoutObjectI18n::getById($bookOutObjectsAudit->id); ?>
							<?php $informationObjectsAudit = QubitInformationObject::getById($accessObjectsAudit->object_id); ?>
							<?php $informationObjectsRepo = QubitRepository::getById($informationObjectsAudit->repositoryId); ?>
							<?php if (isset($informationObjectsRepo)) {?>
							<td><?php echo $informationObjectsRepo; ?></td>
							<?php } else { ?>
								<td><?php echo 'Repository not yet set'; ?></td> 
							<?php } ?>
													
					<?php // QubitBookinObject to fix?>
						<?php } elseif ('QubitBookinObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'N/A'; ?></td>
							<?php $bookinObjectsAudit = QubitBookinObject::getById($item['RECORD_ID']); ?>
							<?php if (null == $bookinObjectsAudit) { ?>
								<td><?php echo link_to('Book In missing', ['module' => 'reports', 'action' => 'auditBookIn', 'source' => $item['ID']]); ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($bookinObjectsAudit, ['module' => 'reports', 'action' => 'auditBookIn', 'source' => $item['ID']]); ?></td> 
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>
							
					<?php // QubitDigitalObject to fix?>
						<?php } elseif ('QubitDigitalObject' == $item['CLASS_NAME']) { ?>
							<?php $digitalObjectsAudit = QubitDigitalObject::getById($item['RECORD_ID']); ?>
							<?php if (null == $digitalObjectsAudit) { ?>
								<td><?php echo link_to('Item not found. Call administrator.', ['module' => 'informationobject', 'action' => 'QubitInformationObject', 'source' => $item['RECORD_ID']]); ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($digitalObjectsAudit, [$digitalObjectsAudit, 'module' => 'digitalobject']); ?></td>
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>

							<td><?php echo $digitalObjectsAudit[0]->parentId; ?></td> 
							<?php $digitalObjectsAudit3 = QubitInformationObject::getById($digitalObjectsAudit[0]->parentId); ?>
							<td><?php echo $digitalObjectsAudit3->repositoryId; ?></td> 
							<?php $informationObjectsRepo = QubitRepository::getById($digitalObjectsAudit3->repositoryId); ?>
							<?php if (isset($informationObjectsRepo)) {?>
							<td><?php echo $informationObjectsRepo; ?></td>
							<?php } else { ?>
								<td><?php echo $digitalObjectsAudit; ?></td> 
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>

					<?php // QubitDonor?>
						<?php } elseif ('QubitDonor' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'N/A'; ?></td>
							<?php $donorObjectsAudit = QubitDonor::getById($item['RECORD_ID']); ?>
							<?php if (null == $donorObjectsAudit) { ?>
								<td><?php echo 'Deleted: Donor'; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($donorObjectsAudit, [$donorObjectsAudit, 'module' => 'donor']); ?></td> 
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>

					<?php // QubitPhysicalObject?>
						<?php } elseif ('QubitPhysicalObject' == $item['CLASS_NAME']) { ?>
							<?php $physicalObjectObjectsAudit = QubitPhysicalObject::getById($item['RECORD_ID']); ?>
							<?php $physicalObjectObject = QubitPhysicalObjecti18n::getById($physicalObjectObjectsAudit->id); ?>
							<?php if (null == $physicalObjectObject) { ?>
								<td><?php echo 'N/A'; ?></td> 
							<?php } else { ?>
								<td><?php echo $physicalObjectObject->uniqueIdentifier; ?></td>
							<?php } ?>
							<?php if (null == $physicalObjectObjectsAudit) { ?>
								<td><?php echo 'Deleted: Physical Storage'; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($physicalObjectObjectsAudit, [$physicalObjectObjectsAudit, 'module' => 'physicalstorage']); ?></td>
							<?php } ?>
							<td><?php echo render_value(QubitRepository::getById($physicalObjectObjectsAudit->getRepositoryId(['cultureFallback' => true]))); ?> </td>

					<?php // QubitPresevationObject?>
						<?php } elseif ('QubitPresevationObject' == $item['CLASS_NAME']) { ?>
							<?php $presevationObjectsAudit = QubitPresevationObject::getById($item['RECORD_ID']); ?>
							<?php $presevationObjectsAccess = QubitPresevationObject::getById($presevationObjectsAudit->id); ?>
							<?php $informationObjectsAudit = QubitInformationObject::getById($presevationObjectsAccess->object_id); ?>
							<?php $informationObjectsRepo = QubitRepository::getById($informationObjectsAudit->repositoryId); ?>
							<td><?php echo $informationObjectsAudit->identifier; ?></td>
							<?php if (!isset($informationObjectsAudit)) { ?>
								<td><?php echo 'Deleted: Access'.$presevationObjectsAccess->name; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($informationObjectsAudit, [$informationObjectsAudit, 'module' => 'informationobject']); ?></td> 
							<?php } ?>
							<?php if (isset($informationObjectsRepo)) {?>
								<td><?php echo $informationObjectsRepo; ?></td> 
							<?php } else { ?>
								<td><?php echo 'Repository not yet set'; ?></td> 
							<?php } ?>

					<?php // QubitRegistry?>
						<?php } elseif ('QubitRegistry' == $item['CLASS_NAME']) { ?>
							<?php $registryObjectsAudit = QubitActor::getById($item['RECORD_ID']); ?>
							<?php if (null == !$registryObjectsAudit) { ?>
								<td><?php echo $registryObjectsAudit->corporateBodyIdentifiers; ?></td> 
							<?php } else { ?>
								<td><?php 'N?A?'; ?></td>
							<?php } ?>
							<?php if (null == $registryObjectsAudit) { ?>
								<td><?php echo 'Deleted: Registry'; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($registryObjectsAudit, ['module' => 'registry', 'source' => $item['RECORD_ID']]); ?></td> 
							<?php } ?>
							<td><?php echo 'N/A'; ?></td>

					<?php // QubitResearcher?>
						<?php } elseif ('QubitResearcher' == $item['CLASS_NAME']) { ?>
							<?php $researcherObjectsActor = QubitActor::getById($item['RECORD_ID']); ?>
							<?php $researcherObjectsAudit = QubitResearcher::getById($item['RECORD_ID']); ?>
							<?php if (null == !$researcherObjectsActor) { ?>
								<td><?php echo $researcherObjectsActor->corporateBodyIdentifiers; ?></td> 
							<?php } else { ?>
								<td><?php 'N?A?'; ?></td>
							<?php } ?>
							<?php if (null == $researcherObjectsAudit) { ?>
								<td><?php echo 'N/A'; ?></td>
								<td><?php echo 'Deleted: Researcher'; ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($researcherObjectsAudit, ['module' => 'researcher', 'source' => $item['RECORD_ID']]); ?></td>  
							<?php } ?>
							<?php if ('' != $item['REPOSITORY_ID']) { ?>
								<?php
                                    $getRepo = QubitRepository::getById($researcherObjectsAudit->repositoryId);
                                ?>
								<td><?php echo $getRepo; ?></td>
							<?php } else { ?>
								<td><?php echo 'Unknown'; ?></td>
							<?php } ?>

					<?php // QubitServiceProvider?>
						<?php } elseif ('QubitServiceProvider' == $item['CLASS_NAME']) { ?>
							<?php $serviceProviderObjectsActor = QubitActor::getById($item['RECORD_ID']); ?>
							<?php $serviceProviderObjects = QubitServiceProvider::getById($item['RECORD_ID']); ?>
							<?php if (null == !$serviceProviderObjectsActor) { ?>
								<td><?php echo $serviceProviderObjectsActor->corporateBodyIdentifiers; ?></td> 
							<?php } else { ?>
								<td><?php 'N?A?'; ?></td>
							<?php } ?>
							<?php if (null == $serviceProviderObjectsActor) { ?>
								<td><?php echo link_to('Actor', ['module' => 'reports', 'action' => 'auditServiceProvider', 'source' => $item['RECORD_ID']]); ?></td> 
							<?php } else { ?>
								<td><?php echo link_to($serviceProviderObjectsActor, ['module' => 'serviceProvider', 'source' => $item['RECORD_ID']]); ?></td>
							<?php } ?>
							<?php if ('' != $item['REPOSITORY_ID']) { ?>
								<?php $getRepo = QubitRepository::getById($serviceProviderObjects->repositoryId); ?>
								<td><?php echo $getRepo; ?></td>
							<?php } else { ?>
								<td><?php echo 'Unknown'; ?></td>
							<?php } ?>

					<?php // QubitUser?>
						<?php } elseif ('QubitUser' == $item['CLASS_NAME']) { ?>
							<?php $actorObjectsAudit = QubitActor::getById($item['RECORD_ID']); ?>
							<?php if (null == $actorObjectsAudit) { ?>
								<td><?php echo 'Deleted: User'; ?></td> 
							<?php } else { ?>
								<!--td><?php // echo link_to($actorObjectsAudit, array($actorObjectsAudit, 'module' => 'actor'))?></td--> 
							<?php } ?>
							<td><?php echo $actorObjectsAudit; ?></td>
							<td><?php echo $actorObjectsAudit->email; ?></td>
							<td><?php echo 'N/A'; ?></td>
							
					<?php // QubitTerm?>
						<?php } elseif ('QubitTerm' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'N/A'; ?></td>
						
							<?php if ('acl_group_i18n' == $item['DB_TABLE']) { ?>
								<?php $taxonomyObjectsAudit = \AtomExtensions\Services\AclGroupService::getById($item['RECORD_ID']); ?>
							<?php } else { ?>
								<?php $taxonomyObjectsAudit = term_name($item['RECORD_ID']); ?>
							<?php } ?>
							<?php if (null == $taxonomyObjectsAudit) { ?>
								<td><?php echo link_to('Taxonomy/Term missing', ['module' => '', 'action' => 'taxonomy', 'source' => $item['RECORD_ID']]); ?></td> 
							<?php } else { ?>
								<?php if ('acl_group_i18n' == $item['DB_TABLE']) { ?>
									<td><?php echo link_to($taxonomyObjectsAudit, ['module' => '', 'action' => 'auditPermissions', 'source' => $item['RECORD_ID']]); ?></td> 
								<?php } else { ?>
									<td><?php echo link_to($taxonomyObjectsAudit, ['module' => '', 'action' => 'taxonomy', 'source' => $item['RECORD_ID']]); ?></td> 
								<?php } ?>

							<?php } ?>
							<td><?php echo 'N/A'; ?></td>
							
						<?php } else { ?>
							<td><?php echo $item['ID']; ?></td> 
						<?php } ?>
					<?php } else { ?>				
						<?php if ('' != $identifier) { ?>
							<td><?php echo link_to($identifier, ['module' => 'reports', 'action' => 'reportDeleted', 'source' => $item['RECORD_ID']]); ?></td> 
						<?php } else { ?>
							<td><?php echo $identifier; ?></td> 
						<?php } ?>
						<td><?php echo $title; ?></td> 

					<?php } ?>
					
					<?php if (isset($item['CLASS_NAME'])) {?>
						<?php if ('QubitInformationObject' == $item['CLASS_NAME']) { ?>
							<?php if ('presevation_object' == $item['DB_TABLE']) { ?>
								<td><?php echo 'Preservation'; ?></td> 
							<?php } else { ?>
								<td><?php echo 'Archival Description'; ?></td> 
							<?php } ?>
						<?php } elseif ('qubitActor' == $item['CLASS_NAME']) { ?>
							<?php if ('QubitRegistry' == $item['CLASS_NAME']) { ?>
								<td><?php echo 'Registry'; ?></td> 
							<?php } elseif ('QubitRepository' == $item['CLASS_NAME']) { ?>
								<td><?php echo 'Repository'; ?></td> 
							<?php } else { ?>
								<td><?php echo 'Actor/Authority Record'; ?></td> 
							<?php } ?>
						<?php } elseif ('QubitRepository' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Archival Institution'; ?></td>
							 
						<?php } elseif ('QubitResearcher' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Researcher'; ?></td> 
							
						<?php } elseif ('QubitServiceProvider' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Service Provider'; ?></td> 
							
						<?php } elseif ('QubitPhysicalObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Physical Storage'; ?></td> 
							
						<?php } elseif ('QubitRegistry' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Registry'; ?></td> 
							
						<?php } elseif ('QubitRearcher' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Rearcher'; ?></td> 
							
						<?php } elseif ('QubitActor' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Actor/Authority Record'; ?></td> 
							
						<?php } elseif ('QubitUser' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'User'; ?></td> 
							
						<?php } elseif ('QubitDonor' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Donor'; ?></td> 
							
						<?php } elseif ('QubitTerm' == $item['CLASS_NAME']) { ?>
							<?php if ('acl_group_i18n' == $item['DB_TABLE']) { ?>
								<td><?php echo 'Permissions/Groups'; ?></td> 
							<?php } else { ?>
								<td><?php echo 'Taxonomy/Term'; ?></td> 
							<?php } ?>
						<?php } elseif ('QubitBookinObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Book In'; ?></td> 
							
						<?php } elseif ('QubitBookoutObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Book Out'; ?></td> 
							
						<?php } elseif ('QubitAccessObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Access'; ?></td> 
							
						<?php } elseif ('QubitPresevationObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Preservation'; ?></td> 
							
						<?php } elseif ('QubitDigitalObject' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Digital Object'; ?></td> 
							
						<?php } elseif ('QubitObjectTermRelation' == $item['CLASS_NAME']) { ?>
							<td><?php echo 'Object Term Relation'; ?></td> 
							
						<?php } else { ?>
							<td><?php echo $item['CLASS_NAME']; ?></td> 

						<?php } ?>
					<?php } else { ?>
						<?php if ('QubitInformationObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Archival Description'; ?></td> 
							
						<?php } elseif ('QubitActor' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Actor/Authority Record'; ?></td> 

						<?php } elseif ('QubitRepository' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Archival Institution'; ?></td>
							 
						<?php } elseif ('QubitResearcher' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Researcher'; ?></td> 
							
						<?php } elseif ('QubitServiceProvider' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Service Provider'; ?></td> 
							
						<?php } elseif ('QubitPhysicalObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Physical Storage'; ?></td> 
							
						<?php } elseif ('QubitRegistry' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Registry'; ?></td> 
							
						<?php } elseif ('QubitRearcher' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Rearcher'; ?></td> 
							
						<?php } elseif ('QubitActor' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Actor/Authority Record'; ?></td> 
							
						<?php } elseif ('QubitUser' == $item['DB_TABLE']) { ?>
							<td><?php echo 'User'; ?></td> 
							
						<?php } elseif ('QubitDonor' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Donor'; ?></td> 
							
						<?php } elseif ('QubitTerm' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Taxonomy/Term'; ?></td> 
							
						<?php } elseif ('QubitBookinObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Book In'; ?></td> 
							
						<?php } elseif ('QubitBookoutObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Book Out'; ?></td> 
							
						<?php } elseif ('QubitAccessObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Access'; ?></td> 
							
						<?php } elseif ('QubitPresevationObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Preservation'; ?></td> 
							
						<?php } elseif ('QubitDigitalObject' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Digital Object'; ?></td> 
							
						<?php } elseif ('QubitObjectTermRelation' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Object Term Relation'; ?></td> 
							
						<?php } elseif ('QubitAccession' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Accession'; ?></td> 
							
						<?php } elseif ('QubitTaxonomy' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Taxonomy'; ?></td> 
							
						<?php } elseif ('QubitFunction' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Function'; ?></td> 
							
						<?php } elseif ('QubitDeaccession' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Deaccession'; ?></td> 
							
						<?php } else { ?>
							<td><?php echo $item['DB_TABLE']; ?></td> 
							<td><?php echo '-'; ?></td> 
							<td><?php echo '-'; ?></td> 
							<td><?php echo '-'; ?></td> 
							<td><?php echo '-'; ?></td> 
							<td><?php echo '-'; ?></td> 
						<?php } ?>
						<td><?php echo link_to($item['DB_TABLE'], ['module' => 'reports', 'action' => 'auditDeleted', 'source' => $item['ID']]); ?></td> 
						<td><?php echo $getRepo; ?></td> 
					<?php } ?>
				</tr>
			<?php } ?>
			<?php } else { ?>
				<?php foreach ($pager->getResults() as $item) { ?>
				<tr>
					<td><?php echo $item['USER']; ?></td> 
					<td><?php echo $item['ACTION']; ?></td> 


						<?php if ('information_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Archival Description'; ?></td> 
							
						<?php } elseif ('actor' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Actor/Authority Record'; ?></td> 

						<?php } elseif ('repository' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Archival Institution'; ?></td>
							 
						<?php } elseif ('researcher' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Researcher'; ?></td> 
							
						<?php } elseif ('service_provider' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Service Provider'; ?></td> 
							
						<?php } elseif ('physical_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Physical Storage'; ?></td> 
							
						<?php } elseif ('registry' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Registry'; ?></td> 
							
						<?php } elseif ('user' == $item['DB_TABLE']) { ?>
							<td><?php echo 'User'; ?></td> 
							
						<?php } elseif ('donor' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Donor'; ?></td> 
							
						<?php } elseif ('term' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Taxonomy/Term'; ?></td> 
							
						<?php } elseif ('bookin_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Book In'; ?></td> 
							
						<?php } elseif ('bookout_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Book Out'; ?></td> 
							
						<?php } elseif ('access_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Access'; ?></td> 
							
						<?php } elseif ('presevation_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Preservation'; ?></td> 
							
						<?php } elseif ('digital_object' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Digital Object'; ?></td> 
												
						<?php } elseif ('accession' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Accession'; ?></td> 
							
						<?php } elseif ('taxonomy' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Taxonomy'; ?></td> 
							
						<?php } elseif ('function' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Function'; ?></td> 
							
						<?php } elseif ('deaccession' == $item['DB_TABLE']) { ?>
							<td><?php echo 'Deaccession'; ?></td> 
							
						<?php } else { ?>
							<td><?php echo $item['DB_TABLE']; ?></td> 
						<?php } ?>



					<td><?php echo '-'; ?></td> 
					<td><?php echo $item['count']; ?></td> 
				</tr>
			<?php } ?>
			<?php } ?>
		<?php } else { ?>
			<?php decorate_with('layout_2col'); ?>
		<?php } ?>
		
	<?php } else { ?>
		<?php decorate_with('layout_2col'); ?>
	<?php } ?>
    </tbody>
  </table>
<?php end_slot(); ?>

<?php if (isset($pager)) { ?>
	<?php slot('after-content'); ?>
		<?php echo get_partial('default/pager', ['pager' => $pager]); ?>
	<?php end_slot(); ?>
<?php } ?>
