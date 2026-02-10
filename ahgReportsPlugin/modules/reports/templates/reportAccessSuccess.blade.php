@extends('layouts.page')

@section('title')
  <h1 class="multiline">
    <img src="{{ image_path('/images/icons-large/icon-new.png') }}" width="42" height="42" alt="" />
    {{ __('Browse Access Items') }}
  </h1>
@endsection

<style {!! csp_nonce_attr() !!}>
input[type="date"] {
  height: 45px !important;
  font-size: 16px !important;
  width: 100% !important;
  padding: 6px 12px !important;
  box-sizing: border-box;
}
</style>

@section('content')
{!! $form->renderGlobalErrors() !!}
<section class="text-section">
	<body>
		<div>
	        <button type="submit" class="btn"><a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" title="{{ __('Back to reports') }}">{{ __('Back to reports') }}</a></button>
		</div>
		<table class="table table-bordered" border="1" cellpadding="0" cellspacing="0" bordercolor="#999999">
		<h4>{{ __('Filter options') }}</h4>
		<div>
			{!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAccess']), ['method' => 'get']) !!}

			{!! $form->renderHiddenFields() !!}

			<div id='typeOfReport' style="display: none">
				{!! $form->className->label('Types of Reports')->renderRow() !!}
			</div>

			<div class="col-md-4">
			@if (sfConfig::get('app_multi_repository'))
			<tr>
				<td colspan="2">
					{!! $form->repositories->label(__('Repository'))->renderRow() !!}
				</td>
			@endif
			</tr>

			<tr>
				<td colspan="2">
			{!! $form->dateOf->renderRow() !!}
				</td>
			</tr>

			<div class="col-md-4 start-date">
			<tr>
				<td>
				  {!! render_field($form->dateStart->label(__('Date Start')), null, ['type' => 'date']) !!}
				<td>
				  {!! render_field($form->dateEnd->label(__('Date End')), null, ['type' => 'date']) !!}
				</td>
			</tr>
			<tr>
			</tr>
				<td colspan="2">
				<button type="submit" class="btn">{{ __('Search') }}</button>
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
		<th style="width: 110px">{{ __('Identifier') }}</th>
		<th style="width: 250px">{{ __('Title') }}</th>
		<th>{{ __('Refusal') }}</th>
		<th>{{ __('Sensitive') }}</th>
		<th>{{ __('Publish') }}</th>
		<th>{{ __('Classification') }}</th>
		<th>{{ __('Restriction') }}</th>


        @if ('CREATED_AT' != $form->getValue('dateOf'))
          <th style="width: 110px">{{ __('Updated') }}</th>
        @else
          <th style="width: 110px">{{ __('Created') }}</th>
        @endif
      </tr>
    </thead><tbody>
	@php $row = 0; @endphp
	@foreach ($pager->getResults() as $result)
        <tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
			@php
                $infoObjectExist = QubitInformationObject::getById($result->object_id);
                if (isset($infoObjectExist)) {
                    foreach (QubitRelation::getRelationsBySubjectId($result->id) as $item2) {
                        $this->informationObjects = QubitInformationObject::getById($item2->objectId);
			@endphp
						@if (isset($this->informationObjects->identifier)) <td><a href="{{ url_for(['module' => 'informationobject', 'slug' => $this->informationObjects->slug]) }}">{{ $this->informationObjects->identifier }}</a></td> @else <td>-</td> @endif
						@if (isset($this->informationObjects->title)) <td>{{ $this->informationObjects->title }}</td> @else <td>-</td> @endif
					@php
                }
            @endphp

			@if (isset($result->refusalId))
				@if ('Please Select' == QubitTerm::getById($result->refusalId)) <td>-</td> @else
				<td>{{ __('%1%', ['%1%' => QubitTerm::getById($result->refusalId)]) }}</td> @endif
			@else
				<td>-</td>
			@endif
			@if (isset($result->sensitivityId))
				@if ('Please Select' == QubitTerm::getById($result->sensitivityId)) <td>-</td> @else
				<td>{{ __('%1%', ['%1%' => QubitTerm::getById($result->sensitivityId)]) }}</td> @endif
			@else
				<td>-</td>
			@endif
			@if (isset($result->publishId))
				@if ('Please Select' == QubitTerm::getById($result->publishId)) <td>-</td> @else
				<td>{{ __('%1%', ['%1%' => QubitTerm::getById($result->publishId)]) }}</td> @endif
			@else
				<td>-</td>
			@endif
			@if (isset($result->classificationId))
				@if ('Please Select' == QubitTerm::getById($result->publishId)) <td>-</td> @else
				<td>{{ __('%1%', ['%1%' => QubitTerm::getById($result->classificationId)]) }}</td> @endif
			@else
				<td>-</td>
			@endif
			@if (isset($result->restrictionId))
				@if ('Please Select' == QubitTerm::getById($result->restrictionId)) <td>-</td> @else
				<td>{{ __('%1%', ['%1%' => QubitTerm::getById($result->restrictionId)]) }}</td> @endif
			@else
				<td>-</td>
			@endif
			<td>
				@if ('CREATED_AT' != $form->getValue('dateOf'))
				{{ $result->updatedAt }}
				@else
				{{ $result->createdAt }}
				@endif
			</td>

        </tr>
		@php } @endphp


      @endforeach

</section>



    </tbody>
  </table>

@endsection

@section('after-content')
{!! get_partial('default/pager', ['pager' => $pager]) !!}
@endsection
