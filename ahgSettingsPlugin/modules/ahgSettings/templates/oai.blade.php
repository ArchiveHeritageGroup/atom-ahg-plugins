@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('OAI repository settings') }}</h1>

    {!! $oaiRepositoryForm->renderGlobalErrors() !!}

    {!! $oaiRepositoryForm->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'oai'])) !!}

      {!! $oaiRepositoryForm->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="oai-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#oai-collapse" aria-expanded="false" aria-controls="oai-collapse">
              {{ __('OAI repository settings') }}
            </button>
          </h2>
          <div id="oai-collapse" class="accordion-collapse collapse" aria-labelledby="oai-heading">
            <div class="accordion-body">
              <p>{{ __('The OAI-PMH API can be secured, optionally, by requiring API requests authenticate using API keys (granted to specific users).') }}</p>

              {!! render_field($oaiRepositoryForm->oai_authentication_enabled) !!}

              {!! render_field($oaiRepositoryForm->oai_repository_code) !!}

              {!! render_field($oaiRepositoryForm->oai_admin_emails) !!}

              {!! render_field($oaiRepositoryForm->oai_repository_identifier) !!}

              {!! render_field($oaiRepositoryForm->sample_oai_identifier) !!}

              {!! render_field($oaiRepositoryForm->resumption_token_limit, null, ['type' => 'number']) !!}

              {!! render_field($oaiRepositoryForm->oai_additional_sets_enabled) !!}
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
