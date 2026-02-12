@extends('layouts.page')

@section('title', __('Audit Repository/Archival Institution'))

@section('content')

<h1>{{ __('Audit Repository/Archival Institution') }}</h1>

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
		@php $auditObjectsArr[] = [$item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6], $item[7], $item[8], $item[9], $item[10], $item[11]]; @endphp
    @endforeach
  	@foreach ($pager->getResults() as $item)
       <tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
        <td>
    		{!! '<hr>' !!}
			<table border=1>
			<tr>
			<td colspan=3>Repository/Archival Institution Name
			</td>
			</tr>
			<tr>
				<td colspan=3>
					<b>
					{!! $item[1] !!}
					</b>
				</td>
			</tr>

			@if ('insert' == $item[7])
				@php $dAction = 'Inserted into '; @endphp
			@elseif ('update' == $item[7])
				@php $dAction = 'Updated '; @endphp
			@elseif ('delete' == $item[7])
				@php $dAction = 'Deleted from'; @endphp
			@else
				@php $dAction = $item[7]; @endphp
			@endif

			@if ('information_object' == $item[8])
				@php $dTable = "'Archival Description Store'"; @endphp
			@elseif ('repository' == $item[8])
				@php $dTable = 'Repository/Archival Institution'; @endphp
			@elseif ('repository_i18n' == $item[8])
				@php $dTable = 'Repository/Archival Institution Extend'; @endphp
			@elseif ('note' == $item[8])
				@php $dTable = 'Note'; @endphp
			@elseif ('other_name' == $item[8])
				@php $dTable = 'Other Name'; @endphp
			@elseif ('actor' == $item[8])
				@php $dTable = 'Actor'; @endphp
			@elseif ('actor_i18n' == $item[8])
				@php $dTable = 'Actor extend'; @endphp
			@elseif ('contact_information_i18n' == $item['DB_TABLE'])
				@php $dTable = 'Contact Information'; @endphp
			@else
				@php $dTable = $item[8]; @endphp
			@endif

 			@php $user = ''; @endphp
 			@php $date = ''; @endphp


			@php $rOlder = doGetTableValue($auditObjectsArr, $item['ID'], $item['DB_TABLE']); @endphp

			@php $rOlderValues = explode('~!~', $rOlder); @endphp
			@php $dTableOlder = $rOlderValues[0]; @endphp
			@php $dActionOlder = $rOlderValues[1]; @endphp
			@php $user = $rOlderValues[2]; @endphp
			@php $date = $rOlderValues[3]; @endphp

			<tr>
				<td><b>Field</b></td> <td><b>Old Value</b</td> <td><b>New Value</b</td>
			</tr>
			<tr>
				<td>ID</td> <td>{!! $item['ID'] !!}</td> <td>{!! $item['ID'] !!}</td>
			</tr>
			<tr>
				<td>User</td> <td>{!! $user !!}</td> <td>{!! $item['USER'] !!}</td>
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
					@if ('LFT' != $strFields[$i] && 'RGT' != $strFields[$i])
						@php $strValue = $strValues[$i]; @endphp

						@if ('AUTHORIZED_FORM_OF_NAME' == trim($strFields[$i]))

							@php $strOlder = doGetFieldValue('AUTHORIZED_FORM_OF_NAME', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Value</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Value</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('CORPORATE_BODY_IDENTIFIERS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CORPORATE_BODY_IDENTIFIERS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Value</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Value</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('GEOCULTURAL_CONTEXT' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('GEOCULTURAL_CONTEXT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Geocultural Context</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Geocultural Context</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('COLLECTING_POLICIES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('COLLECTING_POLICIES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Collection Policies</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Collection Policies</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif


						@elseif ('BUILDINGS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('BUILDINGS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Buildings</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Buildings</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('HOLDINGS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('HOLDINGS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Holdings</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Holdings</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('OPENING_TIMES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('OPENING_TIMES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Opening Times</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Opening Times</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('UPLOAD_LIMIT' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('UPLOAD_LIMIT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Upload Limit</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Holdings</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DISABLED_ACCESS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DISABLED_ACCESS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Disabled Access</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Disabled Access</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('RESEARCH_SERVICES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RESEARCH_SERVICES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Research Services</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Research Services</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('REPRODUCTION_SERVICES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REPRODUCTION_SERVICES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Reproduction Services</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Reproduction Services</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('PUBLIC_FACILITIES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('PUBLIC_FACILITIES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Public Areas</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Public Areas</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_INSTITUTION_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_INSTITUTION_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>	Institution Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>	Institution Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_RULES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_RULES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Rules and/or Conventions Used</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Rules and/or Conventions Used</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_SOURCES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_SOURCES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Sources</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Sources</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_REVISION_HISTORY' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_REVISION_HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Dates of creation<br> revision and deletion</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Dates of creation<br> revision and deletion</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('HISTORY' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>History</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>History</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('MANDATES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('MANDATES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Mandates/Sources of authority</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Mandates/Sources of authority</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('INTERNAL_STRUCTURES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('INTERNAL_STRUCTURES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Administrative structure</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Administrative structure</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_STATUS_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_STATUS_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Record ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Record ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('OBJECT_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('OBJECT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Object ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Object ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Description Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Description Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('DESC_DETAIL_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESC_DETAIL_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Object ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Object ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('TYPE_ID' == trim($strFields[$i]) && 'relation' == $item[5])
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Physical Storage</i></td><td>'.QubitPhysicalObject::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitPhysicalObject::getById($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Physical Storage</i></td><td>'.QubitPhysicalObject::getById($strOlder).'</td><td>'.QubitPhysicalObject::getById($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('TYPE_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('TYPE_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Type ID</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Type ID</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('STATUS_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('STATUS_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Status</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Status</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('REPOSITORY_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REPOSITORY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Repository</i></td><td>'.QubitRepository::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitRepository::getById($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Repository</i></td><td>'.QubitRepository::getById($strOlder).'</td><td>'.QubitRepository::getById($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('RESTRICTION_CONDITION' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RESTRICTION_CONDITION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Restriction Condition</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Restriction Condition</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('REFUSAL_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REFUSAL_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Refusal</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Refusal</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('SENSITIVITY_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SENSITIVITY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Sensitive</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Sensitive</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('PUBLISH_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('PUBLISH_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Publish</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Publish</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('CLASSIFICATION_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CLASSIFICATION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Classification</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Classification</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('RESTRICTION_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RESTRICTION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Restriction</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Restriction</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('FORMAT_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('FORMAT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Type and form of Archive</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Type and form of Archive</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('REGISTRY_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REGISTRY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Registry</i></td><td>'.QubitRegistry::getById($strOlder)."</td><td bgcolor='#CCFF66'>".QubitRegistry::getById($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Registry</i></td><td>'.QubitRegistry::getById($strOlder).'</td><td>'.QubitRegistry::getById($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('SIZE_ID' == trim($strFields[$i]))

						@elseif ('TYP_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('TYP_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Type</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Type</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('EQUIPMENT_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('EQUIPMENT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Equipment Available</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Equipment Available</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('DISPLAY_STANDARD_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DISPLAY_STANDARD_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Display Standard</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Display Standard</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('SOURCE_STANDARD' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SOURCE_STANDARD', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Standard</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Standard</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('DESCRIPTION_DETAIL_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESCRIPTION_DETAIL_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Description Detail</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Description Detail</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('DESCRIPTION_STATUS_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESCRIPTION_STATUS_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Description Status</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Description Status</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('PARTNO' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('PARTNO', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Part Number</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Part Number</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('LEVEL_OF_DESCRIPTION_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('LEVEL_OF_DESCRIPTION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Level of Description</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Level of Description</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('EXTENT_AND_MEDIUM' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('EXTENT_AND_MEDIUM', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Extent and medium</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Extent and medium</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('ARCHIVAL_HISTORY' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ARCHIVAL_HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Archival history</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Archival history</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('ACQUISITION' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ACQUISITION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Immediate source of acquisition or transfer</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Immediate source of acquisition or transfer</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SCOPE_AND_CONTENT' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SCOPE_AND_CONTENT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Scope and content</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Scope and content</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('APPRAISAL' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('APPRAISAL', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							{!! '<td><i>Appraisal, destruction and scheduling</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
						@elseif ('ACCRUALS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ACCRUALS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Accruals</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Accruals</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('ARRANGEMENT' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ARRANGEMENT', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>System of arrangement</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>System of arrangement</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('ACCESS_CONDITIONS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ACCESS_CONDITIONS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Conditions governing access</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Conditions governing access</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('REPRODUCTION_CONDITIONS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REPRODUCTION_CONDITIONS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Conditions governing reproduction</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Conditions governing reproduction</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('PHYSICAL_CHARACTERISTICS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('PHYSICAL_CHARACTERISTICS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Physical characteristics and technical requirements</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Physical characteristics and technical requirements</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('FINDING_AIDS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('FINDING_AIDS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Finding aids</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Finding aids</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('LOCATION_OF_ORIGINALS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('LOCATION_OF_ORIGINALS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Existence and location of originals</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Existence and location of originals</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('LOCATION_OF_COPIES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('LOCATION_OF_COPIES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Existence and location of copies</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Existence and location of copies</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('RELATED_UNITS_OF_DESCRIPTION' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RELATED_UNITS_OF_DESCRIPTION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Related units of description</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Related units of description</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('INSTITUTION_RESPONSIBLE_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('INSTITUTION_RESPONSIBLE_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Institution identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Institution identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('RULES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RULES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Rules or conventions</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Rules or conventions</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SOURCES' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SOURCES', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Sources</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Sources</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('REVISION_HISTORY' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REVISION_HISTORY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Dates of creation, revision and deletion</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Dates of creation, revision and deletion</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('VOLUME_NUMBER_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('VOLUME_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Volume</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Volume</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('FILE_NUMBER_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('FILE_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>File</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>File</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('PART_NUMBER_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('PART_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Part</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Part</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('CULTURE' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CULTURE', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Culture/Language</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Culture/Language</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('ITEM_NUMBER_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ITEM_NUMBER_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Item</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Item</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('TITLE' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('TITLE', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Title</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Title</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('NAME' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('NAME', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Name</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Name</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('DESCRIPTION_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('DESCRIPTION_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Description Identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Description Identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SOURCE_STANDARD' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SOURCE_STANDARD', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Source Standard</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Source Standard</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('note' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('note', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Note</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Note</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('PARENT_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('PARENT_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Parent ID</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Parent ID</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SOURCE_CULTURE' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SOURCE_CULTURE', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Source Culture</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Source Culture</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif

						@elseif ('USABILITY_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('USABILITY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Usibility</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Usibility</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('MEASURE_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('MEASURE_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Measure</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Measure</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('MEDIUM_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('MEDIUM_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Medium</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Medium</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('AVAILABILITY_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('AVAILABILITY_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Available</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Available</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('RESTORATION_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RESTORATION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Restoration</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Restoration</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('CONSERVATION_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('CONSERVATION_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Conservation</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Conservation</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('Type_ID' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('Type_ID', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Type</i></td><td>'.term_name($strOlder)."</td><td bgcolor='#CCFF66'>".term_name($strValues[$i]).'</td><tr>' !!}
							@else
								{!! '<td><i>Type</i></td><td>'.term_name($strOlder).'</td><td>'.term_name($strValues[$i]).'</td><tr>' !!}
							@endif
						@elseif ('RECORD_CONDITION' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('RECORD_CONDITION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Condition</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Condition</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('AVAILABILITY' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('AVAILABILITY', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Available</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Available</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('LOCATION' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('LOCATION', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Location</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Location</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('SHELF' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('SHELF', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Shelf</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Shelf</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('ROW' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('ROW', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Row</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Row</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('STRONG_ROOM' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('STRONG_ROOM', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Strong room</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Strong room</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('REMARKS' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('REMARKS', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Remarks</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Remarks</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('UNIQUE_IDENTIFIER' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('UNIQUE_IDENTIFIER', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Unique identifier</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Unique identifier</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@elseif ('TIME_PERIOD' == trim($strFields[$i]))
							@php $strOlder = doGetFieldValue('TIME_PERIOD', $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
							@if ($strOlder != $strValues[$i])
								{!! '<td><i>Date/Time</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
							@else
								{!! '<td><i>Date/Time</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
							@endif
						@else
								@if ('ID' != $strFields[$i])
									@php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
									{!! '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
								@endif
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
@endphp

@endsection

@section('after-content')
{!! get_partial('default/pager', ['pager' => $pager]) !!}
@endsection
