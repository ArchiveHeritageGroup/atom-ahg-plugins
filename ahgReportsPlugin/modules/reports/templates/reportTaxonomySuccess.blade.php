@extends('layouts.page')

@section('title')
  <h1 class="multiline">
    <img src="{{ image_path('/images/icons-large/icon-new.png') }}" width="42" height="42" alt="" />
    {{ __('Browse Physical Storage') }}
  </h1>
@endsection

@section('sidebar')
{!! $form->renderGlobalErrors() !!}
<section class="sidebar-widget">

	<body onload="javascript:NewCal('dateStart','ddmmyyyy',false,false,24,true);renderCalendar('dateStart','div0');
			  javascript:NewCal('dateEnd','ddmmyyyy',false,false,24,true);renderCalendar('dateEnd','div1');toggleOff('div3');">

		<div>
	        <button type="submit" class="btn"><a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" title="{{ __('Back to reports') }}">{{ __('Back to reports') }}</a></button>
		</div>
		<h4>{{ __('Filter options') }}</h4>
		<div>

			{!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportPhysicalStorage']), ['method' => 'get']) !!}

			{!! $form->renderHiddenFields() !!}

			<div id='divTypeOfReport' style="display: none">
				{!! $form->className->label('Types of Reports')->renderRow() !!}
			</div>

			@if (false)
			{{ __('Date range') }}
			{!! $form->dateStart->renderError() !!}
			{!! $form->dateEnd->renderError() !!}
			{!! __('%1% to %2%', [
			    '%1%' => $form->dateStart->render(),
			    '%2%' => $form->dateEnd->render()]) !!}
			@endif

			{!! $form->dateOf->renderRow() !!}

			<td>
				@php
					$currentDate = date('d/m/Y H:i:s', strtotime('-3 months'));
				@endphp
				{!! $form->dateStart->renderRow(['value' => $currentDate, 'readonly' => 'true', 'onchange' => 'checkBigger();'], 'Start Date') !!}
				<div id="div0">Auto fill datepicker - Time Period - This will be deleted automatically</div>
			</td>

			<td>
				@php
					$currentDate = date('d/m/Y H:i:s');
				@endphp
				{!! $form->dateEnd->renderRow(['value' => $currentDate, 'readonly' => 'true', 'onchange' => ''], 'End Date') !!}
				<div id="div1">Auto fill datepicker - dateEnd - This will be deleted automatically</div>
			</td>

	        <button type="submit" class="btn">{{ __('Search') }}</button>
      </form>

	</div>

</section>
@endsection

@section('content')

  <table class="table table-bordered" border="1" cellpadding="0" cellspacing="0" bordercolor="#999999">
    <thead>
      <tr>
		<th>{{ __('Unique Identifier') }}</th>
		<th>{{ __('Description/Title') }}</th>
		<th>{{ __('Method of Storage') }}</th>
		<th>{{ __('Location') }}</th>
		<th>{{ __('Period Covered') }}</th>
		<th>{{ __('Extent') }}</th>
		<th>{{ __('Accrual Space') }}</th>
		<th>{{ __('Forms') }}</th>

        @if ('CREATED_AT' != $form->getValue('dateOf'))
          <th style="width: 110px">{{ __('Updated') }}</th>
        @else
          <th style="width: 110px">{{ __('Created') }}</th>
        @endif
      </tr>
    </thead><tbody>
     @foreach ($pager->getResults() as $result)
        <tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
			@if (isset($result->id))
				<td><a href="{{ url_for(['module' => 'physicalobject', 'slug' => $result->slug]) }}">{{ $result->getUniqueIdentifier(['cultureFallback' => true]) ?? $result->getName(['cultureFallback' => true]) ?? '-' }}</a></td>
			@else
				<td>-</td>
			@endif
			@if (isset($result->id)) <td>{{ $result->getDescriptionTitle(['cultureFallback' => true]) }}</td> @else <td>-</td> @endif
			@if (isset($result->type)) <td>{{ $result->type }}</td> @else <td>-</td> @endif
			@if (isset($result->id))
				<td>{{ $result->getLocation(['cultureFallback' => true]) }}</td>
			@else
				<td>-</td>
			@endif

			@if (isset($result->id)) <td>{{ $result->getPeriodCovered(['cultureFallback' => true]) }}</td> @else <td>-</td> @endif
			@if (isset($result->id)) <td>{{ $result->getExtent(['cultureFallback' => true]) }}</td> @else <td>-</td> @endif
			@if (isset($result->id)) <td>{{ $result->getAccrualSpace(['cultureFallback' => true]) }}</td> @else <td>-</td> @endif
			@if (isset($result->id)) <td>{{ $result->getForms(['cultureFallback' => true]) }}</td> @else <td>-</td> @endif

          </td>
		<td>
			@if ('CREATED_AT' != $form->getValue('dateOf'))
			{{ $result->updatedAt }}
			@else
			{{ $result->createdAt }}
			@endif
		</td>

        </tr>

      @endforeach

    </tbody>
  </table>

@endsection

@section('after-content')
{!! get_partial('default/pager', ['pager' => $pager]) !!}
@endsection
