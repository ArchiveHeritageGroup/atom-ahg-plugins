@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Global settings') }}</h1>

    {!! $globalForm->renderGlobalErrors() !!}

    {!! $globalForm->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'global'])) !!}

      {!! $globalForm->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="version-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#version-collapse" aria-expanded="false" aria-controls="version-collapse">
              {{ __('Version') }}
            </button>
          </h2>
          <div id="version-collapse" class="accordion-collapse collapse" aria-labelledby="version-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->version) !!}
              {!! render_field($globalForm->check_for_updates) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="search-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#search-collapse" aria-expanded="false" aria-controls="search-collapse">
              {{ __('Search and browse') }}
            </button>
          </h2>
          <div id="search-collapse" class="accordion-collapse collapse" aria-labelledby="search-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->hits_per_page) !!}
              {!! render_field($globalForm->sort_browser_user) !!}
              {!! render_field($globalForm->sort_browser_anonymous) !!}
              {!! render_field($globalForm->default_archival_description_browse_view) !!}
              {!! render_field($globalForm->default_repository_browse_view) !!}
              {!! render_field($globalForm->escape_queries) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="presentation-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#presentation-collapse" aria-expanded="false" aria-controls="presentation-collapse">
              {{ __('Presentation') }}
            </button>
          </h2>
          <div id="presentation-collapse" class="accordion-collapse collapse" aria-labelledby="presentation-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->show_tooltips) !!}
              {!! render_field($globalForm->draft_notification_enabled) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="multirepo-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#multirepo-collapse" aria-expanded="false" aria-controls="multirepo-collapse">
              {{ __('Multi-repository') }}
            </button>
          </h2>
          <div id="multirepo-collapse" class="accordion-collapse collapse" aria-labelledby="multirepo-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->multi_repository) !!}
              {!! render_field($globalForm->enable_institutional_scoping) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="permalinks-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#permalinks-collapse" aria-expanded="false" aria-controls="permalinks-collapse">
              {{ __('Permalinks') }}
            </button>
          </h2>
          <div id="permalinks-collapse" class="accordion-collapse collapse" aria-labelledby="permalinks-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->slug_basis_informationobject) !!}
              {!! render_field($globalForm->permissive_slug_creation) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="system-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#system-collapse" aria-expanded="false" aria-controls="system-collapse">
              {{ __('System') }}
            </button>
          </h2>
          <div id="system-collapse" class="accordion-collapse collapse" aria-labelledby="system-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->audit_log_enabled) !!}
              {!! render_field($globalForm->generate_reports_as_pub_user) !!}
              {!! render_field($globalForm->cache_xml_on_save) !!}
              {!! render_field($globalForm->defaultPubStatus) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="integrations-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#integrations-collapse" aria-expanded="false" aria-controls="integrations-collapse">
              {{ __('Integrations') }}
            </button>
          </h2>
          <div id="integrations-collapse" class="accordion-collapse collapse" aria-labelledby="integrations-heading">
            <div class="accordion-body">
              {!! render_field($globalForm->google_maps_api_key) !!}
              {!! render_field($globalForm->sword_deposit_dir) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="enhance-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enhance-collapse" aria-expanded="false" aria-controls="enhance-collapse">
              {{ __('Enhancements') }}
            </button>
          </h2>
          <div id="enhance-collapse" class="accordion-collapse collapse" aria-labelledby="enhance-heading">
            <div class="accordion-body">
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
