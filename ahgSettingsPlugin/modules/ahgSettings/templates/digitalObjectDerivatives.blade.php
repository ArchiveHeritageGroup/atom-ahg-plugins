@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('%1% derivatives', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]) }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'digitalObjectDerivatives'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="derivatives-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#derivatives-collapse" aria-expanded="false" aria-controls="derivatives-collapse">
              {{ __('%1% derivatives settings', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]) }}
            </button>
          </h2>
          <div id="derivatives-collapse" class="accordion-collapse collapse" aria-labelledby="derivatives-heading">
            <div class="accordion-body">
              @if ($pdfinfoAvailable)
                {!! render_field(
                    $form->digital_object_derivatives_pdf_page_number
                        ->label(__('PDF page number for image derivative'))
                        ->help(__('If the page number does not exist, the derivative will be generated from the previous closest one.')),
                    null,
                    ['type' => 'number']
                ) !!}
              @else
                <div class="alert alert-danger" role="alert">
                  {{ __('The pdfinfo tool is required to use this functionality. Please contact your system administrator.') }}
                </div>
              @endif

              {!! render_field(
                  $form->reference_image_maxwidth
                      ->label(__('Maximum length on longest edge (pixels)'))
                      ->help(__('The maximum number of pixels on the longest edge for derived reference images.')),
                  null,
                  ['type' => 'number']
              ) !!}
            </div>
          </div>
        </div>
      </div>

      @if ($pdfinfoAvailable)
        <section class="actions mb-3">
          <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Save') }}">
        </section>
      @endif

    </form>

  </div>
</div>
@endsection
