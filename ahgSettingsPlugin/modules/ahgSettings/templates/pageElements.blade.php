@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Default page elements') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'pageElements'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="default-page-elements-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#default-page-elements-collapse" aria-expanded="false" aria-controls="default-page-elements-collapse">
              {{ __('Default page elements settings') }}
            </button>
          </h2>
          <div id="default-page-elements-collapse" class="accordion-collapse collapse" aria-labelledby="default-page-elements-heading">
            <div class="accordion-body">
              <p>{{ __('Enable or disable the display of certain page elements. Unless they have been overridden by a specific theme, these settings will be used site wide.') }}</p>

              {!! render_field($form->toggleLogo->label('Logo')) !!}

              {!! render_field($form->toggleTitle->label('Title')) !!}

              {!! render_field($form->toggleDescription
                  ->label('Description')) !!}

              {!! render_field($form->toggleLanguageMenu
                  ->label('Language menu')) !!}

              {!! render_field($form->toggleIoSlider
                  ->label('Digital object carousel')) !!}

              @php
                $help = $googleMapsApiKeySet
                    ? null
                    : __('This feature will not work until a Google Maps API key is specified on the %1%global%2% settings page.', ['%1%' => '<a href="'.url_for('settings/global').'">', '%2%' => '</a>']);
              @endphp
              {!! render_field($form->toggleDigitalObjectMap
                      ->label('Digital object map')->help($help)) !!}

              {!! render_field($form->toggleCopyrightFilter
                  ->label('Copyright status filter')) !!}

              {!! render_field($form->toggleMaterialFilter
                  ->label('General material designation filter')) !!}
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
