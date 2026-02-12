@extends('layouts.page')

@section('content')

<h1>{{ __('Audit Actor/Authority Record') }}</h1>

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
	@php $auditObjectsArr = []; @endphp
  	@foreach ($auditObjectsOlder as $item)
		@php $auditObjectsArr[] = [$item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $item[8], $item[9]]; @endphp
    @endforeach

  	@foreach ($pager->getResults() as $item)

       <tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
        <td>
    		{!! '<hr>' !!}
			<table border=1>
			<tr>
				<td colspan=3>
					<b>Record ID:
					{!! $item[1] !!}
					</b>
				</td>
			</tr>
			<tr>
				<td>Name</td><td colspan=2><b>{!! $item[11] !!}</b></td>
			</tr>

			@if ('insert' == $item['ACTION'])
				@php $dAction = 'Inserted into '; @endphp
			@elseif ('update' == $item['ACTION'])
				@php $dAction = 'Updated '; @endphp
			@elseif ('delete' == $item['ACTION'])
				@php $dAction = 'Deleted from'; @endphp
			@else
				@php $dAction = $item['ACTION']; @endphp
			@endif

			@if ('actor' == $item['DB_TABLE'])
				@php $dTable = 'Actor'; @endphp
			@elseif ('actor_i18n' == $item['DB_TABLE'])
				@php $dTable = 'Actor Extend'; @endphp
			@elseif ('acl_user_group' == $item['DB_TABLE'])
				@php $dTable = 'User Group'; @endphp
			@elseif ('actor_i18n' == $item['DB_TABLE'])
				@php $dTable = 'Actor Extend'; @endphp
			@elseif ('contact_information_i18n' == $item['DB_TABLE'])
				@php $dTable = 'Contact Information'; @endphp
			@else
				@php $dTable = $item['DB_TABLE']; @endphp
			@endif

 			@php $user = ''; @endphp
 			@php $date = ''; @endphp


			@php $rOlder = doGetTableValue($auditObjectsArr, $item[0], $item['DB_TABLE']); @endphp
			@php $rOlderValues = explode('~!~', $rOlder); @endphp
			@php $dTableOlder = $rOlderValues[0]; @endphp
			@php $dActionOlder = $rOlderValues[1]; @endphp
			@php $user = $rOlderValues[2]; @endphp
			@php $date = $rOlderValues[3]; @endphp

			<tr>
				<td><b>Field</b></td> <td><b>Old Value</b</td> <td><b>New Value</b</td>
			</tr>
			<tr>
				<td>Audit ID</td> <td></td> <td>{!! $item[0] !!}</td>
			</tr>
			<tr>
				<td>User</td> <td>{!! $user !!}</td> <td>{!! $item[8] !!}</td>
			</tr>
			<tr>
				<td>Date & Time</td> <td>{!! $date !!}</td> <td>{!! $item['ACTION_DATE_TIME'] !!}</td>
			</tr>
			<tr>
				<td>Action</td> <td>{!! $dActionOlder.$dTableOlder !!}</td> <td>{!! $dAction.$dTable !!}</td>
			</tr>

			<tr>
				<td colspan=3>
			{!! '<b>DB QUERY: </b><br>' !!}
			</tr>
			<tr>
				@php $strFieldsAndValues = explode('~~~', $item['DB_QUERY']); @endphp
				@php $strFields = explode('~!~', $strFieldsAndValues[0]); @endphp
				@php $strValues = explode('~!~', $strFieldsAndValues[1]); @endphp
				@php $arr_length = count($strFields); @endphp
				@for ($i = 0; $i < $arr_length; ++$i)
					@php $strValue = $strValues[$i]; @endphp
						@if ('USER_ID' == trim($strFields[$i]))

							@php $strOlder = doGetFieldValue('USER_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>User ID</i></td><td>'.QubitUser::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitUser::getById($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>User ID</i></td><td>'.QubitUser::getById($strOlder).'</td><td>'.QubitUser::getById($strValues[$i]).'</td><tr>' !!}
							@endif

						@elseif ('GROUP_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('GROUP_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Group ID</i></td><td>'.\AtomExtensions\Services\AclGroupService::getById($strOlder)."</td><td bgcolor='#CCFF66'>".\AtomExtensions\Services\AclGroupService::getById($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Group ID</i></td><td>'.\AtomExtensions\Services\AclGroupService::getById($strOlder).'</td><td>'.\AtomExtensions\Services\AclGroupService::getById($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('CLASS_NAME' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CLASS_NAME', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Class Name</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Class Name</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('UPDATED_AT' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('UPDATED_AT', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Updated At</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Updated At</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SOURCE_CULTURE' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SOURCE_CULTURE', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Source Language</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Source Language</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('CULTURE' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CULTURE', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Source Language</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Source Language</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SECURITY_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SECURITY_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Security Classification</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Security Classification</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif

						@elseif ('ACTIVE' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ACTIVE', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								@php
								(1 == $strOlder) ? $activeOld = 'Active' : $activeOld = 'Inactive';
								(1 == $strValues[$i]) ? $active = 'Active' : $active = 'Inactive';
								@endphp
								{!! '<td><i>Account Status</i></td><td>'.$activeOld."</td><td bgcolor='#CCFF66'>".$active.'</td><tr>' !!}
							@else
								@php
								(1 == $strOlder) ? $activeOld = 'Active' : $activeOld = 'Inactive';
								(1 == $strValues[$i]) ? $active = 'Active' : $active = 'Inactive';
								@endphp
								{!! '<td><i>Account Status</i></td><td>'.$activeOld.'</td><td>'.$active.'</td><tr>' !!}
							@endif

						@elseif ('ENTITY_TYPE_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ENTITY_TYPE_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Entity Type</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Entity Type</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif

						@elseif ('DESCRIPTION_STATUS_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESCRIPTION_STATUS_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Status</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Status</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif

						@elseif ('DESCRIPTION_DETAIL_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESCRIPTION_DETAIL_ID', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Detail</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Detail</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif

						@elseif ('DESCRIPTION_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESCRIPTION_IDENTIFIER', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Description</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Description</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('CORPORATE_BODY_IDENTIFIERS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CORPORATE_BODY_IDENTIFIERS', $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Identifiers for Corporate Bodies</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Identifiers for Corporate Bodies</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif




						@else
							@php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								@php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item[0], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
								{!! '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

					@endif
				@endfor
      </tr>

 			</table>

        </td>
      </tr>
    @endforeach
  </tbody>
</table>

<div id="result-count">
  {{ __('Showing %1% results', ['%1%' => $foundcount]) }}
</div>

    <section class="actions">
      <ul>
		<li><input class="c-btn c-btn-submit" type="button" onclick="history.back();" value="Back"></li>
      </ul>
    </section>

@php
function doGetFieldValue($keyValue, $auditObjectsArr2, $item_ID, $item, $itemTable)
{
    try {
        $oValue = '';

        $arrSize = sizeof($auditObjectsArr2);

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
@endphp

@endsection
