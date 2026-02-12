<h1><?php echo __('Audit Repository/Archival Institution'); ?></h1>

<table class="sticky-enabled">
  <thead>
    <tr>
      <th>

      </th>
    </tr>
  </thead><tbody>    
    <section class="actions">
      <ul>
		<li><input class="c-btn c-btn-submit" type="button" onclick="history.back();" value="Back"></li>
      </ul>
    </section>
	<?php $auditObjectsArr = []; ?>
  	<?php foreach ($auditObjectsOlder as $item) {  ?>
		<?php $auditObjectsArr[] = [$item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $item[8], $item[9], $item[10], $item[11]]; ?>
    <?php }  ?>
  	<?php foreach ($pager->getResults() as $item) { ?>
       <tr class="<?php echo 0 == @++$row % 2 ? 'even' : 'odd'; ?>">
        <td>
    		<?php echo '<hr>'; ?>
			<table border=1>
			<tr>
			<td colspan=3>Repository/Archival Institution Name
			</td>
			</tr>
			<tr>
				<td colspan=3>
					<b>
					<?php echo $item[1]; ?>
					</b>
				</td>
			</tr>

			<?php if ('insert' == $item[7]) { ?> 
				<?php $dAction = 'Inserted into '; ?> 
			<?php } elseif ('update' == $item[7]) { ?>
				<?php $dAction = 'Updated '; ?> 
			<?php } elseif ('delete' == $item[7]) { ?>
				<?php $dAction = 'Deleted from'; ?> 
			<?php } else { ?>
				<?php $dAction = $item[7]; ?> 
			<?php } ?>
 
			<?php if ('information_object' == $item[8]) { ?>
				<?php $dTable = "'Archival Description Store'"; ?> 
			<?php } elseif ('repository' == $item[8]) { ?>
				<?php $dTable = 'Repository/Archival Institution'; ?> 
			<?php } elseif ('repository_i18n' == $item[8]) { ?>
				<?php $dTable = 'Repository/Archival Institution Extend'; ?> 
			<?php } elseif ('note' == $item[8]) { ?>
				<?php $dTable = 'Note'; ?> 
			<?php } elseif ('other_name' == $item[8]) { ?>
				<?php $dTable = 'Other Name'; ?> 
			<?php } elseif ('actor' == $item[8]) { ?>
				<?php $dTable = 'Actor'; ?> 
			<?php } elseif ('actor_i18n' == $item[8]) { ?>
				<?php $dTable = 'Actor extend'; ?> 
			<?php } elseif ('contact_information_i18n' == $item['DB_TABLE']) { ?> 
				<?php $dTable = 'Contact Information'; ?> 
			<?php } else { ?>
				<?php $dTable = $item[8]; ?> 
			<?php } ?>
 
 			<?php $user = ''; ?>
 			<?php $date = ''; ?>


			<?php $rOlder = doGetTableValue($auditObjectsArr, $item['ID'], $item['DB_TABLE']); ?>
			
			<?php $rOlderValues = explode('~!~', $rOlder); ?>
			<?php $dTableOlder = $rOlderValues[0]; ?> 
			<?php $dActionOlder = $rOlderValues[1]; ?> 
			<?php $user = $rOlderValues[2]; ?>
			<?php $date = $rOlderValues[3]; ?>
			
			<tr>
				<td><b>Field</b></td> <td><b>Old Value</b</td> <td><b>New Value</b</td> 
			</tr>
			<tr>
				<td>ID</td> <td><?php echo $item['ID']; ?></td> <td><?php echo $item['ID']; ?></td> 
			</tr>
			<tr>
				<td>User</td> <td><?php echo $user; ?></td> <td><?php echo $item['USER']; ?></td> 
			</tr>
			<tr>
				<td>Date & Time</td> <td><?php echo $date; ?></td> <td><?php echo $item['ACTION_DATE_TIME']; ?></td> 
			</tr>
			<tr>
				<td>Action</td> <td><?php echo $dActionOlder.$dTableOlder; ?></td> <td><?php echo $dAction.$dTable; ?></td> 
			</tr>
			
			<tr>
				<td colspan=3>
			<?php echo '<b>DB QUERY: </b><br>'; ?>  
			</tr>
			<tr>
				<?php $strFieldsAndValues = explode('~~~', $item['DB_QUERY']); ?> 
				<?php $strFields = explode('~!~', $strFieldsAndValues[0]); ?> 
				<?php $strValues = explode('~!~', $strFieldsAndValues[1]); ?>
				<?php $arr_length = count($strFields); ?>
				<?php for ($i = 0; $i < $arr_length; ++$i) { ?>
					<?php if ('LFT' != $strFields[$i] && 'RGT' != $strFields[$i]) { ?> 
						<?php $strValue = $strValues[$i]; ?> 

						<?php if ('AUTHORIZED_FORM_OF_NAME' == trim($strFields[$i])) { ?>
					
							<?php $strOlder = doGetFieldValue('AUTHORIZED_FORM_OF_NAME', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Value</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Value</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('CORPORATE_BODY_IDENTIFIERS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CORPORATE_BODY_IDENTIFIERS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Value</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Value</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('GEOCULTURAL_CONTEXT' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('GEOCULTURAL_CONTEXT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Geocultural Context</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Geocultural Context</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('COLLECTING_POLICIES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('COLLECTING_POLICIES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Collection Policies</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Collection Policies</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>


						<?php } elseif ('BUILDINGS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('BUILDINGS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Buildings</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Buildings</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('HOLDINGS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('HOLDINGS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Holdings</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Holdings</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('OPENING_TIMES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('OPENING_TIMES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Opening Times</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Opening Times</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('UPLOAD_LIMIT' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('UPLOAD_LIMIT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Upload Limit</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Holdings</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DISABLED_ACCESS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DISABLED_ACCESS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Disabled Access</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Disabled Access</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('RESEARCH_SERVICES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RESEARCH_SERVICES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Research Services</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Research Services</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('REPRODUCTION_SERVICES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REPRODUCTION_SERVICES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Reproduction Services</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Reproduction Services</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('PUBLIC_FACILITIES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('PUBLIC_FACILITIES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Public Areas</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Public Areas</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_INSTITUTION_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_INSTITUTION_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>	Institution Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>	Institution Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_RULES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_RULES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Rules and/or Conventions Used</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Rules and/or Conventions Used</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_SOURCES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_SOURCES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Sources</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Sources</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_REVISION_HISTORY' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_REVISION_HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Dates of creation<br> revision and deletion</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Dates of creation<br> revision and deletion</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('HISTORY' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>History</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>History</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('MANDATES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('MANDATES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Mandates/Sources of authority</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Mandates/Sources of authority</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('INTERNAL_STRUCTURES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('INTERNAL_STRUCTURES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Administrative structure</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Administrative structure</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_STATUS_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_STATUS_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Record ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Record ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('OBJECT_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('OBJECT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Object ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Object ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Description Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Description Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESC_DETAIL_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESC_DETAIL_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Object ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Object ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('TYPE_ID' == trim($strFields[$i]) && 'relation' == $item[5]) { ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Physical Storage</i></td><td>'.QubitPhysicalObject::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitPhysicalObject::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Physical Storage</i></td><td>'.QubitPhysicalObject::getById($strOlder).'</td><td>'.QubitPhysicalObject::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('TYPE_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('TYPE_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Type ID</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Type ID</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('STATUS_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('STATUS_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Status</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Status</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('REPOSITORY_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REPOSITORY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Repository</i></td><td>'.QubitRepository::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitRepository::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Repository</i></td><td>'.QubitRepository::getById($strOlder).'</td><td>'.QubitRepository::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('RESTRICTION_CONDITION' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RESTRICTION_CONDITION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Restriction Condition</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Restriction Condition</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('REFUSAL_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REFUSAL_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Refusal</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Refusal</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SENSITIVITY_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SENSITIVITY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Sensitive</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Sensitive</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('PUBLISH_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('PUBLISH_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Publish</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Publish</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('CLASSIFICATION_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CLASSIFICATION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Classification</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Classification</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('RESTRICTION_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RESTRICTION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Restriction</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Restriction</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('FORMAT_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('FORMAT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Type and form of Archive</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Type and form of Archive</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('REGISTRY_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REGISTRY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Registry</i></td><td>'.QubitRegistry::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitRegistry::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Registry</i></td><td>'.QubitRegistry::getById($strOlder).'</td><td>'.QubitRegistry::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SIZE_ID' == trim($strFields[$i])) { ?>

						<?php } elseif ('TYP_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('TYP_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Type</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Type</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('EQUIPMENT_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('EQUIPMENT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Equipment Available</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Equipment Available</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('DISPLAY_STANDARD_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DISPLAY_STANDARD_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Display Standard</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Display Standard</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SOURCE_STANDARD' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SOURCE_STANDARD', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Standard</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Standard</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('DESCRIPTION_DETAIL_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESCRIPTION_DETAIL_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Description Detail</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Description Detail</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('DESCRIPTION_STATUS_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESCRIPTION_STATUS_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Description Status</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Description Status</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('PARTNO' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('PARTNO', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Part Number</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Part Number</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('LEVEL_OF_DESCRIPTION_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('LEVEL_OF_DESCRIPTION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Level of Description</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Level of Description</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('EXTENT_AND_MEDIUM' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('EXTENT_AND_MEDIUM', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Extent and medium</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?>
							<?php } else { ?>
								<?php echo '<td><i>Extent and medium</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?>
							<?php } ?>
						<?php } elseif ('ARCHIVAL_HISTORY' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ARCHIVAL_HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php // echo "<td><i>Archival history</i></td><td>" . $strOlder . "</td><td>" . $strValues[$i] . "</td><tr>"?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Archival history</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Archival history</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('ACQUISITION' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ACQUISITION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Immediate source of acquisition or transfer</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Immediate source of acquisition or transfer</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SCOPE_AND_CONTENT' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SCOPE_AND_CONTENT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Scope and content</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Scope and content</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('APPRAISAL' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('APPRAISAL', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php echo '<td><i>Appraisal, destruction and scheduling</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?>
						<?php } elseif ('ACCRUALS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ACCRUALS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Accruals</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Accruals</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('ARRANGEMENT' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ARRANGEMENT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>System of arrangement</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>System of arrangement</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('ACCESS_CONDITIONS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ACCESS_CONDITIONS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Conditions governing access</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Conditions governing access</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('REPRODUCTION_CONDITIONS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REPRODUCTION_CONDITIONS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Conditions governing reproduction</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Conditions governing reproduction</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('PHYSICAL_CHARACTERISTICS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('PHYSICAL_CHARACTERISTICS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Physical characteristics and technical requirements</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Physical characteristics and technical requirements</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('FINDING_AIDS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('FINDING_AIDS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Finding aids</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Finding aids</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('LOCATION_OF_ORIGINALS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('LOCATION_OF_ORIGINALS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Existence and location of originals</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Existence and location of originals</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('LOCATION_OF_COPIES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('LOCATION_OF_COPIES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Existence and location of copies</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Existence and location of copies</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('RELATED_UNITS_OF_DESCRIPTION' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RELATED_UNITS_OF_DESCRIPTION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Related units of description</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Related units of description</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('INSTITUTION_RESPONSIBLE_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('INSTITUTION_RESPONSIBLE_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Institution identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Institution identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('RULES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RULES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Rules or conventions</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Rules or conventions</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SOURCES' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SOURCES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Sources</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Sources</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('REVISION_HISTORY' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REVISION_HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Dates of creation, revision and deletion</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Dates of creation, revision and deletion</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('VOLUME_NUMBER_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('VOLUME_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Volume</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Volume</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('FILE_NUMBER_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('FILE_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>File</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>File</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('PART_NUMBER_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('PART_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Part</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Part</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('CULTURE' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CULTURE', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Culture/Language</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Culture/Language</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('ITEM_NUMBER_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ITEM_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Item</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Item</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('TITLE' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('TITLE', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Title</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Title</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('NAME' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('NAME', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Name</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Name</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('DESCRIPTION_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESCRIPTION_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Description Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Description Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SOURCE_STANDARD' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SOURCE_STANDARD', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Source Standard</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Source Standard</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('note' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('note', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Note</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Note</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('PARENT_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('PARENT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Parent ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Parent ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SOURCE_CULTURE' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SOURCE_CULTURE', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Source Culture</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Source Culture</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('USABILITY_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('USABILITY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Usibility</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Usibility</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('MEASURE_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('MEASURE_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Measure</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Measure</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('MEDIUM_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('MEDIUM_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Medium</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Medium</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('AVAILABILITY_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('AVAILABILITY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Available</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Available</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('RESTORATION_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RESTORATION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Restoration</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Restoration</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('CONSERVATION_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CONSERVATION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Conservation</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Conservation</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('Type_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('Type_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Type</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Type</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('RECORD_CONDITION' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('RECORD_CONDITION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Condition</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Condition</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('AVAILABILITY' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('AVAILABILITY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Available</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Available</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('LOCATION' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('LOCATION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Location</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Location</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SHELF' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SHELF', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Shelf</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Shelf</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('ROW' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ROW', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Row</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Row</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('STRONG_ROOM' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('STRONG_ROOM', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Strong room</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Strong room</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('REMARKS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('REMARKS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Remarks</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Remarks</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('UNIQUE_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('UNIQUE_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Unique identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Unique identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('TIME_PERIOD' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('TIME_PERIOD', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Date/Time</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Date/Time</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } else { ?>
								<?php if ('ID' != $strFields[$i]) { ?>
									<?php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
									<?php echo '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?>
								<?php } ?>
						<?php } ?>
					<?php } ?>
				<?php } ?>
      </tr>
 
 			</table>

        </td>
      </tr>
		<?php // endif;?>
    <?php } ?>
  </tbody>
</table>

<div id="result-count">
  <?php echo __('Showing %1% results', ['%1%' => $foundcount]); ?>
</div>

    <section class="actions">
      <ul>
		<li><input class="c-btn c-btn-submit" type="button" onclick="history.back();" value="Back"></li>
      </ul>
    </section>

<?php
function doGetFieldValue($keyValue, $auditObjectsArr2, $item_ID, $item, $itemTable)
{
    try {
        $oValue = '';

        $arrSize = sizeof($auditObjectsArr2);
        // $arrSize = $arrSize - 1;

        for ($n = 0; $n < $arrSize; ++$n) {
            if ('' != $oValue) {
                break;
            }
            $strFieldsAndValuesOlder2 = explode('~~~', $auditObjectsArr2[$n][9]);
            $strFieldsOlder2 = explode('~!~', $strFieldsAndValuesOlder2[0]);
            $strValuesOlder2 = explode('~!~', $strFieldsAndValuesOlder2[1]);
            if ($item_ID > $auditObjectsArr2[$n][2]) {   // Check for ID to be older than current ID
            if ($itemTable == $auditObjectsArr2[$n][8]) {   // same tables
                for ($j = 0; $j < count($strFieldsOlder2); ++$j) {
                        if ($keyValue == $strFieldsOlder2[$j]) {
                            $oValue = $strValuesOlder2[$j];

                            break;
                        }
                    }
                }
            }
        }

    // echo "oValue=".$oValue."<br>";
        return $oValue;
    } catch (Exception $e) {
        Propel::log($e->getMessage(), Propel::LOG_ERR);

        throw new PropelException('Unable to perform get filed value.', $e);
    }
}

function doGetTableValue($auditObjectsArr2, $item_ID, $itemTable)
{
    try {
        $oValue = '';
        $oAction = '';
        $oTable = '';
        $oUser = '';
        $oDdate = '';

        $arrSize = sizeof($auditObjectsArr2);
        $arrSize = $arrSize - 1;
        for ($n = 0; $n < $arrSize; ++$n) {
            $strFieldsAndValuesOlder2 = explode('~~~', $auditObjectsArr2[$n][9]);
            $strFieldsOlder2 = explode('~!~', $strFieldsAndValuesOlder2[0]);
            $strValuesOlder2 = explode('~!~', $strFieldsAndValuesOlder2[1]);

            if ($item_ID > $auditObjectsArr2[$n][2]) {   // Check for ID to be older than current ID
                if ($itemTable == $auditObjectsArr2[$n][8]) {   // same tables
                $oAction = $auditObjectsArr2[$n][7];
                    $oTable = $auditObjectsArr2[$n][8];
                    $oUser = $auditObjectsArr2[$n][10];
                    $oDdate = $auditObjectsArr2[$n][11];

                    break;
                }
            }
        }

        if ('insert' == $oAction) {
            $dActionOlder = 'Inserted into ';
        } elseif ('update' == $oAction) {
            $dActionOlder = 'Updated ';
        } elseif ('delete' == $oAction) {
            $dActionOlder = 'Deleted from';
        } else {
            $dActionOlder = $oAction;
        }

        if ('repository' == $oTable) {
            $dTableOlder = 'Repository';
        } elseif ('repository_i18n' == $oTable) {
            $dTableOlder = 'Repository Extend';
        } elseif ('contact_information_i18n' == $oTable) {
            $dTableOlder = 'Contact Information';
        } else {
            $dTableOlder = $oTable;
        }

        return $dTableOlder.'~!~'.$dActionOlder.'~!~'.$oUser.'~!~'.$oDdate;
    } catch (Exception $e) {
        Propel::log($e->getMessage(), Propel::LOG_ERR);

        throw new PropelException('Unable to perform get filed value.', $e);
    }
}

?>
<?php slot('after-content'); ?>
<?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php end_slot(); ?>

