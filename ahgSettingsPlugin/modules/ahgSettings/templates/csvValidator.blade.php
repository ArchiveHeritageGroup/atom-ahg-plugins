@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('CSV Validator') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'csvValidator'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="validator-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#validator-collapse" aria-expanded="false" aria-controls="validator-collapse">
              {{ __('CSV Validator settings') }}
            </button>
          </h2>
          <div id="validator-collapse" class="accordion-collapse collapse" aria-labelledby="validator-heading">
            <div class="accordion-body">
              {!! render_field($form->csv_validator_default_import_behaviour->label(__('CSV Validator default behaviour when CSV Import is run'))) !!}
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
