@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Markdown') }}</h1>

    <div class="alert alert-info">
      <p>{{ __('Please rebuild the search index if you are enabling/disabling Markdown support.') }}</p>
      <pre>$ php symfony search:populate</pre>
    </div>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'markdown'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="markdown-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#markdown-collapse" aria-expanded="false" aria-controls="markdown-collapse">
              {{ __('Markdown settings') }}
            </button>
          </h2>
          <div id="markdown-collapse" class="accordion-collapse collapse" aria-labelledby="markdown-heading">
            <div class="accordion-body">
              {!! render_field($form->enabled
                  ->label(__('Enable Markdown support'))) !!}
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
