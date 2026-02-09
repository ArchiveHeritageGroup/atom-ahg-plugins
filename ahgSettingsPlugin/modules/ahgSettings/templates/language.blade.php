@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('I18n language') }}</h1>

    <div class="alert alert-info">
      <p>{{ __('Please rebuild the search index if you are adding new languages.') }}</p>
      <pre>$ php symfony search:populate</pre>
    </div>

    {!! $form->renderGlobalErrors() !!}

    {!! $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'language'])) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="accordion mb-3">
        <div class="accordion-item">
          <h2 class="accordion-header" id="language-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#language-collapse" aria-expanded="false" aria-controls="language-collapse">
              {{ __('i18n language settings') }}
            </button>
          </h2>
          <div id="language-collapse" class="accordion-collapse collapse" aria-labelledby="language-heading">
            <div class="accordion-body">
              @foreach ($i18nLanguages as $setting)
                <div class="row mb-3">
                  <label class="col-11 col-form-label">
                    {!! format_language($setting->getName()) !!}
                    <code class="ms-1">{{ $setting->getName() }}</code>
                  </label>
                  <div class="col-1 px-2 text-end align-middle">
                    @if ($setting->deleteable)
                      <a class="btn atom-btn-white" href="{{ url_for([$setting, 'module' => 'settings', 'action' => 'delete']) }}">
                        <i class="fas fa-fw fa-times" aria-hidden="true"></i>
                        <span class="visually-hidden">{{ __('Delete') }}</span>
                      </a>
                    @else
                      <span class="btn disabled" aria-hidden="true">
                        <i class="fas fa-fw fa-lock"></i>
                      </span>
                    @endif
                  </div>
                </div>
              @endforeach

              <hr>

              {!! render_field($form->languageCode) !!}
            </div>
          </div>
        </div>
      </div>

      <section class="actions mb-3">
        <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Add') }}">
      </section>

    </form>
  </div>
</div>
@endsection
