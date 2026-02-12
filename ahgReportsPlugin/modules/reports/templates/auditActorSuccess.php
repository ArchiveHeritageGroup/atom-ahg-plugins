<h1><?php echo __('Audit Actor/Authority Record'); ?></h1>

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
		<?php $auditObjectsArr[] = [$item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $item[8], $item[9]]; ?>
    <?php }  ?>

  	<?php foreach ($pager->getResults() as $item) { ?>
  	
       <tr class="<?php echo 0 == @++$row % 2 ? 'even' : 'odd'; ?>">
        <td>
    		<?php echo '<hr>'; ?>
			<table border=1>
			<tr>
				<td colspan=3>
					<b>Record ID: 
					<?php echo $item[1]; ?>
					</b>
				</td>
			</tr>
			<tr>
				<td>Name</td><td colspan=2><b><?php echo $item[11]; ?></b></td> 
			</tr>
			
			<?php if ('insert' == $item['ACTION']) { ?> 
				<?php $dAction = 'Inserted into '; ?> 
			<?php } elseif ('update' == $item['ACTION']) { ?>
				<?php $dAction = 'Updated '; ?> 
			<?php } elseif ('delete' == $item['ACTION']) { ?>
				<?php $dAction = 'Deleted from'; ?> 
			<?php } else { ?>
				<?php $dAction = $item['ACTION']; ?> 
			<?php } ?>
 
			<?php if ('actor' == $item['DB_TABLE']) { ?> 
				<?php $dTable = 'Actor'; ?> 
			<?php } elseif ('actor_i18n' == $item['DB_TABLE']) { ?> 
				<?php $dTable = 'Actor Extend'; ?> 
			<?php } elseif ('acl_user_group' == $item['DB_TABLE']) { ?> 
				<?php $dTable = 'User Group'; ?> 
			<?php } elseif ('actor_i18n' == $item['DB_TABLE']) { ?> 
				<?php $dTable = 'Actor Extend'; ?> 
			<?php } elseif ('contact_information_i18n' == $item['DB_TABLE']) { ?> 
				<?php $dTable = 'Contact Information'; ?> 
			<?php } else { ?>
				<?php $dTable = $item['DB_TABLE']; ?> 
			<?php } ?>
 
 			<?php $user = ''; ?>
 			<?php $date = ''; ?>


			<?php $rOlder = doGetTableValue($auditObjectsArr, $item[0], $item['DB_TABLE']); ?>
			<?php $rOlderValues = explode('~!~', $rOlder); ?>
			<?php $dTableOlder = $rOlderValues[0]; ?> 
			<?php $dActionOlder = $rOlderValues[1]; ?> 
			<?php $user = $rOlderValues[2]; ?>
			<?php $date = $rOlderValues[3]; ?>
			
			<tr>
				<td><b>Field</b></td> <td><b>Old Value</b</td> <td><b>New Value</b</td> 
			</tr>
			<tr>
				<td>Audit ID</td> <td><?php // echo $item[0]?></td> <td><?php echo $item[0]; ?></td> 
			</tr>
			<tr>
				<td>User</td> <td><?php echo $user; ?></td> <td><?php echo $item[8]; ?></td> 
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
					<?php $strValue = $strValues[$i]; ?> 
						<?php if ('USER_ID' == trim($strFields[$i])) { ?>
					
							<?php $strOlder = doGetFieldValue('USER_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>User ID</i></td><td>'.QubitUser::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitUser::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>User ID</i></td><td>'.QubitUser::getById($strOlder).'</td><td>'.QubitUser::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('GROUP_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('GROUP_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Group ID</i></td><td>'.QubitAclGroup::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitAclGroup::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Group ID</i></td><td>'.QubitAclGroup::getById($strOlder).'</td><td>'.QubitAclGroup::getById($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('CLASS_NAME' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CLASS_NAME', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Class Name</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Class Name</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('UPDATED_AT' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('UPDATED_AT', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Updated At</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Updated At</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SOURCE_CULTURE' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SOURCE_CULTURE', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Source Language</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Source Language</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('CULTURE' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CULTURE', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Source Language</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Source Language</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>
						<?php } elseif ('SECURITY_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('SECURITY_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Security Classification</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Security Classification</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('ACTIVE' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ACTIVE', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php (1 == $strOlder) ? $activeOld = 'Active' : $activeOld = 'Inactive';
								(1 == $strValues[$i]) ? $active = 'Active' : $active = 'Inactive';
								echo '<td><i>Account Status</i></td><td>'.$activeOld."</td><td bgcolor='#CCFF66'>".$active.'</td><tr>'; ?> 
							<?php } else { ?>
								<?php (1 == $strOlder) ? $activeOld = 'Active' : $activeOld = 'Inactive';
								(1 == $strValues[$i]) ? $active = 'Active' : $active = 'Inactive';
								echo '<td><i>Account Status</i></td><td>'.$activeOld.'</td><td>'.$active.'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('ENTITY_TYPE_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('ENTITY_TYPE_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Entity Type</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Entity Type</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESCRIPTION_STATUS_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESCRIPTION_STATUS_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Status</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Status</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESCRIPTION_DETAIL_ID' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESCRIPTION_DETAIL_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Detail</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Detail</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('DESCRIPTION_IDENTIFIER' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('DESCRIPTION_IDENTIFIER', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Description</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Description</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>

						<?php } elseif ('CORPORATE_BODY_IDENTIFIERS' == trim($strFields[$i])) { ?>
							<?php $strOlder = doGetFieldValue('CORPORATE_BODY_IDENTIFIERS', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>Identifiers for Corporate Bodies</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php echo '<td><i>Identifiers for Corporate Bodies</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?> 
							<?php } ?>




						<?php } else { ?>
							<?php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
							<?php if ($strOlder != $strValues[$i]) { ?>
								<?php echo '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>'; ?> 
							<?php } else { ?>
								<?php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); ?>
								<?php echo '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>'; ?>
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

            $strFieldsAndValuesOlder2 = explode('~~~', $auditObjectsArr2[$n][7]);
            $strFieldsOlder2 = explode('~!~', $strFieldsAndValuesOlder2[0]);
            $strValuesOlder2 = explode('~!~', $strFieldsAndValuesOlder2[1]);

            if ($item_ID > $auditObjectsArr2[$n][0]) {   // Check for ID to be older than current ID
            if ($itemTable == $auditObjectsArr2[$n][6]) {   // same tables
                for ($j = 0; $j < count($strFieldsOlder2); ++$j) {
                        if ($keyValue == $strFieldsOlder2[$j]) {
                            $oValue = $strValuesOlder2[$j];

                            break;
                        }
                    }
                }
            }
        }

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
            $strFieldsAndValuesOlder2 = explode('~~~', $auditObjectsArr2[$n][7]);
            $strFieldsOlder2 = explode('~!~', $strFieldsAndValuesOlder2[0]);
            $strValuesOlder2 = explode('~!~', $strFieldsAndValuesOlder2[1]);

            if ($item_ID > $auditObjectsArr2[$n][0]) {   // Check for ID to be older than current ID
            if ($itemTable == $auditObjectsArr2[$n][6]) {   // same tables
                $oAction = $auditObjectsArr2[$n][5];
                    $oTable = $auditObjectsArr2[$n][6];
                    $oUser = $auditObjectsArr2[$n][8];
                    $oDdate = $auditObjectsArr2[$n][9];

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

        if ('actor' == $oTable) {
            $dTableOlder = 'Actor';
        } elseif ('actor_i18n' == $oTable) {
            $dTableOlder = 'Actor Extend';
        } elseif ('acl_user_group' == $oTable) {
            $dTableOlder = 'User Group';
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

