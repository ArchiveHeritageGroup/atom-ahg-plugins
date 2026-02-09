@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Site information') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'siteInformation'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="site-information-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#site-information-collapse" aria-expanded="false" aria-controls="site-information-collapse">
              {{ __('Site information settings') }}
            </button>
          </h2>
          <div id="site-information-collapse" class="accordion-collapse collapse" aria-labelledby="site-information-heading">
            <div class="accordion-body">
              {!! render_field(
                  $form->siteTitle
                      ->label(__('Site title'))
                      ->help(__('The name of the website for display in the header')),
                  $settings['siteTitle']) !!}

              {!! render_field(
                  $form->siteDescription
                      ->label(__('Site description'))
                      ->help(__('A brief site description or &quot;tagline&quot; for the header')),
                  $settings['siteDescription']) !!}

              {!! render_field(
                  $form->siteBaseUrl
                      ->label(__('Site base URL (used in MODS and EAD exports)'))
                      ->help(__('Used to create absolute URLs, pointing to resources, in XML exports')),
                  $settings['siteBaseUrl']) !!}
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
