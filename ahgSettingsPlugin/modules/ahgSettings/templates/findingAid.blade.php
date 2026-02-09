@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Finding Aid settings') }}</h1>

    {!! $findingAidForm->renderGlobalErrors() !!}

    {!! $findingAidForm->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'findingAid']), ['data-cy' => 'settings-finding-aid-form']) !!}

      {!! $findingAidForm->renderHiddenFields() !!}

      <div class="accordion mb-3" id="finding-aid-settings">
        <div class="accordion-item">
          <h2 class="accordion-header" id="finding-aid-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#finding-aid-collapse" aria-expanded="false" aria-controls="finding-aid-collapse">
              {{ __('Finding Aid settings') }}
            </button>
          </h2>
          <div id="finding-aid-collapse" class="accordion-collapse collapse" aria-labelledby="finding-aid-heading">
            <div class="accordion-body">
              {!! render_field($findingAidForm->finding_aids_enabled) !!}

              {!! render_field($findingAidForm->finding_aid_format) !!}

              {!! render_field($findingAidForm->finding_aid_model) !!}

              {!! render_field($findingAidForm->public_finding_aid) !!}
            </div>
          </div>
        </div>
      </div>

      <section class="actions mb-3">
        <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Save') }}">
      </section>

    </form>
  </div>
</div>
@endsection
