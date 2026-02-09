@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('DIP upload settings') }}</h1>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'dipUpload'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="dip-upload-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dip-upload-collapse" aria-expanded="false" aria-controls="dip-upload-collapse">
              {{ __('DIP Upload settings') }}
            </button>
          </h2>
          <div id="dip-upload-collapse" class="accordion-collapse collapse" aria-labelledby="dip-upload-heading">
            <div class="accordion-body">
              {!! render_field($form->stripExtensions
                  ->label(__('Strip file extensions from information object names'))) !!}
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
