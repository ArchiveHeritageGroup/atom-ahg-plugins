@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Privacy Notification') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'privacyNotification'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="privacy-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#privacy-collapse" aria-expanded="false" aria-controls="privacy-collapse">
              {{ __('Privacy Notification Settings') }}
            </button>
          </h2>
          <div id="privacy-collapse" class="accordion-collapse collapse" aria-labelledby="privacy-heading">
            <div class="accordion-body">
              {!! render_field($form->privacy_notification_enabled
                  ->label(__('Display Privacy Notification on first visit to site'))) !!}

              {!! render_field(
                  $form->privacy_notification
                      ->label(__('Privacy Notification Message')),
                  $settings['privacy_notification']) !!}
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
