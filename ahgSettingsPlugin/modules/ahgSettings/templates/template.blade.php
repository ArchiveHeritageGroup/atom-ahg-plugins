@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Default template') }}</h1>

    {!! $defaultTemplateForm->renderGlobalErrors() !!}

    {!! $defaultTemplateForm->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'template'])) !!}

      {!! $defaultTemplateForm->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="default-template-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#default-template-collapse" aria-expanded="false" aria-controls="default-template-collapse">
              {{ __('Default template settings') }}
            </button>
          </h2>
          <div id="default-template-collapse" class="accordion-collapse collapse" aria-labelledby="default-template-heading">
            <div class="accordion-body">
              {!! render_field($defaultTemplateForm->informationobject) !!}

              {!! render_field($defaultTemplateForm->actor) !!}

              {!! render_field($defaultTemplateForm->repository) !!}
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
