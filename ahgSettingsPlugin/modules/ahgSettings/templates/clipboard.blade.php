@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Clipboard settings') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'clipboard'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="saving-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#saving-collapse" aria-expanded="false" aria-controls="saving-collapse">
              {{ __('Clipboard saving') }}
            </button>
          </h2>
          <div id="saving-collapse" class="accordion-collapse collapse" aria-labelledby="saving-heading">
            <div class="accordion-body">
              {!! render_field(
                  $form->clipboard_save_max_age
                      ->label(__('Saved clipboard maximum age (in days)'))
                      ->help(__('The number of days a saved clipboard should be retained before it is eligible for deletion')),
                  null,
                  ['type' => 'number'],
              ) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="sending-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sending-collapse" aria-expanded="false" aria-controls="sending-collapse">
              {{ __('Clipboard sending') }}
            </button>
          </h2>
          <div id="sending-collapse" class="accordion-collapse collapse" aria-labelledby="sending-heading">
            <div class="accordion-body">
              {!! render_field($form->clipboard_send_enabled
                  ->label(__('Enable clipboard send functionality'))) !!}

              {!! render_field($form->clipboard_send_url
                  ->label(__('External URL to send clipboard contents to'))) !!}

              {!! render_field(
                  $form->clipboard_send_button_text
                      ->label(__('Send button text')),
                  $settings['clipboard_send_button_text']) !!}

              {!! render_field(
                  $form->clipboard_send_message_html
                      ->label(__('Text or HTML to display when sending clipboard contents')),
                  $settings['clipboard_send_message_html']) !!}

              {!! render_field($form->clipboard_send_http_method
                  ->label(__('HTTP method to use when sending clipboard contents'))) !!}
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="export-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#export-collapse" aria-expanded="false" aria-controls="export-collapse">
              {{ __('Clipboard export') }}
            </button>
          </h2>
          <div id="export-collapse" class="accordion-collapse collapse" aria-labelledby="export-heading">
            <div class="accordion-body">
              {!! render_field($form->clipboard_export_digitalobjects_enabled
                  ->label(__('Enable digital object export'))) !!}
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
