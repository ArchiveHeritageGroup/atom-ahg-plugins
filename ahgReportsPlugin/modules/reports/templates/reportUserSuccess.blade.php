@extends('layouts.page')

@section('title')
  <h1 class="multiline">
    <img src="{{ image_path('/images/icons-large/icon-new.png') }}" width="42" height="42" alt="" />
    {{ __('Browse User Activity') }}
    @if (isset($pager) && $pager->getNbResults())
      {{ __('Showing %1% results', ['%1%' => $pager->getNbResults()]) }}
    @else
      {{ __('No results found') }}
    @endif
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
			<form>
			{!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAuditTrail']), ['method' => 'get']) !!}
			{!! $form->renderHiddenFields() !!}
			{!! $form->actionUser->label('User')->renderRow() !!}
			{!! $form->userAction->label('User Action')->renderRow() !!}
			{!! $form->userActivity->label('User Activity')->renderRow() !!}
			{!! $form->chkSummary->label('Summary')->renderRow() !!}

			<td>
			  {!! render_field($form->dateStart->label(__('Date Start')), null, ['type' => 'date']) !!}
			<td>
			  {!! render_field($form->dateEnd->label(__('Date End')), null, ['type' => 'date']) !!}
			</td>
			<button type="submit" class="btn">{{ __('Search') }}</button>
      </form>
	</div>

</section>

@endsection

@section('content')
  <table class="table table-bordered" border="1" cellpadding="0" cellspacing="0" bordercolor="#999999">
    <thead>
		@php $name = $_GET['chkSummary']; @endphp
			@if ('on' == $name)
			  <tr>
				<th>{{ __('User') }}</th>
				<th>{{ __('Action') }}</th>
				<th>{{ __('Table') }}</th>
				<th>{{ __('Repository') }}</th>
				<th>{{ __('Count') }}</th>
			  </tr>

			@else
			  <tr>
				<th>{{ __('User') }}</th>
				<th>{{ __('Action') }}</th>
				<th>{{ __('Action Date') }}</th>
				<th>{{ __('Identifier') }}</th>
				<th>{{ __('Title') }}</th>
				<th>{{ __('Repository') }}</th>
				<th>{{ __('Activity Area') }}</th>
			  </tr>
			@endif
    </thead>
	<tbody>

	@if (isset($pager) && (float) $pager->getNbResults() > 0)
		@if (null != $pager->getResults())
			@if ('on' != $name)
				@foreach ($pager->getResults() as $item)
					@php
					$title = '';
					$identifier = '';
					$getRepo = '';
					$strFieldsAndValues = explode('||', $item['DB_QUERY']);
					$strFields = explode('~', $strFieldsAndValues[0]);
					$strValues = explode('~', $strFieldsAndValues[1]);
					$arr_length = count($strFields);
					@endphp
					@for ($i = 0; $i < $arr_length; ++$i)
						@if ('identifier' == $strFields[$i])
							@if ('' != $strValues[$i])
								@php $identifier = $strValues[$i]; @endphp
							@endif
						@elseif ('corporateBodyIdentifiers' == $strFields[$i])
							@if ('' != $strValues[$i])
								@php $identifier = $strValues[$i]; @endphp
							@endif
						@endif

						@if ('title' == $strFields[$i])
							@php $title = $strValues[$i]; @endphp
						@endif
						@if ('altTitle' == $strFields[$i])
							@if ('' == $title)
								@php $title = $strValues[$i]; @endphp
							@endif
						@endif
						@if ('authorizedFormOfName' == $strFields[$i])
							@if ('' == $title)
								@php $title = $strValues[$i]; @endphp
							@endif
						@endif
						@if ('name' == $strFields[$i])
							@if ('' == $title)
								@php $title = $strValues[$i]; @endphp
							@endif
						@endif
						@if ('sourceCulture' == $strFields[$i])
							@if ('' == $title)
								@php // $title = $strValues[$i] @endphp
							@endif
						@endif
						@if ('repositoryId' == $strFields[$i])
							@if ('' != $strValues[$i])
								@php
                                    $getRepo = QubitRepository::getById($strValues[$i]);
                                @endphp
							@endif
						@endif

						@if ('QubitActor' == $item['DB_TABLE'])
							@if ('sourceCulture' == $strFields[$i])
								@php $title = $strValues[$i]; @endphp
							@endif
						@endif
					@endfor

					<tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
					<td>{{ $item['USER'] }}</td>
					<td>{{ $item['ACTION'] }}</td>
					<td>{{ $item['ACTION_DATE_TIME'] }}</td>
					@if (isset($item['CLASS_NAME']))

					{{-- QubitAccessObject --}}
						@if ('QubitAccessObject' == $item['CLASS_NAME'])
							@php
							$accessObjectsAudit = QubitAccessObject::getById($item['RECORD_ID']);
							$accessObjectsAccess = QubitAccessObjectI18n::getById($accessObjectsAudit->id);
							$informationObjectsAudit = QubitInformationObject::getById($accessObjectsAccess->object_id);
							$informationObjectsRepo = QubitRepository::getById($informationObjectsAudit->repositoryId);
							@endphp
							<td>{{ $informationObjectsAudit->identifier }}</td>
							@if (!isset($informationObjectsAudit))
								<td>{{ 'Deleted: Access'.$accessObjectsAccess->name }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'informationobject', 'slug' => $informationObjectsAudit->slug]) }}">{{ $informationObjectsAudit }}</a></td>
							@endif
							@if (isset($informationObjectsRepo))
								<td>{{ $informationObjectsRepo }}</td>
							@else
								<td>{{ 'Repository not yet set' }}</td>
							@endif

					{{-- QubitInformationObject --}}
						@elseif ('QubitInformationObject' == $item['CLASS_NAME'])
							@php $informationObjectsAudit = QubitInformationObject::getById($item['RECORD_ID']); @endphp
							<td>{{ $informationObjectsAudit->identifier }}</td>
							@if (null == $informationObjectsAudit)
								<td><a href="{{ url_for(['module' => 'informationobject', 'action' => 'informationobject', 'source' => $item['RECORD_ID']]) }}">Deleted: Archival Description</a></td>
							@else
								<td><a href="{{ url_for(['module' => 'informationobject', 'slug' => $informationObjectsAudit->slug]) }}">{{ $informationObjectsAudit }}</a></td>
							@endif
							<td>{{ QubitRepository::getById($informationObjectsAudit->repositoryId) }}</td>

					{{-- QubitRepository --}}
						@elseif ('QubitRepository' == $item['CLASS_NAME'])
							@php
							$actorObjectsAudit = QubitActor::getById($item['RECORD_ID']);
							$repoObjects = QubitRepository::getById($item['RECORD_ID']);
							@endphp
							@if (null == $repoObjects)
								<td>{{ 'Not set' }}</td>
							@else
								<td>{{ $repoObjects->identifier }}</td>
							@endif
							@if (null == $actorObjectsAudit)
								<td>{{ 'Deleted: Repository' }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'repository', 'slug' => $actorObjectsAudit->slug]) }}">{{ $actorObjectsAudit }}</a></td>
							@endif
							<td>{{ 'N/A' }}</td>

					{{-- QubitActor --}}
						@elseif ('QubitActor' == $item['CLASS_NAME'])
							@php $actorObjectsAudit = QubitActor::getById($item['RECORD_ID']); @endphp
							@if (null == $actorObjectsAudit)
								<td>{{ 'Deleted: Actor' }}</td>
							@else
								<td>{{ $actorObjectsAudit->corporateBodyIdentifiers }}</td>
							@endif
							@if (null == $actorObjectsAudit)
								<td>{{ 'Deleted: Actor' }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'actor', 'slug' => $actorObjectsAudit->slug]) }}">{{ $actorObjectsAudit }}</a></td>
							@endif
							<td>{{ 'N/A' }}</td>

					{{-- QubitBookoutObject to fix --}}
						@elseif ('QubitBookoutObject' == $item['CLASS_NAME'])
							<td>{{ 'N/A' }}</td>
							@php $bookOutObjectsAudit = QubitBookoutObject::getById($item['RECORD_ID']); @endphp
							@if (null == $bookOutObjectsAudit)
								<td>{{ 'Deleted: Bookout Object' }}</td>
							@else
								<td>{{ $bookOutObjectsAudit }}</td>
							@endif

							@php
							$accessObjectsAudit = QubitBookoutObjectI18n::getById($bookOutObjectsAudit->id);
							$informationObjectsAudit = QubitInformationObject::getById($accessObjectsAudit->object_id);
							$informationObjectsRepo = QubitRepository::getById($informationObjectsAudit->repositoryId);
							@endphp
							@if (isset($informationObjectsRepo))
							<td>{{ $informationObjectsRepo }}</td>
							@else
								<td>{{ 'Repository not yet set' }}</td>
							@endif

					{{-- QubitBookinObject to fix --}}
						@elseif ('QubitBookinObject' == $item['CLASS_NAME'])
							<td>{{ 'N/A' }}</td>
							@php $bookinObjectsAudit = QubitBookinObject::getById($item['RECORD_ID']); @endphp
							@if (null == $bookinObjectsAudit)
								<td><a href="{{ url_for(['module' => 'reports', 'action' => 'auditBookIn', 'source' => $item['ID']]) }}">Book In missing</a></td>
							@else
								<td><a href="{{ url_for(['module' => 'reports', 'action' => 'auditBookIn', 'source' => $item['ID']]) }}">{{ $bookinObjectsAudit }}</a></td>
							@endif
							<td>{{ 'N/A' }}</td>

					{{-- QubitDigitalObject to fix --}}
						@elseif ('QubitDigitalObject' == $item['CLASS_NAME'])
							@php $digitalObjectsAudit = QubitDigitalObject::getById($item['RECORD_ID']); @endphp
							@if (null == $digitalObjectsAudit)
								<td><a href="{{ url_for(['module' => 'informationobject', 'action' => 'QubitInformationObject', 'source' => $item['RECORD_ID']]) }}">Item not found. Call administrator.</a></td>
							@else
								<td><a href="{{ url_for(['module' => 'digitalobject', 'slug' => $digitalObjectsAudit->slug]) }}">{{ $digitalObjectsAudit }}</a></td>
							@endif
							<td>{{ 'N/A' }}</td>

							<td>{{ $digitalObjectsAudit[0]->parentId }}</td>
							@php $digitalObjectsAudit3 = QubitInformationObject::getById($digitalObjectsAudit[0]->parentId); @endphp
							<td>{{ $digitalObjectsAudit3->repositoryId }}</td>
							@php $informationObjectsRepo = QubitRepository::getById($digitalObjectsAudit3->repositoryId); @endphp
							@if (isset($informationObjectsRepo))
							<td>{{ $informationObjectsRepo }}</td>
							@else
								<td>{{ $digitalObjectsAudit }}</td>
							@endif
							<td>{{ 'N/A' }}</td>

					{{-- QubitDonor --}}
						@elseif ('QubitDonor' == $item['CLASS_NAME'])
							<td>{{ 'N/A' }}</td>
							@php $donorObjectsAudit = QubitDonor::getById($item['RECORD_ID']); @endphp
							@if (null == $donorObjectsAudit)
								<td>{{ 'Deleted: Donor' }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'donor', 'slug' => $donorObjectsAudit->slug]) }}">{{ $donorObjectsAudit }}</a></td>
							@endif
							<td>{{ 'N/A' }}</td>

					{{-- QubitPhysicalObject --}}
						@elseif ('QubitPhysicalObject' == $item['CLASS_NAME'])
							@php
							$physicalObjectObjectsAudit = QubitPhysicalObject::getById($item['RECORD_ID']);
							$physicalObjectObject = QubitPhysicalObjecti18n::getById($physicalObjectObjectsAudit->id);
							@endphp
							@if (null == $physicalObjectObject)
								<td>{{ 'N/A' }}</td>
							@else
								<td>{{ $physicalObjectObject->uniqueIdentifier }}</td>
							@endif
							@if (null == $physicalObjectObjectsAudit)
								<td>{{ 'Deleted: Physical Storage' }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'physicalstorage', 'slug' => $physicalObjectObjectsAudit->slug]) }}">{{ $physicalObjectObjectsAudit }}</a></td>
							@endif
							<td>{!! render_value(QubitRepository::getById($physicalObjectObjectsAudit->getRepositoryId(['cultureFallback' => true]))) !!} </td>

					{{-- QubitPresevationObject --}}
						@elseif ('QubitPresevationObject' == $item['CLASS_NAME'])
							@php
							$presevationObjectsAudit = QubitPresevationObject::getById($item['RECORD_ID']);
							$presevationObjectsAccess = QubitPresevationObject::getById($presevationObjectsAudit->id);
							$informationObjectsAudit = QubitInformationObject::getById($presevationObjectsAccess->object_id);
							$informationObjectsRepo = QubitRepository::getById($informationObjectsAudit->repositoryId);
							@endphp
							<td>{{ $informationObjectsAudit->identifier }}</td>
							@if (!isset($informationObjectsAudit))
								<td>{{ 'Deleted: Access'.$presevationObjectsAccess->name }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'informationobject', 'slug' => $informationObjectsAudit->slug]) }}">{{ $informationObjectsAudit }}</a></td>
							@endif
							@if (isset($informationObjectsRepo))
								<td>{{ $informationObjectsRepo }}</td>
							@else
								<td>{{ 'Repository not yet set' }}</td>
							@endif

					{{-- QubitRegistry --}}
						@elseif ('QubitRegistry' == $item['CLASS_NAME'])
							@php $registryObjectsAudit = QubitActor::getById($item['RECORD_ID']); @endphp
							@if (null == !$registryObjectsAudit)
								<td>{{ $registryObjectsAudit->corporateBodyIdentifiers }}</td>
							@else
								<td>@php 'N?A?'; @endphp</td>
							@endif
							@if (null == $registryObjectsAudit)
								<td>{{ 'Deleted: Registry' }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'registry', 'source' => $item['RECORD_ID']]) }}">{{ $registryObjectsAudit }}</a></td>
							@endif
							<td>{{ 'N/A' }}</td>

					{{-- QubitResearcher --}}
						@elseif ('QubitResearcher' == $item['CLASS_NAME'])
							@php
							$researcherObjectsActor = QubitActor::getById($item['RECORD_ID']);
							$researcherObjectsAudit = QubitResearcher::getById($item['RECORD_ID']);
							@endphp
							@if (null == !$researcherObjectsActor)
								<td>{{ $researcherObjectsActor->corporateBodyIdentifiers }}</td>
							@else
								<td>@php 'N?A?'; @endphp</td>
							@endif
							@if (null == $researcherObjectsAudit)
								<td>{{ 'N/A' }}</td>
								<td>{{ 'Deleted: Researcher' }}</td>
							@else
								<td><a href="{{ url_for(['module' => 'researcher', 'source' => $item['RECORD_ID']]) }}">{{ $researcherObjectsAudit }}</a></td>
							@endif
							@if ('' != $item['REPOSITORY_ID'])
								@php
                                    $getRepo = QubitRepository::getById($researcherObjectsAudit->repositoryId);
                                @endphp
								<td>{{ $getRepo }}</td>
							@else
								<td>{{ 'Unknown' }}</td>
							@endif

					{{-- QubitServiceProvider --}}
						@elseif ('QubitServiceProvider' == $item['CLASS_NAME'])
							@php
							$serviceProviderObjectsActor = QubitActor::getById($item['RECORD_ID']);
							$serviceProviderObjects = QubitServiceProvider::getById($item['RECORD_ID']);
							@endphp
							@if (null == !$serviceProviderObjectsActor)
								<td>{{ $serviceProviderObjectsActor->corporateBodyIdentifiers }}</td>
							@else
								<td>@php 'N?A?'; @endphp</td>
							@endif
							@if (null == $serviceProviderObjectsActor)
								<td><a href="{{ url_for(['module' => 'reports', 'action' => 'auditServiceProvider', 'source' => $item['RECORD_ID']]) }}">Actor</a></td>
							@else
								<td><a href="{{ url_for(['module' => 'serviceProvider', 'source' => $item['RECORD_ID']]) }}">{{ $serviceProviderObjectsActor }}</a></td>
							@endif
							@if ('' != $item['REPOSITORY_ID'])
								@php $getRepo = QubitRepository::getById($serviceProviderObjects->repositoryId); @endphp
								<td>{{ $getRepo }}</td>
							@else
								<td>{{ 'Unknown' }}</td>
							@endif

					{{-- QubitUser --}}
						@elseif ('QubitUser' == $item['CLASS_NAME'])
							@php $actorObjectsAudit = QubitActor::getById($item['RECORD_ID']); @endphp
							@if (null == $actorObjectsAudit)
								<td>{{ 'Deleted: User' }}</td>
							@else
								<!--td><a href="{{ url_for(['module' => 'actor', 'slug' => $actorObjectsAudit->slug]) }}">{{ $actorObjectsAudit }}</a></td-->
							@endif
							<td>{{ $actorObjectsAudit }}</td>
							<td>{{ $actorObjectsAudit->email }}</td>
							<td>{{ 'N/A' }}</td>

					{{-- QubitTerm --}}
						@elseif ('QubitTerm' == $item['CLASS_NAME'])
							<td>{{ 'N/A' }}</td>

							@if ('acl_group_i18n' == $item['DB_TABLE'])
								@php $taxonomyObjectsAudit = QubitAclGroup::getById($item['RECORD_ID']); @endphp
							@else
								@php $taxonomyObjectsAudit = term_name($item['RECORD_ID']); @endphp
							@endif
							@if (null == $taxonomyObjectsAudit)
								<td><a href="{{ url_for(['module' => '', 'action' => 'taxonomy', 'source' => $item['RECORD_ID']]) }}">Taxonomy/Term missing</a></td>
							@else
								@if ('acl_group_i18n' == $item['DB_TABLE'])
									<td><a href="{{ url_for(['module' => '', 'action' => 'auditPermissions', 'source' => $item['RECORD_ID']]) }}">{{ $taxonomyObjectsAudit }}</a></td>
								@else
									<td><a href="{{ url_for(['module' => '', 'action' => 'taxonomy', 'source' => $item['RECORD_ID']]) }}">{{ $taxonomyObjectsAudit }}</a></td>
								@endif

							@endif
							<td>{{ 'N/A' }}</td>

						@else
							<td>{{ $item['ID'] }}</td>
						@endif
					@else
						@if ('' != $identifier)
							<td><a href="{{ url_for(['module' => 'reports', 'action' => 'reportDeleted', 'source' => $item['RECORD_ID']]) }}">{{ $identifier }}</a></td>
						@else
							<td>{{ $identifier }}</td>
						@endif
						<td>{{ $title }}</td>

					@endif

					@if (isset($item['CLASS_NAME']))
						@if ('QubitInformationObject' == $item['CLASS_NAME'])
							@if ('presevation_object' == $item['DB_TABLE'])
								<td>{{ 'Preservation' }}</td>
							@else
								<td>{{ 'Archival Description' }}</td>
							@endif
						@elseif ('qubitActor' == $item['CLASS_NAME'])
							@if ('QubitRegistry' == $item['CLASS_NAME'])
								<td>{{ 'Registry' }}</td>
							@elseif ('QubitRepository' == $item['CLASS_NAME'])
								<td>{{ 'Repository' }}</td>
							@else
								<td>{{ 'Actor/Authority Record' }}</td>
							@endif
						@elseif ('QubitRepository' == $item['CLASS_NAME'])
							<td>{{ 'Archival Institution' }}</td>

						@elseif ('QubitResearcher' == $item['CLASS_NAME'])
							<td>{{ 'Researcher' }}</td>

						@elseif ('QubitServiceProvider' == $item['CLASS_NAME'])
							<td>{{ 'Service Provider' }}</td>

						@elseif ('QubitPhysicalObject' == $item['CLASS_NAME'])
							<td>{{ 'Physical Storage' }}</td>

						@elseif ('QubitRegistry' == $item['CLASS_NAME'])
							<td>{{ 'Registry' }}</td>

						@elseif ('QubitRearcher' == $item['CLASS_NAME'])
							<td>{{ 'Rearcher' }}</td>

						@elseif ('QubitActor' == $item['CLASS_NAME'])
							<td>{{ 'Actor/Authority Record' }}</td>

						@elseif ('QubitUser' == $item['CLASS_NAME'])
							<td>{{ 'User' }}</td>

						@elseif ('QubitDonor' == $item['CLASS_NAME'])
							<td>{{ 'Donor' }}</td>

						@elseif ('QubitTerm' == $item['CLASS_NAME'])
							@if ('acl_group_i18n' == $item['DB_TABLE'])
								<td>{{ 'Permissions/Groups' }}</td>
							@else
								<td>{{ 'Taxonomy/Term' }}</td>
							@endif
						@elseif ('QubitBookinObject' == $item['CLASS_NAME'])
							<td>{{ 'Book In' }}</td>

						@elseif ('QubitBookoutObject' == $item['CLASS_NAME'])
							<td>{{ 'Book Out' }}</td>

						@elseif ('QubitAccessObject' == $item['CLASS_NAME'])
							<td>{{ 'Access' }}</td>

						@elseif ('QubitPresevationObject' == $item['CLASS_NAME'])
							<td>{{ 'Preservation' }}</td>

						@elseif ('QubitDigitalObject' == $item['CLASS_NAME'])
							<td>{{ 'Digital Object' }}</td>

						@elseif ('QubitObjectTermRelation' == $item['CLASS_NAME'])
							<td>{{ 'Object Term Relation' }}</td>

						@else
							<td>{{ $item['CLASS_NAME'] }}</td>

						@endif
					@else
						@if ('QubitInformationObject' == $item['DB_TABLE'])
							<td>{{ 'Archival Description' }}</td>

						@elseif ('QubitActor' == $item['DB_TABLE'])
							<td>{{ 'Actor/Authority Record' }}</td>

						@elseif ('QubitRepository' == $item['DB_TABLE'])
							<td>{{ 'Archival Institution' }}</td>

						@elseif ('QubitResearcher' == $item['DB_TABLE'])
							<td>{{ 'Researcher' }}</td>

						@elseif ('QubitServiceProvider' == $item['DB_TABLE'])
							<td>{{ 'Service Provider' }}</td>

						@elseif ('QubitPhysicalObject' == $item['DB_TABLE'])
							<td>{{ 'Physical Storage' }}</td>

						@elseif ('QubitRegistry' == $item['DB_TABLE'])
							<td>{{ 'Registry' }}</td>

						@elseif ('QubitRearcher' == $item['DB_TABLE'])
							<td>{{ 'Rearcher' }}</td>

						@elseif ('QubitActor' == $item['DB_TABLE'])
							<td>{{ 'Actor/Authority Record' }}</td>

						@elseif ('QubitUser' == $item['DB_TABLE'])
							<td>{{ 'User' }}</td>

						@elseif ('QubitDonor' == $item['DB_TABLE'])
							<td>{{ 'Donor' }}</td>

						@elseif ('QubitTerm' == $item['DB_TABLE'])
							<td>{{ 'Taxonomy/Term' }}</td>

						@elseif ('QubitBookinObject' == $item['DB_TABLE'])
							<td>{{ 'Book In' }}</td>

						@elseif ('QubitBookoutObject' == $item['DB_TABLE'])
							<td>{{ 'Book Out' }}</td>

						@elseif ('QubitAccessObject' == $item['DB_TABLE'])
							<td>{{ 'Access' }}</td>

						@elseif ('QubitPresevationObject' == $item['DB_TABLE'])
							<td>{{ 'Preservation' }}</td>

						@elseif ('QubitDigitalObject' == $item['DB_TABLE'])
							<td>{{ 'Digital Object' }}</td>

						@elseif ('QubitObjectTermRelation' == $item['DB_TABLE'])
							<td>{{ 'Object Term Relation' }}</td>

						@elseif ('QubitAccession' == $item['DB_TABLE'])
							<td>{{ 'Accession' }}</td>

						@elseif ('QubitTaxonomy' == $item['DB_TABLE'])
							<td>{{ 'Taxonomy' }}</td>

						@elseif ('QubitFunction' == $item['DB_TABLE'])
							<td>{{ 'Function' }}</td>

						@elseif ('QubitDeaccession' == $item['DB_TABLE'])
							<td>{{ 'Deaccession' }}</td>

						@else
							<td>{{ $item['DB_TABLE'] }}</td>
							<td>{{ '-' }}</td>
							<td>{{ '-' }}</td>
							<td>{{ '-' }}</td>
							<td>{{ '-' }}</td>
							<td>{{ '-' }}</td>
						@endif
						<td><a href="{{ url_for(['module' => 'reports', 'action' => 'auditDeleted', 'source' => $item['ID']]) }}">{{ $item['DB_TABLE'] }}</a></td>
						<td>{{ $getRepo }}</td>
					@endif
				</tr>
			@endforeach
			@else
				@foreach ($pager->getResults() as $item)
				<tr>
					<td>{{ $item['USER'] }}</td>
					<td>{{ $item['ACTION'] }}</td>


						@if ('information_object' == $item['DB_TABLE'])
							<td>{{ 'Archival Description' }}</td>

						@elseif ('actor' == $item['DB_TABLE'])
							<td>{{ 'Actor/Authority Record' }}</td>

						@elseif ('repository' == $item['DB_TABLE'])
							<td>{{ 'Archival Institution' }}</td>

						@elseif ('researcher' == $item['DB_TABLE'])
							<td>{{ 'Researcher' }}</td>

						@elseif ('service_provider' == $item['DB_TABLE'])
							<td>{{ 'Service Provider' }}</td>

						@elseif ('physical_object' == $item['DB_TABLE'])
							<td>{{ 'Physical Storage' }}</td>

						@elseif ('registry' == $item['DB_TABLE'])
							<td>{{ 'Registry' }}</td>

						@elseif ('user' == $item['DB_TABLE'])
							<td>{{ 'User' }}</td>

						@elseif ('donor' == $item['DB_TABLE'])
							<td>{{ 'Donor' }}</td>

						@elseif ('term' == $item['DB_TABLE'])
							<td>{{ 'Taxonomy/Term' }}</td>

						@elseif ('bookin_object' == $item['DB_TABLE'])
							<td>{{ 'Book In' }}</td>

						@elseif ('bookout_object' == $item['DB_TABLE'])
							<td>{{ 'Book Out' }}</td>

						@elseif ('access_object' == $item['DB_TABLE'])
							<td>{{ 'Access' }}</td>

						@elseif ('presevation_object' == $item['DB_TABLE'])
							<td>{{ 'Preservation' }}</td>

						@elseif ('digital_object' == $item['DB_TABLE'])
							<td>{{ 'Digital Object' }}</td>

						@elseif ('accession' == $item['DB_TABLE'])
							<td>{{ 'Accession' }}</td>

						@elseif ('taxonomy' == $item['DB_TABLE'])
							<td>{{ 'Taxonomy' }}</td>

						@elseif ('function' == $item['DB_TABLE'])
							<td>{{ 'Function' }}</td>

						@elseif ('deaccession' == $item['DB_TABLE'])
							<td>{{ 'Deaccession' }}</td>

						@else
							<td>{{ $item['DB_TABLE'] }}</td>
						@endif



					<td>{{ '-' }}</td>
					<td>{{ $item['count'] }}</td>
				</tr>
			@endforeach
			@endif
		@endif

	@endif
    </tbody>
  </table>
@endsection

@if (isset($pager))
	@section('after-content')
		{!! get_partial('default/pager', ['pager' => $pager]) !!}
	@endsection
@endif
