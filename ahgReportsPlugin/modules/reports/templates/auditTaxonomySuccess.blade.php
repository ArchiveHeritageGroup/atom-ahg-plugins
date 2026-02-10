@extends('layouts.page')

@section('content')
<h1>{{ __('Audit Taxonomy') }}</h1>

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

  	@foreach ($auditObjects as $item)

       <tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
        <td>
    		{!! '<hr>' !!}
			<table border=1>
			<tr>
				<td colspan=3>
					<b>Record ID:
					{{ $item['SECONDARY_VALUE'] }}
					</b>
				</td>
			</tr>
			<tr>
				@php
					$taxonomy = QubitTerm::getById($item['SECONDARY_VALUE']);
					$taxonomyObjectsAudit = QubitTerm::getById($item['RECORD_ID']);
				@endphp
				<td>Name</td><td colspan=2>{!! QubitTaxonomy::getById($taxonomy->taxonomyId).' - '.$taxonomyObjectsAudit !!}</td>
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
			@else
				@php $dTable = $item['DB_TABLE']; @endphp
			@endif

 			@php $user = ''; @endphp
 			@php $date = ''; @endphp


			@php
				$rOlder = doGetTableValue($auditObjectsArr, $item['ID'], $item['DB_TABLE']);
				$rOlderValues = explode('~!~', $rOlder);
				$dTableOlder = $rOlderValues[0];
				$dActionOlder = $rOlderValues[1];
				$user = $rOlderValues[2];
				$date = $rOlderValues[3];
			@endphp

			<tr>
				<td><b>Field</b></td> <td><b>Old Value</b</td> <td><b>New Value</b</td>
			</tr>
			<tr>
				<td>ID</td> <td>{{-- $item[0] --}}</td> <td>{{ $item[0] }}</td>
			</tr>
			<tr>
				<td>User</td> <td>{{ $user }}</td> <td>{{ $item[8] }}</td>
			</tr>
			<tr>
				<td>Date & Time</td> <td>{{ $date }}</td> <td>{{ $item['ACTION_DATE_TIME'] }}</td>
			</tr>
			<tr>
				<td>Action</td> <td>{{ $dActionOlder.$dTableOlder }}</td> <td>{{ $dAction.$dTable }}</td>
			</tr>

			<tr>
				<td colspan=3>
			{!! '<b>DB QUERY: </b><br>' !!}
			</tr>
			<tr>
				@php
					$strFieldsAndValues = explode('~~~', $item['DB_QUERY']);
					$strFields = explode('~!~', $strFieldsAndValues[0]);
					$strValues = explode('~!~', $strFieldsAndValues[1]);
					$arr_length = count($strFields);
				@endphp
				@for ($i = 0; $i < $arr_length; ++$i)
					@php
						$strValue = $strValues[$i];
						$strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']);
					@endphp
					@if ($strOlder != $strValues[$i])
						{!! '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder."</td><td bgcolor='#CCFF66'>".$strValues[$i].'</td><tr>' !!}
					@else
						@php $strOlder = doGetFieldValue($strFields[$i], $auditObjectsArr, $item['ID'], $item['ACTION_DATE_TIME'], $item['DB_TABLE']); @endphp
						{!! '<td><i>'.$strFields[$i].'</i></td><td>'.$strOlder.'</td><td>'.$strValues[$i].'</td><tr>' !!}
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
function doGetFieldValue($keyValue, $auditObjectsArr2, $item_ID, $item, $item4)
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
            if ('NAME' == $keyValue) {
    }
                if ($item4 == $auditObjectsArr2[$n][6]) {   // same tables
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

function doGetTableValue($auditObjectsArr2, $item_ID, $item4)
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
            if ($item4 == $auditObjectsArr2[$n][6]) {   // same tables
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
