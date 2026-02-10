@extends('layouts.page')

@section('content')

<head>
</head>
<h1>{{ __('Publish Preservation') }}</h1>
<h1>{{ __('Publish Preservation %1%', ['%1%' => sfConfig::get('app_ui_label_presevationobject')]) }}</h1>

{!! get_partial('default/pager', ['pager' => $pager]) !!}

@if ($sf_user->hasFlash('error'))
  <div class="messages error">
    <h3>{{ __('Error encountered') }}</h3>
    <div>{{ $sf_user->getFlash('error') }}</div>
  </div>
@endif

@php
    $this->publishYes = QubitXmlImport::translateNameToTermId2('Publish', QubitTerm::PUBLISH_ID, 'Yes');
    $this->publishNo = QubitXmlImport::translateNameToTermId2('Publish', QubitTerm::PUBLISH_ID, 'No');
@endphp

	{!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'browsePublish']), ['method' => 'post']) !!}

    {!! $form->renderHiddenFields() !!}

<table class="table table-bordered sticky-enabled">
  <thead>
    <tr>
      <th class="sortable">
        <a href="{{ url_for(['sort' => ('nameUp' == $sf_request->sort) ? 'nameDown' : 'nameUp'] + $sf_request->getParameterHolder()->getAll()) }}" title="{{ __('Sort') }}" class="sortable">{{ __('Name') }}</a>

        @if ('nameUp' == $sf_request->sort)
          <img src="{{ image_path('up.gif') }}" alt="Sort ascending" />
        @elseif ('nameDown' == $sf_request->sort)
          <img src="{{ image_path('down.gif') }}" alt="Sort descending" />
        @endif
      </th><th>
        {{ __('Identifier') }}
      </th><th>
        {{ __('Publish') }}
      </th><th>
        {{ __('Restriction Condition') }}
      </th><th>
        {{ __('Refusal') }}
      </th><th>
        {{ __('Sensitivity') }}
      </th><th>
        {{ __('Classification') }}
      </th><th>
        {{ __('Restriction') }}
      </th>
    </tr>
  </thead><tbody>
    @foreach ($pager->getResults() as $item)
      <tr class="{{ 0 == @++$row % 2 ? 'even' : 'odd' }}">
        <td>
        	@php $d = "id_{$row}"; @endphp
        	<input type="hidden" name="{{ $d }}" id="{{ $d }}" value="{{ $item->id }}"/>
        	@php
                // find Information Object to link to
                // find relation
                foreach (QubitRelation::getObjectsBySubjectId($item->id, ['typeId' => QubitTaxonomy::ACCESS_TYPE_ID]) as $item2) {
                    // find Information Object
                    $this->informationObj = new QubitInformationObject();
                    $this->informationObj = QubitInformationObject::getById($item2->objectId);
                }
            @endphp
         <a href="{{ url_for(['module' => 'informationobject', 'slug' => $this->informationObj->slug]) }}">{{ $this->informationObj->title ?? $this->informationObj->slug ?? $this->informationObj->identifier }}</a>
        </td><td>
		     {{ $this->informationObj->identifier }}
        </td><td>
			@if ('Please Select' == $item->publish)
		      {{ '-' }}
		    @else
			    @php $n = "publish_{$row}"; @endphp

				<input type="radio" name="{{ $n }}" id="{{ $n }}" value="Yes" class="radio" onclick="updateYesNo('{{ $n }}~{{ $this->publishYes }}','{{ $d }}','{{ $pager->getResults()->count() }}')"

				@if (isset($item->publish) && 'Yes' == $item->publish)
				checked
				@endif
				/> Yes
				<input type="radio" name="{{ $n }}" value="No" id="{{ $n }}" class="radio" onclick="updateYesNo('{{ $n }}~{{ $this->publishNo }}','{{ $d }}','{{ $pager->getResults()->count() }}')"
				@if (isset($item->publish) && 'No' == $item->publish)
				checked
				@endif
				/> No
		    @endif
        </td><td>
			@if ('Please Select' == $item->restriction_condition)
		      {{ '-' }}
		    @else
		      {{ $item->restriction_condition }}
		    @endif
        </td><td>
			@if ('Please Select' == $item->refusal)
		      {{ '-' }}
		    @else
		      {{ $item->refusal }}
		    @endif
 		</td><td>
			@if ('Please Select' == $item->sensitivity)
		      {{ '-' }}
		    @else
		      {{ $item->sensitivity }}
		    @endif
        </td><td>
			@if ('Please Select' == $item->classification)
		      {{ '-' }}
		    @else
		      {{ $item->classification }}
		    @endif
        </td>
        <td>
			@if ('Please Select' == $item->restriction)
		      {{ '-' }}
		    @else
		      {{ $item->restriction }}
		    @endif
        </td>
	</tr>
    @endforeach

  </tbody>
</table>
 <iframe name="inlineframe" src="" frameborder="0" scrolling="auto" width="0" height="0" marginwidth="0" marginheight="0" ></iframe>
<script {!! csp_nonce_attr() !!}>
function updateYesNo(eElement,eId,eCount)
{
	var partsOfStr = eElement.split('~');
	var x=document.getElementById(partsOfStr[0]);
	var y=document.getElementById(eId);
	x.value=partsOfStr[1];

//alert("y.value="+y.value'&cValue='+x.value);

	//to fix jjp
//    window.frames['inlineframe'].location.replace('http://localhost/atom/order.php?id='+y.value+'&cValue='+x.value);
    window.frames['inlineframe'].location.replace('http://10.125.205.219/atom/publish.php?id='+y.value+'&cValue='+x.value);
}

</script>
<section class="actions">
	<ul class="clearfix links">
		<li><a href="{{ url_for(['module' => 'informationobject', 'action' => 'browse']) }}" class="c-btn">{{ __('Return') }}</a></li>
	  	<li><input class="form-submit c-btn c-btn-submit" type="submit" value="{{ __('Continue') }}"/></li>
	</ul>
</section>
</form>

@endsection
